<?php
namespace makarenko\fincalc\reports\control\formula;


use Bitrix\Main\Type\DateTime;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\data\FrcData;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\parameter\Parameter;


/**
 * Class BaseFormula - описывает формулы.
 *
 * @package makarenko\fincalc\reports\control\formula
 */
abstract class BaseFormula {
    private const DEFAULT_FLOAT_VALUE = 0.0;
    private const DEFAULT_STRING_VALUE = '';
    private const DEFAULT_NULL_VALUE = null;
    /** @var int - код параметра, который формула расчитывает. */
    protected const PARAMETER_CODE = 0;

    /** @var array - список кодов обязательных для формулы показателей. */
    protected $requiredParameterCodeList = array();

    /** @var FrcData[] */
    protected $frcDataList;
    /** @var DateTime|null */
    protected $maxSnapshot = null;
    /** @var Parameter|null */
    private $parameter = null;
    /** @var Frc */
    private $frc;
    /** @var DataType */
    private $dataType;


    /**
     * Formula constructor.
     *
     * @param HierarchicalDataNode[] $frcDataList
     * @param DataType $dataType
     * @param Frc $frc
     */
    public function __construct(array $frcDataList, DataType $dataType, Frc $frc) {
        $this->frcDataList = $frcDataList;
        $this->dataType = $dataType;
        $this->frc = $frc;
    }

    /**
     * Запоминает параметр, который будет у данного отчетов.
     *
     * @param Parameter|null $parameter
     */
    public function setParameter(?Parameter $parameter): void {
        $this->parameter = $parameter;
    }

    /**
     * Возвращает код параметра, который расчитывает формула.
     *
     * @return int
     */
    public function getParameterCode(): int {
        return intval(static::PARAMETER_CODE);
    }

    /**
     * Возвращает максимальный запомнившийся снапшот.
     *
     * @return DateTime|null
     */
    public function getMaxSnapshot(): ?DateTime {
        return $this->maxSnapshot;
    }

    /**
     * Вычисляет значение формулы и возвращает результат в виде данного отчетов.
     *
     * @return Data
     */
    public function execute(): Data {
        $preparedFrcDataList = $this->prepareFrcDataList($this->frcDataList);
        $frcValueList = $this->getFrcValueList($preparedFrcDataList);
        $calculatedValue = $this->calculateValue($frcValueList);
        return $this->getDefaultDataWithSumInUsd($calculatedValue);
    }

    /**
     * Подготавливает данные отчетов ЦФО к вычислениям.
     *
     * @param HierarchicalDataNode[] $frcDataList
     *
     * @return array[]
     */
    private function prepareFrcDataList(array $frcDataList): array {
        $preparedFrcDataList = array();

        /**
         * @var int $frcId
         * @var HierarchicalDataNode $frcData
         */
        foreach ($frcDataList as $frcId => $frcData) {
            $dataList = $frcData->getChildNodeList();
            $dataCodeList = array_map(
                    function (Data $data) {
                        $dataParameter = $data->getIndex() ?: $data->getItem();
                        return $dataParameter ? $dataParameter->getCode() : null;
                    },
                    $dataList
            );

            $preparedFrcDataList[$frcId] = array_combine($dataCodeList, $dataList);
        }

        return $preparedFrcDataList;
    }

    /**
     * Возвращает массив значений для вычислений.
     *
     * @param array[] $frcDataList
     *
     * @return array
     */
    protected function getFrcValueList(array $frcDataList): array {
        $frcValueList = array();

        /**
         * @var int $frcId
         * @var Data[] $dataList
         */
        foreach ($frcDataList as $frcId => $dataList) {
            $requiredDataList = array();
            foreach ($this->requiredParameterCodeList as $parameterCode) {
                $data = $dataList[$parameterCode];
                if (!isset($data)) {
                    $requiredDataList[$parameterCode] = BaseFormula::DEFAULT_FLOAT_VALUE;
                    continue;
                }

                $requiredDataList[$parameterCode] = floatval($data->getSumInUsd());
                $this->setMaxSnapshot($data->getSnapshot());
            }

            $frcValueList[$frcId] = $requiredDataList;
        }

        return $frcValueList;
    }

    /**
     * Вычисляет и возвращает значение Sum in USD для данного отчетов.
     *
     * @param array $frcValueList
     *
     * @return float
     */
    abstract protected function calculateValue(array $frcValueList): float;

    /**
     * Создает данное отчетов с дефолтно заполненными полями (всеми, кроме обязательных, индекса, итема и суммы в долларах).
     *
     * @param float $sumInUsd - сумма в долларах.
     *
     * @return Data
     */
    protected function getDefaultDataWithSumInUsd(float $sumInUsd): Data {
        $index = ($this->parameter instanceof Index) ? $this->parameter : null;
        $item = ($this->parameter instanceof Item) ? $this->parameter : null;

        return new Data(
                BaseFormula::DEFAULT_STRING_VALUE,
                $this->dataType,
                BaseFormula::DEFAULT_NULL_VALUE,
                $index,
                $item,
                $this->frc,
                BaseFormula::DEFAULT_NULL_VALUE,
                BaseFormula::DEFAULT_NULL_VALUE,
                $sumInUsd,
                BaseFormula::DEFAULT_NULL_VALUE,
                BaseFormula::DEFAULT_NULL_VALUE,
                new DateTime(),
                BaseFormula::DEFAULT_NULL_VALUE,
                $this->getParameterCode()
        );
    }

    /**
     * Запоминает снапшот, если он больше текущего.
     *
     * @param DateTime $snapshot
     */
    private function setMaxSnapshot(DateTime $snapshot): void {
        if (isset($this->maxSnapshot) && $this->maxSnapshot > $snapshot) {
            return;
        }

        $this->maxSnapshot = $snapshot;
    }
}
