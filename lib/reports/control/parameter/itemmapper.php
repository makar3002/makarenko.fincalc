<?php
namespace makarenko\fincalc\reports\control\parameter;


use Exception;
use makarenko\fincalc\reports\entity\IblockElement;
use makarenko\fincalc\reports\entity\parameter\Item;


/**
 * Class IndexMapper - маппер для индексов. Работает только в сторону преобразований данных из БД в сущности.
 *
 * @package makarenko\fincalc\reports\control\parameter
 */
class ItemMapper {
    /** @var string - название поля "id" для итемов. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для итемов. */
    private const NAME_FIELD_NAME = 'NAME';
    /** @var string - название поля "Код" для итемов. */
    private const CODE_FIELD_NAME = 'KOD_STATI';
    /** @var string - название поля "Активность" для итемов. */
    private const ACTIVE_FIELD_NAME = 'ACTIVE';
    /** @var string - название поля "Тип" для итемов. */
    private const TYPE_FIELD_NAME = 'ITEM_TYPE';
    /** @var string - название поля "Типы отчетов" для итемов. */
    private const REPORT_TYPE_FIELD_NAME = 'REPORT_TYPE';
    /** @var string - название поля "ЦФО" для итемов. */
    private const FRC_FIELD_NAME = 'FRC';

    /** @var int - неопределенный уровень аллокации. */
    public const ALLOCATION_LEVEL_UNDEFINED = 0;
    /** @var int - уровень аллокации affect. */
    public const ALLOCATION_LEVEL_AFFECT = 1;
    /** @var int - уровень аллокации complain. */
    public const ALLOCATION_LEVEL_COMPLAIN = 2;
    /** @var int - уровень аллокации forget. */
    public const ALLOCATION_LEVEL_FORGET = 3;
    /** @var int - уровень аллокации own expenses. */
    public const ALLOCATION_LEVEL_OWN_EXPENSES = 4;
    /** @var int - уровень аллокации amount USD. */
    public const ALLOCATION_LEVEL_AMOUNT_USD = 5;

    /** @var int - код итема уровня аллокации Affect. */
    private const ALLOCATION_LEVEL_AFFECT_CODE = 90110;
    /** @var int - код итема уровня аллокации Complain. */
    private const ALLOCATION_LEVEL_COMPLAIN_CODE = 90105;
    /** @var int - код итема уровня аллокации Forget. */
    private const ALLOCATION_LEVEL_FORGET_CODE = 90101;
    /** @var int - код итема уровня аллокации Own expenses. */
    private const ALLOCATION_LEVEL_OWN_EXPENSES_CODE = 90100;
    /** @var int - код итема уровня аллокации Amount USD. */
    private const ALLOCATION_LEVEL_AMOUNT_USD_CODE = 91000;

    /** @var array - маппинг уровней аллокации. */
    public const ALLOCATION_LEVEL_MAP = array(
            ItemMapper::ALLOCATION_LEVEL_FORGET_CODE => ItemMapper::ALLOCATION_LEVEL_FORGET,
            ItemMapper::ALLOCATION_LEVEL_COMPLAIN_CODE => ItemMapper::ALLOCATION_LEVEL_COMPLAIN,
            ItemMapper::ALLOCATION_LEVEL_AFFECT_CODE => ItemMapper::ALLOCATION_LEVEL_AFFECT,
            ItemMapper::ALLOCATION_LEVEL_OWN_EXPENSES_CODE => ItemMapper::ALLOCATION_LEVEL_OWN_EXPENSES,
            ItemMapper::ALLOCATION_LEVEL_AMOUNT_USD_CODE => ItemMapper::ALLOCATION_LEVEL_AMOUNT_USD
    );

    /** @var string[] - список полей индексов. */
    public const ITEM_FIELD_NAMES = array(
            ItemMapper::ID_FIELD_NAME,
            ItemMapper::NAME_FIELD_NAME
    );

    /** @var string[] - список свойств индексов. */
    public const ITEM_PROPERTY_NAMES = array(
            ItemMapper::CODE_FIELD_NAME,
            ItemMapper::ACTIVE_FIELD_NAME,
            ItemMapper::FRC_FIELD_NAME,
            ItemMapper::TYPE_FIELD_NAME,
            ItemMapper::REPORT_TYPE_FIELD_NAME
    );

    /**
     * Преобразует массив данных из БД в объект итема и возвращает его.
     *
     * @param IblockElement $notPreparedItem - объект неподготовленного итема.
     *
     * @return Item
     *
     * @throws Exception
     */
    public function mapItem(IblockElement $notPreparedItem): Item {
        $itemFields = $notPreparedItem->getFields();
        $itemProperties = $notPreparedItem->getProperties();

        $itemCode = intval($itemProperties[ItemMapper::CODE_FIELD_NAME]);
        $allocationIndex = ItemMapper::ALLOCATION_LEVEL_MAP[$itemCode] ?? ItemMapper::ALLOCATION_LEVEL_UNDEFINED;
        return new Item(
                intval($itemFields[ItemMapper::ID_FIELD_NAME]),
                strval($itemFields[ItemMapper::NAME_FIELD_NAME]),
                $itemCode,
                $itemProperties[ItemMapper::FRC_FIELD_NAME],
                intval($itemProperties[ItemMapper::ACTIVE_FIELD_NAME]),
                $itemProperties[ItemMapper::TYPE_FIELD_NAME],
                $itemProperties[ItemMapper::REPORT_TYPE_FIELD_NAME],
                $allocationIndex
        );
    }
}
