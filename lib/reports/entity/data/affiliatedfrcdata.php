<?php
namespace makarenko\fincalc\reports\entity\data;


use makarenko\fincalc\reports\entity\frc\Frc;


class AffiliatedFrcData {
    /** @var Frc|null - ЦФО. */
    private $affiliatedFrc;
    /** @var Data[] - список данных отчетов. */
    private $frcDataList;

    /**
     * AffiliatedFrcData constructor.
     *
     * @param Frc|null $affiliatedFrc
     * @param FrcData[] $frcDataList
     */
    public function __construct(
            ?Frc $affiliatedFrc,
            array $frcDataList
    ) {
        $this->affiliatedFrc = $affiliatedFrc;
        $this->frcDataList = $frcDataList;
    }

    /**
     * @return Frc|null
     */
    public function getAffiliatedFrc(): ?Frc {
        return $this->affiliatedFrc;
    }

    /**
     * @return Data[]
     */
    public function getFrcDataList(): array {
        return $this->frcDataList;
    }

    /**
     * @param Frc|null $affiliatedFrc
     *
     * @return AffiliatedFrcData
     */
    public function withAffiliatedFrc(?Frc $affiliatedFrc): AffiliatedFrcData {
        $newAffiliatedFrcData = clone $this;
        $newAffiliatedFrcData->affiliatedFrc = $affiliatedFrc;
        return $newAffiliatedFrcData;
    }

    /**
     * @param FrcData[] $frcDataList
     *
     * @return AffiliatedFrcData
     */
    public function withFrcDataList(array $frcDataList): AffiliatedFrcData {
        $newFrcData = clone $this;
        $newFrcData->frcDataList = $frcDataList;
        return $newFrcData;
    }
}
