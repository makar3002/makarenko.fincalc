<?php
namespace makarenko\fincalc\reports\control\formula\expense;


use makarenko\fincalc\reports\control\formula\OneFrcFormula;


/**
 * Class TotalExpensesToFormula - класс для формулы расчета параметра Total Expenses to.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class TotalExpensesToFormula extends OneFrcFormula {
    private const TOTAL_EXPENSES_CODE = 40000;

    protected $requiredParameterCodeList = array(
            TotalExpensesToFormula::TOTAL_EXPENSES_CODE,
    );
    protected const PARAMETER_CODE = 40010;

    protected function calculateValue(array $frcValueList): float {
        $sum = 0.0;
        foreach ($frcValueList as $valueList) {
            $sum += $valueList[TotalExpensesToFormula::TOTAL_EXPENSES_CODE];
        }

        return $sum;
    }
}
