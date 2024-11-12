<?php
namespace makarenko\fincalc\reports\entity\data;


use makarenko\fincalc\reports\entity\period\Period;


class PeriodData {
    /** @var Period|null - период. */
    private $period;
    /** @var AllocationLevelData[] - список данных по ЦФО. */
    private $allocationLevelDataList;

    /**
     * PeriodData constructor.
     *
     * @param Period|null $period
     * @param AllocationLevelData[] $allocationLevelDataList
     */
    public function __construct(
            ?Period $period,
            array $allocationLevelDataList
    ) {
        $this->period = $period;
        $this->allocationLevelDataList = $allocationLevelDataList;
    }

    /**
     * @return Period|null
     */
    public function getPeriod(): ?Period {
        return $this->period;
    }

    /**
     * @return AllocationLevelData[]
     */
    public function getAllocationLevelDataList(): array {
        return $this->allocationLevelDataList;
    }

    /**
     * @param Period|null $period
     *
     * @return PeriodData
     */
    public function withPeriod(?Period $period): PeriodData {
        $newPeriodData = clone $this;
        $newPeriodData->period = $period;
        return $newPeriodData;
    }

    /**
     * @param AllocationLevelData[] $allocationLevelDataList
     *
     * @return PeriodData
     */
    public function withAllocationLevelDataList(array $allocationLevelDataList): PeriodData {
        $newPeriodData = clone $this;
        $newPeriodData->allocationLevelDataList = $allocationLevelDataList;
        return $newPeriodData;
    }
}
