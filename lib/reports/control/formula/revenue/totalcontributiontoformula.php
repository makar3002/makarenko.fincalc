<?php
namespace makarenko\fincalc\reports\control\formula\revenue;


use makarenko\fincalc\reports\control\formula\OneFrcFormula;


/**
 * Class TotalContributionToFormula - класс для формулы расчета параметра Total Contribution to.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class TotalContributionToFormula extends OneFrcFormula {
    private const TOTAL_EXPENSES_CODE = 40000;
    private const TOTAL_MARGIN_CODE = 333333;

    protected $requiredParameterCodeList = array(
            TotalContributionToFormula::TOTAL_EXPENSES_CODE,
            TotalContributionToFormula::TOTAL_MARGIN_CODE
    );
    protected const PARAMETER_CODE = 777777;

    protected function calculateValue(array $frcValueList): float {
        $sum = 0.0;
        foreach ($frcValueList as $valueList) {
            $sum += $valueList[TotalContributionToFormula::TOTAL_MARGIN_CODE];
            $sum -= $valueList[TotalContributionToFormula::TOTAL_EXPENSES_CODE];
        }

        return $sum;
    }
}
