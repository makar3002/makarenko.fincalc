<?php
namespace makarenko\fincalc\reports\boundary;


use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CAgent;
use makarenko\fincalc\reports\control\CalculationBoundary;
use makarenko\fincalc\util\EventLog;


/**
 * Class CalculationFacade - фасадный класс с методами для работы с расчетами Financial calculator.
 * @package makarenko\fincalc\reports\boundary
 */
class CalculationFacade {
    public static function triggerCalculation(?CalculationBoundary $calculationBoundary = null): string {
        try {
            Loader::includeModule('iblock');

            $calculationBoundary = $calculationBoundary ?? new CalculationBoundary();
            $calculationBoundary->calculate();
            return __METHOD__. '();';
        } catch (\Exception $exception) {
            EventLog::add($exception);
            return __METHOD__. '();';
        }
    }

    public static function triggerIterativeCalculation(?CalculationBoundary $calculationBoundary = null): string {
        try {
            Loader::includeModule('iblock');

            $calculationBoundary = $calculationBoundary ?? new CalculationBoundary();
            $calculationBoundary->calculateIteration();
            return __METHOD__. '();';
        } catch (\Exception $exception) {
            EventLog::add($exception);
            return __METHOD__. '();';
        }
    }

    /**
     * Создает агент рекурсивных расчетов.
     *
     * @param int $interval
     */
    public static function createCalculationAgent($interval = 60): void {
        CAgent::AddAgent(
                CalculationFacade::class . '::triggerCalculation();',
                'makarenko.fincalc',
                'N',
                $interval,
                '',
                'Y',
                new DateTime(),
                50
        );
    }


    /**
     * Создает агент периодических расчетов.
     *
     * @param int $interval
     */
    public static function createIterativeCalculationAgent($interval = 2592000): void {
        CAgent::AddAgent(
                CalculationFacade::class . '::triggerIterativeCalculation();',
                'makarenko.fincalc',
                'N',
                $interval,
                '',
                'N',
                new DateTime(),
                40
        );
    }
}
