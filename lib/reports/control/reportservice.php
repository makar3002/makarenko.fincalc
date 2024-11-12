<?php
namespace makarenko\fincalc\reports\control;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Exception;
use makarenko\fincalc\reports\control\data\DataMapper;
use makarenko\fincalc\reports\entity\data\AffiliatedFrcData;
use makarenko\fincalc\reports\entity\data\AllocationLevelData;
use makarenko\fincalc\reports\entity\data\DataTypeData;
use makarenko\fincalc\reports\entity\data\FrcData;
use makarenko\fincalc\reports\entity\data\PeriodData;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\period\Period;
use makarenko\fincalc\reports\entity\IblockElement;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\data\Data;


/**
 * Class ReportService - сервис для работы с данными отчетов.
 *
 * @package makarenko\fincalc\reports\control
 */
class ReportService {
    /** @var int - id элемента для временного данного отчетов по умолчанию. */
    private const DEFAULT_ID_FOR_RUNTIME_FINCALC_DATA_ELEMENTS = -1;
    /** @var IblockElementRepository - репозиторий данных отчетов. */
    private $dataRepository;
    /** @var DataMapper - маппер данных отчетов. */
    private $dataMapper;
    /** @var ReferenceService - сервис для данных справочников. */
    private $referenceService;
    /** @var DataHistoryService - сервис для архивирования данных отчетов. */
    private $dataHistoryService;
    /** @var Data[] - список данных отчетов. */
    private $dataList;
    /** @var DataTypeData[] - список данных отчетов. */
    private $dataStructure;

    /**
     * ReportService constructor.
     *
     * @param ReferenceService|null $referenceService
     * @param IblockElementRepository|null $dataRepository
     * @param DataMapper|null $dataMapper
     * @param DataHistoryService|null $dataHistoryService
     *
     * @throws Exception
     */
    public function __construct(
            ?ReferenceService        $referenceService = null,
            ?IblockElementRepository $dataRepository = null,
            ?DataMapper              $dataMapper = null,
            ?DataHistoryService      $dataHistoryService = null
    ) {
        $this->referenceService = $referenceService ?? new ReferenceService();

        $dataIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID'));
        $this->dataRepository = $dataRepository ?? new IblockElementRepository($dataIblockId);
        $this->dataMapper = $dataMapper ?? new DataMapper();
        $this->dataHistoryService = $dataHistoryService ?? new DataHistoryService();
    }

    /**
     * Возвращает данное отчетов по его id.
     *
     * @param int $dataId
     *
     * @return Data|null
     *
     * @throws Exception
     */
    public function getDataById(int $dataId): ?Data {
        $iblockElementList = $this->dataRepository->getIblockElementData(
                DataMapper::FINCALC_DATA_FIELD_NAMES,
                DataMapper::FINCALC_DATA_PROPERTY_NAMES,
                array(
                        DataMapper::ID_FIELD_NAME => $dataId
                )
        );

        return $this->prepareDataList($iblockElementList)[$dataId];
    }

