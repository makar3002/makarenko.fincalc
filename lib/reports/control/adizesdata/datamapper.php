<?php
namespace makarenko\fincalc\reports\control\data;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use makarenko\fincalc\reports\control\IblockPropertyMapper;
use makarenko\fincalc\reports\entity\currency\Currency;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\IblockElement;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\period\Period;
use makarenko\fincalc\reports\entity\data\Data;

/**
 * Class IndexMapper - маппер для данных отчета. Работает только в сторону преобразований данных из БД в сущности.
 *
 * @package makarenko\fincalc\reports\control\parameter
 */
class DataMapper {
    /** @var string - название поля "id" для ЦФО. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для данного отчета. */
    public const NAME_FIELD_NAME = 'NAME';
    /** @var string - название поля "Тип данного" для данного отчета. */
    public const DATA_TYPE_FIELD_NAME = 'DATA_TYPE';
    /** @var string - название поля "Период" для данного отчета. */
    public const PERIOD_FIELD_NAME = 'PERIOD';
    /** @var string - название поля "Индекс" для данного отчета. */
    public const INDEX_FIELD_NAME = 'INDEX_CODE_NAME';
    /** @var string - название поля "Итем" для данного отчета. */
    public const ITEM_FIELD_NAME = 'ITEM_NAME';
    /** @var string - название поля "ЦФО" для данного отчета. */
    public const FRC_FIELD_NAME = 'FRC';
    /** @var string - название поля "Изначальная валюта" для данного отчета. */
    public const ORIGINAL_CURRENCY_FIELD_NAME = 'ORIGINAL_CURRENCY';
    /** @var string - название поля "Сумма в изначальной валюте" для данного отчета. */
    public const SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME = 'SUM_IN_ORIG_CURRENCY';
    /** @var string - название поля "Сумма в USD" для данного отчета. */
    public const SUM_IN_USD_FIELD_NAME = 'SUM_IN_USD';
    /** @var string - название поля "Уровень аллокации" для данного отчета. */
    public const ALLOCATION_LEVEL_FIELD_NAME = 'ALLOCATION_LEVEL';
    /** @var string - название поля "Комментарии" для данного отчета. */
    public const COMMENTS_FIELD_NAME = 'COMMENTS';
    /** @var string - название поля "Снапшот" для данного отчета. */
    public const SNAPSHOT_FIELD_NAME = 'SNAPSHOT';
    /** @var string - название поля "Связанное ЦФО" для данного отчета. */
    public const AFFILIATED_FRC_FIELD_NAME = 'AFFILIATED_FRC';
    /** @var string - название поля "Код индекса\итема" для данного отчета. */
    public const INDEX_ITEM_CODE_FIELD_NAME = 'ITEM_CODE';
    private const SNAPSHOT_DEFAULT_FORMAT = 'Y-m-d H:i:s';

    /** @var string[] - список полей данных отчета. */
    public const FINCALC_DATA_FIELD_NAMES = array(
            DataMapper::ID_FIELD_NAME,
            DataMapper::NAME_FIELD_NAME
    );

    /** @var string[] - список свойств данных отчета. */
    public const FINCALC_DATA_PROPERTY_NAMES = array(
            DataMapper::DATA_TYPE_FIELD_NAME,
            DataMapper::PERIOD_FIELD_NAME,
            DataMapper::INDEX_FIELD_NAME,
            DataMapper::ITEM_FIELD_NAME,
            DataMapper::FRC_FIELD_NAME,
            DataMapper::ORIGINAL_CURRENCY_FIELD_NAME,
            DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME,
            DataMapper::SUM_IN_USD_FIELD_NAME,
            DataMapper::ALLOCATION_LEVEL_FIELD_NAME,
            DataMapper::COMMENTS_FIELD_NAME,
            DataMapper::SNAPSHOT_FIELD_NAME,
            DataMapper::AFFILIATED_FRC_FIELD_NAME,
            DataMapper::INDEX_ITEM_CODE_FIELD_NAME
    );

    /** @var IblockPropertyMapper - маппер свойств инфоблоков. */
    private $propertyMapper;

    /**
     * FrcMapper constructor.
     *
     * @param IblockPropertyMapper|null $propertyMapper
     */
    public function __construct(?IblockPropertyMapper $propertyMapper = null) {
        $this->propertyMapper = $propertyMapper ?: new IblockPropertyMapper();
    }

