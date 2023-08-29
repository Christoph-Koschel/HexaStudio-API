<?php


class Resource extends Table
{
    const LOGICAL_NAME = "resource";
    const ID = "id";
    const Name = "name";
    const URL = "url";

    const Data = "data";


    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder($this->logicalName, self::Name);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::Name, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::URL, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::Data, SchemaType::$mediumBlob, SCHEMA_PRIVATE);

        return Schema::from($builder);
    }
}

Table::register(new Resource());