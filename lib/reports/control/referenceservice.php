<?php
namespace makarenko\fincalc\reports\control;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Exception;
use makarenko\fincalc\reports\control\data\DataMapper;
use makarenko\fincalc\reports\control\currency\CurrencyMapper;
use makarenko\fincalc\reports\control\currency\OriginalCurrencyMapper;
use makarenko\fincalc\reports\control\datatype\DataTypeMapper;
use makarenko\fincalc\reports\control\expenserequest\ExpenseRequestMapper;
use makarenko\fincalc\reports\control\frc\FrcNotFoundException;
use makarenko\fincalc\reports\control\frc\FrcService;
use makarenko\fincalc\reports\control\parameter\IndexMapper;
use makarenko\fincalc\reports\control\parameter\ItemMapper;
use makarenko\fincalc\reports\control\period\PeriodMapper;
use makarenko\fincalc\reports\entity\currency\Currency;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\currency\OriginalCurrency;
use makarenko\fincalc\reports\entity\expenserequest\ExpenseRequest;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\ListPropertyValue;
use makarenko\fincalc\reports\entity\period\Period;
use makarenko\fincalc\reports\entity\IblockElement;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;


/**
 * Class ReferenceService - сервис для работы с данными отчетов.
 *
 * @package makarenko\fincalc\reports\control
 */
class ReferenceService {
    /** @var ListPropertyValueRepository - репозиторий типов данных. */
    private $dataTypeRepository;
    /** @var DataTypeMapper - маппер типов данных. */
    private $dataTypeMapper;
    /** @var ListPropertyValueRepository - репозиторий оригинальных валют для данных отчетов. */
    private $dataOriginalCurrencyRepository;
    /** @var ListPropertyValueRepository - репозиторий оригинальных валют. */
    private $originalCurrencyRepository;
    /** @var OriginalCurrencyMapper - маппер оригинальных валют. */
    private $originalCurrencyMapper;
    /** @var IblockElementRepository - репозиторий запросов на расходы. */
    private $expenseRequestRepository;
    /** @var ExpenseRequestMapper - маппер запросов на расходы. */
    private $expenseRequestMapper;
    /** @var IblockElementRepository - репозиторий индексов. */
    private $indexRepository;
    /** @var IndexMapper - маппер индексов. */
    private $indexMapper;
    /** @var IblockElementRepository - репозиторий итемов. */
    private $itemRepository;
    /** @var ItemMapper - маппер итемов. */
    private $itemMapper;
    /** @var IblockElementRepository - репозиторий периодов. */
    private $periodRepository;
    /** @var PeriodMapper - маппер периодов. */
    private $periodMapper;
    /** @var IblockElementRepository - репозиторий валют. */
    private $currencyRepository;
    /** @var CurrencyMapper - маппер валют. */
    private $currencyMapper;
    /** @var FrcService - сервис ЦФО. */
    private $frcService;
    /** @var DataType[] - список типов данных. */
    private $dataTypeList;
    /** @var OriginalCurrency[] - список оригинальных валют. */
    private $originalCurrencyList;
    /** @var OriginalCurrency[] - список оригинальных валют данных отчетов. */
    private $dataOriginalCurrencyList;
    /** @var ExpenseRequest[] - список запросов на расходы. */
    private $expenseRequestList;
    /** @var Index[] - список индексов. */
    private $indexList;
    /** @var Item[] - список итемов. */
    private $itemList;
    /** @var Period[] - список периодов. */
    private $periodList;
    /** @var Currency[] - список валют. */
    private $currencyList;
    /** @var Frc[] - список корневых ЦФО. */
    private $rootFrcTreeList;
    /** @var Frc[] - список всех ЦФО в виде одномерного массива. */
    private $flatFrcList;

