<?php
namespace makarenko\fincalc\reports\entity\parameter;

/**
 * Class Index - сущность индекса.
 *
 * @package makarenko\fincalc\reports\entity\parameter
 */
class Index extends Parameter {
    /** @var int - единица измерения индекса. */
    private $unit;
    /** @var int - источник индекса. */
    private $source;

    /**
     * Index constructor.
     *
     * @param int $id
     * @param string $name
     * @param int $code
     * @param array $frcList
     * @param bool $isActive
     * @param array $type
     * @param array $reportType
     * @param int $unit
     * @param int $source
     */
    public function __construct(
            int $id,
            string $name,
            int $code,
            array $frcList,
            bool $isActive,
            array $type,
            array $reportType,
            int $unit,
            int $source
    ) {
        parent::__construct($id, $name, $code, $frcList, $isActive, $type, $reportType);

        $this->unit = $unit;
        $this->source = $source;
    }

    /**
     * @return int - единица измерения индекса.
     */
    public function getUnit(): int {
        return $this->unit;
    }

    /**
     * @return int - источник индекса.
     */
    public function getSource(): int {
        return $this->source;
    }

    /**
     * @param int $unit - единица измерения индекса.
     * @return Index - копия объекта сущности с измененной единицей измерения индекса.
     */
    public function withUnit(int $unit): Index {
        $newIndex = clone $this;
        $newIndex->unit = $unit;
        return $newIndex;
    }

    /**
     * @param int $source - источник индекса.
     * @return Index - копия объекта сущности с измененным источником индекса.
     */
    public function withSource(int $source): Index {
        $newIndex = clone $this;
        $newIndex->source = $source;
        return $newIndex;
    }
}
