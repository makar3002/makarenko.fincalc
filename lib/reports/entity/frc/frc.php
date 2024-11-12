<?php
namespace makarenko\fincalc\reports\entity\frc;


use makarenko\fincalc\reports\entity\HierarchicalDataValue;


/**
 * Class Frc - класс сущности ЦФО.
 * @package makarenko\fincalc\reports\entity\frc
 */
class Frc extends HierarchicalDataValue {
    /** @var int - цвет ЦФО. */
    private $color;
    /** @var string|null - уровень ЦФО. */
    private $level;
    /** @var Frc|null - родительский ЦФО. */
    private $parentFrc;
    /** @var array - массив дочерних красных ЦФО. */
    private $childRedFrcList;
    /** @var array - массив дочерних зеленых ЦФО. */
    private $childGreenFrcList;

    /**
     * Frc constructor.
     *
     * @param int $id - id ЦФО.
     * @param string $name - название ЦФО.
     * @param int $color - цвет ЦФО.
     * @param string|null $level - уровень ЦФО.
     * @param int|null $parentFrc
     * @param array $childGreenFrcList - массив дочерних красных ЦФО.
     * @param array $childRedFrcList - массив дочерних зеленых ЦФО.
     */
    public function __construct(
            int $id,
            string $name,
            int $color,
            ?string $level,
            ?int $parentFrc,
            array $childGreenFrcList,
            array $childRedFrcList
    ) {
        parent::__construct($id, $name);

        $this->color = $color;
        $this->level = $level;
        $this->parentFrc = $parentFrc;
        $this->childGreenFrcList = $childGreenFrcList;
        $this->childRedFrcList = $childRedFrcList;
    }

    /**
     * Возвращает цвет ЦФО.
     *
     * @return int
     */
    public function getColor(): int {
        return $this->color;
    }

    /**
     * Возвращает уровень ЦФО.
     *
     * @return string|null
     */
    public function getLevel(): ?string {
        return $this->level;
    }

    /**
     * Возвращает родительский ЦФО.
     *
     * @return int|null
     */
    public function getParentFrc(): ?int {
        return $this->parentFrc;
    }

    /**
     * Возвращает список дочерних красных ЦФО.
     *
     * @return Frc[]
     */
    public function getChildRedFrcList(): array {
        return $this->childRedFrcList;
    }

    /**
     * Возвращает списко дочерних зеленых ЦФО.
     *
     * @return Frc[]
     */
    public function getChildGreenFrcList(): array {
        return $this->childGreenFrcList;
    }
}