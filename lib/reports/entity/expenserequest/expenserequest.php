<?php
namespace makarenko\fincalc\reports\entity\expenserequest;

use makarenko\fincalc\reports\entity\currency\Currency;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\period\Period;

class ExpenseRequest {
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /**@var Frc */
    public $frc;
    /** @var Item */
    public $item;
    /** @var Period */
    public $period;
    /** @var float */
    public $amountWithoutTaxesUsd;
    /** @var float */
    public $amountInOriginalCurrencyWithoutTaxes;
    /** @var Currency */
    public $currency;

    /**
     * ExpenseRequest constructor.
     *
     * @param int $id
     * @param string $name
     * @param Frc $frc
     * @param Item $item
     * @param Period $period
     * @param float $amountWithoutTaxesUsd
     * @param float|null $amountInOriginalCurrencyWithoutTaxes
     * @param Currency|null $currency
     */
    public function __construct(
            int $id,
            string $name,
            Frc $frc,
            Item $item,
            Period $period,
            float $amountWithoutTaxesUsd,
            ?float $amountInOriginalCurrencyWithoutTaxes,
            ?Currency $currency
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->frc = $frc;
        $this->item = $item;
        $this->period = $period;
        $this->amountWithoutTaxesUsd = $amountWithoutTaxesUsd;
        $this->amountInOriginalCurrencyWithoutTaxes = $amountInOriginalCurrencyWithoutTaxes;
        $this->currency = $currency;
    }
}