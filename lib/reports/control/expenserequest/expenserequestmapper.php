<?php
namespace makarenko\fincalc\reports\control\expenserequest;


use makarenko\fincalc\reports\entity\currency\Currency;
use makarenko\fincalc\reports\entity\expenserequest\ExpenseRequest;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\IblockElement;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\period\Period;


/**
 * Class ExpenseRequestMapper - маппер для ЦФО. Работает только в сторону преобразований данных из БД в сущности.
 *
 * @package makarenko\fincalc\reports\control\parameter
 */
class ExpenseRequestMapper {
    /** @var string - название поля "id" для запроса затрат. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для запроса затрат. */
    public const NAME_FIELD_NAME = 'NAME';
    /** @var string - название свойства "ЦФО" для запроса затрат. */
    public const FRC_FIELD_NAME = 'FINANCIAL_RESPONSIBILITY_CENTER_FRC_CUSTOM';
    /** @var string - название свойства "Итем" для запроса затрат. */
    public const ITEM_FIELD_NAME = 'ITEM_CUSTOM';
    /** @var string - название свойства "Сумма затрат в долларах" для запроса затрат. */
    public const EXPENSE_AMOUNT_W_O_TAXES_USD_FIELD_NAME = 'EXPENSE_AMOUNT_W_O_TAXES_USD';
    /** @var string - название свойства "Дата финального согласования" для запроса затрат. */
    public const DATE_OF_FINAL_APPROVAL_FIELD_NAME = 'DATE_OF_FINAL_APPROVAL';
    /** @var string - название свойства "Сумма затрат в оригинальной валюте" для запроса затрат. */
    public const EXPENSE_AMOUNT_IN_ORIGINAL_CURRENCY_W_O_TAXES_USD_FIELD_NAME = 'EXPENSE_AMOUNT_IN_ORIGINAL_CURRENCY_W_O_TAXES';

    /** @var string[] - список полей запросов затрат. */
    public const EXPENSE_REQUEST_FIELD_NAMES = array(
            ExpenseRequestMapper::ID_FIELD_NAME,
            ExpenseRequestMapper::NAME_FIELD_NAME
    );

    /** @var string[] - список свойств запросов затрат. */
    public const EXPENSE_REQUEST_PROPERTY_NAMES = array(
            ExpenseRequestMapper::FRC_FIELD_NAME,
            ExpenseRequestMapper::ITEM_FIELD_NAME,
            ExpenseRequestMapper::EXPENSE_AMOUNT_W_O_TAXES_USD_FIELD_NAME,
            ExpenseRequestMapper::DATE_OF_FINAL_APPROVAL_FIELD_NAME,
            ExpenseRequestMapper::EXPENSE_AMOUNT_IN_ORIGINAL_CURRENCY_W_O_TAXES_USD_FIELD_NAME
    );

    /**
     * Преобразует массив данных из БД в объект затрат и возвращает его.
     *
     * @param IblockElement $notPreparedExpenseRequest - объект неподготовленных запросов затрат.
     * @param Frc $frc - ЦФО.
     * @param Item $item - итем.
     * @param Period $period - период.
     * @param float|null $amountInOriginalCurrencyWithoutTaxes
     * @param Currency|null $currency
     *
     * @return ExpenseRequest
     */
    public function mapExpenseRequest(
            IblockElement $notPreparedExpenseRequest,
            Frc $frc,
            Item $item,
            Period $period,
            ?float $amountInOriginalCurrencyWithoutTaxes,
            ?Currency $currency
    ): ExpenseRequest {
        $indexFields = $notPreparedExpenseRequest->getFields();
        $propertyFields = $notPreparedExpenseRequest->getProperties();

        return new ExpenseRequest(
                intval($indexFields[ExpenseRequestMapper::ID_FIELD_NAME]),
                strval($indexFields[ExpenseRequestMapper::NAME_FIELD_NAME]),
                $frc,
                $item,
                $period,
                floatval($propertyFields[ExpenseRequestMapper::EXPENSE_AMOUNT_W_O_TAXES_USD_FIELD_NAME]),
                $amountInOriginalCurrencyWithoutTaxes,
                $currency
        );
    }
}
