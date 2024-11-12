<?php
namespace makarenko\fincalc\reports\control;


use makarenko\fincalc\reports\entity\IblockElement;


class IblockPropertyMapper {
    /**
     * Преобразует объект в массив.
     *
     * @param IblockElement $iblockElement - элемент инфоблока.
     *
     * @return array
     */
    public function toArray(IblockElement $iblockElement): array {
        $iblockElementFieldList = $iblockElement->getFields();
        $iblockElementFieldList['PROPERTY_VALUES'] = $iblockElement->getProperties();
        return $iblockElementFieldList;
    }
}
