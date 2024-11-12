<?php
namespace makarenko\fincalc\reports\control;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use CIBlockElement;
use makarenko\fincalc\reports\entity\allocation\AllocationData;
use makarenko\fincalc\reports\entity\allocation\AllocationLevelParameters;
use makarenko\fincalc\reports\entity\allocation\FrcAllocateToParameters;
use makarenko\fincalc\reports\entity\allocation\PeriodParameters;
use makarenko\fincalc\service\Report;


/**
 * Class AllocationDataRepository - репозиторий для параметров аллокации.
 * @package makarenko\fincalc\reports\control
 */
class AllocationDataRepository {
    /** @var float - значение параметра аллокации, если он отсутствует. */
    private const DEFAULT_PERCENT_VALUE = 0.0;

    /** @var int - неопределенный уровень аллокации. */
    public const ALLOCATION_LEVEL_UNDEFINED = 0;
    /** @var int - уровень аллокации affect. */
    public const ALLOCATION_LEVEL_AFFECT = 1;
    /** @var int - уровень аллокации complain. */
    public const ALLOCATION_LEVEL_COMPLAIN = 2;
    /** @var int - уровень аллокации forget. */
    public const ALLOCATION_LEVEL_FORGET = 3;
    /** @var int - уровень аллокации own expenses. */
    public const ALLOCATION_LEVEL_OWN_EXPENSES = 4;
    /** @var int - уровень аллокации amount USD. */
    public const ALLOCATION_LEVEL_AMOUNT_USD = 5;

    /** @var string[] - массив полей параметров аллокации. */
    private const ALLOCATION_PARAMETERS_NON_MULTIPLE_FIELDS = array(
            'ID',
            'PROPERTY_PERIOD',
            'PROPERTY_FRC',
            'PROPERTY_INDEX_CODE_NAME',
            'PROPERTY_SUM_IN_USD',
            'PROPERTY_DATA_TYPE',
            'PROPERTY_ALLOCATION_LEVEL'
    );

    /** @var int - код итема уровня аллокации Affect. */
    private const ALLOCATION_LEVEL_AFFECT_CODE = 90110;
    /** @var int - код итема уровня аллокации Complain. */
    private const ALLOCATION_LEVEL_COMPLAIN_CODE = 90105;
    /** @var int - код итема уровня аллокации Forget. */
    private const ALLOCATION_LEVEL_FORGET_CODE = 90101;
    /** @var int - код итема уровня аллокации Forget. */
    private const ALLOCATION_LEVEL_OWN_EXPENSES_CODE = 90100;
    /** @var int - код итема уровня аллокации Amount USD. */
    private const ALLOCATION_LEVEL_AMOUNT_USD_CODE = 91000;

    /** @var array - маппинг уровней аллокации. */
    public const ALLOCATION_LEVEL_MAP = array(
            AllocationDataRepository::ALLOCATION_LEVEL_FORGET_CODE => AllocationDataRepository::ALLOCATION_LEVEL_FORGET,
            AllocationDataRepository::ALLOCATION_LEVEL_COMPLAIN_CODE => AllocationDataRepository::ALLOCATION_LEVEL_COMPLAIN,
            AllocationDataRepository::ALLOCATION_LEVEL_AFFECT_CODE => AllocationDataRepository::ALLOCATION_LEVEL_AFFECT,
            AllocationDataRepository::ALLOCATION_LEVEL_OWN_EXPENSES_CODE => AllocationDataRepository::ALLOCATION_LEVEL_OWN_EXPENSES,
            AllocationDataRepository::ALLOCATION_LEVEL_AMOUNT_USD_CODE => AllocationDataRepository::ALLOCATION_LEVEL_AMOUNT_USD
    );

    /** @var array - массив структуры параметров аллокации. */
    private $allocationData;
    /** @var array - маппинг уровней аллокации. */
    private $allocationLevelMap;
    /** @var AllocationDataMapper - маппер данных о параметрах аллокации. */
    private $allocationDataMapper;

