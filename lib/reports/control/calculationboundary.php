<?php
namespace makarenko\fincalc\reports\control;


use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Exception;
use makarenko\fincalc\reports\control\calculator\AllocationCalculator;
use makarenko\fincalc\reports\control\calculator\BaseCalculator;
use makarenko\fincalc\reports\control\calculator\ExpenseAndRevenueCalculator;
use makarenko\fincalc\reports\control\calculator\IterativeCalculator;
use makarenko\fincalc\reports\control\formula\FormulaService;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\util\EventLog;


/**
 * Class CalculationBoundary - обертка для расчетов.
 *
 * @package makarenko\fincalc\reports\control
 */
class CalculationBoundary {
    private const MONITORING_FILE_NAME = 'calculation_monitoring.html';

    /** @var FormulaService */
    private $formulaService;
    /** @var ReportService */
    private $reportService;
    /** @var ReferenceService */
    private $referenceService;
    /** @var ChangeDataService */
    private $changeDataService;

    /** @var BaseCalculator[] */
    private $calculatorList;
    /** @var IterativeCalculator[] */
    private $iterativeCalculatorList;
    /** @var DataContainer[] */
    private $containerList = array();
    /** float */
    private $microtime;

    /**
     * CalculationBoundary constructor.
     *
     * @param ReferenceService|null $referenceService - сервис справочников.
     * @param ReportService|null $reportService - сервис отчетов.
     * @param FormulaService|null $formulaService - сервис для формул.
     * @param ChangeDataService|null $changeDataService - сервис для работы с изменениями данных отчетов.
     *
     * @throws Exception
     */
    public function __construct(
            ?ReferenceService  $referenceService = null,
            ?ReportService     $reportService = null,
            ?ChangeDataService $changeDataService = null,
            ?FormulaService    $formulaService = null
    ) {
        $this->referenceService = $referenceService ?? new ReferenceService();
        $this->reportService = $reportService ?? new ReportService($this->referenceService);
        $this->changeDataService = $changeDataService ?? new ChangeDataService($this->referenceService);
        $this->formulaService = $formulaService ?? new FormulaService(array_merge(
                $this->referenceService->getIndexList(),
                $this->referenceService->getItemList()
        ));

        $this->microtime = microtime(true);
    }

    /**
     * Запускает непериодические расчеты.
     *
     * @param string|null $calculatorId - id калькулятора, изменения которого нужно рассчитать.
     *
     * @throws SqlQueryException
     * @throws Exception
     */
    public function calculate(?string $calculatorId = null): void {
        $db = Application::getConnection();

        $changeDataList = $this->changeDataService->getCalculationReadyDataList($calculatorId);
        foreach ($changeDataList as $changeData) {
            try {
                $changedData = $changeData->getData();
                $dataContainer = $this->buildContainer($changedData);

                $this->changeDataService->updateChangeStatus($changeData, ChangeDataService::CHANGE_STATUS_PENDING);

                $db->startTransaction();
                $this->calculateForChangedData($changedData, $dataContainer);
                $this->processContainerAfterCalculation($dataContainer, $changedData);

                $this->changeDataService->updateChangeStatus($changeData, ChangeDataService::CHANGE_STATUS_SUCCESS);
                $db->commitTransaction();
            } catch (Exception $exception) {
                $db->rollbackTransaction();
                $this->changeDataService->updateChangeStatus(
                        $changeData,
                        ChangeDataService::CHANGE_STATUS_FAILURE,
                        $exception->getMessage()
                );
                EventLog::add($exception);
                return;
            }
        }
    }

    /**
     * Запускает вычисление итерации периодических расчетов.
     *
     * @param DataContainer|null $dataContainer
     *
     * @throws SqlQueryException
     * @throws Exception
     */
    public function calculateIteration(?DataContainer $dataContainer = null): void {
        $db = Application::getConnection();
        $dataContainer = $dataContainer ?? $this->buildContainer();
        $db->startTransaction();
        try {
            $calculatorList = $this->getIterativeCalculatorList();
            foreach ($calculatorList as $calculator) {
                $calculator->calculate($dataContainer);
            }
            $this->processContainerAfterCalculation($dataContainer, null, true);
            $db->commitTransaction();
        } catch (Exception $exception) {
            EventLog::add($exception);
            $db->rollbackTransaction();
            return;
        }
    }

/**
 * Запускает непериодические расчеты для изменившегося данного отчетов {@link $changedData}.
 *
 * @param Data $changedData
 * @param DataContainer $dataContainer - контейнер с данными отчетов.
 *
 * @return DataContainer - контейнер вместе с изменившимися в процессе расчетов данными отчетов.
 *
 * @throws calculator\CalculatorException - в случае ошибки при расчетах.
 */
private function calculateForChangedData(
    Data          $changedData,
    DataContainer $dataContainer
): DataContainer {
    $calculatorList = $this->getCalculatorList();
    $data = $dataContainer->getByData($changedData) ?? $changedData;
    foreach ($calculatorList as $calculator) {
        $changedDataMap = array($data) + $dataContainer->getChangedDataMap();
        foreach ($changedDataMap as $changedData) {
            $dataContainer = $calculator->calculate($dataContainer, $changedData);
        }
    }

    return $dataContainer;
}

