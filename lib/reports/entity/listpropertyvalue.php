<?php
namespace makarenko\fincalc\reports\entity;

/**
 * Class ListPropertyValue - сущность значения списочного свойства инфоблока.
 *
 * @package makarenko\fincalc\reports\entity
 */
class ListPropertyValue {
    /** @var array - данные полей элемента инфоблока. */
    protected $fields;

    /**
     * ListPropertyValue constructor.
     *
     * @param array $fields
     */
    public function __construct(
            array $fields
    ) {
        $this->fields = $fields;
    }

    /**
     * @return array - данные полей значения.
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * @param array $fields - данные полей значения.
     *
     * @return ListPropertyValue - копия объекта сущности с измененными данными полей.
     */
    public function withFields(array $fields): ListPropertyValue {
        $newListPropertyValue = clone $this;
        $newListPropertyValue->fields = $fields;
        return $newListPropertyValue;
    }
}