    /**
     * AllocationDataRepository constructor.
     */
    public function __construct() {
        $this->allocationDataMapper = new AllocationDataMapper($this->getAllocationParametersIblockId());
        $this->allocationLevelMap = $this->getAllocationLevelMap();
        $this->allocationData = $this->getAllocationData();
    }

    /**
     * Возращает параметры аллокации, если существуют, иначе - параметры со значениями 0
     * (подразумевается, что отсутствие параметра говорит о том, что он равен 0).
     *
     * @param int $period - id периода.
     * @param int $frc - id ЦФО, к которому относятся параметры аллокации.
     * @param int $allocationLevel - уровень аллокации.
     * @param int $dataType - id типа данных.
     *
     * @return AllocationData
     */
    public function findParameter(
            int $period,
            int $frc,
            int $allocationLevel,
            int $dataType
    ): AllocationData {
        try {
            /** @var PeriodParameters|null $periodParameters */
            $periodParameters = $this->allocationData[$period];
            if ($periodParameters == null) {
                throw new AllocationParameterNotFoundException();
            }

            /** @var FrcAllocateToParameters|null $frcAllocateToParameters */
            $frcAllocateToParameters = $periodParameters->getFrcAllocateToParameterList()[$frc];
            if ($frcAllocateToParameters == null) {
                throw new AllocationParameterNotFoundException();
            }

            /** @var AllocationLevelParameters|null $allocationLevelParameters */
            $allocationLevelParameters = $frcAllocateToParameters->getAllocationLevelParameterList()[$allocationLevel];
            if ($allocationLevelParameters == null) {
                throw new AllocationParameterNotFoundException();
            }

            /** @var AllocationData|null $allocationParameter */
            $allocationData = $allocationLevelParameters->getAllocationParameterList()[$dataType];
            if ($allocationData == null) {
                throw new AllocationParameterNotFoundException();
            }
        } catch (AllocationParameterNotFoundException $objectNotFoundException) {
            $allocationData = new AllocationData(
                    AllocationDataRepository::DEFAULT_PERCENT_VALUE,
                    AllocationDataRepository::DEFAULT_PERCENT_VALUE,
                    AllocationDataRepository::DEFAULT_PERCENT_VALUE
            );
        }


        return $allocationData;
    }