    /**
     * ReferenceService constructor.
     *
     * @param ListPropertyValueRepository|null $dataTypeRepository
     * @param DataTypeMapper|null $dataTypeMapper
     * @param ListPropertyValueRepository|null $originalCurrencyRepository
     * @param ListPropertyValueRepository|null $dataOriginalCurrencyRepository
     * @param OriginalCurrencyMapper|null $originalCurrencyMapper
     * @param IblockElementRepository|null $expenseRequestRepository
     * @param ExpenseRequestMapper|null $expenseRequestMapper
     * @param IblockElementRepository|null $indexRepository
     * @param IndexMapper|null $indexMapper
     * @param IblockElementRepository|null $itemRepository
     * @param ItemMapper|null $itemMapper
     * @param IblockElementRepository|null $periodRepository
     * @param PeriodMapper|null $periodMapper
     * @param IblockElementRepository|null $currencyRepository
     * @param CurrencyMapper|null $currencyMapper
     * @param FrcService|null $frcService
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public function __construct(
            ?ListPropertyValueRepository $dataTypeRepository = null,
            ?DataTypeMapper $dataTypeMapper = null,
            ?ListPropertyValueRepository $originalCurrencyRepository = null,
            ?ListPropertyValueRepository $dataOriginalCurrencyRepository = null,
            ?OriginalCurrencyMapper $originalCurrencyMapper = null,
            ?IblockElementRepository $expenseRequestRepository = null,
            ?ExpenseRequestMapper $expenseRequestMapper = null,
            ?IblockElementRepository $indexRepository = null,
            ?IndexMapper $indexMapper = null,
            ?IblockElementRepository $itemRepository = null,
            ?ItemMapper $itemMapper = null,
            ?IblockElementRepository $periodRepository = null,
            ?PeriodMapper $periodMapper = null,
            ?IblockElementRepository $currencyRepository = null,
            ?CurrencyMapper $currencyMapper = null,
            ?FrcService $frcService = null
    ) {
        $dataIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID'));
        $this->dataTypeRepository = $dataTypeRepository ?? new ListPropertyValueRepository($dataIblockId, DataMapper::DATA_TYPE_FIELD_NAME);
        $this->dataTypeMapper = $dataTypeMapper ?? new DataTypeMapper();

        $this->dataOriginalCurrencyRepository = $originalCurrencyRepository ?? new ListPropertyValueRepository($dataIblockId, DataMapper::ORIGINAL_CURRENCY_FIELD_NAME);
        $this->originalCurrencyMapper = $originalCurrencyMapper ?? new OriginalCurrencyMapper();

        $expenseRequestIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_EXPENCE_REQUEST_IBLOCK_ID'));
        $this->expenseRequestRepository = $expenseRequestRepository ?? new IblockElementRepository($expenseRequestIblockId);
        $this->expenseRequestMapper = $expenseRequestMapper ?? new ExpenseRequestMapper();

        $indexIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_INDEX_CODE_NAME_IBLOCK_ID'));
        $this->indexRepository = $indexRepository ?? new IblockElementRepository($indexIblockId);
        $this->indexMapper = $indexMapper ?? new IndexMapper();

        $itemIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_ITEM_IBLOCK_ID'));
        $this->itemRepository = $itemRepository ?? new IblockElementRepository($itemIblockId);
        $this->itemMapper = $itemMapper ?? new ItemMapper();

        $periodIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_PERIOD_IBLOCK_ID'));
        $this->periodRepository = $periodRepository ?? new IblockElementRepository($periodIblockId);
        $this->periodMapper = $periodMapper ?? new PeriodMapper();

        $currencyIblockId = intval(Option::get('makarenko.fincalc', 'FINCALC_CURRENCY_LIST_IBLOCK_ID'));
        $this->currencyRepository = $currencyRepository ?? new IblockElementRepository($currencyIblockId);
        $this->originalCurrencyRepository = $originalCurrencyRepository ?? new ListPropertyValueRepository($currencyIblockId, CurrencyMapper::ORIGINAL_CURRENCY_FIELD_NAME);
        $this->currencyMapper = $currencyMapper ?? new CurrencyMapper();

        $this->frcService = $frcService ?? new FrcService();
    }

    /**
     * Возвращает тип данных по его id.
     *
     * @param int $id
     *
     * @return DataType
     *
     * @throws Exception
     */
    public function getDataTypeById(int $id): DataType {
        $dataType = $this->getDataTypeList()[$id];
        if (!$dataType) {
            throw new Exception('Data type not found.');
        }

        return $dataType;
    }

    /**
     * Возвращает список типов данных.
     *
     * @return DataType[]
     *
     * @throws Exception
     */
    public function getDataTypeList(): array {
        if (!isset($this->dataTypeList)) {
            $this->dataTypeList = $this->prepareDataTypeList($this->dataTypeRepository->getListPropertyValuesData());
        }

        return $this->dataTypeList;
    }

    /**
     * Подготавливает список типов данных и возвращает его.
     *
     * @param ListPropertyValue[] $notPreparedDataTypeList - неподготовленные данные типов данных из свойства инфоблока.
     *
     * @return DataType[]
     *
     * @throws Exception
     */
    private function prepareDataTypeList(array $notPreparedDataTypeList): array {
        $dataTypeList = array();
        foreach ($notPreparedDataTypeList as $dataTypeId => $notPreparedDataType) {
            $dataTypeList[$dataTypeId] = $this->dataTypeMapper->mapDataType($notPreparedDataType);
        }

        return $dataTypeList;
    }

