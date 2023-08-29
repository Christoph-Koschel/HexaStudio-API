<?php

abstract class Table
{
    /**
     * @var Table[]
     */
    private static array $tables = array();
    private static PDO $db;

    public static function init(string $file): void
    {
        if (!$settings = parse_ini_file($file, TRUE)) {
            print 'Unable to open ' . $file;
            exit(1);
        }

        $dns = $settings['DATABASE']['driver'] .
            ':host=' . $settings['DATABASE']['host'] .
            ((!empty($settings['DATABASE']['port'])) ? (';port=' . $settings['DATABASE']['port']) : '') .
            ';dbname=' . $settings['DATABASE']['schema'];

        self::$db = new PDO($dns, $settings["DATABASE"]["username"], $settings["DATABASE"]["password"]);

        $res = self::$db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = '" . RegisterTable::LOGICAL_NAME . "' AND TABLE_SCHEMA LIKE '" . $settings['DATABASE']['schema'] . "'");
        if ($res->rowCount() == 0) {
            log_info("CORE:TABLE", "Create Table" . RegisterTable::LOGICAL_NAME);
            $register = self::getTable(RegisterTable::LOGICAL_NAME);
            $sql = $register->getSchema()->toSql();
            self::$db->query($sql);

            foreach (self::$tables as $table) {
                if ($table->logicalName == RegisterTable::LOGICAL_NAME) {
                    continue;
                }
                log_info("CORE:TABLE", "Create Table $table->logicalName");

                $sql = $table->getSchema()->toSql();
                $schema = $register->getSchema()->logicalName;
                $id = RegisterTable::ENTITY;
                $sql .= "INSERT INTO $schema ($id) VALUES ('$table->logicalName');";
                self::$db->query($sql);
            }
        } else {
            $register = self::getTable(RegisterTable::LOGICAL_NAME);

            foreach (self::$tables as $table) {
                if ($table->logicalName == RegisterTable::LOGICAL_NAME) {
                    continue;
                }

                $qe = new FilterQuery([RegisterTable::ENTITY], RegisterTable::LOGICAL_NAME);
                $qe->addCriteria(RegisterTable::ENTITY, "=", sql_str($table->logicalName));
                $res = $register->fetch($qe);
                if ($res->count() == 0) {
                    log_warning("CORE:TABLE", "Table $table->logicalName does not exists");
                    log_info("CORE:TABLE", "Create Table $table->logicalName");
                    $sql = $table->getSchema()->toSql();
                    $schema = $register->getSchema()->logicalName;
                    $id = RegisterTable::ENTITY;
                    $sql .= "INSERT INTO $schema ($id) VALUES ('$table->logicalName')";
                    self::$db->query($sql);
                }
            }
        }
    }

    public static function getTable(string $logicalName): Table|false
    {
        foreach (self::$tables as $table) {
            if ($table->logicalName == $logicalName) {
                return $table;
            }
        }

        return false;
    }

    public static function register(Table $table): void
    {
        foreach (self::$tables as $toTest) {
            if ($toTest->logicalName == $table->logicalName) {
                throw new Exception("Plugin with the name '$table->logicalName' already exists");
            }
        }

        self::$tables[] = $table;
    }

    /**
     * @return TableInformation[]
     */
    public static function listTables(): array
    {
        $infos = array();
        foreach (self::$tables as $table) {
            $infos[] = new TableInformation($table);
        }

        return $infos;
    }

    public string $logicalName;

    protected function __construct(string $logicalName)
    {
        $this->logicalName = $logicalName;
    }

    public abstract function getSchema(): Schema;

    public function exists(): bool
    {
        if ($this->logicalName == RegisterTable::LOGICAL_NAME) {
            throw new Exception("Cannot check the existence of the existence contains table");
        }

        $registers = self::getTable(RegisterTable::LOGICAL_NAME);
        $fq = new FilterQuery([RegisterTable::ENTITY], RegisterTable::LOGICAL_NAME);
        $fq->addCriteria(RegisterTable::ENTITY, "=", $this->logicalName);
        $res = $registers->fetch($fq);
        return $res->count() != 0;
    }

