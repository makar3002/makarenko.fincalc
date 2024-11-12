<?php
namespace makarenko\fincalc\reports\control\formula;

use Exception;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\frc\Frc;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;
use makarenko\fincalc\reports\entity\parameter\Parameter;

class FormulaService {
    /** @var Parameter[] */
    private $parameterList;
    /** @var Parameter[]*/
    private $parameterCodeMap;

    /**
     * FormulaService constructor.
     *
     * @param Parameter[] $parameterList
     */
    public function __construct(array $parameterList) {
        $this->parameterList = $parameterList;
    }

    /**
     * Подготавливает массив нод данных отчетов, сгруппированных по ЦФО, для расчетов в формулах.
     *
     * @param HierarchicalDataNode[] $frcDataList
     *
     * @return HierarchicalDataNode[]
     *
     * @throws Exception - в случае обработки некорректного массива нод.
     */
    public function prepareFrcDataList(array $frcDataList): array {
        $preparedDataList = array();
        foreach ($frcDataList as $frcId => $frcData) {
            $frc = $frcData->getValue();
            if (!($frc instanceof Frc)) {
                throw new Exception('Wrong node value.');
            }

            $preparedDataList[$frcId] = $frcData->withChildNodeList(array_filter(
                    $frcData->getChildNodeList(),
                    function (Data $data) use ($frc) {
                        $parameter = $data->getIndex() ?: $data->getItem();
                        return $this->isFrcAvailableForParameter($frc, $parameter);
                    }
            ));
        }

        return $preparedDataList;
    }

    /**
     * Проверяет, можно ли расчитывать параметр для данного ЦФО.
     *
     * @param Frc $frc
     * @param Parameter|null $parameter
     *
     * @return bool
     */
    public function isFrcAvailableForParameter(Frc $frc, ?Parameter $parameter): bool {
        if (!isset($parameter)) {
            return true;
        }

        $frcId = $frc->getId();
        return in_array($frcId, $parameter->getFrcList());
    }

    /**
     * Возвращает параметр по его коду.
     *
     * @param int $code - код параметра.
     *
     * @return Parameter
     */
    public function getParameterByCode(int $code): Parameter {
        $parameterCodeMap = $this->getParameterCodeMap();
        return $parameterCodeMap[$code];
    }

    /**
     * Возвращает маппинг параметров и их кодов.
     *
     * @return Parameter[]
     */
    public function getParameterCodeMap(): array {
        if (!isset($this->parameterCodeMap)) {
            $this->parameterCodeMap = $this->initializeParameterCodeMap($this->parameterList);
        }

        return $this->parameterCodeMap;
    }


    /**
     * Инициализирует и возвращает маппинг параметров и их кодов.
     *
     * @param Parameter[] $parameterList
     *
     * @return Parameter[]
     */
    private function initializeParameterCodeMap(array $parameterList): array {
        $parameterCodeList = array_map(
        /** @var Parameter $parameter */
                function ($parameter) {
                    return $parameter->getCode();
                },
                $parameterList
        );

        return array_combine($parameterCodeList, $parameterList);
    }
}
