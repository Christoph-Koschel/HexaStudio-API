<?php

class ProjectTracker extends Table
{
    const LOGICAL_NAME = "project_tracker";
    const ID = "id";
    const GITHUB = "github";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder(self::LOGICAL_NAME, self::ID);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::GITHUB, SchemaType::$text, SCHEMA_UNIQUE);
        return Schema::from($builder);
    }
}

Table::register(new ProjectTracker());