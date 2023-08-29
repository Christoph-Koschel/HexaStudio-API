<?php

class Token extends Table
{
    const LOGICAL_NAME = "tokens";
    const ID = "id";
    const Token = "token";
    const Table = "table";
    const Read = "read";
    const Write = "write";
    const Delete = "delete";


    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder($this->logicalName, self::ID);

        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::Token, SchemaType::$text, SCHEMA_PRIVATE);
        $builder->addColumn(self::Table, SchemaType::$text, SCHEMA_PRIVATE);
        $builder->addColumn(self::Read, SchemaType::$boolean, SCHEMA_PRIVATE);
        $builder->addColumn(self::Write, SchemaType::$boolean, SCHEMA_PRIVATE);
        $builder->addColumn(self::Delete, SchemaType::$boolean, SCHEMA_PRIVATE);

        return Schema::from($builder);
    }
}

function table_can_x(string $token, string $table, string $logicalName): bool
{
    $fe = new FilterQuery([Token::Read, Token::Write, Token::Delete], Token::LOGICAL_NAME);
    $fe
        ->addCriteria(Token::Table, "=", sql_str($table))
        ->and()
        ->addCriteria(Token::Token, "=", sql_str($token));

    $table = Table::getTable(Token::LOGICAL_NAME);
    $res = $table->fetch($fe);
    if ($res->count() == 0) {
        return false;
    }

    return $res->get(0)->get($logicalName);
}

function table_can_write(string $token, string $table): bool
{
    return table_can_x($token, $table, Token::Write);
}

function table_can_read(string $token, string $table): bool
{
    return table_can_x($token, $table, Token::Read);
}

function table_can_delete(string $token, string $table): bool
{
    return table_can_x($token, $table, Token::Delete);
}

Table::register(new Token());