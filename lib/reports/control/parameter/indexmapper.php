<?php
namespace makarenko\fincalc\reports\control\parameter;

use Exception;
use makarenko\fincalc\reports\control\IblockPropertyMapper;
use makarenko\fincalc\reports\entity\IblockElement;
use makarenko\fincalc\reports\entity\parameter\Index;

/**
 * Class IndexMapper - маппер для индексов. Работает только в сторону преобразований данных из БД в сущности.
 *
 * @package makarenko\fincalc\reports\control\parameter
 */
class IndexMapper {
    /** @var string - название поля "id" для индексов. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для индексов. */
    private const NAME_FIELD_NAME = 'NAME';
    /** @var string - название поля "Код" для индексов. */
    private const CODE_FIELD_NAME = 'INDEX_CODE';
    /** @var string - название поля "Активность" для индексов. */
    private const ACTIVE_FIELD_NAME = 'ACTIVITY';
    /** @var string - название поля "Единица изменений" для индексов. */
    private const UNIT_FIELD_NAME = 'UNIT';
    /** @var string - название поля "Источник" для индексов. */
    private const SOURCE_FIELD_NAME = 'INDEX_SOURCE';
    /** @var string - название поля "Тип" для индексов. */
    private const TYPE_FIELD_NAME = 'INDEX_TYPE';
    /** @var string - название поля "Типы отчетов" для индексов. */
    private const REPORT_TYPE_FIELD_NAME = 'REPORT_TYPE';
    /** @var string - название поля "ЦФО" для индексов. */
    private const FRC_FIELD_NAME = 'FRC';

    /** @var string[] - список полей индексов. */
    public const INDEX_FIELD_NAMES = array(
            IndexMapper::ID_FIELD_NAME,
            IndexMapper::NAME_FIELD_NAME
    );

    /** @var string[] - список свойств индексов. */
    public const INDEX_PROPERTY_NAMES = array(
            IndexMapper::CODE_FIELD_NAME,
            IndexMapper::ACTIVE_FIELD_NAME,
            IndexMapper::UNIT_FIELD_NAME,
            IndexMapper::SOURCE_FIELD_NAME,
            IndexMapper::FRC_FIELD_NAME,
            IndexMapper::TYPE_FIELD_NAME,
            IndexMapper::REPORT_TYPE_FIELD_NAME
    );

    /** @var IblockPropertyMapper - маппер свойств инфоблоков. */
    private $propertyMapper;

    /**
     * IndexMapper constructor.
     *
     * @param IblockPropertyMapper|null $propertyMapper
     */
    public function __construct(?IblockPropertyMapper $propertyMapper = null) {
        $this->propertyMapper = $propertyMapper ?: new IblockPropertyMapper();
    }

    /**
     * Преобразует массив данных из БД в объект индекса и возвращает его.
     *
     * @param IblockElement $notPreparedIndex - объект неподготовленного индекса.
     *
     * @return Index
     *
     * @throws Exception
     */
    public function mapIndex(IblockElement $notPreparedIndex): Index {
        $indexFields = $notPreparedIndex->getFields();
        $indexProperties = $notPreparedIndex->getProperties();

        return new Index(
                intval($indexFields[IndexMapper::ID_FIELD_NAME]),
                strval($indexFields[IndexMapper::NAME_FIELD_NAME]),
                intval($indexProperties[IndexMapper::CODE_FIELD_NAME]),
                $indexProperties[IndexMapper::FRC_FIELD_NAME],
                intval($indexProperties[IndexMapper::ACTIVE_FIELD_NAME]),
                $indexProperties[IndexMapper::TYPE_FIELD_NAME],
                $indexProperties[IndexMapper::REPORT_TYPE_FIELD_NAME],
                intval($indexProperties[IndexMapper::UNIT_FIELD_NAME]),
                intval($indexProperties[IndexMapper::SOURCE_FIELD_NAME])
        );
    }
}
