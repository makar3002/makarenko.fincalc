<?php
namespace makarenko\fincalc\reports\control\formula\allocatedexpenses;


use makarenko\fincalc\reports\control\formula\BaseFormula;
use makarenko\fincalc\reports\control\formula\OneFrcFormula;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;


/**
 * Class AmountToAllocateFormula - класс для формул расчета параметра Amount to allocate.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
abstract class AllocatedExpensesFormula extends OneFrcFormula {
    private const TOTAL_PERCENT_CODE = 40080;
    protected const MAIN_VALUE_CODE = 0;
    /** @var float */
    private $total;

    /**
     * AllocatedExpensesFormula constructor.
     *
     * @param HierarchicalDataNode[] $frcDataList
     * @param DataType $dataType
     * @param Frc $frc
     */
    public function __construct(array $frcDataList, DataType $dataType, Frc $frc) {
        parent::__construct($frcDataList, $dataType, $frc);
        $this->total = $this->getTotalPercentSumInUsd($frcDataList[$frc->getId()]->getChildNodeList());
        if ($this->total < 0) {
            $this->total = 0;
        }
    }

    protected function calculateValue(array $frcValueList): float {
        $sumValue = 0.0;
        foreach ($frcValueList as $valueList) {
            $sumValue += $valueList[static::MAIN_VALUE_CODE];
        }

        return $sumValue * $this->total / 100;
    }


    /**
     * @param Data[] $dataList
     * @return Data|null
     */
    private function getTotalPercentSumInUsd(array $dataList): float {
        foreach ($dataList as $data) {
            $index = $data->getIndex();
            if (!$index || $index->getCode() != AllocatedExpensesFormula::TOTAL_PERCENT_CODE) {
                continue;
            }

            return $data->getSumInUsd() ?? 0.0;
        }

        return 0.0;
    }
}
