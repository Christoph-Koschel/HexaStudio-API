<?php

class Account extends Table
{
    const LOGICAL_NAME = "account";
    const ID = "id";
    const Username = "username";
    const Password = "password";
    const Token = "token";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder(self::LOGICAL_NAME, self::Username);
        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::Username, SchemaType::$text, SCHEMA_UNIQUE);
        $builder->addColumn(self::Password, SchemaType::$text);
        $builder->addColumn(self::Token, SchemaType::$text);

        return Schema::from($builder);
    }
}

Table::register(new Account());