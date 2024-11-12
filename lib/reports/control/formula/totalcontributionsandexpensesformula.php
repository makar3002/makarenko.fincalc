<?php
namespace makarenko\fincalc\reports\control\formula;


use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class TotalContributionsAndExpensesFormula - класс для формулы расчета параметра Total Contributions And Expenses.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class TotalContributionsAndExpensesFormula extends BaseFormula {
    private const TOTAL_EXPENSES_CODE = 40000;
    private const TOTAL_MARGIN_CODE = 333333;

    protected $requiredParameterCodeList = array(
            TotalContributionsAndExpensesFormula::TOTAL_EXPENSES_CODE,
            TotalContributionsAndExpensesFormula::TOTAL_MARGIN_CODE
    );
    protected const PARAMETER_CODE = 777778;

    public function __construct(array $frcDataList, DataType $dataType, Frc $frc) {
        $frcId = $frc->getId();
        $frcData = $frcDataList[$frcId];
        if (!isset($frcData)) {
            $frcDataList = array();
        } else {
            $frcDataList = array($frcId => $frcData);
        }

        parent::__construct($frcDataList, $dataType, $frc);
    }

    protected function calculateValue(array $frcValueList): float {
        $sum = 0.0;
        foreach ($frcValueList as $valueList) {
            $sum += $valueList[TotalContributionsAndExpensesFormula::TOTAL_MARGIN_CODE];
            $sum -= $valueList[TotalContributionsAndExpensesFormula::TOTAL_EXPENSES_CODE];
        }

        return $sum;
    }
}
