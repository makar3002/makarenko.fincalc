<?php
namespace makarenko\fincalc\reports\control;


use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;


class DataHistoryTable extends DataManager {
    public static function getTableName(): string {
        return 'fincalc_data_history';
    }

    public static function getMap(): array {
        return array(
                (new IntegerField('ID'))
                        ->configureAutocomplete(true)
                        ->configurePrimary(true),
                (new StringField('NAME'))
                        ->configureTitle(Loc::getMessage('FINCALC_DATA_HISTORY_NAME')),
                (new IntegerField('DATA_TYPE'))
                        ->configureTitle(Loc::getMessage('FINCALC_DATA_HISTORY_DATA_TYPE'))
                        ->configureRequired(true),
                (new IntegerField('PERIOD_ID')),
                (new IntegerField('FRC_ID'))
                        ->configureRequired(true),
                (new IntegerField('INDEX_ID')),
                (new IntegerField('ITEM_ID')),
                (new IntegerField('ORIGINAL_CURRENCY')),
                (new FloatField('SUM_IN_ORIGINAL_CURRENCY'))
                        ->configureTitle(Loc::getMessage('FINCALC_DATA_HISTORY_SUM_IN_ORIGINAL_CURRENCY')),
                (new FloatField('SUM_IN_USD'))
                        ->configureTitle(Loc::getMessage('FINCALC_DATA_HISTORY_SUM_IN_USD')),
                (new IntegerField('ALLOCATION_LEVEL_ID')),
                (new DatetimeField('SNAPSHOT'))
                        ->configureTitle(Loc::getMessage('FINCALC_DATA_HISTORY_SNAPSHOT')),
                (new IntegerField('AFFILIATED_FRC_ID')),
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