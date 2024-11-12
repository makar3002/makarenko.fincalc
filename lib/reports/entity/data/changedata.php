<?php
namespace makarenko\fincalc\reports\entity\data;


use Bitrix\Main\Type\DateTime;


class ChangeData {
    private $id;
    private $data;
    private $snapshot;
    private $status;
    private $calculatorId;
    private $errorMessage;

    /**
     * ChangeData constructor.
     *
     * @param int $id
     * @param Data $data
     * @param DateTime $snapshot
     * @param string $status
     * @param string $calculatorId
     * @param string|null $errorMessage
     */
    public function __construct(
        int      $id,
        Data     $data,
        DateTime $snapshot,
        string   $status,
        string   $calculatorId,
        ?string  $errorMessage
    ) {
        $this->id = $id;
        $this->data = $data;
        $this->snapshot = $snapshot;
        $this->status = $status;
        $this->calculatorId = $calculatorId;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return Data
     */
    public function getData(): Data {
        return $this->data;
    }

    /**
     * @return DateTime
     */
    public function getSnapshot(): DateTime {
        return $this->snapshot;
    }

    /**
     * @return string
     */
    public function getStatus(): string {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getCalculatorId(): string {
        return $this->calculatorId;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string {
        return $this->errorMessage;
    }

    /**
     * @param int $id
     *
     * @return ChangeData
     */
    public function withId(int $id): ChangeData {
        $newChangeData = clone $this;
        $newChangeData->id = $id;
        return $newChangeData;
    }

    /**
     * @param Data $data
     *
     * @return ChangeData
     */
    public function withData(Data $data): ChangeData {
        $newChangeData = clone $this;
        $newChangeData->data = $data;
        return $newChangeData;
    }

    /**
     * @param DateTime $snapshot
     *
     * @return ChangeData
     */
    public function withSnapshot(DateTime $snapshot): ChangeData {
        $newChangeData = clone $this;
        $newChangeData->snapshot = $snapshot;
        return $newChangeData;
    }

    /**
     * @param string $status
     *
     * @return ChangeData
     */
    public function withStatus(string $status): ChangeData {
        $newChangeData = clone $this;
        $newChangeData->status = $status;
        return $newChangeData;
    }

    /**
     * @param string $calculatorId
     *
     * @return ChangeData
     */
    public function withCalculatorId(string $calculatorId): ChangeData {
        $newChangeData = clone $this;
        $newChangeData->calculatorId = $calculatorId;
        return $newChangeData;
    }

    /**
     * @param string|null $errorMessage
     *
     * @return ChangeData
     */
    public function withErrorMessage(?string $errorMessage): ChangeData {
        $newChangeData = clone $this;
        $newChangeData->errorMessage = $errorMessage;
        return $newChangeData;
    }
}
