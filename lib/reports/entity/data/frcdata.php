<?php
namespace makarenko\fincalc\reports\entity\data;


use makarenko\fincalc\reports\entity\frc\Frc;


class FrcData {
    /** @var Frc - ЦФО. */
    private $frc;
    /** @var Data[] - список данных отчетов. */
    private $dataList;

    /**
     * FrcData constructor.
     *
     * @param Frc $frc
     * @param Data[] $dataList
     */
    public function __construct(
            Frc $frc,
            array $dataList
    ) {
        $this->frc = $frc;
        $this->dataList = $dataList;
    }

    /**
     * @return Frc
     */
    public function getFrc(): Frc {
        return $this->frc;
    }

    /**
     * @return Data[]
     */
    public function getDataList(): array {
        return $this->dataList;
    }

    /**
     * @param Frc $frc
     *
     * @return FrcData
     */
    public function withFrc(Frc $frc): FrcData {
        $newFrcData = clone $this;
        $newFrcData->frc = $frc;
        return $newFrcData;
    }

    /**
     * @param Data[] $dataList
     *
     * @return FrcData
     */
    public function withDataList(array $dataList): FrcData {
        $newFrcData = clone $this;
        $newFrcData->dataList = $dataList;
        return $newFrcData;
    }
}
