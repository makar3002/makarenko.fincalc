<?php
namespace makarenko\fincalc\reports\entity\currency;


use makarenko\fincalc\reports\entity\HierarchicalDataValue;
use makarenko\fincalc\reports\entity\period\Period;


class Currency extends HierarchicalDataValue {
    /** @var OriginalCurrency */
    public $originalCurrency;
    /** @var float */
    public $budgetRate;
    /** @var float */
    public $monthlyRate;
    /** @var Period */
    public $period;

    /**
     * Currency constructor.
     *
     * @param int $id
     * @param string $name
     * @param OriginalCurrency $originalCurrency
     * @param float $budgetRate
     * @param float $monthlyRate
     * @param Period $period
     */
    public function __construct(
            int $id,
            string $name,
            OriginalCurrency $originalCurrency,
            float $budgetRate,
            float $monthlyRate,
            Period $period
    ) {
        parent::__construct($id, $name);
        $this->originalCurrency = $originalCurrency;
        $this->budgetRate = $budgetRate;
        $this->monthlyRate = $monthlyRate;
        $this->period = $period;
    }
}