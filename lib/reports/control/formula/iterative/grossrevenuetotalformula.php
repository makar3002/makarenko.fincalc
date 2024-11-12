<?php
namespace makarenko\fincalc\reports\control\formula\iterative;


use makarenko\fincalc\reports\control\formula\OneFrcFormula;


class GrossRevenueTotalFormula extends OneFrcFormula {
    protected const PARAMETER_CODE = 110001;

    private const GROSS_REVENUE_AFA_CODE = 110010;
    private const GROSS_REVENUE_AFP_CODE = 110020;
    private const NON_COMMISSION_REVENUE_CODE = 110030;
    private const GROSS_REVENUE_GETUNIQ_CODE = 110040;
    protected $requiredParameterCodeList = array(
            GrossRevenueTotalFormula::GROSS_REVENUE_AFA_CODE,
            GrossRevenueTotalFormula::GROSS_REVENUE_AFP_CODE,
            GrossRevenueTotalFormula::NON_COMMISSION_REVENUE_CODE,
            GrossRevenueTotalFormula::GROSS_REVENUE_GETUNIQ_CODE
    );

    public function calculateValue(array $frcValueList): float {
        $result = 0.0;
        foreach ($frcValueList as $valueList) {
            $result += $valueList[GrossRevenueTotalFormula::GROSS_REVENUE_AFA_CODE]
            + $valueList[GrossRevenueTotalFormula::GROSS_REVENUE_AFP_CODE]
            + $valueList[GrossRevenueTotalFormula::NON_COMMISSION_REVENUE_CODE]
            + $valueList[GrossRevenueTotalFormula::GROSS_REVENUE_GETUNIQ_CODE];
        }

        return $result;
    }
}
