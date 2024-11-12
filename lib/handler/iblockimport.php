<?php

namespace makarenko\fincalc\handler;


use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\DateTime;

/**
 * Класс реализует функционал расширения стандартного импорта инфоблоков.
 *
 * Class IblockImport
 * @package makarenko\fincalc\handler
 */
class IblockImport {
    /** @var string Идентификатор модуля. */
    private const MODULE_ID = 'makarenko.fincalc';

    /** @var string Название файла со скриптом импорта. */
    private const IMPORT_FILENAME = 'iblock_data_import.php';

    /** @var string Код свойства инфоблока SNAPSHOT. */
    private const SNAPSHOT_PROPERTY_CODE = 'SNAPSHOT';

    /** @var string Ключ флага, сигнализирующего о том, что элемент является результатом перерасчёта. */
    private const RECALCULATION_FLAG_KEY = 'RECALCULATE_DATA';

    /** @var array Массив с информацией о свойствах инфоблоков. */
    private static $iblockPropertyMapList = array();

    /**
     * Обработчик события iblock:OnStartIblockElementAdd.
     *
     * Выполняется в том случае, если добавление элемента инфоблока происходит из импорта из CSV в административной
     * части И инфоблок, в который импортируются элементы, выбран в соответствующей настройке модуля.
     *
     * @param $elementFields
     * @throws ArgumentException
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function extendImport(&$elementFields): void {
        if (!IblockImport::isElementProcessedByImport()) {
            return;
        }

        /*
         * Осуществляем проверку того, что элемент не является результатом алгоритма перерасчёта.
         *
         * Из-за того, что факт импорта определяется по debug_backtrace, все элементы, созданные в обработчиках так же
         * считаются "импортируемыми" и функционал, реализованный ниже по коду, перебивает значения таких элементов.
         */
        if (IblockImport::isRecalculatedElement($elementFields)) {
            return;
        }

        $iblockId = $elementFields['IBLOCK_ID'];
        if (!IblockImport::isIblockNeedExtend($iblockId)) {
            return;
        }
        $propertyMap = IblockImport::getIblockProperties($iblockId);
        $needConversionPropertyIdList = IblockImport::getNeedConversionPropertyIdList();
        $elementProperties = $elementFields['PROPERTY_VALUES'];
        $elementFields['NAME'] = Encoding::convertEncodingToCurrent($elementFields['NAME']);

