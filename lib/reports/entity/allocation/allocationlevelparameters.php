<?php
namespace makarenko\fincalc\reports\entity\allocation;

/**
 * Class AllocationLevelParameters - сущность параметров аллокации для определенного уровня.
 * @package makarenko\fincalc\reports\entity\allocation
 */
class AllocationLevelParameters {
    /** @var int - уровень аллокации. */
    private $allocationLevel;
    /** @var AllocationData[] - массив параметров аллокации. */
    private $allocationParameterList;

    /**
     * AllocationLevelParameters constructor.
     *
     * @param int $allocationLevel - значение уровня аллокации.
     * @param AllocationData[] $allocationParameterList - массив параметров аллокации.
     */
    public function __construct(
            int $allocationLevel,
            array $allocationParameterList
    ) {
        $this->allocationLevel = $allocationLevel;
        $this->allocationParameterList = $allocationParameterList;
    }

    /**
     * Возвращает уровень аллокации.
     *
     * @return int
     */
    public function getAllocationLevel(): int {
        return $this->allocationLevel;
    }

    /**
     * Возвращает массив параметров аллокации.
     *
     * @return AllocationData[]
     */
    public function getAllocationParameterList(): array {
        return $this->allocationParameterList;
    }

    /**
     * Создает копию объекта сущности с добавленным к ней новым параметром аллокации.
     *
     * @param int $dataType - тип данных.
     * @param AllocationData $allocationData - новый параметр аллокации.
     *
     * @return AllocationLevelParameters
     */
    public function addAllocationParameter(int $dataType, AllocationData $allocationData): AllocationLevelParameters {
        $newAllocationLevelParameters = clone $this;
        $newAllocationLevelParameters->allocationParameterList[$dataType] = $allocationData;
        return $newAllocationLevelParameters;
    }
}
