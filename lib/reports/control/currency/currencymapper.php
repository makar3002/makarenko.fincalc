<?php
namespace makarenko\fincalc\reports\control\currency;

use makarenko\fincalc\reports\entity\currency\Currency;
use makarenko\fincalc\reports\entity\currency\OriginalCurrency;
use makarenko\fincalc\reports\entity\period\Period;
use makarenko\fincalc\reports\entity\IblockElement;

class CurrencyMapper {
    /** @var string - название поля "id" для валют. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для валют. */
    public const NAME_FIELD_NAME = 'NAME';
    /** @var string - название поля "Оригинальная валюта" для валют. */
    public const ORIGINAL_CURRENCY_FIELD_NAME = 'ORIGINAL_CURRENCY';
    /** @var string - название поля "Оборот бюджета" для валют. */
    public const BUDGET_RATE_FIELD_NAME = 'BUDGET_RATE';
    /** @var string - название поля "Месячный оборот" для валют. */
    public const MONTHLY_RATE_FIELD_NAME = 'MONTHLY_RATE';
    /** @var string - название поля "Период" для валют. */
    public const PERIOD_FIELD_NAME = 'PERIOD';

    /** @var string[] - список полей индексов. */
    public const CURRENCY_FIELD_NAMES = array(
            CurrencyMapper::ID_FIELD_NAME,
            CurrencyMapper::NAME_FIELD_NAME
    );

    /** @var string[] - список свойств индексов. */
    public const CURRENCY_PROPERTY_NAMES = array(
            CurrencyMapper::BUDGET_RATE_FIELD_NAME,
            CurrencyMapper::MONTHLY_RATE_FIELD_NAME,
            CurrencyMapper::PERIOD_FIELD_NAME,
            CurrencyMapper::ORIGINAL_CURRENCY_FIELD_NAME
    );

    /**
     * Преобразует массив данных из БД в объект валюты и возвращает его.
     *
     * @param IblockElement $notPreparedCurrency - объект неподготовленной валюты.
     * @param Period $period - период, к которому относится валюта.
     * @param OriginalCurrency $originalCurrency - оригинальная валюта.
     *
     * @return Currency
     */
    public function mapCurrency(
            IblockElement $notPreparedCurrency,
            Period $period,
            OriginalCurrency $originalCurrency
    ): Currency {
        $periodFields = $notPreparedCurrency->getFields();
        $periodProperties = $notPreparedCurrency->getProperties();

        return new Currency(
                intval($periodFields[CurrencyMapper::ID_FIELD_NAME]),
                strval($periodFields[CurrencyMapper::NAME_FIELD_NAME]),
                $originalCurrency,
                floatval($periodProperties[CurrencyMapper::BUDGET_RATE_FIELD_NAME]),
                floatval($periodProperties[CurrencyMapper::MONTHLY_RATE_FIELD_NAME]),
                $period
        );
    }
}