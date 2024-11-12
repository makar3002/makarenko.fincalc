<?php
namespace makarenko\fincalc\reports\control\formula\amounttoallocate;


use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class AmountToAllocateForgetFormula - класс для формулы расчета параметра Amount to allocate (complain).
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class AmountToAllocateComplainFormula extends AmountToAllocateFormula {
    protected const MAIN_VALUE_CODE = 90110;
    protected $requiredParameterCodeList = array(
            AmountToAllocateComplainFormula::MAIN_VALUE_CODE,
    );
    protected const PARAMETER_CODE = 888892;

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
