<?php
namespace makarenko\fincalc\reports\control;

use Exception;
use makarenko\fincalc\reports\entity\data\Data;

class DataHistoryService {
    private const NAME_FIELD_NAME = 'NAME';
    private const DATA_TYPE_FIELD_NAME = 'DATA_TYPE';
    private const PERIOD_ID_FIELD_NAME = 'PERIOD_ID';
    private const FRC_ID_FIELD_NAME = 'FRC_ID';
    private const INDEX_ID_FIELD_NAME = 'INDEX_ID';
    private const ITEM_ID_FIELD_NAME = 'ITEM_ID';
    private const ORIGINAL_CURRENCY_FIELD_NAME = 'ORIGINAL_CURRENCY';
    private const SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME = 'SUM_IN_ORIGINAL_CURRENCY';
    private const SUM_IN_USD_FIELD_NAME = 'SUM_IN_USD';
    private const ALLOCATION_LEVEL_ID_FIELD_NAME = 'ALLOCATION_LEVEL_ID';
    private const SNAPSHOT_FIELD_NAME = 'SNAPSHOT';
    private const AFFILIATED_FRC_FIELD_NAME = 'AFFILIATED_FRC';

    /**
     * Архивирует данное отчетов в историческую таблицу.
     *
     * @param Data $data
     *
     * @throws Exception
     */
    public function archive(Data $data) {
        $preparedData = $this->prepareDataForArchive($data);
        try {
            $result = DataHistoryTable::add($preparedData);
        } catch (Exception $exception) {
            throw new Exception('Could not archive data.', 0, $exception);
        }
    }

    private function prepareDataForArchive(Data $data): array {
        $period = $data->getPeriod();
        $index = $data->getIndex();
        $item = $data->getItem();
        $allocationLevel = $data->getAllocationLevel();
        $affiliatedFrc = $data->getAffiliatedFrc();
        $currency = $data->getOriginalCurrency();
        $originalCurrency = $currency ? $currency->originalCurrency->getId() : null;

        return array(
                DataHistoryService::NAME_FIELD_NAME => $data->getName(),
                DataHistoryService::DATA_TYPE_FIELD_NAME => $data->getDataType()->getId(),
                DataHistoryService::PERIOD_ID_FIELD_NAME => $this->getDataBindedEntityid($period),
                DataHistoryService::FRC_ID_FIELD_NAME => $data->getFrc()->getId(),
                DataHistoryService::INDEX_ID_FIELD_NAME => $this->getDataBindedEntityid($index),
                DataHistoryService::ITEM_ID_FIELD_NAME => $this->getDataBindedEntityid($item),
                DataHistoryService::ORIGINAL_CURRENCY_FIELD_NAME => $originalCurrency,
                DataHistoryService::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME => $data->getSumInOriginalCurrency(),
                DataHistoryService::SUM_IN_USD_FIELD_NAME => $data->getSumInUsd(),
                DataHistoryService::ALLOCATION_LEVEL_ID_FIELD_NAME => $this->getDataBindedEntityid($allocationLevel),
                DataHistoryService::SNAPSHOT_FIELD_NAME => $data->getSnapshot(),
                DataHistoryService::AFFILIATED_FRC_FIELD_NAME => $this->getDataBindedEntityid($affiliatedFrc)
        );
    }

    private function getDataBindedEntityid($bindedEntity) {
        if (!$bindedEntity) {
            return null;
        }

        return $bindedEntity->getId();
    }
}