    /**
     * Возвращает структуру всех данных отчетов, сгруппированную по типам данных.
     *
     * @param Data $data
     * @param bool $changeInDb - нужно ли изменять данные в БД.
     * @param bool $saveChangeForCalculation
     * @param bool $updateSearch
     *
     * @return Data
     *
     * @throws Exception
     */
    public function changeData(
        Data $data,
        bool $changeInDb = true,
        bool $saveChangeForCalculation = false,
        bool $updateSearch = true
    ): Data {
        $data = $data->withSnapshot(new DateTime());
        $dataPeriod = $data->getPeriod();
        $dataPeriodId = $dataPeriod ? $dataPeriod->getId() : 0;
        $dataDataType = $data->getDataType();
        $dataDataTypeId = $dataDataType->getId();
        if ($changeInDb) {
            $currentData = $this->getDataByData($data);
            $isOnlyActualDataMode = Option::get('makarenko.fincalc', 'FINCALC_DATA_IS_ONLY_ACTUAL_DATA_MODE');
            $isOnlyActualDataModeEnabled = $isOnlyActualDataMode == 'Y';
            if ($currentData && $isOnlyActualDataModeEnabled) {
                $data = $data->withId($currentData->getId());
                $dataInfo = $this->prepareData($data, $saveChangeForCalculation);
                $changedDataId = $this->dataRepository->update($dataInfo, $updateSearch);
            } else {
                $dataInfo = $this->prepareData($data, $saveChangeForCalculation);
                $changedDataId = $this->dataRepository->add($dataInfo, $updateSearch);
            }
        } else {
            $changedDataId = $this->generateRuntimeDataId($dataDataTypeId, $dataPeriodId);
        }

        $changedData = $data->withId($changedDataId);
        if (!isset($this->dataList[$dataDataTypeId][$dataPeriodId])) {
            return $changedData;
        }

        $this->addDataToList($changedData);
        if (!isset($this->dataStructure[$dataDataTypeId][$dataPeriodId])) {
            return $changedData;
        }

        $this->dataStructure[$dataDataTypeId][$dataPeriodId] = $this->getDataTypeDataList($this->dataList[$dataDataTypeId][$dataPeriodId]);
        return $changedData;
    }

