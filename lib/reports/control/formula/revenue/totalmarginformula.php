<?php
namespace makarenko\fincalc\reports\control\formula\revenue;


use makarenko\fincalc\reports\control\formula\BaseFormula;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\expenserequest\ExpenseRequest;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;


/**
 * Class TotalMarginFormula - класс формулы для перерасчета показателя Total Margin.
 * @package makarenko\fincalc\reports\control\formula
 */
class TotalMarginFormula extends BaseFormula {
    protected const PARAMETER_CODE = 333333;
    protected $requiredParameterCodeList = array();
    private $expenseRequestSum = 0.0;

    /**
     * TotalExpensesFormula constructor.
     *
     * @param HierarchicalDataNode[] $frcDataList
     * @param DataType $dataType
     * @param Frc $frc
     * @param ExpenseRequest[] $expenseRequestList
     */
    public function __construct(array $frcDataList, DataType $dataType, Frc $frc, array $expenseRequestList) {
        foreach ($frcDataList as $frcData) {
            $dataList = $frcData->getChildNodeList();
            array_walk($dataList, function (Data $data) {
                $parameter = $data->getIndex() ?? $data->getItem();
                $parameterId = $parameter ? $parameter->getId() : null;
                $this->requiredParameterCodeList[$parameterId] = $parameter ? $parameter->getCode() : null;
            });
        }

        array_walk($expenseRequestList, function (ExpenseRequest $expenseRequest) {
            $this->expenseRequestSum += $expenseRequest->amountWithoutTaxesUsd;
        });

        parent::__construct($frcDataList, $dataType, $frc);
    }

    protected function calculateValue(array $frcValueList): float {
        $sumValue = $this->expenseRequestSum;
        foreach ($frcValueList as $valueList) {
            foreach ($valueList as $value) {
                $sumValue += $value;
            }
        }

        return $sumValue;
    }
}