    /**
     * Возвращает список запросов затрат для конкретного периода.
     *
     * @param Period $period - период, для которого нужно получить список запросов затрат.
     *
     * @return ExpenseRequest[]
     *
     * @throws Exception
     */
    public function getExpenseRequestList(Period $period): array {
        $periodId = $period->getId();
        if (!isset($this->expenseRequestList[$periodId])) {
            $this->expenseRequestList[$periodId] = $this->prepareExpenseRequestList($this->expenseRequestRepository->getIblockElementData(
                    ExpenseRequestMapper::EXPENSE_REQUEST_FIELD_NAMES,
                    ExpenseRequestMapper::EXPENSE_REQUEST_PROPERTY_NAMES,
                    array(
                            '>=' . IblockElementRepository::PROPERTY_PREFIX . ExpenseRequestMapper::DATE_OF_FINAL_APPROVAL_FIELD_NAME => $period->getStart()->format('Y-m-d'),
                            '<=' . IblockElementRepository::PROPERTY_PREFIX . ExpenseRequestMapper::DATE_OF_FINAL_APPROVAL_FIELD_NAME => $period->getEnd()->format('Y-m-d'),
                            '!' . IblockElementRepository::PROPERTY_PREFIX . ExpenseRequestMapper::ITEM_FIELD_NAME => false,
                            '!' . IblockElementRepository::PROPERTY_PREFIX . ExpenseRequestMapper::FRC_FIELD_NAME => false
                    )
            ), $period);
        }

        return $this->expenseRequestList[$periodId];
    }

    /**
     * Подготавливает список запросов затрат и возвращает его.
     *
     * @param IblockElement[] $notPreparedExpenseRequestList - неподготовленные данные запросов затрат из инфоблока.
     * @param Period $period - период, для которого получаются запросы затрат.
     *
     * @return ExpenseRequest[]
     *
     * @throws Exception
     */
    private function prepareExpenseRequestList(array $notPreparedExpenseRequestList, Period $period): array {
        $itemList = $this->getItemList();
        $frcList = $this->getFlatFrcList();
        $currencyList = $this->getCurrencyList();

        $expenseRequestList = array();
        foreach ($notPreparedExpenseRequestList as $expenseRequestId => $notPreparedExpenseRequest) {
            $propertyList = $notPreparedExpenseRequest->getProperties();
            preg_match(
                    '/(?P<SUM_VALUE>.+)\|(?P<CURRENCY_VALUE>\w+)/',
                    $propertyList[ExpenseRequestMapper::EXPENSE_AMOUNT_IN_ORIGINAL_CURRENCY_W_O_TAXES_USD_FIELD_NAME],
                    $sumInOriginalCurrencyInfo
            );

            $sumInOriginalCurrency = $sumInOriginalCurrencyInfo['SUM_VALUE'];
            $originalCurrency = $sumInOriginalCurrencyInfo['CURRENCY_VALUE'];
            if (is_null($sumInOriginalCurrency) || is_null($originalCurrency)) {
                $currency = null;
                $sumInOriginalCurrency = null;
            } else {
                $currency = $currencyList[$period->getId() . '|' . $originalCurrency];
            }

            $frc = $frcList[$propertyList[ExpenseRequestMapper::FRC_FIELD_NAME]];
            $item = $itemList[$propertyList[ExpenseRequestMapper::ITEM_FIELD_NAME]];
            if (!$frc || !$item) {
                continue;
            }

            $expenseRequestList[$expenseRequestId] = $this->expenseRequestMapper->mapExpenseRequest(
                    $notPreparedExpenseRequest,
                    $frc,
                    $item,
                    $period,
                    $sumInOriginalCurrency,
                    $currency
            );
        }

        return $expenseRequestList;
    }

    /**
     * Возвращает список индексов.
     *
     * @return Index[]
     *
     * @throws Exception
     */
    public function getIndexList(): array {
        if (!isset($this->indexList)) {
            $this->indexList = $this->prepareIndexList($this->indexRepository->getIblockElementData(
                    IndexMapper::INDEX_FIELD_NAMES,
                    IndexMapper::INDEX_PROPERTY_NAMES
            ));
        }

        return $this->indexList;
    }

    /**
     * Подготавливает список индексов.
     *
     * @param IblockElement[] $notPreparedIndexList - неподготовленные данные индексов из инфоблока.
     *
     * @return Index[]
     *
     * @throws Exception
     */
    private function prepareIndexList(array $notPreparedIndexList): array {
        $indexList = array();
        foreach ($notPreparedIndexList as $indexId => $notPreparedIndex) {
            $indexList[$indexId] = $this->indexMapper->mapIndex($notPreparedIndex);
        }

        return $indexList;
    }

