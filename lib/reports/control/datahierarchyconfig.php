<?php
namespace makarenko\fincalc\reports\control;

use makarenko\fincalc\reports\control\data\DataMapper;

class DataHierarchyConfig {
    public const FINCALC_DATA_FIELD_METHOD_MAP = array(
            DataMapper::DATA_TYPE_FIELD_NAME => 'getDataType',
            DataMapper::PERIOD_FIELD_NAME => 'getPeriod',
            DataMapper::ALLOCATION_LEVEL_FIELD_NAME => 'getAllocationLevel',
            DataMapper::AFFILIATED_FRC_FIELD_NAME => 'getAffiliatedFrc',
            DataMapper::FRC_FIELD_NAME => 'getFrc'
    );

    private $config;

    public function __construct(array $config = array()) {
        $this->config = empty($config) ? DataHierarchyConfig::FINCALC_DATA_FIELD_METHOD_MAP : $config;
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function withConfig(array $config): DataHierarchyConfig {
        $newDataHierarchyConfig = clone $this;
        $newDataHierarchyConfig->config = $config;
        return $newDataHierarchyConfig;
    }
}
