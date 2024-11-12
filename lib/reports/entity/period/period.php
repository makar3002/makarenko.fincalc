<?php
namespace makarenko\fincalc\reports\entity\period;


use Bitrix\Main\Type\DateTime;
use makarenko\fincalc\reports\entity\HierarchicalDataValue;


class Period extends HierarchicalDataValue {
    private $type;
    private $isOpen;
    private $start;
    private $end;
    private $aliSys;
    private $aliWeb;

    /**
     * Period constructor.
     *
     * @param int $id
     * @param string $name
     * @param int $type
     * @param bool $isOpen
     * @param DateTime $start
     * @param DateTime $end
     * @param float $aliSys
     * @param float $aliWeb
     */
    public function __construct(
            int $id,
            string $name,
            int $type,
            bool $isOpen,
            DateTime $start,
            DateTime $end,
            float $aliSys,
            float $aliWeb
    ) {
        parent::__construct($id, $name);

        $this->type = $type;
        $this->isOpen = $isOpen;
        $this->start = $start;
        $this->end = $end;
        $this->aliSys = $aliSys;
        $this->aliWeb = $aliWeb;
    }

    /**
     * @return int
     */
    public function getType(): int {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isOpen(): bool {
        return $this->isOpen;
    }

    /**
     * @return DateTime
     */
    public function getStart(): DateTime {
        return $this->start;
    }

    /**
     * @return DateTime
     */
    public function getEnd(): DateTime {
        return $this->end;
    }

    /**
     * @return float
     */
    public function getAliSys(): float {
        return $this->aliSys;
    }

    /**
     * @return float
     */
    public function getAliWeb(): float {
        return $this->aliWeb;
    }

    /**
     * @param int $id
     * @return Period
     */
    public function withId(int $id): Period {
        $newPeriod = clone $this;
        $newPeriod->id = $id;
        return $newPeriod;
    }

    /**
     * @param string $name
     * @return Period
     */
    public function withName(string $name): Period {
        $newPeriod = clone $this;
        $newPeriod->name = $name;
        return $newPeriod;
    }

    /**
     * @param int $type
     * @return Period
     */
    public function withType(int $type): Period {
        $newPeriod = clone $this;
        $newPeriod->type = $type;
        return $newPeriod;
    }

    /**
     * @param bool $isOpen
     * @return Period
     */
    public function withIsOpen(bool $isOpen): Period {
        $newPeriod = clone $this;
        $newPeriod->isOpen = $isOpen;
        return $newPeriod;
    }

    /**
     * @param DateTime $start
     * @return Period
     */
    public function withStart(DateTime $start): Period {
        $newPeriod = clone $this;
        $newPeriod->start = $start;
        return $newPeriod;
    }

    /**
     * @param DateTime $end
     * @return Period
     */
    public function withEnd(DateTime $end): Period {
        $newPeriod = clone $this;
        $newPeriod->end = $end;
        return $newPeriod;
    }

    /**
     * @param float $aliSys
     * @return Period
     */
    public function withAliSys(float $aliSys): Period {
        $newPeriod = clone $this;
        $newPeriod->aliSys = $aliSys;
        return $newPeriod;
    }

    /**
     * @param float $aliWeb
     * @return Period
     */
    public function withAliWeb(float $aliWeb): Period {
        $newPeriod = clone $this;
        $newPeriod->aliWeb = $aliWeb;
        return $newPeriod;
    }
}