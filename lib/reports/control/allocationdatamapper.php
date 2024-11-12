<?php
namespace makarenko\fincalc\reports\control;


use CIBlockElement;
use makarenko\fincalc\reports\entity\allocation\AllocationData;


/**
 * Class AllocationDataMapper - маппер для параметров аллокации.
 * @package makarenko\fincalc\reports\control
 */
class AllocationDataMapper {
    /** @var int - код индекса Total (%) */
    public const TOTAL_PERCENT_INDEX_CODE = 40080;
    /** @var int - код индекса Take (%) */
    public const TAKE_PERCENT_INDEX_CODE = 40040;
    /** @var int - код индекса Tax (%) */
    public const TAX_PERCENT_INDEX_CODE = 40060;
    /** @var int[] список кодов индексов для параметров аллокации. */
    public const INDEX_NAME_LIST = array(
            AllocationDataMapper::TOTAL_PERCENT_INDEX_CODE,
            AllocationDataMapper::TAKE_PERCENT_INDEX_CODE,
            AllocationDataMapper::TAX_PERCENT_INDEX_CODE
    );

    /** @var array - маппинг id и кодов индексов для параметров аллокации. */
    private $allocationParameterIndexNameMap;
    /** @var array - id инфоблока для параметров аллокации. */
    private $allocationParametersIblockId;

    /**
     * AllocationDataMapper constructor.
     *
     * @param int $allocationParametersIblockId - id инфоблока с параметрами аллокации.
     */
    public function __construct(int $allocationParametersIblockId) {
        $this->allocationParametersIblockId = $allocationParametersIblockId;
        $this->allocationParameterIndexNameMap = $this->initializeIndexNameMap();
    }

    /**
     * Преобразует массив данных из БД в объект параметра аллокации и возвращает его.
     *
     * @param array $allocationParameterList - массив данных о параметрах аллокации.
     *
     * @return AllocationData
     */
    public function mapAllocationData(array $allocationParameterList): AllocationData {
        $allocationParameterFields = array();
        foreach ($allocationParameterList as $allocationParameterInfo) {
            $parameterIndexName = $allocationParameterInfo['INDEX_NAME'];
            $parameterIndexCode = $this->allocationParameterIndexNameMap[$parameterIndexName];

            $isAllocationParameterIndexCode = isset($parameterIndexCode);
            $isParameterIndexCodeAlreadySet = isset($allocationParameterFields[$parameterIndexCode]);
            if (!$isAllocationParameterIndexCode || $isParameterIndexCodeAlreadySet) {
                continue;
            }

            $parameterValue = $allocationParameterInfo['VALUE'];
            $allocationParameterFields[$parameterIndexCode] = $parameterValue;
        }

        return new AllocationData(
                floatval($allocationParameterFields[AllocationDataMapper::TOTAL_PERCENT_INDEX_CODE]),
                floatval($allocationParameterFields[AllocationDataMapper::TAKE_PERCENT_INDEX_CODE]),
                floatval($allocationParameterFields[AllocationDataMapper::TAX_PERCENT_INDEX_CODE])
        );
    }

    /**
     * Преобразует объект параметра аллокации в массив и возвращает его.
     *
     * @param AllocationData $allocationData - параметры аллокации.
     *
     * @return array[]
     */
    public function unmapAllocationData(AllocationData $allocationData): array {
        $allocationParameterIndexCodeMap = array_flip($this->allocationParameterIndexNameMap);
        $allocationParameterList = array(
                AllocationDataMapper::TOTAL_PERCENT_INDEX_CODE => array(
                        'INDEX_NAME' => $allocationParameterIndexCodeMap[AllocationDataMapper::TOTAL_PERCENT_INDEX_CODE],
                        'VALUE' => $allocationData->getTotal()
                ),
                AllocationDataMapper::TAKE_PERCENT_INDEX_CODE => array(
                        'INDEX_NAME' => $allocationParameterIndexCodeMap[AllocationDataMapper::TAKE_PERCENT_INDEX_CODE],
                        'VALUE' => $allocationData->getTake()
                ),
                AllocationDataMapper::TAX_PERCENT_INDEX_CODE => array(
                        'INDEX_NAME' => $allocationParameterIndexCodeMap[AllocationDataMapper::TAX_PERCENT_INDEX_CODE],
                        'VALUE' => $allocationData->getTax()
                )
        );

        return $allocationParameterList;
    }

    /**
     * Возвращает маппинг индексов параметров аллокации.
     *
     * @return array
     */
    public function getIndexNameMap(): array {
        if (isset($this->allocationParameterIndexNameMap)) {
            return $this->allocationParameterIndexNameMap;
        }

        $this->allocationParameterIndexNameMap = $this->initializeIndexNameMap();
        return $this->allocationParameterIndexNameMap;
    }

    /**
     * Возвращает проинициализированный маппинг индексов параметров аллокации.
     *
     * @return array
     */
    private function initializeIndexNameMap() {
        $indexNameProperty = \CIBlockProperty::GetList(
                array('LINK_IBLOCK_ID'),
                array(
                        'IBLOCK_ID' => $this->allocationParametersIblockId,
                        'CODE' => 'INDEX_CODE_NAME'
                )
        )->Fetch();

        $indexNameIblockId = $indexNameProperty['LINK_IBLOCK_ID'];
        $indexNameElementResult = CIBlockElement::GetList(
                array('DATE_CHANGE' => 'DESC'),
                array('IBLOCK_ID' => $indexNameIblockId),
                false,
                array(),
                array('ID', 'PROPERTY_INDEX_CODE')
        );

        $indexNameMap = array();
        while($indexNameElement = $indexNameElementResult->Fetch()) {
            $indexCode = $indexNameElement['PROPERTY_INDEX_CODE_VALUE'];
            $indexNameId = $indexNameElement['ID'];

            $isAllocationParameterIndexCode = in_array($indexCode, AllocationDataMapper::INDEX_NAME_LIST);
            $isIndexNameCodeAlreadySet = isset($indexNameMap[$indexNameId]);

            if (!$isAllocationParameterIndexCode || $isIndexNameCodeAlreadySet) {
                continue;
            }

            $indexNameMap[$indexNameId] = $indexCode;
        }

        return $indexNameMap;
    }
}
