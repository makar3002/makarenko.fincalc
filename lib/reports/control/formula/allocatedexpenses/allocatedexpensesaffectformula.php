<?php
namespace makarenko\fincalc\reports\control\formula\allocatedexpenses;

/**
 * Class AllocatedExpensesAffectFormula - класс для формулы расчета параметра Allocated expenses (affect).
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class AllocatedExpensesAffectFormula extends AllocatedExpensesFormula {
    protected const MAIN_VALUE_CODE = 888891;
    protected $requiredParameterCodeList = array(
            AllocatedExpensesAffectFormula::MAIN_VALUE_CODE,
    );
    protected const PARAMETER_CODE = 90110;
}
