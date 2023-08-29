<?php

class ProjectDetails extends Table
{
    const LOGICAL_NAME = "project_details";
    const ID = "id";
    const Project = "project";
    const Language = "language";
    const Percentage = "percentage";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder(self::LOGICAL_NAME, self::Project);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::Project, SchemaType::$text);
        $builder->addColumn(self::Language, SchemaType::ref_of(Languages::LOGICAL_NAME));
        $builder->addColumn(self::Percentage, SchemaType::$double);

        return Schema::from($builder);
    }
}

Table::register(new ProjectDetails());