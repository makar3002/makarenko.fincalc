<?php
namespace makarenko\fincalc\reports\control\currency;


use Exception;
use makarenko\fincalc\reports\entity\currency\OriginalCurrency;
use makarenko\fincalc\reports\entity\ListPropertyValue;


class OriginalCurrencyMapper {
    /** @var string - название поля "id" для оригинальных валют. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для оригинальных валют. */
    public const NAME_FIELD_NAME = 'VALUE';

    /**
     * Преобразует массив данных из БД в объект оригинальной валюты и возвращает его.
     *
     * @param ListPropertyValue $notPreparedOriginalCurrency - объект неподготовленной оригинальной валюты.
     *
     * @return OriginalCurrency
     */
    public function mapOriginalCurrency(ListPropertyValue $notPreparedOriginalCurrency): OriginalCurrency {
        $originalCurrencyFields = $notPreparedOriginalCurrency->getFields();

        return new OriginalCurrency(
                intval($originalCurrencyFields[OriginalCurrencyMapper::ID_FIELD_NAME]),
                strval($originalCurrencyFields[OriginalCurrencyMapper::NAME_FIELD_NAME])
        );
    }
}