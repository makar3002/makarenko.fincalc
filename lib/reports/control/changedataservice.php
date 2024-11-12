<?php
namespace makarenko\fincalc\reports\control;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Exception;
use makarenko\fincalc\reports\entity\data\ChangeData;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\util\EventLog;

class ChangeDataService {
    private const ID_FIELD_NAME = 'ID';
    private const DATA_TYPE_FIELD_NAME = 'DATA_TYPE';
    private const PERIOD_ID_FIELD_NAME = 'PERIOD_ID';
    private const FRC_ID_FIELD_NAME = 'FRC_ID';
    private const INDEX_ID_FIELD_NAME = 'INDEX_ID';
    private const ITEM_ID_FIELD_NAME = 'ITEM_ID';
    private const ALLOCATION_LEVEL_ID_FIELD_NAME = 'ALLOCATION_LEVEL_ID';
    private const AFFILIATED_FRC_FIELD_NAME = 'AFFILIATED_FRC';
    private const SNAPSHOT_FIELD_NAME = 'SNAPSHOT';
    public const STATUS_FIELD_NAME = 'STATUS';
    private const CALCULATOR_ID_FIELD_NAME = 'CALCULATOR_ID';
    public const ERROR_MESSAGE_FIELD_NAME = 'ERROR_MESSAGE';

    public const SAVE_CHANGE_KEY = 'SAVE_CHANGE';
    public const CHANGE_STATUS_NEW = 'NEW';
    public const CHANGE_STATUS_PENDING = 'PENDING';
    public const CHANGE_STATUS_SUCCESS = 'SUCCESS';
    public const CHANGE_STATUS_FAILURE = 'FAILURE';
    public const CHANGE_STATUS_UNDEFINED = 'UNDEFINED';
    private const CHANGE_STATUS_LIST = array(
            ChangeDataService::CHANGE_STATUS_NEW,
            ChangeDataService::CHANGE_STATUS_PENDING,
            ChangeDataService::CHANGE_STATUS_SUCCESS,
            ChangeDataService::CHANGE_STATUS_FAILURE,
    );

    /** @var ReferenceService */
    private $referenceService;

    public function __construct(?ReferenceService $referenceService = null) {
        $this->referenceService = $referenceService ?? new ReferenceService();
    }

    /**
     * @param DataContainer $dataContainer
     *
     * @return ChangeData[]
     *
     * @throws Exception
     */
    public function saveDataChange(Data $data): ChangeData {
        try {
            $period = $data->getPeriod();
            $index = $data->getIndex();
            $item = $data->getItem();
            $affiliatedFrc = $data->getAffiliatedFrc();
            $allocationLevel = $data->getAllocationLevel();
            $snapshot = new DateTime();
            $status = ChangeDataService::CHANGE_STATUS_NEW;
            $calculatorId = Option::get('makarenko.fincalc', 'FINCALC_DATA_CALCULATOR_ID');
            $errorMessage = '';
            $changeDataResult = ChangeDataTable::add(array(
                    ChangeDataService::DATA_TYPE_FIELD_NAME => $data->getDataType()->getId(),
                    ChangeDataService::PERIOD_ID_FIELD_NAME => $period ? $period->getId() : null,
                    ChangeDataService::FRC_ID_FIELD_NAME => $data->getFrc()->getId(),
                    ChangeDataService::INDEX_ID_FIELD_NAME => $index ? $index->getId() : null,
                    ChangeDataService::ITEM_ID_FIELD_NAME => $item ? $item->getId() : null,
                    ChangeDataService::ALLOCATION_LEVEL_ID_FIELD_NAME => $allocationLevel ? $allocationLevel->getId() : null,
                    ChangeDataService::AFFILIATED_FRC_FIELD_NAME => $affiliatedFrc ? $affiliatedFrc->getId() : null,
                    ChangeDataService::SNAPSHOT_FIELD_NAME => $snapshot,
                    ChangeDataService::STATUS_FIELD_NAME => $status,
                    ChangeDataService::CALCULATOR_ID_FIELD_NAME => $calculatorId,
                    ChangeDataService::ERROR_MESSAGE_FIELD_NAME => $errorMessage
            ));

            $errorMessages = $changeDataResult->getErrorMessages();

            if (empty($errorMessages)) {
                return new ChangeData(
                        $changeDataResult->getId(),
                        $data,
                        $snapshot,
                        $status,
                        $calculatorId,
                        $errorMessage
                );
            }

            throw new Exception(reset($errorMessages));
        } catch (Exception $exception) {
            EventLog::add($exception);
            throw new Exception('Could not add fincalc data change info.', 0, $exception);
        }
    }