    /**
     * Возвращает список итемов.
     *
     * @return Item[]
     *
     * @throws Exception
     */
    public function getItemList(): array {
        if (!isset($this->itemList)) {
            $this->itemList = $this->prepareItemList($this->itemRepository->getIblockElementData(
                    ItemMapper::ITEM_FIELD_NAMES,
                    ItemMapper::ITEM_PROPERTY_NAMES
            ));
        }

        return $this->itemList;
    }

    /**
     * Подготавливает список итемов.
     *
     * @param IblockElement[] $notPreparedItemList - неподготовленные итемов индексов из инфоблока.
     *
     * @return Item[]
     *
     * @throws Exception
     */
    private function prepareItemList(array $notPreparedItemList): array {
        $itemList = array();
        foreach ($notPreparedItemList as $itemId => $notPreparedItem) {
            $itemList[$itemId] = $this->itemMapper->mapItem($notPreparedItem);
        }

        return $itemList;
    }

    /**
     * Возвращает список уровней аллокации.
     *
     * @return Item[]
     *
     * @throws Exception
     */
    public function getAllocationLevelList(): array {
        if (!isset($this->allocationLevelList)) {
            $this->allocationLevelList = $this->prepareAllocationLevelList($this->getItemList());
        }

        return $this->allocationLevelList;
    }

    /**
     * Подготавливает список уровней аллокации.
     *
     * @param Item[] $itemList - список итемов.
     *
     * @return Item[]
     */
    private function prepareAllocationLevelList(array $itemList): array {
        $allocationLevelList = array();
        foreach ($itemList as $itemId => $item) {
            $allocationIndex = ItemMapper::ALLOCATION_LEVEL_MAP[$item->getCode()];
            if (!$allocationIndex) {
                continue;
            }

            $allocationLevelList[$allocationIndex] = $item;
        }

        ksort($allocationLevelList);

        return $allocationLevelList;
    }

    /**
     * Возвращает список периодов.
     *
     * @return Period[]
     *
     * @throws Exception
     */
    public function getPeriodList(): array {
        if (!isset($this->periodList)) {
            $this->periodList = $this->preparePeriodList($this->periodRepository->getIblockElementData(
                    PeriodMapper::PERIOD_FIELD_NAMES,
                    PeriodMapper::PERIOD_PROPERTY_NAMES
            ));
        }

        return $this->periodList;
    }

    /**
     * Подготавливает список периодов и возвращает его.
     *
     * @param IblockElement[] $notPreparedPeriodList - неподготовленные данные периодов из инфоблока.
     *
     * @return Period[]
     *
     * @throws Exception
     */
    private function preparePeriodList(array $notPreparedPeriodList): array {
        $periodList = array();
        foreach ($notPreparedPeriodList as $periodId => $notPreparedPeriod) {
            $periodList[$periodId] = $this->periodMapper->mapPeriod($notPreparedPeriod);
        }

        return $periodList;
    }

    /**
     * Возвращает список валют.
     *
     * @return Currency[]
     *
     * @throws Exception
     */
    public function getCurrencyList(): array {
        if (!isset($this->currencyList)) {
            $this->currencyList = $this->prepareCurrencyList($this->currencyRepository->getIblockElementData(
                    CurrencyMapper::CURRENCY_FIELD_NAMES,
                    CurrencyMapper::CURRENCY_PROPERTY_NAMES
            ));
        }

        return $this->currencyList;
    }

    /**
     * Подготавливает список валют и возвращает его.
     *
     * @param IblockElement[] $notPreparedCurrencyList - неподготовленные данные валют из инфоблока.
     *
     * @return Currency[]
     *
     * @throws Exception
     */
    private function prepareCurrencyList(array $notPreparedCurrencyList): array {
        $currencyList = array();
        foreach ($notPreparedCurrencyList as $currencyId => $notPreparedCurrency) {
            $propertyList = $notPreparedCurrency->getProperties();
            $period = $this->getPeriodList()[$propertyList[CurrencyMapper::PERIOD_FIELD_NAME]];
            $originalCurrency = $this->getOriginalCurrencyForCurrenciesList()[$propertyList[CurrencyMapper::ORIGINAL_CURRENCY_FIELD_NAME]];
            if (!$period || !$originalCurrency) {
                continue;
            }

            $currencyIndex = $period->getId() . '|' . $originalCurrency->getName();
            $currencyList[$currencyIndex] = $this->currencyMapper->mapCurrency($notPreparedCurrency, $period, $originalCurrency);
        }

        return $currencyList;
    }

