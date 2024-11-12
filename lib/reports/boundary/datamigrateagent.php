<?php
namespace makarenko\fincalc\reports\boundary;


use Bitrix\Main\Config\Option;
use CAgent;
use Exception;
use makarenko\fincalc\reports\control\DataHistoryService;
use makarenko\fincalc\reports\control\DataMigrateService;
use makarenko\fincalc\reports\control\exception\OldDataNotFoundException;
use makarenko\fincalc\reports\control\ReportService;
use makarenko\fincalc\util\EventLog;


/**
 * Class DataMigrateAgent - класс агента,
 * ответственного за миграцию актуальных и удаление неактульных данных отчетов.
 *
 * @package makarenko\fincalc\reports\boundary
 */
class DataMigrateAgent {
    private const OLD_FINCALC_DATA_ITERATION_DELETE_COUNT = 1000;

    private static $triggerIsOn;
    private static $allocationTriggerIsOn;

    /**
     * Метод агента, очищает инфоблок Data for  reports от неактуальных данных.
     *
     * @return string
     *
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function migrateData(): string {
        try {
            DataMigrateAgent::deactivateCalculation();
            $isAllOldDataDeleted = DataMigrateAgent::deleteOldData();
            if (!$isAllOldDataDeleted) {
                return __METHOD__ . '();';
            }

            DataMigrateAgent::archiveData();
            Option::set('makarenko.fincalc', 'FINCALC_DATA_IS_ONLY_ACTUAL_DATA_MODE', 'Y');
        } catch (Exception $exception) {
            EventLog::add($exception);
        } finally {
            DataMigrateAgent::activateCalculation();
        }

        return '';
    }

    /**
     * Удаляет неактуальные данные отчетов.
     *
     * @return bool - все ли данные удалены.
     *
     * @throws OldDataNotFoundException
     */
    private static function deleteOldData(): bool {
        $dataMigrateService = new DataMigrateService();
        $deletedDataCount = 1;

        while($data = $dataMigrateService->getOldDataElement()) {
            if ($deletedDataCount > DataMigrateAgent::OLD_FINCALC_DATA_ITERATION_DELETE_COUNT) {
                return false;
            }

            $dataMigrateService->deleteDataById($data['ID']);
            $deletedDataCount += 1;
        }

        return true;
    }

    /**
     * Архивирует все данные отчетов в историческую таблицу.
     */
    private static function archiveData(): void {
        $reportService = new ReportService();
        $dataHistoryService = new DataHistoryService();
        try {
            $dataList = $reportService->getDataList();
            foreach ($dataList as $data) {
                $dataHistoryService->archive($data);
            }
        } catch (Exception $exception) {

        }
    }

    /**
     * Создает агент.
     *
     * @param int $interval
     */
    public static function createAgent($interval = 60): void {
        CAgent::AddAgent(
                DataMigrateAgent::class . '::migrateData();',
                'makarenko.fincalc',
                'N',
                $interval
        );
    }

    /**
     * Вклоючает срабатывание обработчиков расчетов.
     *
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    private static function activateCalculation(): void {
        Option::set('makarenko.fincalc', 'FINCALC_ALLOCATION_TRIGGER_IS_ON', DataMigrateAgent::$allocationTriggerIsOn);
        Option::set('makarenko.fincalc', 'FINCALC_TRIGGER_IS_ON', DataMigrateAgent::$triggerIsOn);
    }

    /**
     * Отключает срабатывание обработчиков расчетов.
     *
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    private static function deactivateCalculation(): void {
        DataMigrateAgent::$allocationTriggerIsOn = Option::get('makarenko.fincalc', 'FINCALC_ALLOCATION_TRIGGER_IS_ON');
        DataMigrateAgent::$triggerIsOn = Option::get('makarenko.fincalc', 'FINCALC_TRIGGER_IS_ON');

        Option::set('makarenko.fincalc', 'FINCALC_ALLOCATION_TRIGGER_IS_ON', 'N');
        Option::set('makarenko.fincalc', 'FINCALC_TRIGGER_IS_ON', 'N');
    }
}
