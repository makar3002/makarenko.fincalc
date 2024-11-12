<?php
namespace makarenko\fincalc\reports\control\calculator;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Exception;
use makarenko\fincalc\reports\control\DataContainer;
use makarenko\fincalc\reports\control\formula\allocatedexpenses\AllocatedExpensesAffectFormula;
use makarenko\fincalc\reports\control\formula\allocatedexpenses\AllocatedExpensesComplainFormula;
use makarenko\fincalc\reports\control\formula\allocatedexpenses\AllocatedExpensesForgetFormula;
use makarenko\fincalc\reports\control\formula\allocatedexpenses\AllocatedExpensesFormula;
use makarenko\fincalc\reports\control\formula\amounttoallocate\AmountToAllocateAffectFormula;
use makarenko\fincalc\reports\control\formula\amounttoallocate\AmountToAllocateComplainFormula;
use makarenko\fincalc\reports\control\formula\amounttoallocate\AmountToAllocateForgetFormula;
use makarenko\fincalc\reports\control\formula\amounttoallocate\AmountToAllocateFormula;
use makarenko\fincalc\reports\control\formula\TotalPercentFormula;
use makarenko\fincalc\reports\control\frc\FrcNotFoundException;
use makarenko\fincalc\reports\control\parameter\ItemMapper;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;
use makarenko\fincalc\reports\entity\parameter\Item;

class AllocationCalculator extends BaseCalculator {
    /** @var int - код  для индекса take (%). */
    private const TAKE_PERCENT_INDEX_CODE = 40040;
    /** @var int - код  для индекса tax (%). */
    private const TAX_PERCENT_INDEX_CODE = 40060;
    /** @var array - список кодов для индексов, использующихся для расчета параметров аллокации. */
    private const ALLOCATION_PARAMETERS_INDEX_CODE_LIST = array(
            AllocationCalculator::TAKE_PERCENT_INDEX_CODE,
            AllocationCalculator::TAX_PERCENT_INDEX_CODE
    );

    /** @var int - код для индекса Amount to allocate (affect). */
    private const AMOUNT_TO_ALLOCATE_AFFECT_INDEX_CODE = 888891;
    /** @var int - код для индекса Amount to allocate (complain). */
    private const AMOUNT_TO_ALLOCATE_COMPLAIN_INDEX_CODE = 888892;
    /** @var int - код для индекса Amount to allocate (forget). */
    private const AMOUNT_TO_ALLOCATE_FORGET_INDEX_CODE = 888893;
    /** @var array - список кодов для индексов, использующихся для расчета аллоцируемой суммы. */
    private const AMOUNT_TO_ALLOCATE_INDEX_CODE_LIST = array(
            AllocationCalculator::AMOUNT_TO_ALLOCATE_AFFECT_INDEX_CODE,
            AllocationCalculator::AMOUNT_TO_ALLOCATE_COMPLAIN_INDEX_CODE,
            AllocationCalculator::AMOUNT_TO_ALLOCATE_FORGET_INDEX_CODE
    );

    /** @var int - код для индекса Allocated expenses (affect). */
    private const ALLOCATED_EXPENSES_AFFECT_ITEM_CODE = 90110;
    /** @var int - код для индекса Allocated expenses (complain). */
    private const ALLOCATED_EXPENSES_COMPLAIN_ITEM_CODE = 90105;
    /** @var int - код для индекса Allocated expenses (forget). */
    private const ALLOCATED_EXPENSES_FORGET_ITEM_CODE = 90101;
    /** @var array - список кодов для индексов, использующихся для расчета аллоцированных затрат. */
    private const ALLOCATED_EXPENSES_ITEM_CODE_LIST = array(
            AllocationCalculator::ALLOCATED_EXPENSES_AFFECT_ITEM_CODE,
            AllocationCalculator::ALLOCATED_EXPENSES_COMPLAIN_ITEM_CODE,
            AllocationCalculator::ALLOCATED_EXPENSES_FORGET_ITEM_CODE
    );

    /**
     * Производит расчет для при изменении конкретного данного.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - изменившееся данное отчетов.
     *
     * @return DataContainer - контейнер содержит в себе все расчитанные показатели.
     *
     * @throws CalculatorException
     */
    public function calculate(DataContainer $dataContainer, Data $data): DataContainer {
        try {
            if (!$this->isCalculatorEnabled()) {
                return $dataContainer;
            }

            return $this->calculateData($dataContainer, $data);
        } catch (Exception $exception) {
            throw new CalculatorException(
                    'Allocation calculator failure. Reason: ' . $exception->getMessage(),
                    0,
                    $exception
            );
        }
    }

