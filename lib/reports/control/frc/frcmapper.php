<?php
namespace makarenko\fincalc\reports\control\frc;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use CIBlockProperty;
use makarenko\fincalc\reports\control\IblockPropertyMapper;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\IblockElement;


/**
 * Class IndexMapper - маппер для ЦФО. Работает только в сторону преобразований данных из БД в сущности.
 *
 * @package makarenko\fincalc\reports\control\parameter
 */
class FrcMapper {
    /** @var string - название поля "id" для ЦФО. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для ЦФО. */
    private const NAME_FIELD_NAME = 'NAME';
    /** @var string - название поля "Цвет" для ЦФО. */
    public const COLOR_FIELD_NAME = 'COLOR';
    /** @var string - название поля "Уровень аллокации" для ЦФО. */
    private const LEVEL_FIELD_NAME = 'LEVEL';
    /** @var string - название поля "Родительский ЦФО" для ЦФО. */
    public const PARENT_FRC_FIELD_NAME = 'PARENT_FRC';

    /** @var int - неопределенный цвет ЦФО. */
    public const FRC_UNDEFINED_COLOR = 0;
    /** @var int - зеленый цвет ЦФО. */
    public const FRC_GREEN_COLOR = 1;
    /** @var int - красный цвет ЦФО. */
    public const FRC_RED_COLOR = 2;
    /** @var array - маппинг цветов ЦФО. */
    private const FRC_COLOR_MAP = array(
            'green' => FrcMapper::FRC_GREEN_COLOR,
            'red' => FrcMapper::FRC_RED_COLOR
    );

    /** @var string[] - список полей ЦФО. */
    public const FRC_FIELD_NAMES = array(
            FrcMapper::ID_FIELD_NAME,
            FrcMapper::NAME_FIELD_NAME
    );

    /** @var string[] - список свойств ЦФО. */
    public const FRC_PROPERTY_NAMES = array(
            FrcMapper::COLOR_FIELD_NAME,
            FrcMapper::LEVEL_FIELD_NAME,
            FrcMapper::PARENT_FRC_FIELD_NAME
    );

    /** @var IblockPropertyMapper - маппер свойств инфоблоков. */
    private $propertyMapper;
    /** @var array - маппинг ID цветов и их значений. */
    private $colorPropertyMap;

    /**
     * FrcMapper constructor.
     *
     * @param IblockPropertyMapper|null $propertyMapper
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public function __construct(?IblockPropertyMapper $propertyMapper = null) {
        $this->propertyMapper = $propertyMapper ?: new IblockPropertyMapper();
        $this->colorPropertyMap = $this->getColorPropertyMap();
    }

    /**
     * Преобразует массив данных из БД в объект ЦФО и возвращает его.
     *
     * @param IblockElement $notPreparedIndex - объект неподготовленного ЦФО.
     * @param array $childGreenFrcList
     * @param array $childRedFrcList
     *
     * @return Frc
     */
    public function mapFrc(
            IblockElement $notPreparedIndex,
            array $childGreenFrcList = array(),
            array $childRedFrcList = array()
    ): Frc {
        $indexFields = $notPreparedIndex->getFields();
        $indexProperties = $notPreparedIndex->getProperties();

        $colorPropertyValue = $this->colorPropertyMap[$indexProperties[FrcMapper::COLOR_FIELD_NAME]];

        return new Frc(
                intval($indexFields[FrcMapper::ID_FIELD_NAME]),
                strval($indexFields[FrcMapper::NAME_FIELD_NAME]),
                FrcMapper::FRC_COLOR_MAP[$colorPropertyValue] ?: FrcMapper::FRC_UNDEFINED_COLOR,
                $indexProperties[FrcMapper::LEVEL_FIELD_NAME] ?: null,
                intval($indexProperties[FrcMapper::PARENT_FRC_FIELD_NAME]) ?: null,
                $childGreenFrcList,
                $childRedFrcList
        );
    }

    /**
     * Возвращает маппинг ID цветов и их значений.
     *
     * @return array
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public function getColorPropertyMap(): array {
        if (!isset($this->colorPropertyMap)) {
            $colorPropertyResult = CIBlockProperty::GetPropertyEnum(
                    FrcMapper::COLOR_FIELD_NAME,
                    array(),
                    array('IBLOCK_ID' => Option::get('makarenko.fincalc', 'FINCALC_FRC_IBLOCK_ID'))
            );

            $colorPropertyMap = array();
            while ($colorProperty = $colorPropertyResult->Fetch()) {
                $colorPropertyMap[$colorProperty['ID']] = $colorProperty['VALUE'];
            }

            $this->colorPropertyMap = $colorPropertyMap;
        }

        return $this->colorPropertyMap;
    }
}
