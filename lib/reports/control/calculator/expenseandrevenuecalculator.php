<?php
namespace makarenko\fincalc\reports\control\calculator;


use Bitrix\Main\Config\Option;
use Exception;
use makarenko\fincalc\reports\control\DataContainer;
use makarenko\fincalc\reports\control\DataHierarchyConfig;
use makarenko\fincalc\reports\control\formula\revenue\ContributionFromFormula;
use makarenko\fincalc\reports\control\formula\expense\ExpensesFromFormula;
use makarenko\fincalc\reports\control\formula\FormulaService;
use makarenko\fincalc\reports\control\formula\TotalContributionsAndExpensesFormula;
use makarenko\fincalc\reports\control\formula\revenue\TotalContributionToFormula;
use makarenko\fincalc\reports\control\formula\expense\TotalExpensesFormula;
use makarenko\fincalc\reports\control\formula\expense\TotalExpensesToFormula;
use makarenko\fincalc\reports\control\formula\revenue\TotalMarginFormula;
use makarenko\fincalc\reports\control\frc\FrcMapper;
use makarenko\fincalc\reports\control\frc\FrcNotFoundException;
use makarenko\fincalc\reports\control\parameter\ItemMapper;
use makarenko\fincalc\reports\control\ReferenceService;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\expenserequest\ExpenseRequest;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;


/**
 * Class ExpenseAndRevenueCalculator
 *
 * @package makarenko\fincalc\reports\control\calculator
 */
class ExpenseAndRevenueCalculator extends BaseCalculator {
    private const TOTAL_EXPENSES_CODE = 40000;
    private const TOTAL_MARGIN_CODE = 333333;
    private const TOTAL_EXPENSES_TO_CODE = 40010;
    private const TOTAL_CONTRIBUTION_TO_CODE = 777777;
    private const EXPENSES_FROM_CODE = 40020;
    private const CONTRIBUTION_FROM_CODE = 777779;
    private const FACT_DATA_TYPE_NAME = 'fact';
    private const EXPENSES_CALCULATION_CODE_LIST = array(
            ExpenseAndRevenueCalculator::TOTAL_EXPENSES_CODE,
            ExpenseAndRevenueCalculator::TOTAL_EXPENSES_TO_CODE,
            ExpenseAndRevenueCalculator::EXPENSES_FROM_CODE
    );

    private const REVENUE_CALCULATION_CODE_LIST = array(
            ExpenseAndRevenueCalculator::TOTAL_MARGIN_CODE,
            ExpenseAndRevenueCalculator::TOTAL_CONTRIBUTION_TO_CODE,
            ExpenseAndRevenueCalculator::CONTRIBUTION_FROM_CODE
    );

    private $indexExpensesSectionId;
    private $itemExpensesSectionId;
    private $indexRevenueSectionId;
    private $itemRevenueSectionId;

    public function __construct(ReferenceService $referenceService, FormulaService $formulaService) {
        $this->indexExpensesSectionId = Option::get('makarenko.fincalc', 'FINCALC_INDEX_EXPENSES_SECTION_ID');
        $this->itemExpensesSectionId = Option::get('makarenko.fincalc', 'FINCALC_ITEM_EXPENSES_SECTION_ID');
        $this->indexRevenueSectionId = Option::get('makarenko.fincalc', 'FINCALC_INDEX_REVENUE_SECTION_ID');
        $this->itemRevenueSectionId = Option::get('makarenko.fincalc', 'FINCALC_ITEM_REVENUE_SECTION_ID');

        parent::__construct($referenceService, $formulaService);
    }

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

        if (!$this->checkChangedData($data)) {
            return $dataContainer;
        }

