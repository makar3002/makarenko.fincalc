<?php
namespace makarenko\fincalc\reports\control;


use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\HierarchicalDataNode;


class DataContainer {
    private const DEFAULT_CHANGE_COUNT_VALUE = 1;

    /** @var HierarchicalDataNode - корневая нода структуры данных отчетов. */
    private $dataNode;
    /** @var int - счетчик изменений в структуре данных отчетов. */
    private $changeCount = DataContainer::DEFAULT_CHANGE_COUNT_VALUE;
    /** @var DataHierarchyConfig - конфигурация для иерархии данных отчетов. */
    private $hierarchyConfig;

    /**
     * DataContainer constructor.
     *
     * @param HierarchicalDataNode $dataNode
     * @param DataHierarchyConfig $hierarchyConfig
     */
    public function __construct(HierarchicalDataNode $dataNode, DataHierarchyConfig $hierarchyConfig) {
        $this->dataNode = $dataNode;
        $this->hierarchyConfig = $hierarchyConfig;
    }

    public function getDataNode(): HierarchicalDataNode {
        return $this->dataNode;
    }

    public function getHierarchyConfig(): DataHierarchyConfig {
        return $this->hierarchyConfig;
    }

    /**
     * Меняет конфиг иерархической структуры и соответственно перестраивает структуру.
     *
     * @param DataHierarchyConfig $newConfig
     *
     * @throws \Exception
     */
    public function changeHierarchyConfig(DataHierarchyConfig $newConfig): void {
        $dataList = $this->mapNode($this->dataNode, function ($data) {return $data;});
        $nodeBuilder = new DataStructureBuilder($newConfig);
        $nodeBuilder->setDataList($dataList);
        $this->dataNode = $nodeBuilder->build();
        $this->hierarchyConfig = $newConfig;
    }

    /**
     * Возвращает данное отчетов, выбранное по полям переданного данного отчета, определяющим уникальность данного.
     *
     * @param Data $data
     *
     * @return Data|null
     */
    public function getByData(Data $data): ?Data {
        return $this->getDataByData(
                $data,
                $this->dataNode,
                $this->hierarchyConfig->getConfig()
        );
    }

    /**
     * Возвращает данное отчетов, выбранное по полям переданного данного отчета, определяющим уникальность данного.
     *
     * @param Data $data
     * @param HierarchicalDataNode $dataNode
     * @param array $config
     *
     * @return Data|null
     */
    private function getDataByData(
        Data                 $data,
        HierarchicalDataNode $dataNode,
        array                $config
    ): ?Data {
        if (empty($config)) {
            $parameterId = $this->getFieldId($data->getIndex() ?: $data->getItem());
            return $dataNode->getChildNodeList()[$parameterId];
        }

        $getValueMethod = array_shift($config);
        $childDataNode = $dataNode->getChildNodeList()[$this->getFieldId($data->$getValueMethod())];
        if (!$childDataNode) {
            return null;
        }

        return $this->getDataByData($data, $childDataNode, $config);
    }

    public function change(Data $data): Data {
        $data = $data->withChangeOrderNumber($this->changeCount);
        $this->dataNode = $this->changeData($data, $this->dataNode, $this->hierarchyConfig->getConfig());
        $this->changeCount++;
        return $data;
    }

    private function changeData(
        Data                 $data,
        HierarchicalDataNode $dataNode,
        array                $config
    ): HierarchicalDataNode {
        if (empty($config)) {
            $parameterId = $this->getFieldId($data->getIndex() ?: $data->getItem());
            $dataNode = $dataNode->withChildNodeList(
                    array(
                            $parameterId => $data
                    ) + $dataNode->getChildNodeList()
            );

            return $dataNode;
        }

        $getValueMethod = array_shift($config);
        $childDataNode = $dataNode->getChildNodeList()[$this->getFieldId($data->$getValueMethod())];
        if (!$childDataNode) {
            $childDataNode = new HierarchicalDataNode(
                    $data->$getValueMethod(),
                    $dataNode->getLevel() + 1,
                    array()
            );
        }

        $childDataNode = $this->changeData($data, $childDataNode, $config);
        $dataNode = $dataNode->withChildNodeList(
                array(
                        $this->getFieldId($data->$getValueMethod()) => $childDataNode
                ) + $dataNode->getChildNodeList()
        );
        return $dataNode;
    }

