<?php
namespace makarenko\fincalc\reports\entity\parameter;


use makarenko\fincalc\reports\entity\HierarchicalDataValue;


/**
 * Class Parameter - сущность показателя.
 *
 * @package makarenko\fincalc\reports\entity\parameter
 */
abstract class Parameter extends HierarchicalDataValue {
    /** @var int - код показателя. */
    protected $code;
    /** @var array - список FRC, для которых этому показателю разрешено участвовать в расчетах. */
    protected $frcList;
    /** @var bool - активность показателя. */
    protected $isActive;
    /** @var array - список типов показателя. */
    protected $type;
    /** @var array - список типов отчетов, к которым относится показатель. */
    protected $reportType;

    /**
     * Parameter constructor.
     *
     * @param int $id
     * @param string $name
     * @param int $code
     * @param array $frcList
     * @param bool $isActive
     * @param array $type
     * @param array $reportType
     */
    public function __construct(
            int $id,
            string $name,
            int $code,
            array $frcList,
            bool $isActive,
            array $type,
            array $reportType
    ) {
        parent::__construct($id, $name);

        $this->code = $code;
        $this->frcList = $frcList;
        $this->isActive = $isActive;
        $this->reportType = $reportType;
        $this->type = $type;
    }

    /**
     * @return int - код показателя.
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * @return array - список FRC, для которых этому показателю разрешено участвовать в расчетах.
     */
    public function getFrcList() {
        return $this->frcList;
    }

    /**
     * @return bool - активность показателя.
     */
    public function isActive() {
        return $this->isActive;
    }

    /**
     * @return array - список типов показателя.
     */
    public function getType(): array {
        return $this->type;
    }

    /**
     * @return array - список типов отчетов, к которым относится показатель.
     */
    public function getReportType(): array {
        return $this->reportType;
    }

    /**
     * @param int $id - id показателя.
     * @return Parameter - копия объекта сущности с измененным id показателя.
     */
    public function withId(int $id): Parameter {
        $newParameter = clone $this;
        $newParameter->id = $id;
        return $newParameter;
    }

    /**
     * @param string $name - название показателя.
     * @return Parameter - копия объекта сущности с измененным названием показателя.
     */
    public function withName(string $name): Parameter {
        $newParameter = clone $this;
        $newParameter->name = $name;
        return $newParameter;
    }

    /**
     * @param int $code - код показателя.
     * @return Parameter - копия объекта сущности с измененным кодом показателя.
     */
    public function withCode(int $code): Parameter {
        $newParameter = clone $this;
        $newParameter->code = $code;
        return $newParameter;
    }

    /**
     * @param array $frcList - список FRC, для которых этому показателю разрешено участвовать в расчетах.
     * @return Parameter - копия объекта сущности с измененным списком FRC, для которых этому показателю разрешено участвовать в расчетах.
     */
    public function withFrcList(array $frcList): Parameter {
        $newParameter = clone $this;
        $newParameter->frcList = $frcList;
        return $newParameter;
    }

    /**
     * @param bool $isActive - активность показателя.
     * @return Parameter - копия объекта сущности с измененной активностю показателя.
     */
    public function withIsActive(bool $isActive): Parameter {
        $newParameter = clone $this;
        $newParameter->isActive = $isActive;
        return $newParameter;
    }

    /**
     * @param array $type - список типов показателя.
     * @return Parameter - копия объекта сущности с измененным списоком типов показателя.
     */
    public function withType(array $type): Parameter {
        $newIndex = clone $this;
        $newIndex->type = $type;
        return $newIndex;
    }

    /**
     * @param array $reportType - список типов отчетов, к которым относится показатель.
     * @return Parameter - копия объекта сущности с измененным списком типов отчетов, к которым относится показатель.
     */
    public function withReportType(array $reportType): Parameter {
        $newParameter = clone $this;
        $newParameter->reportType = $reportType;
        return $newParameter;
    }
}
