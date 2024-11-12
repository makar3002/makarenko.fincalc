<?php
namespace makarenko\fincalc\reports\boundary;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use CIBlockProperty;
use Exception;
use makarenko\fincalc\reports\control\ChangeDataService;
use makarenko\fincalc\reports\control\data\DataMapper;
use makarenko\fincalc\reports\control\DataHistoryService;
use makarenko\fincalc\reports\control\frc\FrcNotFoundException;
use makarenko\fincalc\reports\control\ReferenceService;
use makarenko\fincalc\reports\control\ReportService;
use makarenko\fincalc\reports\entity\data\Data;


/**
 * Class DataFacade - класс для обработчиков, регулирующих работу с инфоблоком Data for  reports.
 *
 * @package makarenko\fincalc\reports\boundary
 */
class DataFacade {
    /** @var ReportService - объект сервиса отчетов. */
    private static $reportService;
    /** @var ReferenceService - объект сервиса справочников. */
    private static $referenceService;
    /** @var Data|null $rememberedDataElement */
    private static $rememberedDataElement = null;

    /**
     * Метод обработчика события iblock:OnBeforeIblockElementAdd, предотвращает добавление данных, если элемент уже
     * существует.
     *
     * @param array $element - информация об элементе инфоблока.
     *
     * @return bool - нужно ли добавлять элемент.
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws Exception
     */
    public static function preventAddingExistsElement(array $element): bool {
        global $APPLICATION;

        if (!DataFacade::isOnlyActualModeEnabled()) {
            return true;
        }

        $iblockId = $element['IBLOCK_ID'];
        $dataIblockId = Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID');
        if ($iblockId != $dataIblockId) {
            return true;
        }

        if (!DataFacade::checkData($element)) {
            $APPLICATION->ThrowException(' data has not index or item.');
            return false;
        }

        if (!DataFacade::getAlreadyExistsData($element)) {
            return true;
        }

        $APPLICATION->ThrowException(' data already exists');
        return false;
    }

    /**
     * Метод обработчика события iblock:OnBeforeIblockElementUpdate, предотвращает обновление данных, если элемент уже
     * существует.
     *
     * @param array $element - информация об элементе инфоблока.
     *
     * @return bool - нужно ли обновлять элемент.
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws Exception
     */
    public static function preventUpdatingExistsElement(array $element): bool {
        global $APPLICATION;

        if (!DataFacade::isOnlyActualModeEnabled()) {
            return true;
        }

        $iblockId = $element['IBLOCK_ID'];
        $dataIblockId = Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID');
        if ($iblockId != $dataIblockId) {
            return true;
        }

        if (!DataFacade::checkData($element)) {
            $APPLICATION->ThrowException(' data has not index or item.');
            return false;
        }

        $existsData = DataFacade::getAlreadyExistsData($element)
                ?? DataFacade::getReportService()->getDataById(intval($element['ID']));
        if (!$existsData) {
            $APPLICATION->ThrowException('data with id ' . $element['ID'] . ' not exists');
            return false;
        }

        if ($existsData->getId() == $element['ID']) {
            return true;
        }

        $APPLICATION->ThrowException('data with id ' . $existsData->getId() . ' already exists, please change it or create new data.');
        return false;
    }

