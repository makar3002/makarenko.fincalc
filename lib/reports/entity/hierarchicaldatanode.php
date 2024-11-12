<?php
namespace makarenko\fincalc\reports\entity;

use makarenko\fincalc\reports\entity\data\Data;

class HierarchicalDataNode {
    /** @var HierarchicalDataValue|null - значение поля, определяющего положение в иерархии. */
    private $value;
    /** @var int - значение поля, определяющего уровень вложенности в иерархии. */
    private $level;
    /** @var HierarchicalDataNode[]|Data[] - список дочерних по иерархии данных. */
    private $childNodeList;

    /**
     * HierarchicalDataNode constructor.
     *
     * @param HierarchicalDataValue|null $value
     * @param int $level
     * @param HierarchicalDataNode[]|Data[] $childNodeList
     */
    public function __construct(
            ?HierarchicalDataValue $value,
            int                    $level,
            array                  $childNodeList
    ) {
        $this->value = $value;
        $this->level = $level;
        $this->childNodeList = $childNodeList;
    }

    /**
     * @return HierarchicalDataValue|null
     */
    public function getValue(): ?HierarchicalDataValue {
        return $this->value;
    }

    /**
     * @return int|null
     */
    public function getLevel(): ?int {
        return $this->level;
    }

    /**
     * @return HierarchicalDataNode[]|Data[]
     */
    public function getChildNodeList(): array {
        return $this->childNodeList;
    }

    /**
     * @param HierarchicalDataValue $value
     *
     * @return HierarchicalDataNode
     */
    public function withValue(HierarchicalDataValue $value): HierarchicalDataNode {
        $newNode = clone $this;
        $newNode->value = $value;
        return $newNode;
    }

    /**
     * @param int $level
     *
     * @return HierarchicalDataNode
     */
    public function withLevel(HierarchicalDataValue $level): HierarchicalDataNode {
        $newNode = clone $this;
        $newNode->level = $level;
        return $newNode;
    }

    /**
     * @param HierarchicalDataNode[]|Data[] $childNodeList
     *
     * @return HierarchicalDataNode
     */
    public function withChildNodeList(array $childNodeList): HierarchicalDataNode {
        $newNode = clone $this;
        $newNode->childNodeList = $childNodeList;
        return $newNode;
    }
}