    /**
     * Добавляет данные параметров аллокации (в БД попадают только те, которые не изменились).
     *
     * @param int $period - id периода.
     * @param int $frc - id ЦФО, к которому относятся параметры аллокации.
     * @param int $allocationLevel - уровень аллокации.
     * @param int $dataType - id типа данных.
     * @param AllocationData $allocationData - данные параметров аллокации.
     */
    public function addParameter(
            int $period,
            int $frc,
            int $allocationLevel,
            int $dataType,
            AllocationData $allocationData
    ): void {
        $allocationParameterList = $this->allocationDataMapper->unmapAllocationData($allocationData);
        $currentAllocationData = $this->findParameter($period, $frc, $allocationLevel, $dataType);
        if ($currentAllocationData->getTotal() === $allocationData->getTotal()) {
            unset($allocationParameterList[AllocationDataMapper::TOTAL_PERCENT_INDEX_CODE]);
        }
        if ($currentAllocationData->getTake() === $allocationData->getTake()) {
            unset($allocationParameterList[AllocationDataMapper::TAKE_PERCENT_INDEX_CODE]);
        }
        if ($currentAllocationData->getTax() === $allocationData->getTax()) {
            unset($allocationParameterList[AllocationDataMapper::TAX_PERCENT_INDEX_CODE]);
        }

        foreach ($allocationParameterList as $allocationParameterCode => $allocationParameter) {
            $indexName = $allocationParameter['INDEX_NAME'];
            $value = $allocationParameter['VALUE'];

            $this->addAllocationParameterToDb(Loc::getMessage('ALLOCATION_PARAMETER_' . $allocationParameterCode . '_TITLE'), $period, $frc, $allocationLevel, $dataType, $indexName, $value);
        }

        /** @var PeriodParameters $periodParameters */
        $periodParameters = $this->allocationData[$period];
        if (!$periodParameters) {
            $this->allocationData[$period] = $this->createEmptyPeriodParameters(
                    $period,
                    $frc,
                    $allocationLevel,
                    $dataType,
                    $allocationData
            );
            return;
        }

        $frcAllocateToParametersList = $periodParameters->getFrcAllocateToParameterList();
        /** @var FrcAllocateToParameters $frcAllocateToParameters */
        $frcAllocateToParameters = $frcAllocateToParametersList[$frc];
        if (!$frcAllocateToParameters) {
            $this->allocationData[$period] = $periodParameters->addFrcAllocateToParameter(
                    $this->createEmptyFrcAllocateToParameters(
                            $frc,
                            $allocationLevel,
                            $dataType,
                            $allocationData
                    )
            );
            return;
        }

        $allocationLevelParametersList = $frcAllocateToParameters->getAllocationLevelParameterList();
        /** @var AllocationLevelParameters $allocationLevelParameters */
        $allocationLevelParameters = $allocationLevelParametersList[$allocationLevel];
        if (!$allocationLevelParameters) {
            $this->allocationData[$period] = $periodParameters->addFrcAllocateToParameter(
                    $frcAllocateToParameters->addAllocationLevelParameters(
                            $this->createEmptyAllocationLevelParameters(
                                    $allocationLevel,
                                    $dataType,
                                    $allocationData
                            )
                    )
            );
            return;
        }

        $this->allocationData[$period] = $periodParameters->addFrcAllocateToParameter(
                $frcAllocateToParameters->addAllocationLevelParameters(
                        $allocationLevelParameters->addAllocationParameter(
                                $dataType,
                                $allocationData
                        )
                )
        );
    }

    /**
     * Возвращает объект сущности PeriodParameters с одним объектом AllocationData внутри.
     *
     * @param int $period - id периода.
     * @param int $frc - id ЦФО, к которому относятся параметры аллокации.
     * @param int $allocationLevel - уровень аллокации.
     * @param int $dataType - id типа данных.
     * @param AllocationData $allocationData - данные параметров аллокации.
     *
     * @return PeriodParameters
     */
    private function createEmptyPeriodParameters(
            int $period,
            int $frc,
            int $allocationLevel,
            int $dataType,
            AllocationData $allocationData
    ): PeriodParameters {
        return new PeriodParameters(
                $period,
                array(
                        $frc => $this->createEmptyFrcAllocateToParameters(
                                $frc,
                                $allocationLevel,
                                $dataType,
                                $allocationData
                        )
                )
        );
    }

    /**
     * Возвращает объект сущности FrcAllocateToParameters с одним объектом AllocationData внутри.
     *
     * @param int $frc - id ЦФО, к которому относятся параметры аллокации.
     * @param int $allocationLevel - уровень аллокации.
     * @param int $dataType - id типа данных.
     * @param AllocationData $allocationData - данные параметров аллокации.
     *
     * @return FrcAllocateToParameters
     */
    private function createEmptyFrcAllocateToParameters(
            int $frc,
            int $allocationLevel,
            int $dataType,
            AllocationData $allocationData
    ): FrcAllocateToParameters {
        return new FrcAllocateToParameters(
                $frc,
                array(
                        $allocationLevel => $this->createEmptyAllocationLevelParameters(
                                $allocationLevel,
                                $dataType,
                                $allocationData
                        )
                )
        );
    }

    /**
     * Возвращает объект сущности AllocationLevelParameters с одним объектом AllocationData внутри.
     *
     * @param int $allocationLevel - уровень аллокации.
     * @param int $dataType - id типа данных.
     * @param AllocationData $allocationData - данные параметров аллокации.
     *
     * @return AllocationLevelParameters
     */
    private function createEmptyAllocationLevelParameters(
            int $allocationLevel,
            int $dataType,
            AllocationData $allocationData
    ): AllocationLevelParameters {
        return new AllocationLevelParameters(
                $allocationLevel,
                array(
                        $dataType => $allocationData
                )
        );
    }

