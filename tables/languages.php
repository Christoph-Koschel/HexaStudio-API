<?php

class Languages extends Table
{
    const LOGICAL_NAME = "languages";
    const ID = "id";
    const NAME = "name";
    const DisplayName = "display_name";
    const Language = "language";
    const Color = "color";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder(self::LOGICAL_NAME, self::NAME);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::NAME, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::DisplayName, SchemaType::$text);
        $builder->addColumn(self::Language, SchemaType::$boolean);
        $builder->addColumn(self::Color, SchemaType::$text);

        return Schema::from($builder);
    }
}

Table::register(new Languages());