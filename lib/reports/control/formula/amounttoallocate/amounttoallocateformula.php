<?php
namespace makarenko\fincalc\reports\control\formula\amounttoallocate;


use makarenko\fincalc\reports\control\formula\BaseFormula;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class AmountToAllocateFormula - класс для формул расчета параметра Amount to allocate.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
abstract class AmountToAllocateFormula extends BaseFormula {
    protected const MAIN_VALUE_CODE = 0;

    public function __construct(array $frcDataList, DataType $dataType, Frc $frc, Frc $parentFrc) {
        parent::__construct($frcDataList, $dataType, $frc);
    }

    protected function calculateValue(array $frcValueList): float {
        $sumValue = 0.0;
        foreach ($frcValueList as $valueList) {
            foreach ($this->requiredParameterCodeList as $valueCode) {
                $sumValue += $valueList[$valueCode];
            }
        }

        return $sumValue;
    }
}