    /**
     * Инициирует расчет данных по аллокации.
     *
     * @param DataContainer $dataContainer
     * @param Data $data
     *
     * @return DataContainer
     *
     * @throws Exception
     */
    private function calculateData(
        DataContainer $dataContainer,
        Data          $data
    ): DataContainer {
        $parameterCode = $this->getDataParameterCode($data);
        if (in_array($parameterCode, AllocationCalculator::ALLOCATION_PARAMETERS_INDEX_CODE_LIST)) {
            $frc = $data->getFrc();
            try {
                $parentFrc = $this->referenceService->getParentFrcByChildFrc($frc);
            } catch (FrcNotFoundException $frcNotFoundException) {
                return $dataContainer;
            }

            $this->calculateByAllocationParameterChange(
                    $dataContainer,
                    $data,
                    $parentFrc
            );
        }

        if (in_array($parameterCode, AllocationCalculator::AMOUNT_TO_ALLOCATE_INDEX_CODE_LIST)) {
            $this->calculateByAmountToAllocateChange(
                    $dataContainer,
                    $data
            );
        }

        if (in_array($parameterCode, AllocationCalculator::ALLOCATED_EXPENSES_ITEM_CODE_LIST)) {
            $this->calculateByAllocatedExpensesChange(
                    $dataContainer,
                    $data
            );
        }

        return $dataContainer;
    }

    /**
     * Расчитывает показатели после изменения параметра аллокации.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - измененное данное отчетов, являющееся параметром аллокации.
     * @param Frc $parentFrc - родительский для {@link $data} ЦФО.
     *
     * @throws Exception
     */
    private function calculateByAllocationParameterChange(
        DataContainer $dataContainer,
        Data          $data,
        Frc           $parentFrc
    ): void {
        $frcDataList = $this->getFrcDataNodeList($dataContainer, $data);
        $totalPercentFormula = new TotalPercentFormula(
                $frcDataList,
                $data->getDataType(),
                $data->getFrc()
        );

        $totalPercentFormula->setParameter(
                $this->formulaService->getParameterByCode($totalPercentFormula->getParameterCode())
        );

        $totalPercent = $totalPercentFormula->execute();
        $totalPercent = $totalPercent->withName($totalPercent->getIndex()->getName())
                ->withPeriod($data->getPeriod())
                ->withSnapshot(new DateTime())
                ->withAllocationLevel($data->getAllocationLevel());

        $data = $dataContainer->change($totalPercent);
        $this->calculateByLevel($dataContainer, $data, $parentFrc);
    }

    /**
     * Расчитывает показатели после изменения аллоцируемой суммы.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - измененное данное отчетов, являющееся аллоцируемой суммой.
     *
     * @throws Exception - если Frc имеет некорректный уровень.
     */
    private function calculateByAmountToAllocateChange(
        DataContainer $dataContainer,
        Data          $data
    ) {
        $this->calculateAllocatedExpensesByLevel($dataContainer, $data);
        $this->calculateByAllocatedExpensesChange($dataContainer, $data);
    }

    /**
     * Расчитывает показатели после изменения аллоцируемых затрат.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - данное отчетов, являющееся аллоцированными затратами.
     *
     * @throws Exception - если Frc имеет некорректный уровень.
     */
    private function calculateByAllocatedExpensesChange(
        DataContainer $dataContainer,
        Data          $data
    ) {
        $allocationIndex = $data->getAllocationLevel()->getAllocationIndex();
        $allocationIndex = $allocationIndex >= ItemMapper::ALLOCATION_LEVEL_FORGET
                    ? ItemMapper::ALLOCATION_LEVEL_FORGET
                    : $allocationIndex + 1;
        $nextAllocationLevel = $this->getAllocationLevelByIndex($allocationIndex);
        $frc = $data->getFrc();
        $childGreenFrcList = $frc->getChildGreenFrcList();
        foreach ($childGreenFrcList as $childFrcId => $childFrc) {
            $newData = $data->withFrc($childFrc)->withAllocationLevel($nextAllocationLevel);
            $this->calculateByLevel($dataContainer, $newData, $frc);
        }
    }

    /**
     * Расчитывает показатели после изменения аллоцируемой суммы.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - данное отчетов, стриггеревшее расчет по уровню аллокации.
     * @param Frc $parentFrc - родительский для $frc ЦФО.
     *
     * @throws Exception - если Frc имеет некорректный уровень.
     */
    private function calculateByLevel(
        DataContainer $dataContainer,
        Data          $data,
        Frc           $parentFrc
    ): void {
        $frcLevel = $data->getFrc()->getLevel();
        if ($frcLevel === 'N') {
            throw new Exception('Wrong FRC level: N');
        }

        if (!in_array($data->getAllocationLevel()->getAllocationIndex(), array(
                ItemMapper::ALLOCATION_LEVEL_AFFECT,
                ItemMapper::ALLOCATION_LEVEL_COMPLAIN,
                ItemMapper::ALLOCATION_LEVEL_FORGET
        ))) {
            return;
        }

        $this->calculateAmountToAllocateByLevel($dataContainer, $data, $parentFrc);
        $this->calculateAllocatedExpensesByLevel($dataContainer, $data);

        $this->calculateByAllocatedExpensesChange($dataContainer, $data);
    }

