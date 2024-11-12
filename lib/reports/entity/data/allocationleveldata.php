<?php
namespace makarenko\fincalc\reports\entity\data;


use makarenko\fincalc\reports\entity\parameter\Item;


class AllocationLevelData {
    /** @var Item|null - уровень аллокации. */
    private $allocationLevel;
    /** @var AffiliatedFrcData[] - список данных по ЦФО. */
    private $affiliatedFrcDataList;

    /**
     * PeriodData constructor.
     *
     * @param Item|null $allocationLevel
     * @param AffiliatedFrcData[] $affiliatedFrcDataList
     */
    public function __construct(
            ?Item $allocationLevel,
            array $affiliatedFrcDataList
    ) {
        $this->allocationLevel = $allocationLevel;
        $this->affiliatedFrcDataList = $affiliatedFrcDataList;
    }

    /**
     * @return Item|null
     */
    public function getAllocationLevel(): ?Item {
        return $this->allocationLevel;
    }

    /**
     * @return AffiliatedFrcData[]
     */
    public function getAffiliatedFrcDataList(): array {
        return $this->affiliatedFrcDataList;
    }

    /**
     * @param Item|null $allocationLevel
     *
     * @return AllocationLevelData
     */
    public function withPeriod(?Item $allocationLevel): AllocationLevelData {
        $newAllocationLevelData = clone $this;
        $newAllocationLevelData->allocationLevel = $allocationLevel;
        return $newAllocationLevelData;
    }

    /**
     * @param AffiliatedFrcData[] $affiliatedFrcDataList
     *
     * @return AllocationLevelData
     */
    public function withAffiliatedFrcDataList(array $affiliatedFrcDataList): AllocationLevelData {
        $newAllocationLevelData = clone $this;
        $newAllocationLevelData->affiliatedFrcDataList = $affiliatedFrcDataList;
        return $newAllocationLevelData;
    }
}