    /**
     * Возвращает массив со структурой параметров аллокации.
     *
     * @return array
     */
    public function getAllocationData(): array {
        if (isset($this->allocationData)) {
            return $this->allocationData;
        }

        $this->allocationData = $this->initializeAllocationData();
        return $this->allocationData;
    }

    /**
     * Возвращает проинициализированный массив со структурой параметров аллокации.
     *
     * @return array
     */
    private function initializeAllocationData(): array {
        $allocationParametersList = $this->getAllocationParametersList();

        $allocationData = array();
        foreach ($allocationParametersList as $parameterPeriod => $periodAllocationParameterList) {
            $periodAllocationParameterStructure = array();
            foreach ($periodAllocationParameterList as $parameterFrcAllocateTo => $frcAllocateToParameterList) {
                $frcAllocateToParameterStructure = array();
                foreach ($frcAllocateToParameterList as $parameterAllocationLevel => $allocationLevelParameterList) {
                    $allocationLevelParameterStructure = array();
                    foreach ($allocationLevelParameterList as $parameterFrcAllocateFrom => $frcAllocateFromParameterList) {
                        $allocationLevelParameterStructure[$parameterFrcAllocateFrom] = $this->createAllocationParameterFromDbInfo($frcAllocateFromParameterList);
                    }

                    $frcAllocateToParameterStructure[$parameterAllocationLevel] = new AllocationLevelParameters(
                            intval($parameterAllocationLevel),
                            $allocationLevelParameterStructure
                    );
                }

                $periodAllocationParameterStructure[$parameterFrcAllocateTo] = new FrcAllocateToParameters(
                        intval($parameterFrcAllocateTo),
                        $frcAllocateToParameterStructure
                );
            }

            $allocationData[$parameterPeriod] = new PeriodParameters(
                    intval($parameterPeriod),
                    $periodAllocationParameterStructure
            );
        }

        return $allocationData;
    }

    /**
     * Возвращает массив данных о параметрах аллокации из БД.
     *
     * @return array
     */
    private function getAllocationParametersList(): array {
        $selectFieldList = array_merge(
                array('IBLOCK_ID'),
                AllocationDataRepository::ALLOCATION_PARAMETERS_NON_MULTIPLE_FIELDS
        );

        $allocationParametersResult = CIBlockElement::GetList(
                array('PROPERTY_SNAPSHOT' => 'DESC'),
                array(
                        '=IBLOCK_ID' => $this->getAllocationParametersIblockId(),
                        'PROPERTY_INDEX_CODE_NAME' => array_keys($this->allocationDataMapper->getIndexNameMap())
                ),
                false,
                array(),
                $selectFieldList
        );

        $allocationParameterStructure = array();
        while ($allocationParameterElement = $allocationParametersResult->GetNextElement()) {
            $allocationParameter = $allocationParameterElement->GetFields();

            $parameterPeriod = intval($allocationParameter['PROPERTY_PERIOD_VALUE']);
            $parameterFrc = intval($allocationParameter['PROPERTY_FRC_VALUE']);
            $allocationLevelId = $allocationParameter['PROPERTY_ALLOCATION_LEVEL_VALUE'];
            $parameterAllocationLevel = intval($this->allocationLevelMap[$allocationLevelId] ?: AllocationDataRepository::ALLOCATION_LEVEL_UNDEFINED);
            $parameterDataType = intval($allocationParameter['PROPERTY_DATA_TYPE_ENUM_ID']);
            $parameterIndexName = intval($allocationParameter['PROPERTY_INDEX_CODE_NAME_VALUE']);
            $parameterValue = floatval($allocationParameter['PROPERTY_SUM_IN_USD_VALUE']);

            $allocationParameterStructure[$parameterPeriod][$parameterFrc][$parameterAllocationLevel][$parameterDataType][] = array(
                    'INDEX_NAME' => $parameterIndexName,
                    'VALUE' => $parameterValue
            );
        }

        return $allocationParameterStructure;
    }

