<?php
namespace makarenko\fincalc\reports\entity\allocation;

/**
 * Class FrcAllocateToParameters - сущность параметров аллокации для определенного ЦФО.
 * @package makarenko\fincalc\reports\entity\allocation
 */
class FrcAllocateToParameters {
    /** @var int - id ЦФО. */
    private $frcAllocateTo;
    /** @var AllocationLevelParameters[] - массив параметров аллокации для определенного уровня. */
    private $allocationLevelParameterList;

    /**
     * FrcAllocateToParameters constructor.
     *
     * @param int $frcAllocateTo - id ЦФО.
     * @param AllocationLevelParameters[] $allocationLevelParameterList - массив параметров аллокации для определенного уровня.
     */
    public function __construct(
            int $frcAllocateTo,
            array $allocationLevelParameterList
    ) {
        $this->frcAllocateTo = $frcAllocateTo;
        $this->allocationLevelParameterList = $allocationLevelParameterList;
    }

    /**
     * Возвращает id ЦФО.
     *
     * @return int
     */
    public function getFrcAllocateTo(): int {
        return $this->frcAllocateTo;
    }

    /**
     * Возвращает массив параметров аллокации для определенного уровня.
     *
     * @return AllocationLevelParameters[]
     */
    public function getAllocationLevelParameterList(): array {
        return $this->allocationLevelParameterList;
    }

    /**
     * Создает копию объекта сущности с добавленным к ней новым параметром аллокации для определенного уровня аллокации.
     *
     * @param AllocationLevelParameters $allocationLevelParameters
     *
     * @return FrcAllocateToParameters
     */
    public function addAllocationLevelParameters(AllocationLevelParameters $allocationLevelParameters): FrcAllocateToParameters {
        $newFrcAllocateToParameters = clone $this;
        $allocationLevel = $allocationLevelParameters->getAllocationLevel();
        $newFrcAllocateToParameters->allocationLevelParameterList[$allocationLevel] = $allocationLevelParameters;
        return $newFrcAllocateToParameters;
    }
}