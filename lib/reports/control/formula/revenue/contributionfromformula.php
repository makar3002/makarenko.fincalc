<?php
namespace makarenko\fincalc\reports\control\formula\revenue;


use makarenko\fincalc\reports\control\formula\BaseFormula;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


/**
 * Class ContributionFromFormula - класс для формулы расчета параметра Contribution from <Child FRC name>.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
class ContributionFromFormula extends BaseFormula {
    private const TOTAL_CONTRIBUTION_TO_CODE = 777777;

    protected $requiredParameterCodeList = array(
            ContributionFromFormula::TOTAL_CONTRIBUTION_TO_CODE
    );
    protected const PARAMETER_CODE = 777779;

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
            $sum += $valueList[ContributionFromFormula::TOTAL_CONTRIBUTION_TO_CODE];
        }

        return $sum;
    }
}
