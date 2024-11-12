<?php
namespace makarenko\fincalc\reports\control;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use CIBlockElement;
use CIBlockResult;
use Exception;


/**
 * Class DataMigrateService
 *
 * @package makarenko\fincalc\reports\control
 */
class DataMigrateService {
    /** @var string - id инфоблока Data for  Reports. */
    private $dataIblockid;
    /** @var CIBlockResult - источник */
    private $dataResult;
    /** @var array */
    private $dataStructure = array();

    /**
     * DataMigrateService constructor.
     *
     * @throws Exception
     */
    public function __construct() {
        if (!Loader::includeModule('iblock')) {
            throw new Exception('Iblock module not installed.');
        }

        $this->dataIblockid = Option::get('makarenko.fincalc', 'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID');
        $this->dataResult = $this->getDataResult();
    }

    /**
     * Удаляет элемент инфоблока по его Id.
     *
     * @param int $dataId
     *
     * @throws Exception
     */
    public function deleteDataById(int $dataId): void {
        $isDeleted = CIBlockElement::Delete($dataId);
        if (!$isDeleted) {
            throw new Exception('Could not delete data with id ' . $dataId);
        }
    }

    /**
     * Возвращает массив данных из инфоблока неактуального данного отчетов.
     *
     * @return array|null
     */
    public function getOldDataElement(): ?array {
        while ($data = $this->dataResult->Fetch()) {
            if ($this->isOldElement($data)) {
                return $data;
            }

            $this->setActualData($data);
        }

        $this->dataResult = $this->getDataResult();
        return null;
    }

    /**
     * @return CIBlockResult
     */
    private function getDataResult(): CIBlockResult {
        $dataResult = CIBlockElement::GetList(
                array('PROPERTY_SNAPSHOT' => 'DESC'),
                array(
                        '=IBLOCK_ID' => $this->dataIblockid,
                ),
                false,
                false,
                array(
                        'ID',
                        'PROPERTY_DATA_TYPE',
                        'PROPERTY_PERIOD',
                        'PROPERTY_FRC',
                        'PROPERTY_INDEX_CODE_NAME',
                        'PROPERTY_ITEM_NAME',
                        'PROPERTY_ALLOCATION_LEVEL',
                        'PROPERTY_AFFILIATED_FRC'
                )
        );

        return $dataResult;
    }

    private function isOldElement(array $data): bool {
        return isset($this->dataStructure[
                intval($data['PROPERTY_DATA_TYPE_ENUM_ID'])
        ][
                intval($data['PROPERTY_PERIOD_VALUE'])
        ][
                intval($data['PROPERTY_ALLOCATION_LEVEL_VALUE'])
        ][
                intval($data['PROPERTY_AFFILIATED_FRC_VALUE'])
        ][
                intval($data['PROPERTY_FRC_VALUE'])
        ][
                intval($data['PROPERTY_INDEX_CODE_NAME_VALUE']) . '|' . intval($data['PROPERTY_ITEM_NAME_VALUE'])
        ]);
    }


    private function setActualData(array $data): void {
        $this->dataStructure[
                intval($data['PROPERTY_DATA_TYPE_ENUM_ID'])
        ][
                intval($data['PROPERTY_PERIOD_VALUE'])
        ][
                intval($data['PROPERTY_ALLOCATION_LEVEL_VALUE'])
        ][
                intval($data['PROPERTY_AFFILIATED_FRC_VALUE'])
        ][
                intval($data['PROPERTY_FRC_VALUE'])
        ][
                intval($data['PROPERTY_INDEX_CODE_NAME_VALUE']) . '|' . intval($data['PROPERTY_ITEM_NAME_VALUE'])
        ] = $data;
    }
}