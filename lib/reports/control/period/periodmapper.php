<?php
namespace makarenko\fincalc\reports\control\period;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use CIBlockProperty;
use Exception;
use makarenko\fincalc\reports\entity\period\Period;
use makarenko\fincalc\reports\control\IblockPropertyMapper;
use makarenko\fincalc\reports\entity\IblockElement;

class PeriodMapper {
    /** @var string - название поля "id" для периодов. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для периодов. */
    private const NAME_FIELD_NAME = 'NAME';
    /** @var string - название поля "Тип" для периодов. */
    private const TYPE_FIELD_NAME = 'PERIOD_TYPE';
    /** @var string - название поля "Открытый?" для периодов. */
    private const IS_OPEN_FIELD_NAME = 'OPEN_CLOSED';
    /** @var string - название поля "Начало" для периодов. */
    private const START_FIELD_NAME = 'PERIOD_START';
    /** @var string - название поля "Конец" для периодов. */
    private const END_FIELD_NAME = 'PERIOD_END';
    /** @var string - название поля "AliSys" для периодов. */
    private const ALISYS_FIELD_NAME = 'ALIBONUS';
    /** @var string - название поля "AliWeb" для периодов. */
    private const ALIWEB_FIELD_NAME = 'ANTISPAM';

    /** @var string - значение по-умолчанию для списочного поля "Открытый?", означающее "Истина". */
    private const IS_OPEN_DEFAULT_OPEN_VALUE = 'Open';

    /** @var string[] - список полей индексов. */
    public const PERIOD_FIELD_NAMES = array(
            PeriodMapper::ID_FIELD_NAME,
            PeriodMapper::NAME_FIELD_NAME
    );

    /** @var string[] - список свойств индексов. */
    public const PERIOD_PROPERTY_NAMES = array(
            PeriodMapper::TYPE_FIELD_NAME,
            PeriodMapper::IS_OPEN_FIELD_NAME,
            PeriodMapper::START_FIELD_NAME,
            PeriodMapper::END_FIELD_NAME,
            PeriodMapper::ALISYS_FIELD_NAME,
            PeriodMapper::ALIWEB_FIELD_NAME
    );

    /** @var IblockPropertyMapper */
    private $propertyMapper;
    /** @var int - значение открытого периода. */
    private $isOpenPropertyOpenValue;

    /**
     * PeriodMapper constructor.
     *
     * @param IblockPropertyMapper|null $propertyMapper
     *
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function __construct(?IblockPropertyMapper $propertyMapper = null) {
        $this->propertyMapper = $propertyMapper ?: new IblockPropertyMapper();
        $this->isOpenPropertyOpenValue = $this->getIsOpenPropertyOpenValueId();
    }


    /**
     * Преобразует массив данных из БД в объект периода и возвращает его.
     *
     * @param IblockElement $notPreparedPeriod - объект неподготовленного периода.
     *
     * @return Period
     *
     * @throws Exception
     */
    public function mapPeriod(IblockElement $notPreparedPeriod): Period {
        $periodFields = $notPreparedPeriod->getFields();
        $periodProperties = $notPreparedPeriod->getProperties();

        return new Period(
                intval($periodFields[PeriodMapper::ID_FIELD_NAME]),
                strval($periodFields[PeriodMapper::NAME_FIELD_NAME]),
                intval($periodProperties[PeriodMapper::TYPE_FIELD_NAME]),
                $periodProperties[PeriodMapper::IS_OPEN_FIELD_NAME] == $this->isOpenPropertyOpenValue,
                new DateTime($periodProperties[PeriodMapper::START_FIELD_NAME], 'Y-m-d'),
                new DateTime($periodProperties[PeriodMapper::END_FIELD_NAME], 'Y-m-d'),
                floatval($periodProperties[PeriodMapper::ALISYS_FIELD_NAME]),
                floatval($periodProperties[PeriodMapper::ALIWEB_FIELD_NAME])
        );
    }

    /**
     * Возвращает значение открытого периода.
     *
     * @return int
     *
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    private function getIsOpenPropertyOpenValueId(): int {
        $isOpenPropertyResult = CIBlockProperty::GetPropertyEnum(
                PeriodMapper::IS_OPEN_FIELD_NAME,
                array(),
                array(
                        'IBLOCK_ID' => Option::get('makarenko.fincalc', 'FINCALC_PERIOD_IBLOCK_ID'),
                        'VALUE' => PeriodMapper::IS_OPEN_DEFAULT_OPEN_VALUE
                )
        );

        return intval($isOpenPropertyResult->Fetch()['ID']);
    }
}