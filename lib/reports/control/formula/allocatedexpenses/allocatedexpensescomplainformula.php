<?php
namespace makarenko\fincalc\reports\control\formula\allocatedexpenses;

/**
 * Class AllocatedExpensesComplainFormula - класс для формулы расчета параметра Allocated expenses (complain).
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class AllocatedExpensesComplainFormula extends AllocatedExpensesFormula {
    protected const MAIN_VALUE_CODE = 888892;
    protected $requiredParameterCodeList = array(
            AllocatedExpensesComplainFormula::MAIN_VALUE_CODE,
    );
    protected const PARAMETER_CODE = 90105;
}
