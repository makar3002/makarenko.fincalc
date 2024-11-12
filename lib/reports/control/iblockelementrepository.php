<?php
namespace makarenko\fincalc\reports\control;


use Bitrix\Iblock\PropertyTable;
use CIBlockElement;
use Exception;
use makarenko\fincalc\reports\entity\IblockElement;


/**
 * Class IblockElementRepository - репозиторий для элементов инфоблока.
 *
 * @package makarenko\fincalc\reports\control
 */
class IblockElementRepository {
    /** @var string - название поля "id" в элементах инфоблока. */
    private const IBLOCK_ELEMENT_ID_FIELD_NAME = 'ID';
    /** @var string - название поля "id инфоблока" в элементах инфоблока. */
    private const IBLOCK_ELEMENT_IBLOCK_ID_FIELD_NAME = 'IBLOCK_ID';

    /** @var string - список полей инфоблока по-умолчанию. */
    public const IBLOCK_ELEMENT_DEFAULT_FIELD_NAMES = array(
            IblockElementRepository::IBLOCK_ELEMENT_IBLOCK_ID_FIELD_NAME
    );

    /** @var string - префикс свойств элементов инфоблоков. */
    public const PROPERTY_PREFIX = 'PROPERTY_';

    /** @var array - хранилище данных для любых переданных списков полей и свойств. */
    protected $elementData = array();
    /** @var int - id инфоблока. */
    protected $elementIblockId;

    /**
     * IblockElementRepository constructor.
     *
     * @param int $elementIblockId
     */
    public function __construct(int $elementIblockId) {
        $this->elementIblockId = $elementIblockId;
    }

    /**
     * Добавляет в БД элемент инфоблока и возвращает его ID.
     *
     * @param array $iblockElementInfo - информация об элементе инфоблоке.
     * @param bool $updateSearch
     *
     * @return int
     *
     * @throws Exception - если добавить не получилось.
     */
    public function add(array $iblockElementInfo, bool $updateSearch = true): int {
        unset($iblockElementInfo['ID']);
        $iblockElementInfo['IBLOCK_ID'] = $this->elementIblockId;

        $iblockObject = new CIBlockElement();
        $iblockElementId = intval($iblockObject->Add($iblockElementInfo, false, $updateSearch));
        if ($iblockElementId <= 0 || !empty($iblockObject->LAST_ERROR)) {
            throw new Exception($iblockObject->LAST_ERROR);
        }

        return $iblockElementId;
    }

    /**
     * Обновляет в БД элемент инфоблока и возвращает его ID.
     *
     * @param array $iblockElementInfo - информация об элементе инфоблока.
     * @param bool $updateSearch
     *
     * @return int
     *
     * @throws Exception - если обновить не получилось.
     */
    public function update(array $iblockElementInfo, bool $updateSearch = true): int {
        $iblockElementId = intval($iblockElementInfo['ID']);
        $iblockElementInfo['IBLOCK_ID'] = $this->elementIblockId;

        $iblockObject = new CIBlockElement();
        if (
                !$iblockObject->Update(
                        $iblockElementId,
                        $iblockElementInfo,
                        false,
                        $updateSearch
                )
                || !empty($iblockObject->LAST_ERROR)
        ) {
            throw new Exception($iblockObject->LAST_ERROR);
        }

        return $iblockElementId;
    }

    /**
     * Обновляет в БД поисковый индекс элементов инфоблока.
     *
     * @param array $iblockElementIdList - массив ID элементов инфоблока
     *
     * @return void
     *
     * @throws Exception - если обновить не получилось.
     */
    public function updateSearch(array $iblockElementIdList): void {
        $iblockObject = new CIBlockElement();
        foreach ($iblockElementIdList as $iblockElementId) {
            $iblockObject->UpdateSearch($iblockElementId);
        }

        if (!empty($iblockObject->LAST_ERROR)) {
            throw new Exception($iblockObject->LAST_ERROR);
        }
    }

    /**
     * Возвращает список элементов инфоблока.
     *
     * @param array $fieldNames - имена полей для select'а.
     * @param array $propertyNames - имена свойств для select'а.
     * @param array $filter - дополнительный фильтр для элементов.
     *
     * @return IblockElement[]
     *
     * @throws Exception
     */
    public function getIblockElementData(array $fieldNames, array $propertyNames, array $filter = array()): array {
        return $this->initializeIblockElementData($fieldNames, $propertyNames, $filter);
    }

