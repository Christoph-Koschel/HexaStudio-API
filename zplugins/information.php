<?php

class Information extends Plugin
{
    const LOGICAL_NAME = "information";

    const ACTION_TABLES = "tables";
    const ACTION_TABLE = "table";
    const ACTION_ACCOUNT = "account";
    const ACTION_ACCESS_KEY = "accesskey";
    const ACTION_CREATE_ROW = "createrow";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function verify(Request $req): bool
    {
        if ($req->method == METHOD_FETCH) {
            if ($req->arguments->get("action") == self::ACTION_TABLES || $req->arguments->get("action") == self::ACTION_TABLE) {
                return table_can_read($req->token, RegisterTable::LOGICAL_NAME);
            } else if ($req->arguments->get("action") == self::ACTION_ACCOUNT) {
                return table_can_read($req->token, Account::LOGICAL_NAME);
            } else if ($req->arguments->get("action") == self::ACTION_ACCESS_KEY) {
                return table_can_read($req->token, $req->arguments->get("table"));
            }
        } else if ($req->method == METHOD_INSERT) {
            if ($req->arguments->get("action") == self::ACTION_CREATE_ROW) {
                return table_can_write($req->token, $req->arguments->get("table"));
            }
        }

        return false;
    }

    public function fetch(FetchRequest $req): void
    {
        if ($req->arguments->get("action") == self::ACTION_TABLES) {
            $this->fetchTables();
        } else if ($req->arguments->get("action") == self::ACTION_TABLE) {
            $this->fetchTable($req);
        } else if ($req->arguments->get("action") == self::ACTION_ACCOUNT) {

        } else if ($req->arguments->get("action") == self::ACTION_ACCESS_KEY) {
            $this->fetchAccessKey($req);
        }
    }

    public function update(UpdateRequest $req): void
    {
        res_send(STATUS_NOT_SUPPORT);
    }

    public function delete(DeleteRequest $req): void
    {
        res_send(STATUS_NOT_SUPPORT);
    }

    public function insert(InsertRequest $req): void
    {
        if ($req->arguments->get("action") == self::ACTION_CREATE_ROW) {
            $this->insertCreateRow($req);
        }
    }

    private function fetchTables(): void
    {
        foreach (Table::listTables() as $table) {
            res_push_attributes(array(
                "logicalName" => $table->logicalName,
                "displayName" => $table->displayName,
                "columns" => count($table->schema->getColumns())
            ));
        }
    }

    private function fetchTable(FetchRequest $req): void
    {
        foreach (Table::listTables() as $table) {
            if ($table->logicalName == $req->arguments->get("table")) {
                res_set_attribute("logicalName", $table->logicalName);
                res_set_attribute("displayName", $table->displayName);
                res_set_attribute("columns", count($table->schema->getColumns()));
                res_set_attribute("schema", $table->schema->toJSON());
            }
        }
    }

    private function fetchAccessKey(FetchRequest $req): void
    {
        $table = Table::getTable($req->arguments->get("table"));
        $key = $table->getSchema()->referenceKey;
        $query = new BulgFetchQuery([$key], $table->logicalName);
        $res = $table->fetch($query);

        $send = array();

        for ($i = 0; $i < $res->count(); $i++) {

            if (!in_array($res->get($i)->get($key), $send)) {
                $send[] = $res->get($i)->get($key);
            }
        }

        res_set_attribute("values", $send);
    }

    private function insertCreateRow(InsertRequest $req): void
    {
        $table = Table::getTable($req->arguments->get("table"));
        $columns = json_decode($req->arguments->get("columns"), true);
        $query = new BulgInsertQuery($columns, $table->logicalName);
        $schema = $table->getSchema();

        $row = array();

        foreach ($columns as $column) {
            if ($schema->getColumn($column)->type->equals(SchemaType::$blob) || $schema->getColumn($column)->type->equals(SchemaType::$mediumBlob)) {
                $row[$column] = file_get_contents($_FILES[$column]["tmp_name"]);
            } else {
                $row[$column] = $req->arguments->get($column);
            }
        }
        $query->addValue($row);
        res_set_attribute("finished", $table->insert($query));
    }
}

Plugin::register(new Information());