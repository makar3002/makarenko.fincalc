<?php
namespace makarenko\fincalc\reports\entity\allocation;

/**
 * Class FrcAllocateToParameters - сущность параметров аллокации для определенного периода.
 * @package makarenko\fincalc\reports\entity\allocation
 */
class PeriodParameters {
    /** @var int - id периода. */
    private $period;
    /** @var FrcAllocateToParameters[] - массив параметров аллокации для определенного ЦФО. */
    private $frcAllocateToParameterList;

    /**
     * PeriodParameters constructor.
     *
     * @param int $period - id периода.
     * @param FrcAllocateToParameters[] $frcAllocateToParameterList - массив параметров аллокации для определенного ЦФО.
     */
    public function __construct(
            int $period,
            array $frcAllocateToParameterList
    ) {
        $this->period = $period;
        $this->frcAllocateToParameterList = $frcAllocateToParameterList;
    }

    /**
     * Возвращает id периода.
     *
     * @return int
     */
    public function getPeriod(): int {
        return $this->period;
    }

    /**
     * Возвращает массив параметров аллокации для определенного ЦФО.
     *
     * @return FrcAllocateToParameters[]
     */
    public function getFrcAllocateToParameterList(): array {
        return $this->frcAllocateToParameterList;
    }

    /**
     * Создает копию объекта сущности с добавленным к ней новым параметром аллокации для определенного ЦФО.
     *
     * @param FrcAllocateToParameters $frcAllocateToParameters
     *
     * @return PeriodParameters
     */
    public function addFrcAllocateToParameter(FrcAllocateToParameters $frcAllocateToParameters): PeriodParameters {
        $newPeriodParameters = clone $this;
        $frcAllocateTo = $frcAllocateToParameters->getFrcAllocateTo();
        $newPeriodParameters->frcAllocateToParameterList[$frcAllocateTo] = $frcAllocateToParameters;
        return $newPeriodParameters;
    }
}