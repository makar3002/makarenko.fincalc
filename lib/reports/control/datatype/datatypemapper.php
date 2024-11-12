<?php
namespace makarenko\fincalc\reports\control\datatype;


use Exception;
use makarenko\fincalc\reports\entity\datatype\DataType;
use makarenko\fincalc\reports\entity\ListPropertyValue;


class DataTypeMapper {
    /** @var string - название поля "id" для типов данных. */
    public const ID_FIELD_NAME = 'ID';
    /** @var string - название поля "Название" для типов данных. */
    private const NAME_FIELD_NAME = 'VALUE';

    /**
     * Преобразует массив данных из БД в объект типа данных и возвращает его.
     *
     * @param ListPropertyValue $notPreparedDataType - объект неподготовленного типа данных.
     *
     * @return DataType
     *
     * @throws Exception
     */
    public function mapDataType(ListPropertyValue $notPreparedDataType): DataType {
        $dataTypeFields = $notPreparedDataType->getFields();

        return new DataType(
                intval($dataTypeFields[DataTypeMapper::ID_FIELD_NAME]),
                strval($dataTypeFields[DataTypeMapper::NAME_FIELD_NAME])
        );
    }
}