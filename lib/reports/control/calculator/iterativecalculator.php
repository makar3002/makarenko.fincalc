<?php
namespace makarenko\fincalc\reports\control\calculator;

use Bitrix\Main\Type\DateTime;
use Exception;
use makarenko\fincalc\reports\control\data\DataMapper;
use makarenko\fincalc\reports\control\DataContainer;
use makarenko\fincalc\reports\control\DataHierarchyConfig;
use makarenko\fincalc\reports\control\AllocationDataRepository;
use makarenko\fincalc\reports\control\formula\BaseFormula;
use makarenko\fincalc\reports\control\formula\FormulaService;
use makarenko\fincalc\reports\control\formula\iterative\FxResultFormula;
use makarenko\fincalc\reports\control\formula\iterative\GrossRevenueTotalFormula;
use makarenko\fincalc\reports\control\formula\iterative\NetProfitAfterBonusesFormula;
use makarenko\fincalc\reports\control\formula\iterative\NetProfitBeforeBonusesFormula;
use makarenko\fincalc\reports\control\formula\iterative\NetRevenueTotalFormula;
use makarenko\fincalc\reports\control\frc\FrcNotFoundException;
use makarenko\fincalc\reports\control\ReferenceService;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\period\Period;

class IterativeCalculator {
    protected const SIMPLE_FORMULA_CLASS_LIST = array(
            NetProfitBeforeBonusesFormula::class,
            NetProfitAfterBonusesFormula::class,
            GrossRevenueTotalFormula::class,
            NetRevenueTotalFormula::class
    );

    protected const ITERATIVE_CONTAINER_CONFIG = array(
        DataMapper::DATA_TYPE_FIELD_NAME => DataHierarchyConfig::FINCALC_DATA_FIELD_METHOD_MAP[DataMapper::DATA_TYPE_FIELD_NAME],
        DataMapper::PERIOD_FIELD_NAME => DataHierarchyConfig::FINCALC_DATA_FIELD_METHOD_MAP[DataMapper::PERIOD_FIELD_NAME],
        DataMapper::FRC_FIELD_NAME => DataHierarchyConfig::FINCALC_DATA_FIELD_METHOD_MAP[DataMapper::FRC_FIELD_NAME]
    );

    /** @var FormulaService - сервис для подготовки данных для формул. */
    protected $formulaService;
    /** @var ReferenceService - сервис для справочников. */
    protected $referenceService;
    /** @var DataHierarchyConfig - конфиг. */
    protected $iterativeHierarchyConfig;

    public function __construct(ReferenceService $referenceService, FormulaService $formulaService) {
        $this->referenceService = $referenceService;
        $this->formulaService = $formulaService;
        $this->iterativeHierarchyConfig = new DataHierarchyConfig(IterativeCalculator::ITERATIVE_CONTAINER_CONFIG);;
    }

