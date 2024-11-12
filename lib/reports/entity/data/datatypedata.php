<?php
namespace makarenko\fincalc\reports\entity\data;

use makarenko\fincalc\reports\entity\datatype\DataType;

class DataTypeData {
    /** @var DataType - id типа данных. */
    private $dataType;
    /** @var PeriodData[] - список данных по периодам. */
    private $periodDataList;

    /**
     * DataTypeData constructor.
     *
     * @param DataType $dataType
     * @param PeriodData[] $periodDataList
     */
    public function __construct(
            DataType $dataType,
            array $periodDataList
    ) {
        $this->dataType = $dataType;
        $this->periodDataList = $periodDataList;
    }

    /**
     * @return DataType
     */
    public function getDataType(): DataType {
        return $this->dataType;
    }

    /**
     * @return PeriodData[]
     */
    public function getPeriodDataList(): array {
        return $this->periodDataList;
    }

    /**
     * @param DataType $dataType
     *
     * @return DataTypeData
     */
    public function withDataType(DataType $dataType): DataTypeData {
        $newDataTypeData = clone $this;
        $newDataTypeData->dataType = $dataType;
        return $newDataTypeData;
    }

    /**
     * @param PeriodData[] $periodDataList
     *
     * @return DataTypeData
     */
    public function withPeriodDataList(array $periodDataList): DataTypeData {
        $newDataTypeData = clone $this;
        $newDataTypeData->periodDataList = $periodDataList;
        return $newDataTypeData;
    }
}
