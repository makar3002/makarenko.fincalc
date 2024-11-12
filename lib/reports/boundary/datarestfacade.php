<?php
namespace makarenko\fincalc\reports\boundary;


use Bitrix\Main\Config\Option;
use Bitrix\Rest\RestException;
use CRestServer;
use Exception;
use makarenko\fincalc\reports\control\ChangeDataService;
use makarenko\fincalc\reports\control\data\DataMapper;
use makarenko\fincalc\reports\control\exception\DataRestException;
use makarenko\fincalc\reports\control\frc\FrcNotFoundException;
use makarenko\fincalc\reports\control\ReferenceService;
use makarenko\fincalc\reports\control\ReportService;
use makarenko\fincalc\reports\entity\data\Data;
use makarenko\fincalc\reports\entity\parameter\Index;
use makarenko\fincalc\reports\entity\parameter\Item;
use makarenko\fincalc\reports\entity\parameter\Parameter;


/**
 * Class DataRestFacade - класс для rest-методов.
 *
 * @package makarenko\fincalc\reports\boundary
 */
class DataRestFacade {
    private const CALCULATOR_ID_KEY = 'CALCULATOR_ID';

    /** @var array - массив названий полей, которые обязательны для метода fincalc.data.change. */
    private const FINCALC_DATA_CHANGE_REQUIRED_FIELD_NAME_LIST = array(
            DataMapper::NAME_FIELD_NAME,
            DataMapper::DATA_TYPE_FIELD_NAME,
            DataMapper::FRC_FIELD_NAME
    );

    /** @var ReportService - объект сервиса отчетов. */
    private static $reportService;
    /** @var ChangeDataService - объект сервиса изменений данных отчетов. */
    private static $changeDataService;
    /** @var ReferenceService - объект сервиса справочников. */
    private static $referenceService;

    /**
     * @var array - массив списков названий полей, которые требуют,
     * чтобы хотя бы одно из перечисленных в них полей было заполненно, для метода fincalc.data.change.
     */
    private const FINCALC_DATA_CHANGE_ONE_OF_REQUIRED_FIELD_NAME_LIST = array();

    /**
     * @var array - массив списков названий полей, которые требуют,
     * чтобы было заполненно не больше одного из перечисленных в них поля, для метода fincalc.data.change.
     */
    private const FINCALC_DATA_CHANGE_NOT_MORE_THAN_ONE_REQUIRED_FIELD_NAME_LIST = array(
            array(
                    DataMapper::INDEX_FIELD_NAME,
                    DataMapper::ITEM_FIELD_NAME
            )
    );

    /**
     * Используется в качестве rest-метода, на основе параметров формирует новое\изменяет старое данное отчетов.
     *
     * @param array $dataFields - массив переданных параметров выборки.
     * @param $n
     * @param CRestServer $server
     *
     * @return array
     *
     * @throws RestException
     */
    public static function change(array $dataFields, $n, CRestServer $server): array {
        try {
            $nonSetRequiredFieldName = DataRestFacade::getNonSetRequiredAdzesDataFieldName($dataFields);
            if (!is_null($nonSetRequiredFieldName)) {
                throw new DataRestException("Required field {$nonSetRequiredFieldName} not set");
            }

            $nonSetOneOfFieldNameList = DataRestFacade::getNonSetOneOfAdzesDataFieldNameList($dataFields);
            if (!is_null($nonSetOneOfFieldNameList)) {
                $nonSetOneOfFieldNames = implode(', ', $nonSetOneOfFieldNameList);
                throw new DataRestException("One of the listed fields not set: {$nonSetOneOfFieldNames}");
            }

            $moreThanOneFieldNameList = DataRestFacade::getMoreThanOneSetAdzesDataFieldNameList($dataFields);
            if (!is_null($moreThanOneFieldNameList)) {
                $moreThanOneFieldNames = implode(', ', $moreThanOneFieldNameList);
                throw new DataRestException("More than one of the listed fields is set: {$moreThanOneFieldNames}");
            }

            $reportService = DataRestFacade::getReportService();
            $searchData = DataRestFacade::getSearchDataByRestMethodFields($dataFields);
            $existsData = $reportService->getDataByData($searchData);
            if (!$existsData) {
                $newData = $searchData;
            } else {
                $newData = DataRestFacade::getNewDataFromOld($existsData, $dataFields);
            }

            $newData = $reportService->changeData($newData, true, true);
            return array(
                    DataMapper::ID_FIELD_NAME => $newData->getId(),
                    DataRestFacade::CALCULATOR_ID_KEY => Option::get('makarenko.fincalc', 'FINCALC_DATA_CALCULATOR_ID')
            );
        } catch (DataRestException $dataRestException) {
            throw new RestException(
                    $dataRestException->getMessage(),
                    400,
                    CRestServer::STATUS_WRONG_REQUEST
            );
        } catch (Exception $exception) {
            throw new RestException(
                    $exception->getMessage(),
                    500,
                    CRestServer::STATUS_INTERNAL
            );
        }
    }

