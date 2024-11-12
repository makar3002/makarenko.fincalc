<?php
namespace makarenko\fincalc\reports\control;


use CIBlockProperty;
use CIBlockPropertyEnum;
use Exception;
use makarenko\fincalc\reports\entity\ListPropertyValue;


/**
 * Class ListPropertyValueRepository - репозиторий для значений списочных полей.
 *
 * @package makarenko\fincalc\reports\control
 */
class ListPropertyValueRepository {
    /** @var string - название поля "id значения" в свойствах инфоблока. */
    private const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "id инфоблока" в свойствах инфоблока. */
    private const IBLOCK_ID_FIELD_NAME = 'IBLOCK_ID';
    /** @var string - название поля "id инфоблока" в свойствах инфоблока. */
    private const PROPERTY_ID_FIELD_NAME = 'PROPERTY_ID';
    /** @var string - название поля "код" в свойствах инфоблока. */
    private const CODE_FIELD_NAME = 'CODE';

    /** @var array - хранилище данных для любых переданных списков полей свойства. */
    protected $valueData;
    /** @var int - id инфоблока. */
    protected $listPropertyIblockId;
    /** @var string - код списочного свойства. */
    protected $listPropertyCode;
    /** @var int - id списочного свойства. */
    protected $listPropertyId;

    /**
     * IblockElementRepository constructor.
     *
     * @param int $listPropertyIblockId
     * @param string $listPropertyCode
     */
    public function __construct(int $listPropertyIblockId, string $listPropertyCode) {
        $this->listPropertyIblockId = $listPropertyIblockId;
        $this->listPropertyCode = $listPropertyCode;
    }

    /**
     * Добавляет в БД значение списочного свойства и возвращает его ID.
     *
     * @param array $listPropertyValueInfo - информация о значении списочного свойства.
     *
     * @return int
     *
     * @throws Exception - если добавить не получилось.
     */
    public function add(array $listPropertyValueInfo): int {
        $propertyId = $this->getListPropertyId();
        unset($listPropertyValueInfo[ListPropertyValueRepository::ID_FIELD_NAME]);
        $listPropertyValueInfo[ListPropertyValueRepository::PROPERTY_ID_FIELD_NAME] = $propertyId;

        $propertyValuesObject = new CIBlockPropertyEnum();
        $valueId = intval($propertyValuesObject->Add($listPropertyValueInfo));
        if ($valueId <= 0) {
            throw new Exception('Could not add property value');
        }

        return $valueId;
    }

    /**
     * Обновляет в БД значение списочного свойства и возвращает его ID.
     *
     * @param array $listPropertyValueInfo - информация о значении списочного свойства.
     *
     * @return int
     *
     * @throws Exception - если обновить не получилось.
     */
    public function update(array $listPropertyValueInfo): int {
        $listPropertyValueId = intval($listPropertyValueInfo[ListPropertyValueRepository::ID_FIELD_NAME]);
        $listPropertyValueInfo[ListPropertyValueRepository::PROPERTY_ID_FIELD_NAME] = $this->getListPropertyId();

        $propertyValuesObject = new CIBlockPropertyEnum();
        if (!$propertyValuesObject->Update($listPropertyValueId, $listPropertyValueInfo)) {
            throw new Exception('Could not update property value');
        }

        return $listPropertyValueId;
    }

    /**
     * Возвращает список значений списочного свойства.
     *
     * @return ListPropertyValue[]
     */
    public function getListPropertyValuesData(): array {
        if (!isset($this->valueData)) {
            $this->valueData = $this->initializeListPropertyValuesData();
        }

        return $this->valueData;
    }

    /**
     * Инициализирует и возвращает список значений списочного свойства.
     *
     * @return ListPropertyValue[]
     */
    private function initializeListPropertyValuesData(): array {
        $listPropertyValueList = $this->getListPropertyValueList();

        $listPropertyValueData = array();
        foreach ($listPropertyValueList as $valueId => $value) {
            $listPropertyValueData[$valueId] = new ListPropertyValue(
                    $value['FIELDS']
            );
        }

        return $listPropertyValueData;
    }

    /**
     * Возвращает список необработанных значений списочного свойства.
     *
     * @return array[]
     */
    private function getListPropertyValueList(): array {
        $listPropertyValuesResult = CIBlockPropertyEnum::GetList(
                array(ListPropertyValueRepository::ID_FIELD_NAME),
                array(
                        ListPropertyValueRepository::IBLOCK_ID_FIELD_NAME => $this->listPropertyIblockId,
                        ListPropertyValueRepository::CODE_FIELD_NAME => $this->listPropertyCode
                )
        );

        $listPropertyValuesList = array();
        while ($listPropertyValue = $listPropertyValuesResult->Fetch()) {
            $listPropertyValueId = $listPropertyValue[ListPropertyValueRepository::ID_FIELD_NAME];
            $preparedListPropertyValue = array(
                    'FIELDS' => $listPropertyValue
            );

            $listPropertyValuesList[$listPropertyValueId] = $preparedListPropertyValue;
        }

        return $listPropertyValuesList;
    }

    /**
     * Возвращает ID свойства.
     *
     * @return int
     */
    private function getListPropertyId(): int {
        if (!isset($this->listPropertyId)) {
            $property = CIBlockProperty::GetList(
                    array(),
                    array(
                            ListPropertyValueRepository::IBLOCK_ID_FIELD_NAME => $this->listPropertyIblockId,
                            ListPropertyValueRepository::CODE_FIELD_NAME => $this->listPropertyCode
                    )
            )->Fetch();

            $this->listPropertyId = intval($property[ListPropertyValueRepository::ID_FIELD_NAME]);
        }

        return $this->listPropertyId;
    }
}
