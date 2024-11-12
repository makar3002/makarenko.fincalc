<?php
namespace makarenko\fincalc\reports\control\formula\iterative;


use makarenko\fincalc\reports\control\formula\OneFrcFormula;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\expenserequest\ExpenseRequest;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;


class FxResultFormula extends OneFrcFormula {
    protected const PARAMETER_CODE = 99990;

    private const FX_RESULT_REVENUE_CODE = 99990;
    protected $requiredParameterCodeList = array(
            FxResultFormula::FX_RESULT_REVENUE_CODE,
    );

    private $expenseRequestSum;

    /**
     * FxResultFormula constructor.
     *
     * @param HierarchicalDataNode[] $frcDataList
     * @param DataType $dataType
     * @param Frc $frc
     * @param ExpenseRequest[] $expenseRequestList
     */
    public function __construct(array $frcDataList, DataType $dataType, Frc $frc, array $expenseRequestList) {
        $frcId = $frc->getId();
        $frcData = $frcDataList[$frcId];
        if (!isset($frcData)) {
            $frcDataList = array();
        } else {
            $frcDataList = array($frcId => $frcData);
            $this->requiredParameterCodeList = array_merge(
                    $this->requiredParameterCodeList,
                    $this->getFxResultCodeList($frcData)
            );
        }

        $this->expenseRequestSum = $this->calculateExpenseRequestFxResult($expenseRequestList, $frc);
        parent::__construct($frcDataList, $dataType, $frc);
    }

    public function calculateValue(array $frcValueList): float {
        $result = $this->expenseRequestSum;
        foreach ($frcValueList as $valueList) {
            $result += array_sum($valueList);
        }

        return $result;
    }

    /**
     * @param ExpenseRequest[] $expenseRequestList
     * @param Frc $frc
     *
     * @return float
     */
    private function calculateExpenseRequestFxResult(array $expenseRequestList, Frc $frc): float {
        $filteredExpenseRequestList = array_filter(
                $expenseRequestList,
                function (ExpenseRequest $expenseRequest) use ($frc) {
                    return
                            $expenseRequest->frc === $frc
                            && !is_null($expenseRequest->amountInOriginalCurrencyWithoutTaxes)
                            && !is_null($expenseRequest->currency);
                }
        );

        $expenseRequestSum = 0.0;
        foreach ($filteredExpenseRequestList as $expenseRequest) {
            if (is_null($expenseRequest->amountWithoutTaxesUsd)) {
                $expenseRequestSum +=
                        $expenseRequest->amountInOriginalCurrencyWithoutTaxes / $expenseRequest->currency->budgetRate
                        - $expenseRequest->amountInOriginalCurrencyWithoutTaxes / $expenseRequest->currency->monthlyRate;
            } else {
                $expenseRequestSum += $expenseRequest->amountWithoutTaxesUsd;
            }
        }

        return $expenseRequestSum;
    }


    /**
     * @param HierarchicalDataNode $frcDataNode
     *
     * @return int[]
     */
    private function getFxResultCodeList(HierarchicalDataNode $frcDataNode): array {
        $runtimeCodesList = array();
        /** @var Data $data */
        foreach ($frcDataNode->getChildNodeList() as $data) {
            if (is_null($data->getSumInOriginalCurrency()) || is_null($data->getOriginalCurrency())) {
                continue;
            }

            $parameter = $data->getIndex() ?? $data->getItem();
            $runtimeCodesList[$parameter->getCode()] = $parameter->getCode();
        }

        return $runtimeCodesList;
    }
}
