<?php
namespace makarenko\fincalc\reports\control;


use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Query\Join;


class ChangeDataTable extends DataManager {
    public static function getTableName(): string {
        return 'fincalc_change_data';
    }

    public static function getMap(): array {
        return array(
            (new IntegerField('ID'))
                ->configureAutocomplete()
                ->configurePrimary(),
            (new IntegerField('DATA_TYPE'))
                ->configureTitle(Loc::getMessage('FINCALC_DATA_HISTORY_DATA_TYPE'))
                ->configureRequired(),
            (new IntegerField('PERIOD_ID'))->configureNullable(),
            (new IntegerField('FRC_ID'))
                ->configureRequired(),
            (new IntegerField('INDEX_ID'))->configureNullable(),
            (new IntegerField('ITEM_ID'))->configureNullable(),
            (new IntegerField('ALLOCATION_LEVEL_ID'))->configureNullable(),
            (new IntegerField('AFFILIATED_FRC_ID'))->configureNullable(),
            (new DatetimeField('SNAPSHOT'))
                ->configureTitle(Loc::getMessage('FINCALC_DATA_HISTORY_SNAPSHOT'))
                ->configureNullable(),
            (new StringField('STATUS'))
                ->configureRequired(),
            (new StringField('CALCULATOR_ID'))
                ->configureRequired(),
            (new StringField('ERROR_MESSAGE'))->configureNullable(),
            (new ReferenceField(
                'PERIOD_ID',
                ElementTable::class,
                Join::on('this.PERIOD_ID', 'ref.ID')
            )),
            (new ReferenceField(
                'FRC_ID',
                ElementTable::class,
                Join::on('this.FRC_ID', 'ref.ID')
            )),
            (new ReferenceField(
                'INDEX_ID',
                ElementTable::class,
                Join::on('this.INDEX_ID', 'ref.ID')
            )),
            (new ReferenceField(
                'ITEM_ID',
                ElementTable::class,
                Join::on('this.ITEM_ID', 'ref.ID')
            )),
            (new ReferenceField(
                'ALLOCATION_LEVEL_ID',
                ElementTable::class,
                Join::on('this.ALLOCATION_LEVEL_ID', 'ref.ID')
            )),
            (new ReferenceField(
                'AFFILIATED_FRC_ID',
                ElementTable::class,
                Join::on('this.AFFILIATED_FRC_ID', 'ref.ID')
            ))
        );
    }
}