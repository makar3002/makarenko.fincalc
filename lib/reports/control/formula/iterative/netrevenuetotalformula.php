<?php
namespace makarenko\fincalc\reports\control\formula\iterative;


use makarenko\fincalc\reports\control\formula\OneFrcFormula;


class NetRevenueTotalFormula extends OneFrcFormula {
    protected const PARAMETER_CODE = 310001;

    private const NET_REVENUE_AFA_CODE = 310010;
    private const NET_REVENUE_AFP_CODE = 310020;
    private const NON_COMMISSION_REVENUE_CODE = 110030;
    private const NET_REVENUE_GETUNIQ_CODE = 310040;

    protected $requiredParameterCodeList = array(
            NetRevenueTotalFormula::NET_REVENUE_AFA_CODE,
            NetRevenueTotalFormula::NET_REVENUE_AFP_CODE,
            NetRevenueTotalFormula::NON_COMMISSION_REVENUE_CODE,
            NetRevenueTotalFormula::NET_REVENUE_GETUNIQ_CODE
    );

    public function calculateValue(array $frcValueList): float {
        $result = 0.0;
        foreach ($frcValueList as $valueList) {
            $result += $valueList[NetRevenueTotalFormula::NET_REVENUE_AFA_CODE]
                    + $valueList[NetRevenueTotalFormula::NET_REVENUE_AFP_CODE]
                    + $valueList[NetRevenueTotalFormula::NON_COMMISSION_REVENUE_CODE]
                    + $valueList[NetRevenueTotalFormula::NET_REVENUE_GETUNIQ_CODE];
        }

        return $result;
    }
}
