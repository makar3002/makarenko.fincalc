<?php
namespace makarenko\fincalc\reports\entity\data;


use Bitrix\Main\Type\DateTime;
use makarenko\fincalc\reports\entity\currency\Currency;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\period\Period;


class Data {
    private $id;
    private $name;
    private $dataType;
    private $period;
    private $index;
    private $item;
    private $frc;
    private $originalCurrency;
    private $sumInOriginalCurrency;
    private $sumInUsd;
    private $allocationLevel;
    private $comments;
    private $snapshot;
    private $affiliatedFrc;
    private $indexItemCode;
    private $changeOrderNumber;

    /**
     * Data constructor.
     *
     * @param string $name
     * @param DataType $dataType
     * @param Period|null $period
     * @param Index|null $index
     * @param Item|null $item
     * @param Frc $frc
     * @param Currency|null $originalCurrency
     * @param float|null $sumInOriginalCurrency
     * @param float|null $sumInUsd
     * @param Item|null $allocationLevel
     * @param string|null $comments
     * @param DateTime|null $snapshot
     * @param Frc|null $affiliatedFrc
     * @param int|null $indexItemCode
     * @param int|null $id
     * @param int|null $changeOrderNumber
     */
    public function __construct(
            string $name,
            DataType $dataType,
            ?Period $period,
            ?Index $index,
            ?Item $item,
            Frc $frc,
            ?Currency $originalCurrency,
            ?float $sumInOriginalCurrency,
            ?float $sumInUsd,
            ?Item $allocationLevel,
            ?string $comments,
            ?DateTime $snapshot,
            ?Frc $affiliatedFrc,
            ?int $indexItemCode,
            ?int $id = null,
            ?int $changeOrderNumber = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->dataType = $dataType;
        $this->period = $period;
        $this->index = $index;
        $this->item = $item;
        $this->frc = $frc;
        $this->originalCurrency = $originalCurrency;
        $this->sumInOriginalCurrency = $sumInOriginalCurrency;
        $this->sumInUsd = $sumInUsd;
        $this->allocationLevel = $allocationLevel;
        $this->comments = $comments;
        $this->snapshot = $snapshot;
        $this->affiliatedFrc = $affiliatedFrc;
        $this->indexItemCode = $indexItemCode;
        $this->changeOrderNumber = $changeOrderNumber;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return DataType
     */
    public function getDataType(): DataType {
        return $this->dataType;
    }

    /**
     * @return Period|null
     */
    public function getPeriod(): ?Period {
        return $this->period;
    }

    /**
     * @return Index|null
     */
    public function getIndex(): ?Index {
        return $this->index;
    }

    /**
     * @return Item|null
     */
    public function getItem(): ?Item {
        return $this->item;
    }

    /**
     * @return Frc
     */
    public function getFrc(): Frc {
        return $this->frc;
    }

    /**
     * @return Currency|null
     */
    public function getOriginalCurrency(): ?Currency {
        return $this->originalCurrency;
    }

    /**
     * @return float|null
     */
    public function getSumInOriginalCurrency(): ?float {
        return $this->sumInOriginalCurrency;
    }

    /**
     * @return float|null
     */
    public function getSumInUsd(): ?float {
        return $this->sumInUsd;
    }

    /**
     * @return Item|null
     */
    public function getAllocationLevel(): ?Item {
        return $this->allocationLevel;
    }

    /**
     * @return string|null
     */
    public function getComments(): ?string {
        return $this->comments;
    }

    /**
     * @return DateTime
     */
    public function getSnapshot(): DateTime {
        return $this->snapshot;
    }

    /**
     * @return Frc|null
     */
    public function getAffiliatedFrc(): ?Frc {
        return $this->affiliatedFrc;
    }

    /**
     * @return int|null
     */
    public function getIndexItemCode(): ?int {
        return $this->indexItemCode;
    }

    /**
     * @return int|null
     */
    public function getChangeOrderNumber(): ?int {
        return $this->changeOrderNumber;
    }

    /**
     * @param int|null $id
     *
     * @return Data
     */
    public function withId(?int $id): Data {
        $newData = clone $this;
        $newData->id = $id;
        return $newData;
    }

    /**
     * @param string $name
     *
     * @return Data
     */
    public function withName(string $name): Data {
        $newData = clone $this;
        $newData->name = $name;
        return $newData;
    }

    /**
     * @param DataType $dataType
     *
     * @return Data
     */
    public function withDataType(DataType $dataType): Data {
        $newData = clone $this;
        $newData->dataType = $dataType;
        return $newData;
    }

    /**
     * @param Period|null $period
     *
     * @return Data
     */
    public function withPeriod(?Period $period): Data {
        $newData = clone $this;
        $newData->period = $period;
        return $newData;
    }

    /**
     * @param Index|null $index
     *
     * @return Data
     */
    public function withIndex(?Index $index): Data {
        $newData = clone $this;
        $newData->index = $index;
        return $newData;
    }

    /**
     * @param Item|null $item
     *
     * @return Data
     */
    public function withItem(?Item $item): Data {
        $newData = clone $this;
        $newData->item = $item;
        return $newData;
    }

    /**
     * @param Frc $frc
     *
     * @return Data
     */
    public function withFrc(Frc $frc): Data {
        $newData = clone $this;
        $newData->frc = $frc;
        return $newData;
    }

    /**
     * @param Currency|null $originalCurrency
     *
     * @return Data
     */
    public function withOriginalCurrency(?Currency $originalCurrency): Data {
        $newData = clone $this;
        $newData->originalCurrency = $originalCurrency;
        return $newData;
    }

    /**
     * @param float|null $sumInOriginalCurrency
     *
     * @return Data
     */
    public function withSumInOriginalCurrency(?float $sumInOriginalCurrency): Data {
        $newData = clone $this;
        $newData->sumInOriginalCurrency = $sumInOriginalCurrency;
        return $newData;
    }

    /**
     * @param float|null $sumInUsd
     *
     * @return Data
     */
    public function withSumInUsd(?float $sumInUsd): Data {
        $newData = clone $this;
        $newData->sumInUsd = $sumInUsd;
        return $newData;
    }

    /**
     * @param Item|null $allocationLevel
     *
     * @return Data
     */
    public function withAllocationLevel(?Item $allocationLevel): Data {
        $newData = clone $this;
        $newData->allocationLevel = $allocationLevel;
        return $newData;
    }

    /**
     * @param string|null $comments
     *
     * @return Data
     */
    public function withComments(?string $comments): Data {
        $newData = clone $this;
        $newData->comments = $comments;
        return $newData;
    }

    /**
     * @param DateTime $snapshot
     *
     * @return Data
     */
    public function withSnapshot(DateTime $snapshot): Data {
        $newData = clone $this;
        $newData->snapshot = $snapshot;
        return $newData;
    }

    /**
     * @param Frc|null $affiliatedFrc
     *
     * @return Data
     */
    public function withAffiliatedFrc(?Frc $affiliatedFrc): Data {
        $newData = clone $this;
        $newData->affiliatedFrc = $affiliatedFrc;
        return $newData;
    }

    /**
     * @param int|null $indexItemCode
     *
     * @return Data
     */
    public function withIndexItemCode(?int $indexItemCode): Data {
        $newData = clone $this;
        $newData->indexItemCode = $indexItemCode;
        return $newData;
    }

    /**
     * @param int|null $changeOrderNumber
     *
     * @return Data
     */
    public function withChangeOrderNumber(?int $changeOrderNumber): Data {
        $newData = clone $this;
        $newData->changeOrderNumber = $changeOrderNumber;
        return $newData;
    }
}
