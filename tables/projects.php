<?php

class Projects extends Table
{
    const LOGICAL_NAME = "projects";
    const ID = "id";
    const Name = "name";
    const PrimaryLanguage = "primary_language";
    const PageURL = "page_url";
    const Banner = "banner";
    const Description = "description";
    const Details = "details";
    const GitHub = "github";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder(self::LOGICAL_NAME, self::ID);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::Name, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::PrimaryLanguage, SchemaType::ref_of(Languages::LOGICAL_NAME));
        $builder->addColumn(self::PageURL, SchemaType::ref_of(Pages::LOGICAL_NAME));
        $builder->addColumn(self::Banner, SchemaType::ref_of(Resource::LOGICAL_NAME));
        $builder->addColumn(self::Description, SchemaType::ref_of(Resource::LOGICAL_NAME), SCHEMA_UNIQUE);
        $builder->addColumn(self::Details, SchemaType::group_of(ProjectDetails::LOGICAL_NAME), SCHEMA_UNIQUE);
        $builder->addColumn(self::GitHub, SchemaType::$text, SCHEMA_UNIQUE);

        return Schema::from($builder);
    }
}

Table::register(new Projects());