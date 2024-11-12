<?php
namespace makarenko\fincalc\reports\entity\allocation;

/**
 * Class AllocationData - сущность параметров аллокации.
 * @package makarenko\fincalc\reports\entity\allocation
 */
class AllocationData {
    /** @var float - значение показателя Total. */
    private $total;
    /** @var float - значение показателя Take. */
    private $take;
    /** @var float - значение показателя Tax. */
    private $tax;

    /**
     * AllocationData constructor.
     *
     * @param float $total - значение показателя Total.
     * @param float|null $take - значение показателя Take.
     * @param float|null $tax - значение показателя Tax.
     */
    public function __construct(
            float $total,
            ?float $take,
            ?float $tax
    ) {
        $this->total = $total;
        $this->take = $take ?: 0.0;
        $this->tax = $tax ?: 0.0;
    }

    /**
     * Возвращает значение показателя Total (%).
     *
     * @return float
     */
    public function getTotal(): float {
        return $this->total;
    }

    /**
     * Возвращает значение показателя Take (%).
     *
     * @return float
     */
    public function getTake(): float {
        return $this->take;
    }

    /**
     * Возвращает значение показателя Tax (%).
     *
     * @return float
     */
    public function getTax(): float {
        return $this->tax;
    }

    /**
     * Создает копию объекта сущности с измененным значением Total (%).
     * @param float $total
     * @return AllocationData
     */
    public function withTotal(float $total): AllocationData {
        $newAllocationParameter = clone $this;
        $newAllocationParameter->total = $total;

        return $newAllocationParameter;
    }
}
