<?php

const SCHEMA_PRIMARY_KEY = 0b1;
const SCHEMA_NULLABLE = 0b10;
const SCHEMA_AUTO_INCREMENT = 0b100;

const SCHEMA_UNIQUE = 0b1000;
const SCHEMA_PRIVATE = 0b10000;

class Schema
{
    public static function from(SchemaBuilder $builder): Schema
    {
        $sawReferenceKey = false;
        $sawPK = false;

        foreach ($builder->columns as $column) {
            if ($column->logicalName == $builder->referenceKey) {
                $sawReferenceKey = true;
            }

            if (($column->attributes & SCHEMA_PRIMARY_KEY) == SCHEMA_PRIMARY_KEY) {
                if ($sawPK) {
                    throw new Exception("Only one column can contains the primary key");
                } else {
                    $sawPK = true;
                }
            }
            if (($column->attributes & SCHEMA_AUTO_INCREMENT) == SCHEMA_AUTO_INCREMENT) {
                if (!$column->type->equals(SchemaType::$int)) {
                    throw new Exception("Only int type can have the auto increment attribute");
                }
            }
        }

        if (!$sawReferenceKey) {
            throw new Exception("Reference Key does not exists");
        }

        return new Schema($builder->logicalName, $builder->columns, $builder->referenceKey);
    }

    public readonly string $logicalName;
    public readonly string $referenceKey;

    /**
     * @var SchemaColumn[]
     */
    private readonly array $columns;

    private function __construct(string $logicalName, array $columns, string $referenceKey)
    {
        $this->logicalName = $logicalName;
        $this->referenceKey = $referenceKey;
        $this->columns = $columns;
    }

    public function getColumnsLogicalNames(): array
    {
        $map = array();
        foreach ($this->columns as $column) {
            $map[] = $column->logicalName;
        }

        return $map;
    }

    /**
     * @return SchemaColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $logicalName): SchemaColumn|false
    {
        foreach ($this->columns as $column) {
            if ($column->logicalName == $logicalName) {
                return $column;
            }
        }

        return false;
    }

    public function getType(string $logicalName): SchemaType|false
    {
        foreach ($this->columns as $column) {
            if ($column->logicalName == $logicalName) {
                return $column->type;
            }
        }

        return false;
    }

    public function getAttribute(string $logicalName): int
    {
        foreach ($this->columns as $column) {
            if ($column->logicalName == $logicalName) {
                return $column->attributes;
            }
        }
        return 0;
    }

    public function exists(string $logicalName): bool
    {
        foreach ($this->columns as $column) {
            if ($column->logicalName == $logicalName) {
                return true;
            }
        }
        return false;
    }

    public function toSql(): string
    {
        $cols = array();
        $primaryKey = "";
        foreach ($this->columns as $column) {
            $str = "`$column->logicalName`";
            $str .= " " . $column->type->sql;
            if ($column->attributes != 0) {
                if (($column->attributes & SCHEMA_PRIMARY_KEY) == SCHEMA_PRIMARY_KEY) {
                    $primaryKey = ", PRIMARY KEY ($column->logicalName)";
                }
                if (($column->attributes & SCHEMA_AUTO_INCREMENT) == SCHEMA_AUTO_INCREMENT) {
                    $str .= " AUTO_INCREMENT";
                }
                if (($column->attributes & SCHEMA_UNIQUE) == SCHEMA_UNIQUE) {
                    $str .= " UNIQUE";
                }
                if (($column->attributes & SCHEMA_NULLABLE) != SCHEMA_NULLABLE) {
                    $str .= " NOT NULL";
                }
            }

            $cols[] = $str;
        }

        $colStr = implode(',', $cols);
        return "CREATE TABLE $this->logicalName ($colStr $primaryKey);";
    }

    public function toJSON(): array
    {
        $cols = array();
        foreach ($this->columns as $column) {
            $cols[] = array(
                "logicalName" => $column->logicalName,
                "type" => array(
                    "logicalName" => $column->type->logicalName,
                    "value" => $column->type->value == null ? 0 : $column->type->value
                ),
                "attributes" => $column->attributes
            );
        }

        return $cols;
    }
}

class SchemaBuilder
{
    public readonly string $logicalName;
    public readonly string $referenceKey;
    /**
     * @var SchemaColumn[]
     */
    public array $columns;

