<?php
namespace makarenko\fincalc\reports\control\formula\amounttoallocate;


use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class AmountToAllocateForgetFormula - класс для формулы расчета параметра Amount to allocate (forget).
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class AmountToAllocateForgetFormula extends AmountToAllocateFormula {
    protected const MAIN_VALUE_CODE = 90105;
    protected const ALLOCATED_EXPENSES_FORGET_VALUE_CODE = 90101;
    protected $requiredParameterCodeList = array(
            AmountToAllocateForgetFormula::MAIN_VALUE_CODE,
            AmountToAllocateForgetFormula::ALLOCATED_EXPENSES_FORGET_VALUE_CODE
    );
    protected const PARAMETER_CODE = 888893;

    public function __construct(array $frcDataList, DataType $dataType, Frc $frc, Frc $parentFrc) {
        $frcId = $parentFrc->getId();
        $frcData = $frcDataList[$frcId];
        if (!isset($frcData)) {
            $frcDataList = array();
        } else {
            $frcDataList = array($frcId => $frcData);
        }

        parent::__construct($frcDataList, $dataType, $frc, $parentFrc);
    }
}
