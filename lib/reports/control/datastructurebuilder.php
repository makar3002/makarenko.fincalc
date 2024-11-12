<?php
namespace makarenko\fincalc\reports\control;


use Exception;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;
use makarenko\fincalc\reports\entity\HierarchicalDataValue;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;


class DataStructureBuilder {
    private $dataList;
    /** @var string[] */
    private $fieldHierarchyOrder;
    /**@var DataHierarchyConfig */
    private $hierarchyConfig;

    /**
     * DataStructureBuilder constructor.
     *
     * @param DataHierarchyConfig $hierarchyConfig
     */
    public function __construct(DataHierarchyConfig $hierarchyConfig) {
        $this->hierarchyConfig = $hierarchyConfig;
        $this->fieldHierarchyOrder = array_keys($hierarchyConfig->getConfig());
    }

    public function setDataList(array $dataList): DataStructureBuilder {
        $this->dataList = $dataList;
        return $this;
    }

    /**
     * Устанавливает конфиг иерархии структуры данных отчетов.
     *
     * @param array $fieldHierarchyOrder
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setFieldHierarchyOrder(array $fieldHierarchyOrder): DataStructureBuilder {
        $config = $this->hierarchyConfig->getConfig();
        foreach ($fieldHierarchyOrder as $fieldName) {
            if (!$config[$fieldName]) {
                throw new Exception('Config has not field ' . $fieldName);
            }
        }

        if (count($fieldHierarchyOrder) != count($config)) {
            throw new Exception('Wrong field hierarchy order');
        }

        $this->fieldHierarchyOrder = $fieldHierarchyOrder;

        return $this;
    }

    /**
     * Создает ноду с полной структурой данных отчетов.
     *
     * @return HierarchicalDataNode
     *
     * @throws Exception
     */
    public function build(): HierarchicalDataNode {
        if (!$this->isDataListSet()) {
            throw new Exception('There is no data list to build structure.');
        }

        return $this->getNodeFromList($this->dataList, $this->hierarchyConfig->getConfig());
    }

    private function getNodeFromList(
            array $dataList,
            array $fieldHierarchyOrder,
            int $level = 0,
            ?HierarchicalDataValue $nodeValue = null
    ): HierarchicalDataNode {
        if (empty($fieldHierarchyOrder)) {
            $dataNodeList = $this->getActualDataList($dataList);
        } else {
            $valueGetMethod = array_shift($fieldHierarchyOrder);
            $valueList = array_unique(array_map(function (Data $data) use ($valueGetMethod) {
                return $data->$valueGetMethod();
            }, $dataList), SORT_REGULAR);

            $dataNodeList = array();
            foreach ($valueList as $value) {
                $currentDataNodeList = array_filter(
                        $dataList,
                        function (Data $data) use ($value, $valueGetMethod) {
                            return $data->$valueGetMethod() == $value;
                        }
                );

                $dataNodeList[$this->getFieldId($value)] = $this->getNodeFromList(
                        $currentDataNodeList,
                        $fieldHierarchyOrder,
                        $level + 1,
                        $value
                );
            }
        }

        return new HierarchicalDataNode($nodeValue, $level, $dataNodeList);
    }

    private function getActualDataList(array $dataList): array {
        usort($dataList, function (Data $firstData, Data $secondData) {
            return $firstData->getSnapshot() < $secondData->getSnapshot();
        });

        $actualDataList = array();
        foreach ($dataList as $data) {
            /** @var Index|Item $parameter */
            $parameter = $data->getIndex() ?: $data->getItem();
            if (!isset($parameter)) {
                continue;
            }

            $parameterId = $parameter->getId();
            if (isset($actualDataList[$parameterId])) {
                continue;
            }

            $actualDataList[$parameterId] = $data;
        }

        return $actualDataList;
    }

    private function isDataListSet(): bool {
        return isset($this->dataList);
    }

    private function getFieldId($field): ?int {
        if (!$field) {
            return null;
        }

        return $field->getId();
    }
}
