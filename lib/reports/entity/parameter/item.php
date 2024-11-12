<?php
namespace makarenko\fincalc\reports\entity\parameter;

/**
 * Class Item - сущность итема.
 *
 * @package makarenko\fincalc\reports\entity\parameter
 */
class Item extends Parameter {
    /** @var int - индекс уровня аллокации. */
    protected $allocationIndex;

    /**
     * Item constructor.
     *
     * @param int $id
     * @param string $name
     * @param int $code
     * @param array $frcList
     * @param bool $isActive
     * @param array $type
     * @param array $reportType
     * @param int $allocationIndex
     */
    public function __construct(
            int $id,
            string $name,
            int $code,
            array $frcList,
            bool $isActive,
            array $type,
            array $reportType,
            int $allocationIndex
    ) {
        parent::__construct($id, $name, $code, $frcList, $isActive, $type, $reportType);

        $this->allocationIndex = $allocationIndex;
    }

    /**
     * @return int - список типов итема.
     */
    public function getAllocationIndex(): int {
        return $this->allocationIndex;
    }

    /**
     * @param int $allocationIndex - индекс уровня аллокации.
     * @return Parameter - копия объекта сущности с измененным списоком типов итема.
     */
    public function withAllocationIndex(int $allocationIndex): Parameter {
        $newItem = clone $this;
        $newItem->allocationIndex = $allocationIndex;
        return $newItem;
    }
}