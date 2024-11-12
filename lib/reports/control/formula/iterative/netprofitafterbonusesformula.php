<?php
namespace makarenko\fincalc\reports\control\formula\iterative;


use makarenko\fincalc\reports\control\formula\OneFrcFormula;


class NetProfitAfterBonusesFormula extends OneFrcFormula {
    protected const PARAMETER_CODE = 555555;

    private const NET_PROFIT_BEFORE_BONUSES_CODE = 444444;
    private const BONUSES_BELOW_THE_LINE_CODE = 44015;

    protected $requiredParameterCodeList = array(
            NetProfitAfterBonusesFormula::NET_PROFIT_BEFORE_BONUSES_CODE,
            NetProfitAfterBonusesFormula::BONUSES_BELOW_THE_LINE_CODE,
    );

    public function calculateValue(array $frcValueList): float {
        $result = 0.0;
        foreach ($frcValueList as $valueList) {
            $result += $valueList[NetProfitAfterBonusesFormula::NET_PROFIT_BEFORE_BONUSES_CODE]
                    - $valueList[NetProfitAfterBonusesFormula::BONUSES_BELOW_THE_LINE_CODE];
        }

        return $result;
    }
}