    /**
     * Создает объект сущности данных аллокации по данным из БД.
     *
     * @param array $allocationParametersInfo - массив с данными аллокации.
     *
     * @return AllocationData
     */
    private function createAllocationParameterFromDbInfo(array $allocationParametersInfo): AllocationData {
        return $this->allocationDataMapper->mapAllocationData($allocationParametersInfo);
    }

    /**
     * Возвращает маппинг уровней аллокации.
     *
     * @return array
     */
    public function getAllocationLevelMap(): array {
        if (isset($this->allocationLevelMap)) {
            return $this->allocationLevelMap;
        }

        $this->allocationLevelMap = $this->initializeAllocationLevelMap();
        return $this->allocationLevelMap;
    }

    /**
     * Возвращает проинициализированный маппинг уровней аллокации.
     *
     * @return array
     */
    private function initializeAllocationLevelMap(): array {
        $allocationLevelProperty = \CIBlockProperty::GetList(
                array(),
                array(
                        'IBLOCK_ID' => $this->getAllocationParametersIblockId(),
                        'CODE' => 'ALLOCATION_LEVEL'
                )
        )->Fetch();

        $allocationLevelIblockId = $allocationLevelProperty['LINK_IBLOCK_ID'];
        $allocationLevelElementResult = CIBlockElement::GetList(
                array('DATE_CHANGE' => 'DESC'),
                array('IBLOCK_ID' => $allocationLevelIblockId),
                false,
                array(),
                array('ID', 'PROPERTY_KOD_STATI')
        );

        $allocationLevelMap = array();
        while($allocationLevelElement = $allocationLevelElementResult->Fetch()) {
            $allocationLevelCode = $allocationLevelElement['PROPERTY_KOD_STATI_VALUE'];
            $allocationLevel = AllocationDataRepository::ALLOCATION_LEVEL_MAP[$allocationLevelCode];

            $isAllocationLevel = isset($allocationLevel);
            $isAllocationLevelAlreadySet = in_array($allocationLevel, $allocationLevelMap);
            if (!$isAllocationLevel && $isAllocationLevelAlreadySet) {
                continue;
            }

            $allocationLevelId = $allocationLevelElement['ID'];
            $allocationLevelMap[$allocationLevelId] = $allocationLevel;
        }

        return $allocationLevelMap;
    }

    /**
     * Добавляет данные параметра аллокации в БД.
     *
     * @param string $name - название параметра.
     * @param int $period - id период.
     * @param int $frc - id ЦФО.
     * @param int $allocationLevel - id уровня аллокации.
     * @param int $dataType - id типа данных.
     * @param int $indexName - id индекса (параметры аллокации имеют только индекс).
     * @param float $value - значение SUM_IN_USD параметра.
     */
    private function addAllocationParameterToDb(
            string $name,
            int $period,
            int $frc,
            int $allocationLevel,
            int $dataType,
            int $indexName,
            float $value
    ): void {
        $propertyValues = array(
                'PERIOD' => $period,
                'FRC' => $frc,
                'ALLOCATION_LEVEL' => array_search($allocationLevel, $this->allocationLevelMap),
                'DATA_TYPE' => $dataType,
                'INDEX_CODE_NAME' => $indexName,
                'SUM_IN_USD' => $value,
                'SNAPSHOT' => new DateTime()
        );

        Report::getInstance()->addElementToDataList($name, $propertyValues);
    }

    /**
     * Возвращает id инфоблока с данными о параметрах аллокации.
     *
     * @return int
     *
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    private function getAllocationParametersIblockId(): int {
        return intval(Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID'));
    }
}
