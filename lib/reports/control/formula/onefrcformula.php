<?php
namespace makarenko\fincalc\reports\control\formula;


use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class OneFrcFormula - класс для формулы расчета показателя, требующего параметры толька из того же ЦФО, что и он сам.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
abstract class OneFrcFormula extends BaseFormula {
    public function __construct(array $frcDataList, DataType $dataType, Frc $frc) {
        $frcId = $frc->getId();
        $frcData = $frcDataList[$frcId];
        if (!isset($frcData)) {
            $frcDataList = array();
        } else {
            $frcDataList = array($frcId => $frcData);
        }

        parent::__construct($frcDataList, $dataType, $frc);
    }
}
