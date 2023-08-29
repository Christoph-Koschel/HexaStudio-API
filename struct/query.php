<?php

function sql_str(string $str): string
{
    return "'$str'";
}


abstract class Query
{
    protected array|false $columns;
    protected string $logicalName;

    protected function __construct(array|false $columns, string $logicalName)
    {
        $this->columns = $columns;
        $this->logicalName = $logicalName;
    }

    public function getColumns(): array
    {
        if ($this->columns) {
            return $this->columns;
        }

        $table = Table::getTable($this->logicalName);
        $columns = $table->getSchema()->getColumns();
        $res = array();
        foreach ($columns as $column) {
            $res[] = $column->logicalName;
        }

        return $res;
    }

    public abstract function toSql(): string;
}

abstract class FetchQuery extends Query
{
    protected function __construct(array|false $columns, string $logicalName)
    {
        parent::__construct($columns, $logicalName);
    }
}

class BulgFetchQuery extends FetchQuery
{
    public function __construct(array|false $columns, string $logicalName)
    {
        parent::__construct($columns, $logicalName);
    }

    public function toSql(): string
    {
        $schema = Table::getTable($this->logicalName)->getSchema();

        $columns = array();

        if ($this->columns) {
            foreach ($this->columns as $column) {
                if (!$schema->exists($column)) {
                    throw new Exception("Column '$column' does not exists in table '$this->logicalName'");
                }
                $columns[] = "`$column`";
            }
        }

        return "SELECT " . ($this->columns ? implode(", ", $columns) : "*") . " FROM $schema->logicalName";
    }
}

class FilterQuery extends FetchQuery
{
    private array $criteria;

    public function __construct(array|false $columns, string $logicalName)
    {
        parent::__construct($columns, $logicalName);
        $this->criteria = array();
    }

    public function addCriteria(string $logicalName, string $operation, string $should, bool $ignoreCase = false): FilterQueryConcat
    {
        $this->criteria[] = array(
            "left" => $logicalName,
            "op" => $operation,
            "right" => $should,
            "ignoreCase" => $ignoreCase
        );
        return new FilterQueryConcat($this);
    }

    public function toSql(): string
    {
        $schema = Table::getTable($this->logicalName)->getSchema();
        {
            $last = $this->criteria[count($this->criteria) - 1];
            if ($last["left"] == "" && ($last["op"] == "and" || $last["op"] == "or") && $last["right"] == "") {
                throw new Exception("FilterQuery cannot end with an concat expression");
            }
        }

        $columns = array();

        if ($this->columns) {
            foreach ($this->columns as $column) {
                if (!$schema->exists($column)) {
                    throw new Exception("Column '$column' does not exists in table '$this->logicalName'");
                }
                $columns[] = "`$column`";
            }
        }

        $sql = "SELECT " . ($this->columns ? implode(", ", $columns) : "*") . " FROM $schema->logicalName";
        $odd = false;
        $first = true;
        if (count($this->criteria) != 0) {
            $sql .= " WHERE";
        }


        foreach ($this->criteria as $criterion) {
            if ($criterion["left"] == "" && ($criterion["op"] == "and" || $criterion["op"] == "or") && $criterion["right"] == "") {
                if (!$odd) {
                    throw new Exception("After a concat expression cannot follow another concat expression");
                }
                $sql .= " {$criterion["op"]}";
            } else {
                if ($odd && !$first) {
                    throw new Exception("After a filter condition cannot follow another filter condition");
                }
                if ($criterion["ignoreCase"]) {
                    $sql .= " UPPER(`{$criterion["left"]}`) ${criterion["op"]} UPPER({$criterion["right"]})";
                } else {
                    $sql .= " `{$criterion["left"]}` ${criterion["op"]} {$criterion["right"]}";
                }
            }
            $odd = !$odd;
            $first = false;
        }

        return $sql;
    }
}

class FilterQueryConcat
{
    private FilterQuery $query;

    public function __construct(FilterQuery $query)
    {

        $this->query = $query;
    }

    public function and(): FilterQuery
    {
        $this->query->addCriteria("", "and", "");
        return $this->query;
    }

    public function or(): FilterQuery
    {
        $this->query->addCriteria("", "or", "");
        return $this->query;
    }

    public function finish(): FilterQuery
    {
        return $this->query;
    }
}

class QueryResult
{
    private array $rows;
    private Table $table;

    public function __construct(array $rows, Table $table)
    {
        $this->rows = $rows;
        $this->table = $table;
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function get(int $row): QueryRow
    {
        return new QueryRow($this->rows[$row], $this->table, $this);
    }
}

class QueryRow
{
    private readonly array $row;
    private readonly Table $table;
    private readonly QueryResult $res;

    public function __construct(array $row, Table $table, QueryResult $res)
    {
        $this->row = $row;
        $this->table = $table;
        $this->res = $res;
    }

    public function get(string $logicalName): mixed
    {
        return $this->row[$logicalName];
    }
}

abstract class InsertQuery extends Query
{
    protected function __construct(array|false $columns, string $logicalName)
    {
        parent::__construct($columns, $logicalName);
    }
}

class BulgInsertQuery extends InsertQuery
{
    private array $values;

    public function __construct(false|array $columns, string $logicalName)
    {
        parent::__construct($columns, $logicalName);
        $this->values = array();
    }


    public function addValue(array $row): void
    {
        $seenNames = array();
        $keys = array_keys($row);
        foreach ($this->columns as $column) {
            if (!in_array($column, $keys)) {
                res_set_attribute("error", "Missing column '$column'");
                res_send(STATUS_FAIL);
            } else if (in_array($column, $seenNames)) {
                res_set_attribute("error", "Duplicated column '$column'");
                res_send(STATUS_FAIL);
            }

            $seenNames[] = $column;
        }

        $this->values[] = $row;
    }

    public function toSql(): string
    {
        $schema = Table::getTable($this->logicalName);
        if (count($this->values) == 0) {
            res_set_attribute("error", "No values are provided to insert");
            res_send(STATUS_FAIL);
        }

        $sql = "INSERT INTO $this->logicalName (";
        $first = true;

        foreach ($this->columns as $column) {
            if (!$first) {
                $sql .= ", ";
            } else {
                $first = false;
            }
            $sql .= "'" . $column . "'";
        }
        $sql .= ") VALUES ";

        $first = true;
        foreach ($this->values as $value) {
            if (!$first) {
                $sql .= ", ";
            } else {
                $first = false;
            }
            $sql .= "(";
            $subFirst = true;
            foreach ($this->columns as $column) {
                if (!$subFirst) {
                    $sql .= ", ";
                } else {
                    $subFirst = false;
                }
                if ($schema->getSchema()->getColumn($column)->type->sql == "BLOB" || $schema->getSchema()->getColumn($column)->type->sql == "MEDIUMBLOB") {
                    $bin = addslashes($value[$column]);
                    $sql .= "'$bin'";
                } else if ($schema->getSchema()->getColumn($column)->type->sql == "TEXT") {
                    $sql .= "'" . $value[$column] . "'";
                } else {
                    $sql .= $value[$column];
                }
            }
            $sql .= ")";
        }
        $sql .= ";";
        return $sql;
    }
}