    /**
     * Расчитывает сумму аллокации для указанного уровня.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - данное отчетов, по которому высчитывается показатель.
     * @param Frc $parentFrc - родительский для $data ЦФО.
     *
     * @throws Exception - если передан некорректный уровень аллокации.
     */
    private function calculateAmountToAllocateByLevel(
        DataContainer $dataContainer,
        Data          $data,
        Frc           $parentFrc
    ): void {
        $allocationLevel = $data->getAllocationLevel();
        $allocationIndex = $allocationLevel->getAllocationIndex();
        $filterAllocationIndex = $allocationIndex == ItemMapper::ALLOCATION_LEVEL_AFFECT
                ? ItemMapper::ALLOCATION_LEVEL_OWN_EXPENSES
                : ItemMapper::ALLOCATION_LEVEL_AMOUNT_USD;
        $filterAllocationLevel = $this->getAllocationLevelByIndex($filterAllocationIndex);
        $filterData = $data->withFrc($parentFrc)->withAllocationLevel($filterAllocationLevel);
        $frcDataList = $this->getFrcDataNodeList($dataContainer, $filterData);
        $filterFrcList = array_merge(array($parentFrc), $parentFrc->getChildRedFrcList());
        $frcDataList = array_filter(
                $frcDataList,
                function (HierarchicalDataNode $dataNode) use ($filterFrcList) {
                    return in_array($dataNode->getValue(), $filterFrcList);
                }
        );

        $preparedFrcDataList = $this->formulaService->prepareFrcDataList($frcDataList);
        $amountToAllocateFormulaClass = $this->getAmountToAllocateFormulaClassByLevel($allocationLevel);
        if (!is_subclass_of($amountToAllocateFormulaClass, AmountToAllocateFormula::class)) {
            throw new Exception('Wrong amount to allocate formula class');
        }

        /** @var AmountToAllocateFormula $amountToAllocateFormula */
        $amountToAllocateFormula = new $amountToAllocateFormulaClass(
                $preparedFrcDataList,
                $data->getDataType(),
                $data->getFrc(),
                $parentFrc
        );
        $amountToAllocateParameter = $this->formulaService->getParameterByCode($amountToAllocateFormula->getParameterCode());
        $amountToAllocateFormula->setParameter($amountToAllocateParameter);

        $amountToAllocate = $amountToAllocateFormula->execute()
                ->withName($amountToAllocateParameter->getName())
                ->withPeriod($data->getPeriod())
                ->withAllocationLevel($allocationLevel);

        $data = $dataContainer->change($amountToAllocate);
    }

    /**
     * Расчитывает сумму аллокации для указанного уровня.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - данное отчетов, из-за которого происходит расчет данного показателя.
     *
     * @throws Exception
     */
    private function calculateAllocatedExpensesByLevel(
        DataContainer $dataContainer,
        Data          $data
    ): void {
        $frcDataList = $this->getFrcDataNodeList($dataContainer, $data);
        $preparedFrcDataList = $this->formulaService->prepareFrcDataList($frcDataList);
        $amountUsdAllocationLevel = $this->getAllocationLevelByIndex(ItemMapper::ALLOCATION_LEVEL_AMOUNT_USD);
        $allocatedExpensesFormulaClass = $this->getAllocatedExpensesFormulaClassByLevel($data->getAllocationLevel());
        /** @var AllocatedExpensesFormula $allocatedExpensesFormula */
        $allocatedExpensesFormula = new $allocatedExpensesFormulaClass(
                $preparedFrcDataList,
                $data->getDataType(),
                $data->getFrc()
        );

        $allocatedExpensesFormula->setParameter(
                $this->formulaService->getParameterByCode($allocatedExpensesFormula->getParameterCode())
        );

        $allocatedExpenses = $allocatedExpensesFormula->execute()
                ->withName($amountUsdAllocationLevel->getName())
                ->withPeriod($data->getPeriod())
                ->withAllocationLevel($amountUsdAllocationLevel);

        $data = $dataContainer->change($allocatedExpenses);
    }