        foreach ($propertyMap as $propertyId => $propertyInfo) {
            $propertyValue = $elementProperties[$propertyId];

            switch ($propertyInfo['PROPERTY_TYPE']) {
                case 'E':
                    /*
                     * По переданному названию находим соответствующий элемент инфоблока и подставляем в поля
                     * импортируемого элемента полученный идентификатор.
                     *
                     * В случае, если по переданному названию не было найдено элементов, в поле будет записано null,
                     * что приведет к выводу ошибки в административной части.
                     */
                    $id = IblockImport::getElementIdByName($propertyValue ?? '', $propertyInfo['LINK_IBLOCK_ID']);
                    $elementFields['PROPERTY_VALUES'][$propertyId] = $id;
                    break;
                default:
                    break;
            }

            if ($propertyInfo['CODE'] == IblockImport::SNAPSHOT_PROPERTY_CODE) {
                $elementFields['PROPERTY_VALUES'][$propertyId] = new DateTime();
            }

            if (in_array($propertyId, $needConversionPropertyIdList)) {
                $elementFields['PROPERTY_VALUES'][$propertyId] = Encoding::convertEncodingToCurrent($propertyValue);
            }
        }
    }

    /**
     * Обработчик события iblock:OnStartIblockElementUpdate.
     *
     * Прерывает обновление элемента инфоблока путем замены его названия на пустое - эта ситуация
     * обрабатывается битриксом после выполнения данного обработчика как неприемлимая.
     *
     * Выполняется в том случае, если обновление элемента инфоблока происходит из импорта из CSV в административной
     * части И инфоблок, в который импортируются элементы, выбран в соответствующей настройке модуля.
     *
     * @param $elementFields
     *
     * @return bool
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public static function disableImportElementsUpdate(&$elementFields): bool {
        if (!IblockImport::isElementProcessedByImport()) {
            return true;
        }

        $iblockId = $elementFields['IBLOCK_ID'];
        if (!IblockImport::isIblockNeedExtend($iblockId)) {
            return true;
        }

        $elementFields['NAME'] = '';
        return false;
    }

    /**
     * Возвращает массив свойств переданного инфоблока.
     *
     * @param int $iblockId Идентификатор инфоблока.
     * @return array Массив с описанием свойств инфоблока.
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getIblockProperties(int $iblockId) {
        if (!IblockImport::$iblockPropertyMapList[$iblockId]) {
            $properties = PropertyTable::getList(array(
                    'select' => array('ID', 'PROPERTY_TYPE', 'LINK_IBLOCK_ID', 'CODE'),
                    'filter' => array('IBLOCK_ID' => $iblockId),
            ));

            $propertyMap = array();
            while ($property = $properties->fetch()) {
                $propertyMap[$property['ID']]['PROPERTY_TYPE'] = $property['PROPERTY_TYPE'];
                $propertyMap[$property['ID']]['CODE'] = $property['CODE'];
                if ($property['LINK_IBLOCK_ID']) {
                    $propertyMap[$property['ID']]['LINK_IBLOCK_ID'] = $property['LINK_IBLOCK_ID'];
                }
            }

            IblockImport::$iblockPropertyMapList[$iblockId] = $propertyMap;
        }

        return IblockImport::$iblockPropertyMapList[$iblockId];
    }

    /**
     * Возвращает идентификатор элемента инфоблока по его названию.
     *
     * @param string $name Название элемента.
     * @param int $iblockId Идентификатор инфоблока.
     * @return int|null Идентификатор найденного по названию элемента или NULL, если элемент не был найден.
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getElementIdByName(string $name, int $iblockId): ?int {
        $element = ElementTable::getList(array(
            'select' => array('ID'),
            'filter' => array(
                'NAME' => $name,
                'IBLOCK_ID' => $iblockId
            )
        ))->fetch();

        return $element['ID'] ?? null;
    }

    /**
     * Возвращает флаг того факта, что текущий элемент добавляется из стандартного импорта из административной части.
     *
     * @return bool Признак импорта элемента из административной части.
     */
    private static function isElementProcessedByImport(): bool {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $backtraceRoot = end($backtrace);
        $backtraceRootFilename = $backtraceRoot['file'];

        return strpos($backtraceRootFilename, IblockImport::IMPORT_FILENAME) !== false;
    }

    /**
     * Возвращает флаг наличия инфоблока текущего импортируемого элемента в списке доступных для расширения инфоблоков.
     *
     * @param int $iblockId Идентификатор инфоблока текущего импортируемого элемента.
     * @return bool Признак наличия переданного инфоблока в списке доступных для расширения импорта.
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    private static function isIblockNeedExtend(int $iblockId): bool {
        $extendedIblocks = Option::get(IblockImport::MODULE_ID, 'EXTEND_IBLOCK_IDS');
        $extendedIblocks = explode(',', $extendedIblocks);

        return in_array($iblockId, $extendedIblocks);
    }

    /**
     * Возвращает массив id свойств инфоблоков, которые выбраны в настройках как требующие конвертации в UTF-8 поля.
     *
     * @return array
     *
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    private static function getNeedConversionPropertyIdList(): array {
        $needConversionPropertyIds = Option::get(IblockImport::MODULE_ID, 'IMPORT_PROPERTIES_NEEDED_CONVERSION');
        $needConversionPropertyIds = explode(',', $needConversionPropertyIds);

        return $needConversionPropertyIds;
    }

    /**
     * Проверяет является ли элемент результатом алгоритма перерасчёта данных.
     * При перерасчёте данных, в полях элемента передаётся специальный ключ "RECALCULATE_DATA" - наличие его среди полей
     * элемента означает, что он не импортируется.
     *
     * @param array $element - Поля элемента.
     * @return bool
     */
    private static function isRecalculatedElement(array $element) : bool {
        return isset($element[IblockImport::RECALCULATION_FLAG_KEY]);
    }
}
