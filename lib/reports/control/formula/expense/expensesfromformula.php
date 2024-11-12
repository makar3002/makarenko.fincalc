<?php
namespace makarenko\fincalc\reports\control\formula\expense;


use makarenko\fincalc\reports\control\formula\BaseFormula;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class ExpensesFromFormula - класс для формулы расчета параметра Expenses from <Child FRC name>.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class ExpensesFromFormula extends BaseFormula {
    private const TOTAL_EXPENSES_TO_CODE = 40010;

    protected $requiredParameterCodeList = array(
            ExpensesFromFormula::TOTAL_EXPENSES_TO_CODE
    );
    protected const PARAMETER_CODE = 40020;

    public function __construct(array $frcDataList, DataType $dataType, Frc $frc, Frc $childFrc) {
        $frcId = $childFrc->getId();
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
            $sum += $valueList[ExpensesFromFormula::TOTAL_EXPENSES_TO_CODE];
        }

        return $sum;
    }
}