    /**
     * Производит расчет для при изменении конкретного данного.
     *
     * @param DataContainer $dataContainer - контейнер данных отчетов.
     *
     * @return DataContainer - контейнер содержит в себе все расчитанные показатели.
     *
     * @throws CalculatorException
     */
    public function calculate(DataContainer $dataContainer): DataContainer {
        try {
            if (!$this->isCalculatorEnabled()) {
                return $dataContainer;
            }

            return $this->calculateData($dataContainer);
        } catch (Exception $exception) {
            throw new CalculatorException(
                    'Iterative calculator failure. Reason: ' . $exception->getMessage(),
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
     * @throws FrcNotFoundException
     * @throws Exception
     */
    private function calculateData(
        DataContainer $dataContainer
    ): DataContainer {
        $iterativeDataContainer = $this->getIterativeDataContainer($dataContainer);
        $dataContainer = $this->calculateIterativeData($dataContainer, $iterativeDataContainer);
        return $dataContainer;
    }

    /**
     * Инициирует расчет данных периодического агента.
     *
     * @param DataContainer $dataContainer
     * @param DataContainer $iterativeDataContainer
     *
     * @return DataContainer
     */
    private function calculateIterativeData(
        DataContainer $dataContainer,
        DataContainer $iterativeDataContainer
    ): DataContainer {
        list($dataContainer, $iterativeDataContainer) = $this->calculateByCallback(
                $dataContainer,
                $iterativeDataContainer,
                FxResultFormula::class,
                function (
                        array $frcDataNodeList,
                        DataType $dataType,
                        Period $period,
                        Frc $frc,
                        string $formulaClass
                ) {
                    return $this->calculateFxResult(
                            $frcDataNodeList,
                            $dataType,
                            $period,
                            $frc
                    );
                }
        );

        foreach (IterativeCalculator::SIMPLE_FORMULA_CLASS_LIST as $formulaClass) {
            list($dataContainer, $iterativeDataContainer) = $this->calculateByCallback(
                    $dataContainer,
                    $iterativeDataContainer,
                    $formulaClass,
                    function (
                            array $frcDataNodeList,
                            DataType $dataType,
                            Period $period,
                            Frc $frc,
                            string $formulaClass
                    ) {
                        return $this->calculateSimpleFormula(
                                $frcDataNodeList,
                                $dataType,
                                $period,
                                $frc,
                                $formulaClass
                        );
                    }
            );
        }

        return $dataContainer;
    }

    private function calculateByCallback(
        DataContainer $dataContainer,
        DataContainer $iterativeDataContainer,
        string        $formulaClass,
        callable      $callback
    ): array {
        $iterativeDataNode = $iterativeDataContainer->getDataNode();
        foreach ($iterativeDataNode->getChildNodeList() as $dataTypeDataNode) {
            /** @var DataType $dataType */
            $dataType = $dataTypeDataNode->getValue();
            foreach ($dataTypeDataNode->getChildNodeList() as $periodDataNode) {
                /** @var Period $period */
                $period = $periodDataNode->getValue();
                $frcDataNodeList = $periodDataNode->getChildNodeList();
                foreach ($frcDataNodeList as $frcId => $frcDataNode) {
                    /** @var Frc $frc */
                    $frc = $frcDataNode->getValue();
                    /** @var Data|null $result */
                    $result = $callback(
                            $frcDataNodeList,
                            $dataType,
                            $period,
                            $frc,
                            $formulaClass
                    );

                    if (!$result) {
                        continue;
                    }

                    $currentData = $iterativeDataContainer->getByData($result);
                    if (
                            !is_null($currentData)
                            && $currentData->getSnapshot()->getTimestamp() > $result->getSnapshot()->getTimestamp()
                    ) {
                        continue;
                    }

                    list($dataContainer, $iterativeDataContainer) = $this->addDataToTwoContainers(
                            $dataContainer,
                            $iterativeDataContainer,
                            $result
                    );
                }
            }
        }

        return array($dataContainer, $iterativeDataContainer);
    }

    private function calculateFxResult(
            array $frcDataNodeList,
            DataType $dataType,
            Period $period,
            Frc $frc
    ): ?Data {
        $frcDataNodeList = $this->formulaService->prepareFrcDataList($frcDataNodeList);
        $expenseRequestList = $this->referenceService->getExpenseRequestList($period);
        $fxResultFormula = new FxResultFormula($frcDataNodeList, $dataType, $frc, $expenseRequestList);
        return $this->calculateFormula($fxResultFormula, $period);
    }

    private function calculateSimpleFormula(
            array $frcDataNodeList,
            DataType $dataType,
            Period $period,
            Frc $frc,
            string $formulaClass
    ): ?Data {
        $frcDataNodeList = $this->formulaService->prepareFrcDataList($frcDataNodeList);
        $formula = new $formulaClass($frcDataNodeList, $dataType, $frc);
        return $this->calculateFormula($formula, $period);
    }

    private function calculateFormula(BaseFormula $formula, Period $period): ?Data {
        $formulaIndex = $this->formulaService->getParameterByCode($formula->getParameterCode());
        $formula->setParameter($formulaIndex);
        $result = $formula->execute()
                ->withName($formulaIndex->getName())
                ->withPeriod($period)
                ->withSnapshot($formula->getMaxSnapshot() ?? new DateTime());

        if (!$this->formulaService->isFrcAvailableForParameter($result->getFrc(), $formulaIndex)) {
            return null;
        }

        return $result;
    }

    private function getIterativeDataContainer(
        DataContainer $dataContainer
    ): DataContainer {
        $originalHierarchyConfig = $dataContainer->getHierarchyConfig();
        $dataNode = $dataContainer->getDataNode();
        $filteredDataNode = $dataContainer->filter($dataNode, function (Data $data) {
            return $this->isIterativeData($data);
        }) ?? new HierarchicalDataNode(null, 0, array());



        $newDataContainer = new DataContainer($filteredDataNode, $originalHierarchyConfig);
        $newDataContainer->changeHierarchyConfig($this->iterativeHierarchyConfig);

        return $newDataContainer;
    }

    private function addDataToTwoContainers(
        DataContainer $firstDataContainer,
        DataContainer $secondDataContainer,
        Data          $data
    ): array {
        $firstDataContainer->change($data);
        $secondDataContainer->change($data);

        return array($firstDataContainer, $secondDataContainer);
    }

    /**
     * Проверяет, имеют ли данные отчетов идентичные тип данных, период, уровень аллокации и affiliated ЦФО.
     * Если указанные поля одинаковые, то данное $nodeData является валидным.
     *
     * @param Data $data - данное отчета, валидность которого проверяется.
     *
     * @return bool
     */
    private function isIterativeData(Data $data): bool {
        if (!$data->getPeriod() || !$data->getPeriod()->isOpen()) {
            return false;
        }

        if (!is_null($data->getAffiliatedFrc())) {
            return false;
        }

        if (!is_null($data->getAllocationLevel())) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     *
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    private function isCalculatorEnabled(): bool {
        //TODO: Заменить на проверку нужной опции.
        return true;
    }
}