    /**
     * @return Data[]
     */
    public function getChangedDataMap(): array {
        $isDataChangedCallback = function ($data) {
            return $this->isDataChanged($data);
        };

        $changedDataNode = $this->filter($this->dataNode, $isDataChangedCallback);
        if (!$changedDataNode) {
            return array();
        }

        $changedDataList = $this->mapNode($changedDataNode, function ($data) {return $data;});
        $changedDataMap = array_combine(
                array_map(function (Data $data) {
                    return $data->getChangeOrderNumber();
                }, $changedDataList),
                $changedDataList
        );

        ksort($changedDataMap);
        return $changedDataMap;
    }

    public function reset() {
        $resetChangeOrderNumberCallback = function ($data) {
            return $this->resetChangeOrderNumber($data);
        };

        $this->dataNode = $this->walkAroundNode(
                $this->dataNode,
                $resetChangeOrderNumberCallback
        );

        $this->changeCount = DataContainer::DEFAULT_CHANGE_COUNT_VALUE;
    }

    private function resetChangeOrderNumber(Data $data): Data {
        return $data->withChangeOrderNumber(null);
    }

    private function isDataChanged(Data $data): bool {
        return !is_null($data->getChangeOrderNumber());
    }

    /**
     * @param HierarchicalDataNode $node
     * @param callable $callback
     *
     * @return HierarchicalDataNode
     */
    private function walkAroundNode(HierarchicalDataNode $node, callable $callback): HierarchicalDataNode {
        $newNodeList = array();
        foreach ($node->getChildNodeList() as $childNodeId => $childNode) {
            if ($childNode instanceof HierarchicalDataNode) {
                $newChildNode = $this->walkAroundNode($childNode, $callback);
                $newNodeList[$childNodeId] = $newChildNode;
            } else {
                $newNodeList[$childNodeId] = $callback($childNode);
            }
        }

        return $node->withChildNodeList($newNodeList);
    }

    /**
     * @param HierarchicalDataNode $node
     * @param callable $callback
     *
     * @return HierarchicalDataNode|null
     */
    public function filter(HierarchicalDataNode $node, callable $callback): ?HierarchicalDataNode {
        $filteredNodeList = array();
        foreach ($node->getChildNodeList() as $childNode) {
            if ($childNode instanceof HierarchicalDataNode) {
                $filteredNode = $this->filter($childNode, $callback);
                if (!$filteredNode) {
                    continue;
                }

                $filteredNodeList[$this->getFieldId($filteredNode->getValue())] = $filteredNode;
            } elseif ($callback($childNode)) {
                $filteredNodeList[$this->getFieldId($childNode->getIndex() ?: $childNode->getItem())] = $childNode;
            }
        }

        return empty($filteredNodeList) ? null : $node->withChildNodeList($filteredNodeList);
    }

    /**
     * @param HierarchicalDataNode $node
     * @param callable $callback
     *
     * @return array
     */
    private function mapNode(HierarchicalDataNode $node, callable $callback): array {
        $result = array();
        foreach ($node->getChildNodeList() as $childNode) {
            if ($childNode instanceof HierarchicalDataNode) {
                $result = array_merge($result, $this->mapNode($childNode, $callback));
            } else {
                $result[] = $callback($childNode);
            }
        }

        return $result;
    }

    private function getFieldId($field): ?int {
        if (!$field) {
            return null;
        }

        return $field->getId();
    }
}