    public function __construct(string $logicalName, string $referenceKey)
    {
        $this->logicalName = $logicalName;
        $this->referenceKey = $referenceKey;
        $this->columns = array();
    }


    public function addColumn(string $logicalName, SchemaType $type, int $attributes = 0): void
    {
        foreach ($this->columns as $column) {
            if ($column->logicalName == $logicalName) {
                throw new Exception("A column with the logical name '$logicalName' exists already in the schema '$this->logicalName'");
            }
        }

        $this->columns[] = new SchemaColumn($logicalName, $attributes, $type);
    }
}

class SchemaColumn
{
    public readonly string $logicalName;
    public readonly int $attributes;

    public readonly SchemaType $type;

    public function __construct(string $logicalName, int $attributes, SchemaType $type)
    {
        if ($type->equals(SchemaType::$reference) && $type->value == null) {
            throw new Exception("SchemaType value of reference cannot be null");
        } else if ($type->equals(SchemaType::$group) && $type->value == null) {
            throw new Exception("SchemaType value of group cannot be null");
        }

        $this->logicalName = $logicalName;
        $this->attributes = $attributes;
        $this->type = $type;
    }

}

class SchemaType
{
    public readonly string $sql;
    public readonly string $logicalName;
    public readonly mixed $value;

    private function __construct(string $logicalName, string $sqlName, mixed $value = null)
    {
        $this->sql = $sqlName;
        $this->logicalName = $logicalName;
        $this->value = $value;
    }

    public function equals(SchemaType $other): bool
    {
        return $this->logicalName == $other->logicalName;
    }

    public static function init(): void
    {
        self::$text = new SchemaType("sql.primitive.text", "TEXT");
        self::$int = new SchemaType("sql.primitive.int", "INTEGER");
        self::$boolean = new SchemaType("sql.primitive.bool", "BOOLEAN");
        self::$float = new SchemaType("sql.primitive.float", "FLOAT");
        self::$double = new SchemaType("sql.primitive.double", "DOUBLE");
        self::$decimal = new SchemaType("sql.primitive.decimal", "DECIMAL");
        self::$date = new SchemaType("sql.primitive.date", "DATE");
        self::$timestamp = new SchemaType("sql.primitive.timestamp", "TIMESTAMP");
        self::$blob = new SchemaType("sql.primitive.blob", "BLOB");
        self::$mediumBlob = new SchemaType("sql.extends.mediumblob", "MEDIUMBLOB");
        self::$reference = new SchemaType("sql.extends.ref", "TEXT");
        self::$group = new SchemaType("sql.extends.group", "TEXT");
    }

    public static SchemaType $text;

    public static SchemaType $int;
    public static SchemaType $boolean;
    public static SchemaType $float;
    public static SchemaType $double;
    public static SchemaType $decimal;
    public static SchemaType $date;
    public static SchemaType $timestamp;
    public static SchemaType $reference;
    public static SchemaType $blob;
    public static SchemaType $mediumBlob;
    public static SchemaType $group;

    public static function ref_of(string $table): SchemaType
    {
        if (!Table::getTable($table)) {
            throw new Exception("Cannot find Table '$table'");
        }

        return new SchemaType(self::$reference->logicalName, self::$reference->sql, $table);
    }

    public static function group_of(string $table): SchemaType
    {
        if (!Table::getTable($table)) {
            throw new Exception("Cannot find Table '$table'");
        }

        return new SchemaType(self::$group->logicalName, self::$group->sql, $table);
    }
}

SchemaType::init();