    public function fetch(FetchQuery $query): QueryResult
    {
        $sql = $query->toSql();
        log_info("SERVICE::SQL", "Execute sql: '$sql'");
        $schema = $this->getSchema();
        $res = self::$db->query($sql);
        if (!$res) {
            throw new Exception("Failed to execute ");
        }

        $rows = array();
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        $columns = $query->getColumns();
        foreach ($columns as $column) {
            $type = $schema->getColumn($column)->type;
            if ($type->equals(SchemaType::$reference)) {
                $rows = $this->fetchReference($rows, $column, $type);
            } else if ($type->equals(SchemaType::$group)) {
                $rows = $this->fetchGroup($rows, $column, $type);
            } else if ($type->equals(SchemaType::$boolean)) {
                $rows = $this->fetchBoolean($rows, $column, $type);
            } else if ($type->equals(SchemaType::$float) || $type->equals(SchemaType::$double) || $type->equals(SchemaType::$decimal)) {
                $rows = $this->fetchFloat($rows, $column, $type);
            }
        }

        return new QueryResult($rows, $this);
    }

    private function fetchReference(array $rows, string $column, SchemaType $type): array
    {
        for ($i = 0; $i < count($rows); $i++) {
            if (empty($rows[$i][$column])) {
                continue;
            }
            $tableName = (string)$type->value;
            $id = $rows[$i][$column];

            $subTable = Table::getTable($tableName);
            $subSchema = $subTable->getSchema();
            $filter = new FilterQuery(false, $tableName);
            $filter->addCriteria($subSchema->referenceKey, "=", sql_str($id));
            $subRes = $subTable->fetch($filter);
            $rows[$i][$column] = array();
            foreach ($subSchema->getColumns() as $subColumn) {
                if (($subColumn->attributes & SCHEMA_PRIVATE) != SCHEMA_PRIVATE) {
                    $rows[$i][$column][$subColumn->logicalName] = $subRes->get(0)->get($subColumn->logicalName);
                }
            }
        }
        return $rows;
    }

    private function fetchGroup(array $rows, string $column, SchemaType $type): array
    {
        for ($i = 0; $i < count($rows); $i++) {
            if (empty($rows[$i][$column])) {
                continue;
            }

            $tableName = (string)$type->value;
            $id = $rows[$i][$column];
            $subTable = Table::getTable($tableName);
            $subSchema = $subTable->getSchema();
            $filter = new FilterQuery(false, $tableName);
            $filter->addCriteria($subSchema->referenceKey, "=", sql_str($id));
            $subRes = $subTable->fetch($filter);
            $rows[$i][$column] = array();
            for ($k = 0; $k < $subRes->count(); $k++) {
                $rows[$i][$column][$k] = array();
                foreach ($subSchema->getColumns() as $subColumn) {
                    if (($subColumn->attributes & SCHEMA_PRIVATE) != SCHEMA_PRIVATE) {
                        $rows[$i][$column][$k][$subColumn->logicalName] = $subRes->get($k)->get($subColumn->logicalName);
                    }
                }
            }
        }

        return $rows;
    }

    private function fetchBoolean(array $rows, string $column, SchemaType $type): array
    {
        for ($i = 0; $i < count($rows); $i++) {
            if (empty($rows[$i][$column])) {
                $rows[$i][$column] = false;
                continue;
            }

            $rows[$i][$column] = $rows[$i][$column] != 0;
        }

        return $rows;
    }

    private function fetchFloat(array $rows, string $column, SchemaType $type): array
    {
        for ($i = 0; $i < count($rows); $i++) {
            if (empty($rows[$i][$column])) {
                $rows[$i][$column] = false;
                continue;
            }

            $rows[$i][$column] = (double)$rows[$i][$column];
        }

        return $rows;
    }

    public function insert(InsertQuery $query): bool
    {
        $sql = $query->toSql();
        log_info("SERVICE::SQL", "Execute sql: '$sql'");
        return self::$db->query($sql) != false;
    }
}

class TableInformation
{
    public readonly Schema $schema;
    public readonly string $logicalName;
    public readonly string $displayName;

    public function __construct(Table $table)
    {
        $this->schema = $table->getSchema();
        $this->logicalName = $table->logicalName;
        $this->displayName = $table::class;
    }
}


class RegisterTable extends Table
{
    const LOGICAL_NAME = "registrations";
    const ID = "id";
    const ENTITY = "entity";

    public function __construct()
    {
        parent::__construct(self::LOGICAL_NAME);
    }

    public function getSchema(): Schema
    {
        $builder = new SchemaBuilder($this->logicalName, self::ID);

        $builder->addColumn(self::ID, SchemaType::$int, SCHEMA_PRIMARY_KEY | SCHEMA_AUTO_INCREMENT | SCHEMA_PRIVATE);
        $builder->addColumn(self::ENTITY, SchemaType::$text);

        return Schema::from($builder);
    }
}

Table::register(new RegisterTable());