    /**
     * Используется в качестве rest-метода, на основе параметров формирует новое\изменяется старое данное отчетов.
     *
     * @param array $calculatorFields - массив переданных параметров выборки.
     * @param $n
     * @param CRestServer $server
     *
     * @return array
     *
     * @throws RestException
     */
    public static function getCalculationStatus(array $calculatorFields, $n, CRestServer $server): array {
        try {
            $calculatorId = $calculatorFields[DataRestFacade::CALCULATOR_ID_KEY];
            if (empty($calculatorId)) {
                throw new DataRestException('Wrong calculator id.');
            }

            $changeService = DataRestFacade::getChangeService();
            $calculatorStatusInfo = $changeService->getCalculatorStatusById($calculatorId);
            $status = $calculatorStatusInfo[ChangeDataService::STATUS_FIELD_NAME];
            if ($status == ChangeDataService::CHANGE_STATUS_FAILURE) {
                $errorMessage = $calculatorStatusInfo[ChangeDataService::ERROR_MESSAGE_FIELD_NAME];
                return array(
                        'status' => $status,
                        'error_message' => $errorMessage
                );
            } else {
                return array(
                        'status' => $status,
                );
            }
        } catch (DataRestException $dataRestException) {
            throw new RestException(
                    $dataRestException->getMessage(),
                    400,
                    CRestServer::STATUS_WRONG_REQUEST
            );
        } catch (Exception $exception) {
            throw new RestException(
                    $exception->getMessage(),
                    500,
                    CRestServer::STATUS_INTERNAL
            );
        }
    }

    /**
     * Возвращает новое данное отчета, полученное из старого и полей нового.
     *
     * @param Data $oldData
     * @param array $newDataFields
     *
     * @return Data
     */
    private static function getNewDataFromOld(Data $oldData, array $newDataFields): Data {
        $newData = clone $oldData;
        if (array_key_exists(DataMapper::SUM_IN_USD_FIELD_NAME, $newDataFields)) {
            $newData = $newData->withSumInUsd($newDataFields[DataMapper::SUM_IN_USD_FIELD_NAME]);
        }

        if (array_key_exists(DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME, $newDataFields)) {
            $newData = $newData->withSumInOriginalCurrency($newDataFields[DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME]);
        }

        if (array_key_exists(DataMapper::ORIGINAL_CURRENCY_FIELD_NAME, $newDataFields)) {
            $newData = $newData->withOriginalCurrency($newDataFields[DataMapper::ORIGINAL_CURRENCY_FIELD_NAME]);
        }

        if (array_key_exists(DataMapper::COMMENTS_FIELD_NAME, $newDataFields)) {
            $newData = $newData->withComments($newDataFields[DataMapper::COMMENTS_FIELD_NAME]);
        }

        if (array_key_exists(DataMapper::NAME_FIELD_NAME, $newDataFields)) {
            $newData = $newData->withName($newDataFields[DataMapper::NAME_FIELD_NAME]);
        }

        return $newData;
    }

    /**
     * Формирует и возвращает данное отчета, предназначенное для поиска.
     *
     * @param array $dataFields
     *
     * @return Data
     *
     * @throws FrcNotFoundException
     * @throws DataRestException
     * @throws Exception
     */
    private static function getSearchDataByRestMethodFields(array $dataFields): Data {
        $referenceService = DataRestFacade::getReferenceService();

        $searchIndex = $referenceService->getIndexList()[$dataFields[DataMapper::INDEX_FIELD_NAME]];
        $searchItem = $referenceService->getItemList()[$dataFields[DataMapper::ITEM_FIELD_NAME]];
        if (!isset($searchIndex) && !isset($searchItem)) {
            $searchParameter = DataRestFacade::getParameterByName($dataFields[DataMapper::NAME_FIELD_NAME]);
            if (!$searchParameter) {
                throw new DataRestException('Wrong index or item name');
            } elseif ($searchParameter instanceof Index) {
                $searchIndex = $searchParameter;
            } elseif ($searchParameter instanceof Item) {
                $searchItem = $searchParameter;
            } else {
                throw new DataRestException('Unsupported parameter type');
            }
        }

        $referenceService = DataRestFacade::getReferenceService();
        $searchData = new Data(
                $dataFields[DataMapper::NAME_FIELD_NAME],
                $referenceService->getDataTypeList()[$dataFields[DataMapper::DATA_TYPE_FIELD_NAME]],
                $referenceService->getPeriodList()[$dataFields[DataMapper::PERIOD_FIELD_NAME]],
                $searchIndex,
                $searchItem,
                $referenceService->getFrcById($dataFields[DataMapper::FRC_FIELD_NAME]),
                $dataFields[DataMapper::ORIGINAL_CURRENCY_FIELD_NAME],
                $dataFields[DataMapper::SUM_IN_ORIGINAL_CURRENCY_FIELD_NAME],
                $dataFields[DataMapper::SUM_IN_USD_FIELD_NAME],
                $referenceService->getItemList()[$dataFields[DataMapper::ALLOCATION_LEVEL_FIELD_NAME]],
                $dataFields[DataMapper::COMMENTS_FIELD_NAME],
                null,
                $referenceService->getFlatFrcList()[$dataFields[DataMapper::AFFILIATED_FRC_FIELD_NAME]],
                $searchParameter->getCode()
        );

        return $searchData;
    }