    /**
     * Метод обработчика события iblock:OnAfterIblockElementAdd и iblock:OnAfterIblockElementUpdate,
     * архивирует изменения данных отчетов в историческую таблицу.
     *
     * @param array $element
     *
     * @return bool
     */
    public static function archiveChangedDataElement(array $element): bool {
        try {
            if ($element['RESULT'] === false) {
                return true;
            }

            $iblockId = intval($element['IBLOCK_ID']);
            $dataIblockId = Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID');
            if ($iblockId != $dataIblockId) {
                return true;
            }

            $isOnlyActualDataModeEnabled = DataFacade::isOnlyActualModeEnabled();
            if (!$isOnlyActualDataModeEnabled) {
                return true;
            }

            $dataId = intval($element['ID']);
            if ($dataId <= 0) {
                throw new Exception('Wrong data ID.');
            }

            $data = DataFacade::getDataFromArray($element)
                    ?? DataFacade::getReportService()->getDataById($dataId);

            if (!$data) {
                throw new \RuntimeException('data not found.');
            }

            $dataHistoryService = new DataHistoryService();
            $dataHistoryService->archive($data);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Метод обработчика события iblock:OnBeforeIblockElementDelete,
     * запоминает уделиение данного отчетов для последующей обработки.
     *
     * @param int $dataId
     * @param array $element
     *
     * @return bool
     */
    public static function rememberDataChangeBeforeDelete(int $dataId, array $element): bool {
        try {
            $iblockId = intval($element['IBLOCK_ID']);
            $dataIblockId = Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID');
            if ($iblockId != $dataIblockId) {
                return true;
            }

            if ($dataId <= 0) {
                throw new Exception('Wrong data ID.');
            }

            $reportService = DataFacade::getReportService();
            DataFacade::$rememberedDataElement = DataFacade::getAlreadyExistsData($element)
                    ?? $reportService->getDataById($dataId);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Метод обработчика события iblock:OnIBlockElementAdd, iblock:OnIBlockElementUpdate
     * и iblock:OnIBlockElementDelete, сохраняет изменения данных отчетов в соответствующую таблицу.
     *
     * @param array $element
     *
     * @return bool
     */
    public static function saveDataChange(array $element): bool {
        try {
            if ($element['RESULT'] === false) {
                return true;
            }

            $iblockId = intval($element['IBLOCK_ID']);
            $dataIblockId = Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID');
            if ($iblockId != $dataIblockId) {
                return true;
            }

            $dataId = intval($element['ID']);
            if ($dataId <= 0) {
                throw new Exception('Wrong data ID.');
            }

            if ($element[ChangeDataService::SAVE_CHANGE_KEY] === false) {
                return true;
            }

            $data = DataFacade::$rememberedDataElement
                    ?? DataFacade::getDataFromArray($element);

            if (is_null($data)) {
                throw new Exception('Could not find data.');
            }

            $dataHistoryService = new ChangeDataService();
            $dataHistoryService->saveDataChange($data);
        } catch (Exception $exception) {
            return false;
        } finally {
            DataFacade::$rememberedDataElement = null;
        }

        return true;
    }

    private static function checkData(array $dataInfo): bool {
        $dataPropertyInfo = $dataInfo['PROPERTY_VALUES'] ?? array();
        $preparedDataProperties = DataFacade::getPreparedElementProperties($dataPropertyInfo);
        if ($preparedDataProperties['INDEX_ID'] == 0 && $preparedDataProperties['ITEM_ID'] == 0) {
            return false;
        }

        return true;
    }

    /**
     * Получает данное отчетов из массива с информацией о нем и возвращает его.
     *
     * @param array $dataInfo - информациая о данном отчета в виде массива.
     *
     * @return Data|null
     *
     * @throws Exception
     */
    private static function getDataFromArray(array $dataInfo): ?Data {
        try {
            $dataPropertyInfo = $dataInfo['PROPERTY_VALUES'] ?? array();
            $preparedDataProperties = DataFacade::getPreparedElementProperties($dataPropertyInfo);
            $referenceService = DataFacade::getReferenceService();

            $dataType = $referenceService->getDataTypeById($preparedDataProperties['DATATYPE']);
            $period = $referenceService->getPeriodList()[$preparedDataProperties['PERIOD_ID']];
            $frc = $referenceService->getFrcById($preparedDataProperties['FRC_ID']);
            $index = $referenceService->getIndexList()[$preparedDataProperties['INDEX_ID']];
            $item = $referenceService->getItemList()[$preparedDataProperties['ITEM_ID']];
            $originalCurrency = $referenceService->getOriginalCurrencyList()[$preparedDataProperties['ORIGINAL_CURRENCY_ID']];
            $originalCurrencyName = $originalCurrency ? $originalCurrency->getName() : '';
            $currency = $referenceService->getCurrencyList()[$preparedDataProperties['PERIOD_ID'] . '|' . $originalCurrencyName];
            $allocationLevel = $referenceService->getItemList()[$preparedDataProperties['ALLOCATION_LEVEL_ID']];
            $affectedFrc = $referenceService->getFlatFrcList()[$preparedDataProperties['AFFILIATED_FRC_ID']];

            $data = new Data(
                    $dataInfo['NAME'],
                    $dataType,
                    $period,
                    $index,
                    $item,
                    $frc,
                    $currency,
                    $preparedDataProperties['SUM_IN_ORIGINAL_CURRENCY'],
                    $preparedDataProperties['SUM_IN_USD'],
                    $allocationLevel,
                    null,
                    $preparedDataProperties['SNAPSHOT'],
                    $affectedFrc,
                    ($index ?? $item)->getCode(),
                    intval($dataInfo['ID'])
            );
        } catch (Exception $notFoundException) {
            $data = null;
        }

        return $data;
    }



    /**
     * Получает уже существующее данное отчетов с одинаковыми свойствами, определяющими его уникальность, или null,
     * если его нет.
     *
     * @param array $dataInfo - информациая о данном отчета в виде массива.
     *
     * @return Data|null
     *
     * @throws Exception
     */
    private static function getAlreadyExistsData(array $dataInfo): ?Data {
        $reportService = DataFacade::getReportService();
        $dataId = intval($dataInfo['ID']);
        try {
            $dataPropertyInfo = $dataInfo['PROPERTY_VALUES'] ?? array();
            $preparedDataProperties = DataFacade::getPreparedElementProperties($dataPropertyInfo);
            $referenceService = DataFacade::getReferenceService();

            $searchData = new Data(
                    $dataInfo['NAME'],
                    $referenceService->getDataTypeById($preparedDataProperties['DATATYPE']),
                    $referenceService->getPeriodList()[$preparedDataProperties['PERIOD_ID']],
                    $referenceService->getIndexList()[$preparedDataProperties['INDEX_ID']],
                    $referenceService->getItemList()[$preparedDataProperties['ITEM_ID']],
                    $referenceService->getFrcById($preparedDataProperties['FRC_ID']),
                    null,
                    null,
                    null,
                    $referenceService->getItemList()[$preparedDataProperties['ALLOCATION_LEVEL_ID']],
                    null,
                    null,
                    $referenceService->getFlatFrcList()[$preparedDataProperties['AFFILIATED_FRC_ID']],
                    null,
                    $dataId
            );

            $resultData = $reportService->getDataByData($searchData);
        } catch (Exception $notFoundException) {
            $resultData = null;
        }

        return $resultData;
    }

    /**
     * Получает подготовленные свойства элемента инфоблока, определяющими его уникальность.
     *
     * @param array $propertyInfo - информация о всех свойствах элемента.
     *
     * @return array
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    private static function getPreparedElementProperties(array $propertyInfo): array {
        $dataPropertyNameMap = DataFacade::getDataIblockPropertyNamesMap();

        $dataType = intval($propertyInfo[$dataPropertyNameMap[DataMapper::DATA_TYPE_FIELD_NAME]] ?: $propertyInfo[DataMapper::DATA_TYPE_FIELD_NAME]);

        $periodProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::PERIOD_FIELD_NAME]];
        $periodId = intval(is_array($periodProperty) ? DataFacade::getValueFromElementProperty($periodProperty) : ($periodProperty ?: $propertyInfo[DataMapper::PERIOD_FIELD_NAME]));

        $frcProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::FRC_FIELD_NAME]];
        $frcId = intval(is_array($frcProperty) ? DataFacade::getValueFromElementProperty($frcProperty) : ($frcProperty ?: $propertyInfo[DataMapper::FRC_FIELD_NAME]));

        $indexProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::INDEX_FIELD_NAME]];
        $indexId = intval(is_array($indexProperty) ? DataFacade::getValueFromElementProperty($indexProperty) : ($indexProperty ?: $propertyInfo[DataMapper::INDEX_FIELD_NAME]));

        $itemProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::ITEM_FIELD_NAME]];
        $itemId = intval(is_array($itemProperty) ? DataFacade::getValueFromElementProperty($itemProperty) : ($itemProperty ?: $propertyInfo[DataMapper::ITEM_FIELD_NAME]));

        $allocationLevelId = intval($propertyInfo[$dataPropertyNameMap[DataMapper::ALLOCATION_LEVEL_FIELD_NAME]] ?: $propertyInfo[DataMapper::ALLOCATION_LEVEL_FIELD_NAME]);
        $affiliatedFrcId = intval($propertyInfo[$dataPropertyNameMap[DataMapper::AFFILIATED_FRC_FIELD_NAME]] ?: $propertyInfo[DataMapper::AFFILIATED_FRC_FIELD_NAME]);

        $sumInUsdProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::SUM_IN_USD_FIELD_NAME]];
        $sumInUsd = floatval(is_array($sumInUsdProperty) ? DataFacade::getValueFromElementProperty($sumInUsdProperty) : ($sumInUsdProperty ?: $propertyInfo[DataMapper::SUM_IN_USD_FIELD_NAME]));

        $sumInOriginalCurrencyProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME]];
        $sumInOriginalCurrency = floatval(is_array($sumInOriginalCurrencyProperty) ? DataFacade::getValueFromElementProperty($sumInOriginalCurrencyProperty) : ($sumInOriginalCurrencyProperty ?: $propertyInfo[DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME]));

        $originalCurrencyProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::ORIGINAL_CURRENCY_FIELD_NAME]];
        $originalCurrencyId = intval(is_array($originalCurrencyProperty) ? DataFacade::getValueFromElementProperty($originalCurrencyProperty) : ($originalCurrencyProperty ?: $propertyInfo[DataMapper::ORIGINAL_CURRENCY_FIELD_NAME]));

        $snapshotProperty = $propertyInfo[$dataPropertyNameMap[DataMapper::SNAPSHOT_FIELD_NAME]];
        $snapshot = new DateTime(is_array($snapshotProperty) ? DataFacade::getValueFromElementProperty($snapshotProperty) : ($snapshotProperty ?: $propertyInfo[DataMapper::SNAPSHOT_FIELD_NAME]));
        return array(
                'PERIOD_ID' => $periodId,
                'INDEX_ID' => $indexId,
                'ITEM_ID' => $itemId,
                'FRC_ID' => $frcId,
                'DATATYPE' => $dataType,
                'ALLOCATION_LEVEL_ID' => $allocationLevelId,
                'AFFILIATED_FRC_ID' => $affiliatedFrcId,
                'SUM_IN_USD' => $sumInUsd,
                'SUM_IN_ORIGINAL_CURRENCY' => $sumInOriginalCurrency,
                'ORIGINAL_CURRENCY_ID' => $originalCurrencyId,
                'SNAPSHOT' => $snapshot
        );
    }

    /**
     * Возвращает значение из массива, описывающего значения свойства элемента инфоблока.
     *
     * @param array|null $elementProperty
     *
     * @return mixed
     */
    private static function getValueFromElementProperty(?array $elementProperty) {
        if (!$elementProperty) {
            return null;
        }

        $value = array_pop($elementProperty);
        if (is_array($value)) {
            return array_pop($value);
        }

        return $value;
    }

    /**
     * Возвращает маппинг названий свойств и их id в виде массива.
     *
     * @return array
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    private static function getDataIblockPropertyNamesMap(): array {
        $dataIblockId = Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID');
        $propertyResult = CIBlockProperty::GetList(
                array(),
                array(
                        'IBLOCK_ID' => $dataIblockId,
                )
        );

        $propertyNameMap = array();
        while ($property = $propertyResult->Fetch()) {
            if (!in_array($property['CODE'], DataMapper::FINCALC_DATA_PROPERTY_NAMES)) {
                continue;
            }

            $propertyNameMap[$property['CODE']] = $property['ID'];
        }

        return $propertyNameMap;
    }

    /**
     * Возвращает флаг, включен ли режим "Только актуальные данные в данных отчетов."
     *
     * @return bool
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    private static function isOnlyActualModeEnabled(): bool {
        $isOnlyActualDataMode = Option::get('makarenko.fincalc', 'FINCALC_DATA_IS_ONLY_ACTUAL_DATA_MODE');
        return $isOnlyActualDataMode == 'Y';
    }

    /**
     * Возвращает объект сервиса отчетов. Если он не был создан ранее, инициализирует его.
     *
     * @return ReportService
     * @throws Exception
     */
    private static function getReportService(): ReportService {
        if (!isset(DataFacade::$reportService)) {
            DataFacade::$reportService = new ReportService(DataFacade::getReferenceService());
        }

        return DataFacade::$reportService;
    }

    /**
     * Возвращает объект сервиса отчетов. Если он не был создан ранее, инициализирует его.
     *
     * @return ReferenceService
     */
    private static function getReferenceService(): ReferenceService {
        if (!isset(DataFacade::$referenceService)) {
            DataFacade::$referenceService = new ReferenceService();
        }

        return DataFacade::$referenceService;
    }
}