    /**
     * Обрабатывает изменившиеся данные в контейнере, помечая их неизменившимися, и возвращает его.
     *
     * @param DataContainer $dataContainer
     * @param Data|null $changedData
     * @param bool $saveChange
     *
     * @return DataContainer
     * @throws Exception
     */
    private function processContainerAfterCalculation(
        DataContainer $dataContainer,
        ?Data         $changedData = null,
        bool          $saveChange = false
    ): DataContainer {
        $changedDataMap = $dataContainer->getChangedDataMap();
        $this->dumpTimeDiffFromLastDump('Get changed fincalc data map');

        foreach ($changedDataMap as $changedData) {
            $dataId = $this->reportService->changeData(
                    $changedData,
                    true,
                    $saveChange,
                    false
            )->getId();
            $this->dumpTimeDiffFromLastDump('Change fincalc data with id ' . $dataId);
        }

        list($dataTypeId, $periodId) = $this->getDataDataTypeAndPeriod($changedData);
        $this->containerList[$dataTypeId][$periodId] = $dataContainer;
        $dataContainer->reset();
        return $dataContainer;
    }

    /**
     * Возвращает массив объектов калькуляторов для расчетов.
     *
     * @return BaseCalculator[]
     */
    private function getCalculatorList(): array {
        if (!isset($this->calculatorList)) {
            $this->calculatorList = $this->initCalculatorList();
        }

        return $this->calculatorList;
    }

/**
 * Инициализирует и возвращает массив калькуляторов для расчетов.
 *
 * @return BaseCalculator[]
 */
private function initCalculatorList(): array {
    return array(
            new ExpenseAndRevenueCalculator($this->referenceService, $this->formulaService),
            new AllocationCalculator($this->referenceService, $this->formulaService)
    );
}

    /**
     * Возвращает массив объектов калькуляторов для расчетов.
     *
     * @return IterativeCalculator[]
     */
    private function getIterativeCalculatorList(): array {
        if (!isset($this->iterativeCalculatorList)) {
            $this->iterativeCalculatorList = $this->initIterativeCalculatorList();
        }

        return $this->iterativeCalculatorList;
    }

    /**
     * Инициализирует и возвращает массив калькуляторов для расчетов.
     *
     * @return IterativeCalculator[]
     */
    private function initIterativeCalculatorList(): array {
        return array(
                new IterativeCalculator($this->referenceService, $this->formulaService),
        );
    }

    /**
     * Создает контейнер с данными отчетов для типа данных и периода переданного данного отчетов.
     *
     * @param Data $data
     *
     * @return DataContainer
     *
     * @throws Exception - если создать не получилось.
     */
    private function buildContainer(?Data $data = null): DataContainer {
        $dataHierarchyConfig = new DataHierarchyConfig();

        list($dataTypeId, $periodId) = $this->getDataDataTypeAndPeriod($data);
        if (!$this->containerList[$dataTypeId][$periodId]) {
            $dataNode = (new DataStructureBuilder($dataHierarchyConfig))
                    ->setDataList($this->reportService->getDataList($dataTypeId, $periodId))
                    ->build();

            $this->containerList[$dataTypeId][$periodId] = new DataContainer(
                    $dataNode,
                    $dataHierarchyConfig
            );
        }

        return $this->containerList[$dataTypeId][$periodId];
    }

    /**
     * Возвращает массив с id типа данных и периода.
     *
     * @param Data|null $data
     *
     * @return array
     */
    private function getDataDataTypeAndPeriod(?Data $data = null): array {
        $period = $data ? $data->getPeriod() : null;

        $dataTypeId = $data ? $data->getDataType()->getId() : 0;
        $periodId = $period ? $period->getId() : 0;

        return array($dataTypeId, $periodId);
    }

    private function dumpTimeDiffFromLastDump(string $text): void {
        if (Option::get('makarenko.fincalc', 'FINCALC_CALCULATOR_MONITORING_MODE') != 'Y') {
            return;
        }

        $currentMicrotime = microtime(true);
        $microtimeDiff = $currentMicrotime - $this->microtime;
        Debug::dumpToFile($microtimeDiff, $text, CalculationBoundary::MONITORING_FILE_NAME);
        $this->microtime = $currentMicrotime;
    }
}