    /**
     * Проверяет, заполнены ли обязательные для данного отчетов поля
     * и возвращает название первого попавшегося незаполненного поля, если такое есть.
     *
     * @param array $dataFields - информация о полях.
     *
     * @return string
     */
    private static function getNonSetRequiredAdzesDataFieldName(array $dataFields): ?string {
        foreach (DataRestFacade::FINCALC_DATA_CHANGE_REQUIRED_FIELD_NAME_LIST as $fieldName) {
            if (!isset($dataFields[$fieldName])) {
                return $fieldName;
            }
        }

        return null;
    }

    /**
     * Проверяет, заполненно ли хотя бы одно из перечисленных в соответствующих списках полей,
     * и возвращает спискок полей, из которого не задано ни одно поле, если такой есть.
     *
     * @param array $dataFields - информация о полях.
     *
     * @return array|null
     */
    private static function getNonSetOneOfAdzesDataFieldNameList(array $dataFields): ?array {
        foreach (DataRestFacade::FINCALC_DATA_CHANGE_ONE_OF_REQUIRED_FIELD_NAME_LIST as $fieldNameList) {
            $oneOfFields = array_filter($dataFields, function ($fieldName) use ($fieldNameList) {
                return in_array($fieldName, $fieldNameList);
            }, ARRAY_FILTER_USE_KEY);

            if (count($oneOfFields) == 0) {
                return $fieldNameList;
            }
        }

        return null;
    }

    /**
     * Проверяет, заполненно ли хотя бы одно из перечисленных в соответствующих списках полей,
     * и возвращает спискок полей, из которого не задано ни одно поле, если такой есть.
     *
     * @param array $dataFields - информация о полях.
     *
     * @return array|null
     */
    private static function getMoreThanOneSetAdzesDataFieldNameList(array $dataFields): ?array {
        foreach (DataRestFacade::FINCALC_DATA_CHANGE_NOT_MORE_THAN_ONE_REQUIRED_FIELD_NAME_LIST as $fieldNameList) {
            $moreThanOneFields = array_filter($dataFields, function ($fieldName) use ($fieldNameList) {
                return in_array($fieldName, $fieldNameList);
            }, ARRAY_FILTER_USE_KEY);

            if (count($moreThanOneFields) > 1) {
                return $fieldNameList;
            }
        }

        return null;
    }

    /**
     * Обработчик события onRestServiceBuildDescription модуля rest,
     * регистрирует rest-метод для изменения данных отчетов.
     *
     * @return array[][][]
     */
    public static function onRestServiceBuildDescription(): array {
        return array(
                'fincalc.data' => array(
                        'fincalc.data.change' => array(
                                'callback' => array(__CLASS__, 'change'),
                                'options' => array(),
                        ),
                        'fincalc.data.getcalculatorstatus' => array(
                                'callback' => array(__CLASS__, 'getCalculationStatus'),
                                'options' => array(),
                        ),
                )
        );
    }

    /**
     * Возвращает параметр по его названию или null, если параметра с указанным названием не существует.
     *
     * @param string $parameterName
     *
     * @return Parameter|null
     *
     * @throws Exception
     */
    public static function getParameterByName(string $parameterName): ?Parameter {
        $referenceService = DataRestFacade::getReferenceService();
        $indexList = $referenceService->getIndexList();
        foreach ($indexList as $index) {
            if ($index->getName() == $parameterName) {
                return $index;
            }
        }

        $itemList = $referenceService->getItemList();
        foreach ($itemList as $item) {
            if ($item->getName() == $parameterName) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Возвращает объект сервиса отчетов. Если он не был создан ранее, инициализирует его.
     *
     * @return ReportService
     *
     * @throws Exception
     */
    private static function getReportService(): ReportService {
        if (!isset(DataRestFacade::$reportService)) {
            DataRestFacade::$reportService = new ReportService(DataRestFacade::getReferenceService());
        }

        return DataRestFacade::$reportService;
    }

    /**
     * Возвращает объект сервиса изменений данных отчетов. Если он не был создан ранее, инициализирует его.
     *
     * @return ChangeDataService
     *
     * @throws Exception
     */
    private static function getChangeService(): ChangeDataService {
        if (!isset(DataRestFacade::$changeDataService)) {
            DataRestFacade::$changeDataService = new ChangeDataService(DataRestFacade::getReferenceService());
        }

        return DataRestFacade::$changeDataService;
    }

    /**
     * Возвращает объект сервиса отчетов. Если он не был создан ранее, инициализирует его.
     *
     * @return ReferenceService
     */
    private static function getReferenceService(): ReferenceService {
        if (!isset(DataRestFacade::$referenceService)) {
            DataRestFacade::$referenceService = new ReferenceService();
        }

        return DataRestFacade::$referenceService;
    }
}