        $dataContainer = $this->recalculateData($data, $dataContainer);
        return $dataContainer;
    } catch (Exception $exception) {
        throw new CalculatorException(
                'Expense and revenue calculator failure. Reason: ' . $exception->getMessage(),
                0,
                $exception
        );
    }
}


    /**
     * Метод для запуска перерасчёта данных для изменившегося данного отчетов {@link $data}.
     *
     * @param Data $data
     * @param DataContainer $dataContainer
     *
     * @return DataContainer
     *
     * @throws Exception
     */
    public function recalculateData(Data $data, DataContainer $dataContainer) : DataContainer {
        switch ($data->getFrc()->getColor()) {
            case FrcMapper::FRC_GREEN_COLOR:
                return $this->calculateGreenFrcData($data, $dataContainer);
            case FrcMapper::FRC_RED_COLOR:
                return $this->calculateRedFrcData($data, $dataContainer);
        }

        return $dataContainer;
    }

    /**
     * Подсчитывает данные для FRC типа Green.
     * Для FRC типа Green необходимо расчитывать Total Contribution To.
     *
     * @param Data $data
     * @param DataContainer $dataContainer
     *
     * @return DataContainer
     *
     * @throws Exception
     */
    private function calculateGreenFrcData(
        Data          $data,
        DataContainer $dataContainer
    ): DataContainer {
        $totalExpenses = $this->calculateTotalExpenses($data, $dataContainer);
        $totalExpenses = $dataContainer->change($totalExpenses);

        $totalMargin = $this->calculateTotalMargin($data, $dataContainer);
        $totalMargin = $dataContainer->change($totalMargin);

        if (is_null($data->getFrc()->getParentFrc())) {
            $totalContributionsAndExpenses = $this->calculateTotalContributionsAndExpenses($totalExpenses, $totalMargin);
            $dataContainer->change($totalContributionsAndExpenses);
            return $dataContainer;
        }

        $totalContributionTo = $this->calculateTotalContributionTo($totalExpenses, $totalMargin);
        $totalContributionTo = $dataContainer->change($totalContributionTo);
        $contributionFrom = $this->calculateParentsData($totalContributionTo);
        $contributionFrom = $dataContainer->change($contributionFrom);

        return $this->recalculateData($contributionFrom->withAffiliatedFrc(null), $dataContainer);
    }

    /**
     * Подсчитывает данные для FRC типа Red.
     * Для FRC типа Red необходимо расчитывать Total Expenses To.
     *
     * @param Data $data
     * @param DataContainer $dataContainer
     *
     * @return DataContainer
     *
     * @throws Exception
     */
    private function calculateRedFrcData(
        Data          $data,
        DataContainer $dataContainer
    ): DataContainer {
        $totalExpenses = $this->calculateTotalExpenses($data, $dataContainer);
        $totalExpenses = $dataContainer->change($totalExpenses);

        $totalMargin = $this->calculateTotalMargin($data, $dataContainer);
        $dataContainer->change($totalMargin);

        if (is_null($data->getFrc()->getParentFrc())) {
            return $dataContainer;
        }

        $totalExpensesTo = $this->calculateTotalExpensesTo($totalExpenses);
        $totalExpensesTo = $dataContainer->change($totalExpensesTo);
        $expensesFrom = $this->calculateParentsData($totalExpensesTo);
        $expensesFrom = $dataContainer->change($expensesFrom);

        return $this->recalculateData($expensesFrom->withAffiliatedFrc(null), $dataContainer);

    }

    /**
     * Подсчитывает значение Total Expenses по изменившемуся данному отчетов {@link $data}.
     *
     * @param Data $data
     * @param DataContainer $dataContainer
     *
     * @return Data
     *
     * @throws Exception
     */
    private function calculateTotalExpenses(
        Data                $data,
        DataContainer $dataContainer
    ): Data {
        $expenseFrcDataList = $this->getExpenseDataByData($dataContainer, $data);

        $isDataTypeFact = $data->getDataType()->getName() === ExpenseAndRevenueCalculator::FACT_DATA_TYPE_NAME;
        $expenseRequestList = $isDataTypeFact
                ? $this->getExpenseRequestListByDataAndSectionId($data, $this->itemExpensesSectionId)
                : array();

        $preparedExpenseRequestList = $this->deleteAlreadyUsedExpensesRequest(
                $expenseRequestList,
                $expenseFrcDataList
        );

        $totalExpensesFormula = new TotalExpensesFormula(
                $expenseFrcDataList,
                $data->getDataType(),
                $data->getFrc(),
                $preparedExpenseRequestList
        );

        $totalExpensesIndex = $this->formulaService->getParameterByCode($totalExpensesFormula->getParameterCode());
        $totalExpensesFormula->setParameter($totalExpensesIndex);
        $totalExpenses = $totalExpensesFormula->execute()
                ->withAllocationLevel($this->referenceService->getAllocationLevelList()[ItemMapper::ALLOCATION_LEVEL_OWN_EXPENSES])
                ->withName($totalExpensesIndex->getName())
                ->withPeriod($data->getPeriod());

        return $totalExpenses;
    }

    /**
     * Подсчитывает значение Total Margin по изменившемуся данному отчетов {@link $data}.
     *
     * @param Data $data
     * @param DataContainer $dataContainer
     *
     * @return Data
     *
     * @throws Exception
     */
    private function calculateTotalMargin(
        Data                $data,
        DataContainer $dataContainer
    ): Data {
        $expenseFrcDataList = $this->getRevenueDataByData($dataContainer, $data);
        $isDataTypeFact = $data->getDataType()->getName() === ExpenseAndRevenueCalculator::FACT_DATA_TYPE_NAME;
        $expenseRequestList = $isDataTypeFact
                ? $this->getExpenseRequestListByDataAndSectionId($data, $this->itemRevenueSectionId)
                : array();

        $preparedExpenseRequestList = $this->deleteAlreadyUsedExpensesRequest(
                $expenseRequestList,
                $expenseFrcDataList
        );

        $totalMarginFormula = new TotalMarginFormula(
                $expenseFrcDataList,
                $data->getDataType(),
                $data->getFrc(),
                $preparedExpenseRequestList
        );

        $totalMarginIndex = $this->formulaService->getParameterByCode($totalMarginFormula->getParameterCode());
        $totalMarginFormula->setParameter($totalMarginIndex);
        $totalMargin = $totalMarginFormula->execute()
                ->withName($totalMarginIndex->getName())
                ->withPeriod($data->getPeriod());

        return $totalMargin;
    }

    /**
     * Подсчитывает значение Total Contributions And Expenses по переданным показателям
     * Total Expenses и Total Contribution.
     *
     * @param Data $totalExpenses
     * @param Data $totalMargin
     *
     * @return Data
     */
    private function calculateTotalContributionsAndExpenses(
        Data $totalExpenses,
        Data $totalMargin
    ): Data {
        $frc = $totalExpenses->getFrc();
        $frcDataList = array(
                $frc->getId() => new HierarchicalDataNode($frc, 0, array(
                        $totalExpenses,
                        $totalMargin
                ))
        );

        $totalContributionsAndExpensesFormula = new TotalContributionsAndExpensesFormula(
                $frcDataList,
                $totalExpenses->getDataType(),
                $frc
        );

        $totalContributionsAndExpensesIndex = $this->formulaService->getParameterByCode($totalContributionsAndExpensesFormula->getParameterCode());
        $totalContributionsAndExpensesFormula->setParameter($totalContributionsAndExpensesIndex);
        $totalContributionsAndExpenses = $totalContributionsAndExpensesFormula->execute()
                ->withName($totalContributionsAndExpensesIndex->getName())
                ->withPeriod($totalExpenses->getPeriod());

        return $totalContributionsAndExpenses;
    }

    /**
     * Перерасчитывает значения Contribution from или Expense from для родительского FRC по переданному показателю
     * Total Expenses to или Total Contribution to.
     *
     * @param Data $data
     *
     * @return Data
     *
     * @throws Exception
     */
    private function calculateParentsData(Data $data): Data {
        $index = $data->getIndex();
        if (is_null($index)) {
            throw new Exception('Index not set');
        }

        $frc = $data->getFrc();
        try {
            $parentFrc = $this->referenceService->getParentFrcByChildFrc($frc);
        } catch (FrcNotFoundException $frcNotFoundException) {
            throw new Exception('Parent frc not set');
        }

        $frcDataList = array(
                $frc->getId() => new HierarchicalDataNode($frc, 0, array(
                        $data
                ))
        );

        if ($index->getCode() == ExpenseAndRevenueCalculator::TOTAL_EXPENSES_TO_CODE) {
            $formula = new ExpensesFromFormula(
                    $frcDataList,
                    $data->getDataType(),
                    $parentFrc,
                    $frc
            );
        } elseif ($index->getCode() == ExpenseAndRevenueCalculator::TOTAL_CONTRIBUTION_TO_CODE) {
            $formula = new ContributionFromFormula(
                    $frcDataList,
                    $data->getDataType(),
                    $parentFrc,
                    $frc
            );
        } else {
            throw new Exception('Wrong index value: ' . $index->getCode());
        }

        $formulaIndex = $this->formulaService->getParameterByCode($formula->getParameterCode());
        $formula->setParameter($formulaIndex);
        $formulaValue = $formula->execute()
                ->withPeriod($data->getPeriod())
                ->withName($formulaIndex->getName() . ' ' . $frc->getName())
                ->withAffiliatedFrc($frc);

        return $formulaValue;
    }

    /**
     * Подсчитывает значение Total Expenses to по переданному показателю Total expenses.
     *
     * @param Data $totalExpenses
     *
     * @return Data
     */
    private function calculateTotalExpensesTo(
        Data $totalExpenses
    ): Data {
        $frc = $totalExpenses->getFrc();
        $frcDataList = array(
                $frc->getId() => new HierarchicalDataNode($frc, 0, array(
                        $totalExpenses
                ))
        );

        $totalExpensesToFormula = new TotalExpensesToFormula(
                $frcDataList,
                $totalExpenses->getDataType(),
                $frc
        );

        $totalExpensesToIndex = $this->formulaService->getParameterByCode($totalExpensesToFormula->getParameterCode());
        $totalExpensesToFormula->setParameter($totalExpensesToIndex);
        $totalContributionTo = $totalExpensesToFormula->execute()
                ->withName($totalExpensesToIndex->getName())
                ->withPeriod($totalExpenses->getPeriod());

        return $totalContributionTo;
    }

    /**
     * Подсчитывает значение Total Contribution to по переданным показателям Total expenses и Total margin.
     *
     * @param Data $totalExpenses
     * @param Data $totalMargin
     *
     * @return Data
     */
    private function calculateTotalContributionTo(
        Data $totalExpenses,
        Data $totalMargin
    ): Data {
        $frc = $totalExpenses->getFrc();
        $frcDataList = array(
                $frc->getId() => new HierarchicalDataNode($frc, 0, array(
                        $totalExpenses,
                        $totalMargin
                ))
        );

        $totalContributionToFormula = new TotalContributionToFormula(
                $frcDataList,
                $totalExpenses->getDataType(),
                $frc
        );

        $totalContributionToIndex = $this->formulaService->getParameterByCode($totalContributionToFormula->getParameterCode());
        $totalContributionToFormula->setParameter($totalContributionToIndex);
        $totalContributionTo = $totalContributionToFormula->execute()
                ->withName($totalContributionToIndex->getName())
                ->withPeriod($totalExpenses->getPeriod());

        return $totalContributionTo;
    }

    /**
     * Возвращает список запросов затрат, находящихся в том же ЦФО и имеющих указанный блок
     * по данному отчетов {@link $data}.
     *
     * @param Data $data
     * @param int $sectionId
     *
     * @return ExpenseRequest[]
     *
     * @throws Exception
     */
    private function getExpenseRequestListByDataAndSectionId(Data $data, int $sectionId): array {
        $expenseRequestList = $this->referenceService->getExpenseRequestList($data->getPeriod());
        $filteredExpenseRequestList = array_filter($expenseRequestList, function (ExpenseRequest $expenseRequest) use ($data, $sectionId) {
            return $expenseRequest->frc === $data->getFrc()
                    && in_array($sectionId, $expenseRequest->item->getType())
                    && $this->formulaService->isFrcAvailableForParameter($expenseRequest->frc, $expenseRequest->item);
        });

        return $filteredExpenseRequestList;
    }

    /**
     * Возвращает список данных отчетов, использующихся в расчетах показателя Total Expenses
     * по изменившемуся данному отчетов {@link $filterData}.
     *
     * @param DataContainer $dataContainer
     * @param Data $filterData
     *
     * @return HierarchicalDataNode[]
     *
     * @throws Exception
     */
    private function getExpenseDataByData(
        DataContainer $dataContainer,
        Data          $filterData
    ): array {
        $dataNode = $dataContainer->getDataNode();
        $filteredDataNode = $dataContainer->filter(
                $dataNode,
                function (Data $data) use ($filterData) {
                    return $this->isSamePeriodAndDataType($data, $filterData)
                            && $this->isTotalExpensesData($data, $filterData);
                }
        );

        if (is_null($filteredDataNode)) {
            return array();
        }

        return $this->getFrcDataNodeList($filteredDataNode, $dataContainer->getHierarchyConfig());
    }

    /**
     * Возвращает список данных отчетов, использующихся в расчетах показателя Total Margin
     * по изменившемуся данному отчетов {@link $filterData}.
     *
     * @param DataContainer $dataContainer
     * @param Data $filterData
     *
     * @return HierarchicalDataNode[]
     *
     * @throws Exception
     */
    private function getRevenueDataByData(DataContainer $dataContainer, Data $filterData): array {
        $dataNode = $dataContainer->getDataNode();
        $filteredDataNode = $dataContainer->filter($dataNode, function (Data $data) use ($filterData) {
            return $this->isSamePeriodAndDataType($data, $filterData)
                    && $this->isTotalMarginData($data, $filterData);
        });

        if (is_null($filteredDataNode)) {
            return array();
        }

        return $this->getFrcDataNodeList($filteredDataNode, $dataContainer->getHierarchyConfig());
    }

    /**
     * Проверяет, входит ли данное отчетов {@link $Data} в список данных для расчета показателя Total Expenses
     * по изменившемуся данному отчетов {@link $filterData}.
     *
     * @param Data $data
     * @param Data $filterData
     *
     * @return bool
     */
    private function isTotalExpensesData(
        Data $data,
        Data $filterData
    ): bool {
        $frc = $data->getFrc();
        $filterFrc = $filterData->getFrc();
        if ($frc === $filterFrc) {
            $item = $data->getItem();
            $isTotalExpenseItem = $item && in_array($this->itemExpensesSectionId, $item->getType())
                && $this->formulaService->isFrcAvailableForParameter($filterFrc, $item);
            if ($isTotalExpenseItem) {
                return true;
            }

            $index = $data->getIndex();
            $isTotalExpenseIndex = $index && in_array($this->indexExpensesSectionId, $index->getType())
                && $this->formulaService->isFrcAvailableForParameter($filterFrc, $index);
            $isTotalExpensesToOrFromOrGeneral = in_array(
                    $index ? $index->getCode() : null,
                    ExpenseAndRevenueCalculator::EXPENSES_CALCULATION_CODE_LIST
            );

            return $isTotalExpenseIndex && !$isTotalExpensesToOrFromOrGeneral;
        } elseif (
                in_array($frc, $filterFrc->getChildGreenFrcList())
                || in_array($frc, $filterFrc->getChildRedFrcList())
        ) {
            $index = $data->getIndex();
            return $index && $index->getCode() == ExpenseAndRevenueCalculator::TOTAL_EXPENSES_TO_CODE;
        }

        return false;
    }

    /**
     * Проверяет, входит ли данное отчетов {@link $Data} в список данных для расчета показателя Total Margin
     * по изменившемуся данному отчетов {@link $filterData}.
     *
     * @param Data $data
     * @param Data $filterData
     *
     * @return bool
     */
    private function isTotalMarginData(
        Data $data,
        Data $filterData
    ) {
        $frc = $data->getFrc();
        $filterFrc = $filterData->getFrc();
        if ($frc === $filterFrc) {
            $item = $data->getItem();
            $isTotalMarginItem = $item && in_array($this->itemRevenueSectionId, $item->getType())
                && $this->formulaService->isFrcAvailableForParameter($filterFrc, $item);
            if ($isTotalMarginItem) {
                return true;
            }

            $index = $data->getIndex();
            $isTotalMarginIndex = $index && in_array($this->indexRevenueSectionId, $index->getType())
                && $this->formulaService->isFrcAvailableForParameter($filterFrc, $index);
            $isTotalMarginToOrFromOrGeneral = in_array(
                    $index ? $index->getCode() : null,
                    ExpenseAndRevenueCalculator::REVENUE_CALCULATION_CODE_LIST
            );

            return $isTotalMarginIndex && !$isTotalMarginToOrFromOrGeneral;
        } elseif (
                in_array($frc, $filterFrc->getChildGreenFrcList())
                || in_array($frc, $filterFrc->getChildRedFrcList())
        ) {
            $index = $data->getIndex();
            return $index && $index->getCode() == ExpenseAndRevenueCalculator::TOTAL_CONTRIBUTION_TO_CODE;
        }

        return false;
    }

    /**
     * Проверяет, что у {@link $data} и {@link $filterData} одинаковые типы данных и периоды.
     *
     * @param Data $data
     * @param Data $filterData
     *
     * @return bool
     */
    private function isSamePeriodAndDataType(Data $data, Data $filterData): bool {
        return $data->getDataType() === $filterData->getDataType()
                && $data->getPeriod() === $filterData->getPeriod();
    }

    /**
     * Убирает из списка запросов затрат те запросы,
     * которые проходят по имющимся в списке данных отчетов индексам и итемам.
     *
     * @param ExpenseRequest[] $expensesRequestList
     * @param HierarchicalDataNode[] $frcDataList
     *
     * @return ExpenseRequest[]
     */
    private function deleteAlreadyUsedExpensesRequest(array $expensesRequestList, array $frcDataList): array {
        if (empty($expensesRequestList)) {
            return array();
        }

        $dataCodeList = array();
        foreach ($frcDataList as $frcData) {
            $dataCodeList = array_merge($dataCodeList, array_map(
                    function (Data $data) {
                        $parameter = $data->getIndex() ?? $data->getItem();
                        return $parameter ? $parameter->getCode() : null;
                    },
                    $frcData->getChildNodeList()
            ));
        }

        $dataCodeList = array_unique($dataCodeList);
        $expensesRequestList = array_filter(
                $expensesRequestList,
                function (ExpenseRequest $expenseRequest) use ($dataCodeList) {
                    $expenseRequestCode = $expenseRequest->item->getCode();
                    return !is_null($expenseRequestCode) && in_array($expenseRequestCode, $dataCodeList);
                }
        );

        return $expensesRequestList;
    }

    /**
     * Возвращает список нод данных отчетов, сгруппировавших данные по ЦФО,
     * у которых остальные параметры аналогичны переданному данному отчетов {@link $data}.
     *
     * @param HierarchicalDataNode $dataNode
     * @param DataHierarchyConfig $config
     *
     * @return HierarchicalDataNode[]
     *
     * @throws Exception
     */
    private function getFrcDataNodeList(
        HierarchicalDataNode      $dataNode,
        DataHierarchyConfig $config
    ): array {
        $config = $config->getConfig();
        $parentFrcDataNodeLevel = count($config) - 1;
        while ($dataNode instanceof HierarchicalDataNode) {
            if ($dataNode->getLevel() != $parentFrcDataNodeLevel) {
                $childNodeList = $dataNode->getChildNodeList();
                $dataNode = reset($childNodeList);
                continue;
            }

            return $dataNode->getChildNodeList();
        }

        return array();
    }

    /**
     * Проверяет, что в данном отчетов были заполнены все необходимые поля.
     * Необходимые поля: DATA_TYPE, PERIOD, FRC, INDEX_NAME, ITEM_NAME, SUM_IN_USD.
     *
     * @param Data $data
     *
     * @return bool
     *
     * @throws Exception
     */
    private function checkChangedData(Data $data): bool {
        $index = $data->getIndex();
        $item = $data->getItem();
        $isValidIndexOrItem = (bool)$index && $this->isIndexInExpenseOrRevenueBlock($index)
                || (bool)$item && $this->isItemInExpenseOrRevenueBlock($item);

        $isRequiredPropertySet =  (bool)$data->getPeriod()
                || !is_null($data->getSumInUsd()); // Может быть равна 0.

        return $isValidIndexOrItem && $isRequiredPropertySet;
    }

    /**
     * Проверяет, что индекс находится в блоке расходов или доходов.
     *
     * @param Index $index - индекс данного отчетов.
     *
     * @return bool
     *
     * @throws Exception
     */
    private function isIndexInExpenseOrRevenueBlock(Index $index): bool {
        if (
                !in_array($this->indexExpensesSectionId, $index->getType())
                && !in_array($this->indexRevenueSectionId, $index->getType())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет, что итем находится в блоке расходов или доходов.
     *
     * @param Item $item - итем данного отчетов.
     *
     * @return bool
     *
     * @throws Exception
     */
    private function isItemInExpenseOrRevenueBlock(Item $item): bool {
        if (
                !in_array($this->itemExpensesSectionId, $item->getType())
                && !in_array($this->itemRevenueSectionId, $item->getType())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Возвращает флаг, включен ли данный калькулятор.
     *
     * @return bool
     *
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    private function isCalculatorEnabled(): bool {
        return Option::get('makarenko.fincalc', 'FINCALC_TRIGGER_IS_ON') == 'Y';
    }
}