    /**
     * Обновляет поисковый индекс по всем данным отчетов, чьи ID были переданы в массиве.
     *
     * @param array $dataIdList
     *
     * @return bool
     */
    public function updateDataSearch(
            array $dataIdList
    ): bool {
        try {
            $this->dataRepository->updateSearch($dataIdList);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Возвращает данное отчетов, выбранное по полям переданного данного отчета, определяющим уникальность данного.
     *
     * @param Data $data
     *
     * @return Data|null
     *
     * @throws Exception
     */
    public function getDataByData(Data $data): ?Data {
        $dataId = intval($data->getId());

        $dataType = $data->getDataType()->getId();
        $period = $data->getPeriod();
        $periodId = $period ? $period->getId() : null;
        if ($dataId > 0 && $resultData = $this->getDataList($dataType, $periodId)[$dataId]) {
            return $resultData;
        }

        $allocationLevel = $data->getAllocationLevel();
        $allocationLevelId = $allocationLevel ? $allocationLevel->getId() : null;
        $affiliatedFrc = $data->getAffiliatedFrc();
        $affiliatedFrcId = $affiliatedFrc ? $affiliatedFrc->getId() : null;
        $parameter = $data->getIndex() ?: $data->getItem();
        $parameterId = $parameter ? $parameter->getId() : null;
        $frcId = $data->getFrc()->getId();

        $dataTypeData = $this->getDataStructure($dataType, $periodId)[$dataType];
        if (empty($dataTypeData)) {
            return null;
        }

        $periodData = $dataTypeData->getPeriodDataList()[$periodId];
        if (empty($periodData)) {
            return null;
        }

        $allocationLevelData = $periodData->getAllocationLevelDataList()[$allocationLevelId];
        if (empty($allocationLevelData)) {
            return null;
        }

        $affiliatedFrcData = $allocationLevelData->getAffiliatedFrcDataList()[$affiliatedFrcId];
        if (empty($affiliatedFrcData)) {
            return null;
        }

        $frcData = $affiliatedFrcData->getFrcDataList()[$frcId];
        if (empty($frcData)) {
            return null;
        }

        $data = $frcData->getDataList()[$parameterId];
        return $data;
    }

    /**
     * Добавляет данное отчетов в список данных отчетов.
     *
     * @param Data $data
     *
     * @throws Exception
     */
    private function addDataToList(Data $data) {
        $dataPeriod = $data->getPeriod();
        $dataPeriodId = $dataPeriod ? $dataPeriod->getId() : 0;
        $dataDataType = $data->getDataType();

        if (!isset($this->dataList[$dataDataType->getId()][$dataPeriodId])) {
            $this->dataList[$dataDataType->getId()][$dataPeriodId] = $this->prepareDataList($this->dataRepository->getIblockElementData(
                    DataMapper::FINCALC_DATA_FIELD_NAMES,
                    DataMapper::FINCALC_DATA_PROPERTY_NAMES,
                    $this->prepareDataFilter($dataDataType->getId(), $dataPeriodId)
            ));
        }

        $this->dataList[$dataDataType->getId()][$dataPeriodId][$data->getId()] = $data;
    }

    /**
     * Возвращает структуру всех данных отчетов, сгруппированную по типам данных. Поддерживает фильтр по типу данных и
     * периоду.
     *
     * @param int $dataType - тип данных.
     * @param int $periodId - id периода.
     *
     * @return DataTypeData[]
     *
     * @throws Exception
     */
    public function getDataStructure(int $dataType = 0, int $periodId = 0): array {
        if (!isset($this->dataStructure[$dataType][$periodId])) {
            $dataList = $this->getDataList($dataType, $periodId);
            $this->dataStructure[$dataType][$periodId] = $this->getDataTypeDataList($dataList);
        }

        return $this->dataStructure[$dataType][$periodId];
    }

    /**
     * Возвращает структуру данных отчетов, сгруппированную по типам данных.
     *
     * @param Data[] $dataList
     *
     * @return DataTypeData[]
     *
     * @throws Exception
     */
    private function getDataTypeDataList(array $dataList): array {
        $dataTypeList = array_unique(array_map(function (Data $data) {
            return $data->getDataType();
        }, $dataList), SORT_REGULAR);

        $dataTypeDataList = array();
        foreach ($dataTypeList as $dataType) {
            $currentDataTypeDataList = array_filter(
                    $dataList,
                    function (Data $data) use ($dataType) {
                        return $data->getDataType() == $dataType;
                    }
            );
            $dataTypeDataList[$dataType->getId()] = new DataTypeData(
                    $dataType,
                    $this->getPeriodDataList($currentDataTypeDataList)
            );
        }

        return $dataTypeDataList;
    }

    /**
     * Возвращает структуру данных отчетов, сгруппированную по периодам.
     *
     * @param Data[] $dataList
     *
     * @return PeriodData[]
     *
     * @throws Exception
     */
    private function getPeriodDataList(array $dataList): array {
        $periodList = array_unique(array_map(function (Data $data) {
            return $data->getPeriod();
        }, $dataList), SORT_REGULAR);

        $periodDataList = array();
        /** @var Period $period */
        foreach ($periodList as $period) {
            $currentPeriodDataList = array_filter(
                    $dataList,
                    function (Data $data) use ($period) {
                        return $data->getPeriod() === $period;
                    }
            );
            $periodDataList[$period->getId()] = new PeriodData(
                    $period,
                    $this->getAllocationLevelDataList($currentPeriodDataList)
            );
        }

        return $periodDataList;
    }

    /**
     * Возвращает структуру данных отчетов, сгруппированную по уровням аллокации.
     *
     * @param Data[] $dataList
     *
     * @return AllocationLevelData[]
     *
     * @throws Exception
     */
    private function getAllocationLevelDataList(array $dataList): array {
        $allocationLevelList = array_unique(array_map(function (Data $data) {
            return $data->getAllocationLevel();
        }, $dataList), SORT_REGULAR);

        $allocationLevelDataList = array();
        /** @var Item $allocationLevel */
        foreach ($allocationLevelList as $allocationLevel) {
            $currentAllocationLevelDataList = array_filter(
                    $dataList,
                    function (Data $data) use ($allocationLevel) {
                        return $data->getAllocationLevel() === $allocationLevel;
                    }
            );
            $allocationLevelDataList[$allocationLevel ? $allocationLevel->getId() : null] = new AllocationLevelData(
                    $allocationLevel,
                    $this->getAffiliatedFrcDataList($currentAllocationLevelDataList)
            );
        }

        return $allocationLevelDataList;
    }

    /**
     * Возвращает структуру данных отчетов, сгруппированную по ЦФО.
     *
     * @param Data[] $dataList
     *
     * @return AffiliatedFrcData[]
     *
     * @throws Exception
     */
    private function getAffiliatedFrcDataList(array $dataList): array {
        $affiliatedFrcList = array_unique(array_map(function (Data $data) {
            return $data->getAffiliatedFrc();
        }, $dataList), SORT_REGULAR);

        $affiliatedFrcDataList = array();
        /** @var Frc|null $frc */
        foreach ($affiliatedFrcList as $frc) {
            $currentAffiliatedFrcDataList = array_filter(
                    $dataList,
                    function (Data $data) use ($frc) {
                        return $data->getAffiliatedFrc() === $frc;
                    }
            );
            $affiliatedFrcDataList[$frc ? $frc->getId() : null] = new AffiliatedFrcData(
                    $frc,
                    $this->getFrcDataList($currentAffiliatedFrcDataList)
            );
        }

        return $affiliatedFrcDataList;
    }

    /**
     * Возвращает структуру данных отчетов, сгруппированную по ЦФО.
     *
     * @param Data[] $dataList
     *
     * @return FrcData[]
     *
     * @throws Exception
     */
    private function getFrcDataList(array $dataList): array {
        $frcList = array_unique(array_map(function (Data $data) {
            return $data->getFrc();
        }, $dataList), SORT_REGULAR);

        $frcDataList = array();
        /** @var Frc $frc */
        foreach ($frcList as $frc) {
            $currentFrcDataList = array_filter(
                    $dataList,
                    function (Data $data) use ($frc) {
                        return $data->getFrc() === $frc;
                    }
            );
            $frcDataList[$frc->getId()] = new FrcData(
                    $frc,
                    $this->getActualDataList($currentFrcDataList)
            );
        }

        return $frcDataList;
    }

    /**
     * Возвращает структуру актуальных данных отчетов.
     *
     * @param Data[] $dataList
     *
     * @return Data[]
     *
     * @throws Exception
     */
    private function getActualDataList(array $dataList): array {
        usort($dataList, function (Data $firstData, Data $secondData) {
            return $firstData->getSnapshot() < $secondData->getSnapshot();
        });

        $actualDataList = array();
        foreach ($dataList as $data) {
            /** @var Index|Item $parameter */
            $parameter = $data->getIndex() ?: $data->getItem();
            if (!isset($parameter)) {
                continue;
            }

            $parameterId = $parameter->getId();
            if (isset($actualDataList[$parameterId])) {
                continue;
            }

            $actualDataList[$parameterId] = $data;
        }

        return $actualDataList;
    }

    /**
     * Возвращает список данных отчета. Поддерживает фильтр по типу данных и периоду.
     *
     * @param int $dataType - тип данных.
     * @param int $periodId - id периода.
     *
     * @return Data[]
     *
     * @throws Exception
     */
    public function getDataList(int $dataType = 0, int $periodId = 0): array {
        if (!isset($this->dataList[$dataType][$periodId])) {
            $this->dataList[$dataType][$periodId] = $this->prepareDataList($this->dataRepository->getIblockElementData(
                    DataMapper::FINCALC_DATA_FIELD_NAMES,
                    DataMapper::FINCALC_DATA_PROPERTY_NAMES,
                    $this->prepareDataFilter($dataType, $periodId)
            ));
        }

        return $this->dataList[$dataType][$periodId];
    }

    /**
     * Подготавливает список данных отчета и возвращает его.
     *
     * @param IblockElement[] $notPreparedDataList - неподготовленные данные отчетов из инфоблока.
     *
     * @return Data[]
     *
     * @throws Exception
     */
    private function prepareDataList(array $notPreparedDataList): array {
        $dataTypeList = $this->referenceService->getDataTypeList();
        $periodList = $this->referenceService->getPeriodList();
        $indexList = $this->referenceService->getIndexList();
        $itemList = $this->referenceService->getItemList();
        $frcList = $this->referenceService->getFlatFrcList();
        $currencyList = $this->referenceService->getCurrencyList();
        $originalCurrencyList = $this->referenceService->getOriginalCurrencyList();

        $dataList = array();
        foreach ($notPreparedDataList as $dataId => $notPreparedData) {
            $dataFrcId = $notPreparedData->getProperties()[DataMapper::FRC_FIELD_NAME];
            $dataFrc = $frcList[$dataFrcId];
            if (!$dataFrc) {
                continue;
            }

            $dataDataTypeId = $notPreparedData->getProperties()[DataMapper::DATA_TYPE_FIELD_NAME];
            $dataDataType = $dataTypeList[$dataDataTypeId];

            $dataPeriodId = $notPreparedData->getProperties()[DataMapper::PERIOD_FIELD_NAME];
            $dataPeriod = $periodList[$dataPeriodId];

            $dataOriginalCurrencyId = $notPreparedData->getProperties()[DataMapper::ORIGINAL_CURRENCY_FIELD_NAME];
            $dataOriginalCurrency = $originalCurrencyList[$dataOriginalCurrencyId];
            $dataOriginalCurrencyName = $dataOriginalCurrency ? $dataOriginalCurrency->getName() : '';
            $dataOriginalCurrency = $currencyList[$dataPeriodId . '|' . $dataOriginalCurrencyName];

            $dataIndexId = $notPreparedData->getProperties()[DataMapper::INDEX_FIELD_NAME];
            $dataIndex = $indexList[$dataIndexId];

            $dataItemId = $notPreparedData->getProperties()[DataMapper::ITEM_FIELD_NAME];
            $dataItem = $itemList[$dataItemId];

            $dataAllocationLevelId = $notPreparedData->getProperties()[DataMapper::ALLOCATION_LEVEL_FIELD_NAME];
            $dataAllocationLevel = $itemList[$dataAllocationLevelId];

            $dataAffiliatedFrcId = $notPreparedData->getProperties()[DataMapper::AFFILIATED_FRC_FIELD_NAME];
            $dataAffiliatedFrc = $frcList[$dataAffiliatedFrcId];

            $dataList[$dataId] = $this->dataMapper->toData(
                    $notPreparedData,
                    $dataDataType,
                    $dataPeriod,
                    $dataFrc,
                    $dataIndex,
                    $dataItem,
                    $dataAllocationLevel,
                    $dataAffiliatedFrc,
                    $dataOriginalCurrency
            );
        }

        return $dataList;
    }

    /**
     * Подготавливает массив с информацией о данном очтетов для изменений в БД.
     *
     * @param Data $data
     * @param bool $saveChange
     *
     * @return array
     */
    private function prepareData(Data $data, bool $saveChange): array {
        $dataIblockElement = $this->dataMapper->toIblockElement($data);
        $dataInfo = $this->dataMapper->toArray($dataIblockElement);
        $dataInfo[ChangeDataService::SAVE_CHANGE_KEY] = $saveChange;

        return $dataInfo;
    }

    /**
     * Генерирует id для временного данного отчетов.
     *
     * @param $dataType
     * @param $periodId
     *
     * @return int
     */
    private function generateRuntimeDataId($dataType, $periodId): int {
        $id = ReportService::DEFAULT_ID_FOR_RUNTIME_FINCALC_DATA_ELEMENTS;

        while(isset($this->dataList[$dataType][$periodId][$id])) {
            $id--;
        }

        return $id;
    }

    /**
     * Генерирует массив фильтра для получения данных отчетов из инфоблоков по типу данных и id периода.
     *
     * @param int $dataType - тип данных.
     * @param int $periodId - id периода.
     *
     * @return array
     */
    private function prepareDataFilter(int $dataType, int $periodId): array {
        $filter = array();
        if ($dataType > 0) {
            $filter['PROPERTY_' . DataMapper::DATA_TYPE_FIELD_NAME] = $dataType;
        }

        if ($periodId > 0) {
            $filter['PROPERTY_' . DataMapper::PERIOD_FIELD_NAME] = $periodId;
        }

        return $filter;
    }
}