    /**
     * Инициализирует и возвращает список элементов инфоблока.
     *
     * @param array $fieldNames - имена полей для select'а.
     * @param array $propertyNames - имена свойств для select'а.
     * @param array $filter - дополнительный фильтр для элементов.
     *
     * @return IblockElement[]
     *
     * @throws Exception
     */
    private function initializeIblockElementData(array $fieldNames, array $propertyNames, array $filter): array {
        $iblockElementList = $this->getIblockElementList($fieldNames, $propertyNames, $filter);

        $iblockElementData = array();
        foreach ($iblockElementList as $iblockElementId => $iblockElement) {
            $iblockElementData[$iblockElementId] = new IblockElement(
                    $iblockElement['FIELDS'],
                    $iblockElement['PROPERTIES']
            );
        }

        return $iblockElementData;
    }

    /**
     * Возвращает список необработанных данных элементов инфоблоков.
     *
     * @param array $fieldNames - имена полей для select'а.
     * @param array $propertyNames - имена свойств для select'а.
     * @param array $filter - дополнительный фильтр.
     *
     * @return array[]
     *
     * @throws Exception
     */
    private function getIblockElementList(array $fieldNames, array $propertyNames, array $filter): array {
        $fieldNames[] = IblockElementRepository::IBLOCK_ELEMENT_IBLOCK_ID_FIELD_NAME;
        $primaryFilter = array(IblockElementRepository::IBLOCK_ELEMENT_IBLOCK_ID_FIELD_NAME => $this->elementIblockId);
        $extendedFilter = $primaryFilter + $filter;
        $iblockElementResult = CIBlockElement::GetList(
                array(IblockElementRepository::IBLOCK_ELEMENT_ID_FIELD_NAME),
                $extendedFilter,
                false,
                array(),
                $fieldNames
        );

        $iblockElementList = array();
        while ($iblockElement = $iblockElementResult->Fetch()) {
            $iblockElementId = $iblockElement[IblockElementRepository::IBLOCK_ELEMENT_ID_FIELD_NAME];
            $preparedIblockElement = array(
                    'FIELDS' => $this->prepareElementFields($iblockElement, $fieldNames),
                    'PROPERTIES' => array()
            );

            $iblockElementList[$iblockElementId] = $preparedIblockElement;
        }

        $iblockElementIdList = array_keys($iblockElementList);

        $propertyNameMap = $this->getIblockPropertyNameMap($propertyNames);
        $propertyValueResult = CIBlockElement::GetPropertyValues(
                $this->elementIblockId,
                array(),
                false,
                array('ID' => $propertyNameMap)
        );
        while ($propertyValue = $propertyValueResult->Fetch()) {
            $iblockElementId = $propertyValue['IBLOCK_ELEMENT_ID'];
            if (!in_array($iblockElementId, $iblockElementIdList)) {
                continue;
            }

            $elementProperties = array();
            foreach ($propertyNameMap as $propertyName => $propertyId) {
                $elementProperties[$propertyName] = $propertyValue[$propertyId];
            }

            $iblockElementList[$iblockElementId]['PROPERTIES'] = $elementProperties;
        }

        return $iblockElementList;
    }

    /**
     * Возвращает маппиг кодов свойств инфоблока и их id.
     *
     * @param $propertyNames - список свойств.
     *
     * @return array
     *
     * @throws Exception
     */
    private function getIblockPropertyNameMap($propertyNames): array {
        try {
            $propertyResult = PropertyTable::getList(
                    array(
                            'select' => array('ID', 'CODE'),
                            'order' => array('ID'),
                            'filter' => array('@CODE' => $propertyNames, 'IBLOCK_ID' => $this->elementIblockId)
                    )
            );

            $propertyNameMap = array();
            while ($property = $propertyResult->fetch()) {
                $propertyNameMap[$property['CODE']] = $property['ID'];
            }

            return $propertyNameMap;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Возвращает уникальный для конкретного набора полей и свойств индекс для хранения массива данных с элементами инфоблоков.
     *
     * @param array $fieldNames - имена полей для select'а.
     * @param array $propertyNames - имена свойств для select'а.
     * @param array $filter - дополнительный фильтр для элементов.
     *
     * @return string
     */
    private function getIblockElementDataIndex(array $fieldNames, array $propertyNames, array $filter): string {
        $iblockFilterKeys = array_map(function ($value, $key) {
            return $key . '=' . $value;
        }, $filter, array_keys($filter));
        $iblockElementKeys = array_unique(array_merge($fieldNames, $propertyNames, $iblockFilterKeys));
        sort($iblockElementKeys);
        return implode('|', $iblockElementKeys);
    }

    /**
     * Возвращает массив полей элемента инфоблока по массиву всех полученных значений.
     *
     * @param array $element - массив всех данных элемента инфоблока.
     * @param array $fieldNames - список полей.
     *
     * @return array
     */
    private function prepareElementFields(array $element, array $fieldNames): array {
        $fields = array_filter($element, function ($fieldName) use ($fieldNames) {
            return in_array($fieldName, $fieldNames);
        }, ARRAY_FILTER_USE_KEY);

        return $fields;
    }
}
