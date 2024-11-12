<?php
namespace makarenko\fincalc\reports\control\formula\iterative;


use makarenko\fincalc\reports\control\formula\OneFrcFormula;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;


class NetProfitBeforeBonusesFormula extends OneFrcFormula {
    protected const PARAMETER_CODE = 444444;

    private const TOTAL_CONTRIBUTION_TO_CODE = 777777;
    private const ALLOCATED_RED_EXPENSES_CODE = 888880;
    private const ALLOCATED_OWN_RED_EXPENSES_CODE = 888890;
    private const FX_RESULT_CODE = 99990;
    private const TOTAL_CONTRIBUTIONS_AND_EXPENSES_CODE = 777778;
    private const BONUSES_BELOW_THE_LINE_TOTAL_COMPANY_CODE = 44016;

    private const CODE_LIST_FOR_NON_ROOT_FRC = array(
            NetProfitBeforeBonusesFormula::TOTAL_CONTRIBUTION_TO_CODE,
            NetProfitBeforeBonusesFormula::ALLOCATED_RED_EXPENSES_CODE,
            NetProfitBeforeBonusesFormula::ALLOCATED_OWN_RED_EXPENSES_CODE,
            NetProfitBeforeBonusesFormula::FX_RESULT_CODE
    );

    private const CODE_LIST_FOR_ROOT_FRC = array(
            NetProfitBeforeBonusesFormula::TOTAL_CONTRIBUTIONS_AND_EXPENSES_CODE,
            NetProfitBeforeBonusesFormula::BONUSES_BELOW_THE_LINE_TOTAL_COMPANY_CODE
    );

    public function __construct(array $frcDataList, DataType $dataType, Frc $frc) {
        if (is_null($frc->getParentFrc())) {
            $this->requiredParameterCodeList = NetProfitBeforeBonusesFormula::CODE_LIST_FOR_ROOT_FRC;
        } else {
            $this->requiredParameterCodeList = NetProfitBeforeBonusesFormula::CODE_LIST_FOR_NON_ROOT_FRC;
        }

        parent::__construct($frcDataList, $dataType, $frc);
    }

    public function calculateValue(array $frcValueList): float {
        $result = 0.0;
        foreach ($frcValueList as $valueList) {
            $result += $valueList['777777']
                    - (
                            $valueList['888880']
                            + $valueList['888890']
                            + $valueList['99990']
                    )
                    + $valueList['777778']
                    - $valueList['44016'];
        }

        return $result;
    }
}
