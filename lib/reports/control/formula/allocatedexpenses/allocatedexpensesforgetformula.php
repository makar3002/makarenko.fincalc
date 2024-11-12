<?php
namespace makarenko\fincalc\reports\control\formula\allocatedexpenses;

/**
 * Class AllocatedExpensesForgetFormula - класс для формулы расчета параметра Allocated expenses (forget).
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class AllocatedExpensesForgetFormula extends AllocatedExpensesFormula {
    protected const MAIN_VALUE_CODE = 888893;
    protected $requiredParameterCodeList = array(
            AllocatedExpensesForgetFormula::MAIN_VALUE_CODE,
    );
    protected const PARAMETER_CODE = 90101;
}
