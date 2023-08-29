<?php


class GitHubCommits extends Table
{
    const LOGICAL_NAME = "ghcommits";
    const ID = "id";
    const SHA = "sha";
    const Author = "author";
    const Message = "message";
    const Repo = "repo";
    const Date = "date";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder($this->logicalName, self::ID);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::SHA, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::Author, SchemaType::$text);
        $builder->addColumn(self::Message, SchemaType::$text);
        $builder->addColumn(self::Repo, SchemaType::$text);
        $builder->addColumn(self::Date, SchemaType::$date);

        return Schema::from($builder);
    }
}

Table::register(new GitHubCommits());

class GitHubTracks extends Table
{
    const LOGICAL_NAME = "ghtracks";
    const ID = "id";
    const UserName = "username";
    const Repo = "repo";
    const Token = "token";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder($this->logicalName, self::ID);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::UserName, SchemaType::$text);
        $builder->addColumn(self::Repo, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::Token, SchemaType::$text, SCHEMA_PRIVATE);

        return Schema::from($builder);
    }
}

Table::register(new GitHubTracks());