<?php
namespace makarenko\fincalc\reports\entity;

/**
 * Class IblockElement - сущность элемента инфоблока.
 *
 * @package makarenko\fincalc\reports\entity
 */
class IblockElement {
    /** @var array - данные полей элемента инфоблока. */
    protected $fields;
    /** @var array - данные свойств элемента инфоблока. */
    protected $properties;

    /**
     * IblockElement constructor.
     *
     * @param array $fields
     * @param array $properties
     */
    public function __construct(
            array $fields,
            array $properties
    ) {
        $this->fields = $fields;
        $this->properties = $properties;
    }

    /**
     * @return array - данные полей инфоблока.
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * @return array - данные свойств инфоблока.
     */
    public function getProperties(): array {
        return $this->properties;
    }

    /**
     * @param array $fields - данные полей инфоблока.
     * @return IblockElement - копия объекта сущности с измененными данными полей.
     */
    public function withFields(array $fields): IblockElement {
        $newNotPreparedParameter = clone $this;
        $newNotPreparedParameter->fields = $fields;
        return $newNotPreparedParameter;
    }

    /**
     * @param array $properties - данные свойств инфоблока.
     * @return IblockElement - копия объекта сущности с измененными данными полей.
     */
    public function withProperties(array $properties): IblockElement {
        $newNotPreparedParameter = clone $this;
        $newNotPreparedParameter->properties = $properties;
        return $newNotPreparedParameter;
    }
}
