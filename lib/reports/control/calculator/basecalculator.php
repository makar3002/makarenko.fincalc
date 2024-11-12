<?php
namespace makarenko\fincalc\reports\control\calculator;

use Exception;
use makarenko\fincalc\reports\control\DataContainer;
use makarenko\fincalc\reports\control\formula\FormulaService;
use makarenko\fincalc\reports\control\ReferenceService;
use makarenko\fincalc\reports\entity\data\Data;

abstract class BaseCalculator {
    /** @var FormulaService - сервис для подготовки данных для формул. */
    protected $formulaService;
    /** @var ReferenceService - сервис для */
    protected $referenceService;

    public function __construct(ReferenceService $referenceService, FormulaService $formulaService) {
        $this->referenceService = $referenceService;
        $this->formulaService = $formulaService;
    }

    /**
     * @param DataContainer $dataContainer
     * @param Data $data
     *
     * @return DataContainer
     *
     * @throws CalculatorException
     * @throws Exception
     */
    public abstract function calculate(DataContainer $dataContainer, Data $data): DataContainer;
}