    /**
     * Возвращает список нод данных отчетов, сгруппировавших данные по ЦФО,
     * а остальные параметры аналогичны переданному данному отчетов $data.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     * @param Data $data - данное отчетов, по которому фильтруются ноды.
     *
     * @return array
     *
     * @throws Exception
     */
    private function getFrcDataNodeList(DataContainer $dataContainer, Data $data): array {
        $dataNode = $dataContainer->getDataNode();
        $filteredDataNode = $dataContainer->filter(
                $dataNode,
                function ($nodeData) use ($data){
                    return $this->isValidNodeDataByData($nodeData, $data);
                }
        );

        if (is_null($filteredDataNode)) {
            return array();
        }

        $config = $dataContainer->getHierarchyConfig()->getConfig();
        $parentFrcDataNodeLevel = count($config) - 1;
        while ($filteredDataNode instanceof HierarchicalDataNode) {
            if ($filteredDataNode->getLevel() != $parentFrcDataNodeLevel) {
                $childNodeList = $filteredDataNode->getChildNodeList();
                $filteredDataNode = reset($childNodeList);
                continue;
            }

            return $filteredDataNode->getChildNodeList();
        }

        return array();
    }

    /**
     * Возвращает уровень аллокации по его коду.
     *
     * @param int $allocationIndex - код уровеня аллокации.
     *
     * @return Item|null
     *
     * @throws Exception - в случае, если не удалось получить список уровней аллокации.
     */
    private function getAllocationLevelByIndex(int $allocationIndex): ?Item {
        return $this->referenceService->getAllocationLevelList()[$allocationIndex];
    }

    /**
     * Проверяет, имеют ли данные отчетов идентичные тип данных, период, уровень аллокации и affiliated ЦФО.
     * Если указанные поля одинаковые, то данное $nodeData является валидным.
     *
     * @param Data $nodeData - данное отчета, валидность которого проверяется.
     * @param Data $data - данное отчета, по которому проверяется валидность.
     *
     * @return bool
     */
    private function isValidNodeDataByData(Data $nodeData, Data $data): bool {
        if (!($nodeData instanceof Data)) {
            return false;
        }

        if ($nodeData->getDataType() !== $data->getDataType()) {
            return false;
        }

        if ($nodeData->getPeriod() !== $data->getPeriod()) {
            return false;
        }

        if ($nodeData->getAllocationLevel() !== $data->getAllocationLevel()) {
            return false;
        }

        if ($nodeData->getAffiliatedFrc() !== $data->getAffiliatedFrc()) {
            return false;
        }

        return true;
    }

    /**
     * Возвращает код параметра переданного данного отчета.
     *
     * @param Data $data - данное отчетов.
     *
     * @return int
     *
     * @throws Exception - если у данного отчетов не заданы индекс и итем.
     */
    private function getDataParameterCode(Data $data): int {
        $parameter = $data->getIndex() ?? $data->getItem();
        if (!$parameter) {
            throw new Exception('There is no parameter in data');
        }

        return $parameter->getCode();
    }

    /**
     * @return bool
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    private function isCalculatorEnabled(): bool {
        return Option::get('makarenko.fincalc', 'FINCALC_ALLOCATION_TRIGGER_IS_ON') == 'Y';
    }

    /**
     * Возвращает класс формулы для рассчета аллоцированных затрат для указанного уровня аллокации.
     *
     * @param Item $allocationLevel - уровень аллокации.
     *
     * @return string
     *
     * @throws Exception - если передан некорректный уровень аллокации.
     */
    private function getAllocatedExpensesFormulaClassByLevel(Item $allocationLevel): string {
        switch ($allocationLevel->getAllocationIndex()) {
            case ItemMapper::ALLOCATION_LEVEL_AFFECT:
                return AllocatedExpensesAffectFormula::class;
            case ItemMapper::ALLOCATION_LEVEL_COMPLAIN:
                return AllocatedExpensesComplainFormula::class;
            case ItemMapper::ALLOCATION_LEVEL_FORGET:
                return AllocatedExpensesForgetFormula::class;
        }

        throw new Exception('Wrong allocation level');
    }

    /**
     * Возвращает класс формулы для рассчета аллоцируемой суммы для указанного уровня аллокации.
     *
     * @param Item $allocationLevel - уровень аллокации.
     *
     * @return string
     *
     * @throws Exception - если передан некорректный уровень аллокации.
     */
    private function getAmountToAllocateFormulaClassByLevel(Item $allocationLevel): string {
        switch ($allocationLevel->getAllocationIndex()) {
            case ItemMapper::ALLOCATION_LEVEL_AFFECT:
                return AmountToAllocateAffectFormula::class;
            case ItemMapper::ALLOCATION_LEVEL_COMPLAIN:
                return AmountToAllocateComplainFormula::class;
            case ItemMapper::ALLOCATION_LEVEL_FORGET:
                return AmountToAllocateForgetFormula::class;
        }

        throw new Exception('Wrong allocation level');
    }
}
