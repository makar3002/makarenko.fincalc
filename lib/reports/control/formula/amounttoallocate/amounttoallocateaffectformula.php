<?php
namespace makarenko\fincalc\reports\control\formula\amounttoallocate;

/**
 * Class AmountToAllocateForgetFormula - класс для формулы расчета параметра Amount to allocate (affect).
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class AmountToAllocateAffectFormula extends AmountToAllocateFormula {
    protected const MAIN_VALUE_CODE = 40000;
    protected $requiredParameterCodeList = array(
            AmountToAllocateAffectFormula::MAIN_VALUE_CODE,
    );
    protected const PARAMETER_CODE = 888891;
}