    /**
     * Возвращает информацию о статусе расчета по его ID согласно следующей логике:
     *  - если все изменения с указанным id расчета помечены статусом NEW, то у расчета статус NEW;
     *  - если есть хотя бы одно изменение со статусом PENDING, то статус PENDING;
     *  - если есть хотя бы одно изменение со статусом FAILURE, то статус FAILURE;
     *  - если все измения имеют статус SUCCESS, то статус SUCCESS;
     *  - если нет ни одного изменения с указанным id, то статус UNDEFINED.
     *
     * @param string $calculatorId
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCalculatorStatusById(string $calculatorId): array {
        try {
            $changeDataResult = ChangeDataTable::getList(array(
                    'select' => array(
                            ChangeDataService::STATUS_FIELD_NAME,
                            ChangeDataService::ERROR_MESSAGE_FIELD_NAME
                    ),
                    'filter' => array(
                            '=' . ChangeDataService::CALCULATOR_ID_FIELD_NAME => $calculatorId
                    ),
                    'group' => array(ChangeDataService::STATUS_FIELD_NAME)
            ));

            $changeDataGroupList = array();
            while ($changeDataGroup = $changeDataResult->fetch()) {
                $status = $changeDataGroup[ChangeDataService::STATUS_FIELD_NAME];
                $changeDataGroupList[$status] = $changeDataGroup;
            }

            if (count($changeDataGroupList) == 0) {
                return array(
                        ChangeDataService::STATUS_FIELD_NAME => ChangeDataService::CHANGE_STATUS_UNDEFINED
                );
            }

            if (isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_FAILURE])) {
                return $changeDataGroupList[ChangeDataService::CHANGE_STATUS_FAILURE];
            }

            if (isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_PENDING])) {
                return $changeDataGroupList[ChangeDataService::CHANGE_STATUS_PENDING];
            }

            if (count($changeDataGroupList) == 1) {
                if (
                        isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_SUCCESS])
                        && !isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_NEW])
                ) {
                    return $changeDataGroupList[ChangeDataService::CHANGE_STATUS_SUCCESS];
                } elseif (
                        !isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_SUCCESS])
                        && isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_NEW])
                ) {
                    return $changeDataGroupList[ChangeDataService::CHANGE_STATUS_NEW];
                }
            } elseif (
                    count($changeDataGroupList) == 2
                    && isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_SUCCESS])
                    && isset($changeDataGroupList[ChangeDataService::CHANGE_STATUS_NEW])
            ) {
                return array(
                        ChangeDataService::STATUS_FIELD_NAME => ChangeDataService::CHANGE_STATUS_PENDING
                );
            }

            throw new Exception('Unexpected calculator instanse.');
        } catch (Exception $exception) {
            EventLog::add($exception);
            throw new Exception('Could not get calculator status info.', 0, $exception);
        }
    }

    /**
     * @param string|null $calculatorId
     *
     * @return ChangeData[]
     *
     * @throws Exception
     */
    public function getCalculationReadyDataList(?string $calculatorId = null): array {
        try {
            $filter = array(
                    '@' . ChangeDataService::STATUS_FIELD_NAME => array(
                            ChangeDataService::CHANGE_STATUS_NEW,
                            ChangeDataService::CHANGE_STATUS_FAILURE
                    )
            );

            if (!is_null($calculatorId)) {
                $filter['=' . ChangeDataService::CALCULATOR_ID_FIELD_NAME] = $calculatorId;
            }

            $changeDataResult = ChangeDataTable::getList(array(
                    'select' => array('*'),
                    'filter' => $filter,
                    'order' => array(ChangeDataService::SNAPSHOT_FIELD_NAME => 'ASC')
            ));
        } catch (Exception $exception) {
            throw new Exception('Could not get fincalc data change info.');
        }

        $changeDataList = array();
        while ($changeDataInfo = $changeDataResult->fetch()) {
            $changeData = $this->prepareChangeData($changeDataInfo);
            $changeDataList[$changeData->getId()] = $changeData;
        }

        return $changeDataList;
    }

