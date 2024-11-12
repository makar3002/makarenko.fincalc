<?php
namespace makarenko\fincalc\reports\control\formula;


use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class TotalPercentFormula - класс формулы для перерасчета показателя Total (%) в параметрах аллокации.
 * @package makarenko\fincalc\reports\control\formula
 */
class TotalPercentFormula extends BaseFormula {
    protected const PARAMETER_CODE = 40080;
    /** @var int - код индекса Take (%) */
    public const TAKE_PERCENT_INDEX_CODE = 40040;
    /** @var int - код индекса Tax (%) */
    public const TAX_PERCENT_INDEX_CODE = 40060;
    protected $requiredParameterCodeList = array(
            TotalPercentFormula::TAKE_PERCENT_INDEX_CODE,
            TotalPercentFormula::TAX_PERCENT_INDEX_CODE
    );

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

    protected function calculateValue(array $frcValueList): float {
        $sumValue = 0.0;
        foreach ($frcValueList as $valueList) {
            $sumValue += $valueList[TotalPercentFormula::TAKE_PERCENT_INDEX_CODE];
            $sumValue += $valueList[TotalPercentFormula::TAX_PERCENT_INDEX_CODE];
        }

        return $sumValue;
    }
}