    /**
     * Возвращает список оригинальных валют данных отчетов.
     *
     * @return OriginalCurrency[]
     *
     * @throws Exception
     */
    public function getOriginalCurrencyList(): array {
        if (!isset($this->dataOriginalCurrencyList)) {
            $this->dataOriginalCurrencyList = $this->prepareOriginalCurrencyList(
                    $this->dataOriginalCurrencyRepository->getListPropertyValuesData()
            );
        }

        return $this->dataOriginalCurrencyList;
    }



    /**
     * Возвращает список оригинальных валют.
     *
     * @return OriginalCurrency[]
     *
     * @throws Exception
     */
    private function getOriginalCurrencyForCurrenciesList(): array {
        if (!isset($this->originalCurrencyList)) {
            $this->originalCurrencyList = $this->prepareOriginalCurrencyList(
                    $this->originalCurrencyRepository->getListPropertyValuesData()
            );
        }

        return $this->originalCurrencyList;
    }

    /**
     * Подготавливает список оригинальных валют и возвращает его.
     *
     * @param ListPropertyValue[] $notPreparedOriginalCurrencyList - неподготовленные данные оригинальных валют из инфоблока.
     *
     * @return OriginalCurrency[]
     */
    private function prepareOriginalCurrencyList(array $notPreparedOriginalCurrencyList): array {
        $originalOriginalCurrencyList = array();
        foreach ($notPreparedOriginalCurrencyList as $originalCurrencyId => $notPreparedOriginalCurrency) {
            $fieldList = $notPreparedOriginalCurrency->getFields();
            $originalOriginalCurrencyList[$fieldList[OriginalCurrencyMapper::ID_FIELD_NAME]] =
                    $this->originalCurrencyMapper->mapOriginalCurrency($notPreparedOriginalCurrency);
        }

        return $originalOriginalCurrencyList;
    }

    /**
     * Возвращает структурированный список ЦФО.
     *
     * @return Frc[]
     *
     * @throws Exception
     */
    public function getRootFrcList(): array {
        if (!isset($this->rootFrcTreeList)) {
            $this->rootFrcTreeList = $this->frcService->getRootFrcTreeList();
        }

        return $this->rootFrcTreeList;
    }

    /**
     * Возвращает одномерный список со всеми ЦФО.
     *
     * @return Period[]
     *
     * @throws Exception
     */
    public function getFlatFrcList(): array {
        if (!isset($this->flatFrcList)) {
            $rootFrcTreeList = $this->frcService->getRootFrcTreeList();
            $this->flatFrcList = $this->initializeFlatFrcList($rootFrcTreeList);
        }

        return $this->flatFrcList;
    }

    /**
     * Формирует одномерный массив ЦФЩ, использую структурированный в деревья список FRC.
     *
     * @param Frc[] $frcTreeList
     *
     * @return Frc[]
     */
    private function initializeFlatFrcList(array $frcTreeList): array {
        foreach($frcTreeList as $frcId => $frc){
            $frcTreeList = $frcTreeList + $this->initializeFlatFrcList($frc->getChildGreenFrcList());
            $frcTreeList = $frcTreeList + $this->initializeFlatFrcList($frc->getChildRedFrcList());
        }

        return $frcTreeList;
    }

    /**
     * Возвращает ЦФО с переданным id.
     * @param int $frcId - id ЦФО.
     *
     * @return Frc
     *
     * @throws FrcNotFoundException - если ЦФО не найден.
     * @throws Exception
     */
    public function getFrcById(int $frcId): Frc {
        $frc = $this->getFlatFrcList()[$frcId];
        if (!isset($frc)) {
            throw new FrcNotFoundException('Frc with id ' . $frcId . ' not found.');
        }

        return $frc;
    }

    /**
     * Возвращает родителя ЦФО для дочернего ЦФО.
     * @param Frc $childFrc - ЦФО, для которого ищется родитель.
     *
     * @return Frc
     *
     * @throws FrcNotFoundException - если родитель не найден.
     * @throws Exception
     */
    public function getParentFrcByChildFrc(Frc $childFrc): Frc {
        $parentFrcId = intval($childFrc->getParentFrc());
        if ($parentFrcId <= 0) {
            throw new FrcNotFoundException('Frc with id ' . $parentFrcId . ' not found.');
        }

        return $this->getFlatFrcList()[$childFrc->getParentFrc()];
    }
}