    /**
     * Обновляет статус изменения данного отчетов.
     *
     * @param ChangeData $changeData
     * @param string $status
     * @param string|null $errorMessage
     *
     * @throws Exception
     */
    public function updateChangeStatus(
        ChangeData $changeData,
        string     $status,
        ?string    $errorMessage = null
    ): void {
        try {
            if (!$this->isValidStatus($status)) {
                throw new Exception('Wrong status.');
            }

            ChangeDataTable::update($changeData->getId(), array(
                    ChangeDataService::STATUS_FIELD_NAME => $status,
                    ChangeDataService::ERROR_MESSAGE_FIELD_NAME => $errorMessage
            ));
        } catch (Exception $exception) {
            throw new Exception('Could not update fincalc change data.', 0, $exception);
        }
    }

    /**
     * Подготавливает и возвращает изменение данного отчетов.
     *
     * @param array $changeDataInfo
     *
     * @return ChangeData
     *
     * @throws Exception
     */
    private function prepareChangeData(
            array $changeDataInfo
    ): ChangeData {
        $data = $this->getDataByChangeInfo($changeDataInfo);
        return new ChangeData(
                intval($changeDataInfo[ChangeDataService::ID_FIELD_NAME]),
                $data,
                $changeDataInfo[ChangeDataService::SNAPSHOT_FIELD_NAME],
                $changeDataInfo[ChangeDataService::STATUS_FIELD_NAME],
                $changeDataInfo[ChangeDataService::CALCULATOR_ID_FIELD_NAME],
                $changeDataInfo[ChangeDataService::ERROR_MESSAGE_FIELD_NAME] ?: null
        );
    }

    /**
     * Возвращает данное отчетов для поиска по информации о его изменении.
     *
     * @param array $changeDataInfo
     *
     * @return Data
     *
     * @throws Exception
     */
    private function getDataByChangeInfo(array $changeDataInfo): Data {
        return new Data(
                '',
                $this->referenceService->getDataTypeList()[$changeDataInfo[ChangeDataService::DATA_TYPE_FIELD_NAME]],
                $this->referenceService->getPeriodList()[$changeDataInfo[ChangeDataService::PERIOD_ID_FIELD_NAME]],
                $this->referenceService->getIndexList()[$changeDataInfo[ChangeDataService::INDEX_ID_FIELD_NAME]],
                $this->referenceService->getItemList()[$changeDataInfo[ChangeDataService::ITEM_ID_FIELD_NAME]],
                $this->referenceService->getFlatFrcList()[$changeDataInfo[ChangeDataService::FRC_ID_FIELD_NAME]],
                null,
                null,
                null,
                $this->referenceService->getItemList()[$changeDataInfo[ChangeDataService::ALLOCATION_LEVEL_ID_FIELD_NAME]],
                null,
                null,
                $this->referenceService->getFlatFrcList()[$changeDataInfo[ChangeDataService::AFFILIATED_FRC_FIELD_NAME]],
                null
        );
    }

    private function isValidStatus(string $status): bool {
        return in_array($status, ChangeDataService::CHANGE_STATUS_LIST);
    }
}