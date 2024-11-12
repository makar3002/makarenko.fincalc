<?php
namespace makarenko\fincalc\reports\entity;

/**
 * Class DataType - класс сущности значения поля, определяющего его положение в иерархии.
 * @package makarenko\fincalc\reports\entity
 */
abstract class HierarchicalDataValue {
    /** @var int - id значения. */
    protected $id;
    /** @var string - название значения. */
    protected $name;

    /**
     * HierarchicalDataValue constructor.
     *
     * @param int $id - id значения.
     * @param string $name - название значения.
     */
    public function __construct(
            int $id,
            string $name
    ) {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Возвращает id значения.
     *
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Возвращает название значения.
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}