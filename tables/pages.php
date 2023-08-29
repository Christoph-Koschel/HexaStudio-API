<?php

class Pages extends Table
{
    const LOGICAL_NAME = "pages";
    const ID = "id";
    const Name = "name";
    const UrlPath = "url_path";
    const Keywords = "keywords";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder(self::LOGICAL_NAME, self::ID);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::Name, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::UrlPath, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::Keywords, SchemaType::$text);

        return Schema::from($builder);
    }
}

Table::register(new Pages());