    /**
     * Преобразует массив данных из БД в объект данных отчета и возвращает его.
     *
     * @param IblockElement $notPreparedData
     * @param DataType $dataType
     * @param Period|null $period
     * @param Frc $frc
     * @param Index|null $index
     * @param Item|null $item
     * @param Item|null $allocationLevel
     * @param Frc|null $affiliatedFrc
     * @param Currency|null $currency
     *
     * @return Data
     *
     * @throws ObjectException
     */
    public function toData(
            IblockElement $notPreparedData,
            DataType $dataType,
            ?Period $period,
            Frc $frc,
            ?Index $index,
            ?Item $item,
            ?Item $allocationLevel,
            ?Frc $affiliatedFrc,
            ?Currency $currency
    ): Data {
        $dataFields = $notPreparedData->getFields();
        $dataProperties = $notPreparedData->getProperties();

        $sumInOriginalCurrency = $dataProperties[DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME];
        if ($sumInOriginalCurrency !== false && !is_null($currency)) {
            $sumInUsd = $sumInOriginalCurrency / $currency->budgetRate - $sumInOriginalCurrency / $currency->monthlyRate;
        } else {
            $sumInUsd = $dataProperties[DataMapper::SUM_IN_USD_FIELD_NAME];
        }

        $indexItemCode = $dataProperties[DataMapper::INDEX_ITEM_CODE_FIELD_NAME];
        return new Data(
                strval($dataFields[DataMapper::NAME_FIELD_NAME]),
                $dataType,
                $period,
                $index,
                $item,
                $frc,
                $currency,
                $sumInOriginalCurrency === false ? null : floatval($sumInOriginalCurrency),
                $sumInUsd === false ? null : floatval($sumInUsd),
                $allocationLevel,
                strval($dataProperties[DataMapper::COMMENTS_FIELD_NAME]) ?: null,
                new DateTime($dataProperties[DataMapper::SNAPSHOT_FIELD_NAME], DataMapper::SNAPSHOT_DEFAULT_FORMAT) ?: null,
                $affiliatedFrc,
                $indexItemCode === false ? null : intval($indexItemCode),
                intval($dataFields[DataMapper::ID_FIELD_NAME]) ?: null
        );
    }

    /**
     * Преобразует массив данных из БД в объект данных отчета и возвращает его.
     *
     * @param Data $data
     *
     * @return IblockElement
     */
    public function toIblockElement(Data $data): IblockElement {
        $index = $data->getIndex();
        $period = $data->getPeriod();
        $item = $data->getItem();
        $allocationLevel = $data->getAllocationLevel();
        $affiliatedFrc = $data->getAffiliatedFrc();
        $currency = $data->getOriginalCurrency();
        $originalCurrency = $currency->originalCurrency;

        return new IblockElement(
                array(
                        DataMapper::ID_FIELD_NAME => $data->getId(),
                        DataMapper::NAME_FIELD_NAME => $data->getName()
                ),
                array(
                        DataMapper::DATA_TYPE_FIELD_NAME => $data->getDataType()->getId(),
                        DataMapper::INDEX_FIELD_NAME => $index ? $index->getId() : null,
                        DataMapper::ITEM_FIELD_NAME => $item ? $item->getId() : null,
                        DataMapper::FRC_FIELD_NAME => $data->getFrc()->getId(),
                        DataMapper::PERIOD_FIELD_NAME => $period ? $period->getId() : null,
                        DataMapper::ORIGINAL_CURRENCY_FIELD_NAME => $originalCurrency ? $originalCurrency->getId() : null,
                        DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME => $data->getSumInOriginalCurrency(),
                        DataMapper::SUM_IN_USD_FIELD_NAME => $data->getSumInUsd(),
                        DataMapper::ALLOCATION_LEVEL_FIELD_NAME => $allocationLevel ? $allocationLevel->getId(): null,
                        DataMapper::COMMENTS_FIELD_NAME => $data->getComments(),
                        DataMapper::SNAPSHOT_FIELD_NAME => $data->getSnapshot(),
                        DataMapper::AFFILIATED_FRC_FIELD_NAME => $affiliatedFrc ? $affiliatedFrc->getId() : null,
                        DataMapper::INDEX_ITEM_CODE_FIELD_NAME => $data->getIndexItemCode()
                )
        );
    }

    /**
     * Преобразует объект данных отчета инфоблока в массив данных для БД и возвращает его.
     *
     * @param IblockElement $data
     *
     * @return array
     */
    public function toArray(IblockElement $data): array {
        return $this->propertyMapper->toArray($data);
    }
}
