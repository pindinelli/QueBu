<?php
namespace Pindinelli\Quebu;

use PDO;
use InvalidArgumentException;
use Pindinelli\Quebu\Dtos\HavingDto;
use Pindinelli\Quebu\Dtos\OrderByDto;
use Pindinelli\Quebu\Dtos\SelectDto;
use Pindinelli\Quebu\Dtos\WhereDto;
use Pindinelli\Quebu\Dtos\JoinDto;
use Pindinelli\Quebu\Enums\LogicalOperators;
use Pindinelli\Quebu\Enums\Operators;
use Pindinelli\Quebu\Enums\SortDirection;
use Pindinelli\Quebu\Enums\JoinKind;
use Pindinelli\Quebu\Contracts\QueryBuilderInterface;
use Pindinelli\Quebu\Contracts\ReadableQueryInterface;
use Pindinelli\Quebu\Contracts\WritableQueryInterface;

/**
 * DB class for managing database connections and the main interface
 * for building queries. Implements the Singleton pattern for the PDO connection.
 */
class DB implements
    QueryBuilderInterface,
    ReadableQueryInterface,
    WritableQueryInterface
{
    /**
     * PDO connection instance.
     * @var PDO|null
     */
    private static ?PDO $connection = null;

    /**
     * Name of the table to operate on.
     * @var string
     */
    private string $tableName = "";

    /**
     * Columns to be selected.
     */
    private SelectDto $columns;

    /**
     * Query WHERE conditions.
     * @var array<WhereDto>
     */
    private array $where = [];

    /**
     * Query JOIN clauses.
     * @var array<JoinDto>
     */
    private array $joins = [];

    /**
     * Columns for the GROUP BY clause.
     * @var array<string>
     */
    private array $groupBy = [];

    /**
     * Query HAVING conditions.
     * @var array<HavingDto>
     */
    private array $having = [];

    /**
     * Columns for the ORDER BY clause.
     * @var array<OrderByDto>
     */
    private array $orderBy = [];

    /**
     * Maximum number of results to return.
     * @var int|null
     */
    private ?int $limit = null;

    /**
     * Offset for pagination.
     * @var int
     */
    private int $offset = 0;

    /**
     * Values to bind to prepared statement placeholders.
     * @var array<mixed>
     */
    private array $values = [];

    /**
     * If true, allows UPDATE/DELETE queries without a WHERE clause.
     * @var bool
     */
    private bool $allowUnsafeWrites = false;

    /**
     * Private constructor to prevent direct instantiation and force static method usage.
     *
     * @throws \Exception If the database connection has not been established.
     */
    private function __construct()
    {
        if (is_null(self::$connection)) {
            throw new \Exception(
                "Database not connected. Call DB::connect() first.",
            );
        }

        $this->columns = new SelectDto();
    }

    /**
     * Establishes a database connection using PDO.
     *
     * @param string $dsn The DSN string for the PDO connection.
     * @param string|null $user The database user.
     * @param string|null $password The database password.
     * @param array<int, mixed> $options An array of PDO-specific options.
     */
    public static function connect(
        string $dsn,
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ): void {
        if (is_null(self::$connection)) {
            $defaultOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$connection = new PDO(
                $dsn,
                $user,
                $password,
                array_replace($defaultOptions, $options),
            );
        }
    }

    /**
     * Returns the current PDO connection instance.
     *
     * @return PDO|null The PDO object if connected, otherwise null.
     */
    public static function getConnection(): ?PDO
    {
        return self::$connection;
    }

    /**
     * Disconnects from the database by closing the PDO connection.
     *
     * @return void
     */
    public static function disconnect(): void
    {
        self::$connection = null;
    }

    /**
     * Resets the internal state of the query builder to prepare it for a new query.
     *
     * @return void
     */
    private function reset()
    {
        $this->columns = new SelectDto();
        $this->where = [];
        $this->joins = [];
        $this->groupBy = [];
        $this->having = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = 0;
        $this->values = [];
        $this->allowUnsafeWrites = false;
    }

    /**
     * Starts building a SELECT query by specifying the table.
     *
     * @param string $tableName The table name.
     * @return self The current query builder instance.
     */
    public static function from(string $tableName): self
    {
        $instance = new self();
        $instance->tableName = $tableName;
        return $instance;
    }

    /**
     * Specifies the columns to be selected. If not specified, selects all columns (*).
     *
     * @param string ...$columns Column names to select.
     * @return self The current query builder instance.
     */
    public function select(string ...$columns): self
    {
        $this->columns = new SelectDto(...$columns);
        return $this;
    }

    /**
     * Adds a WHERE condition with an AND logical operator.
     *
     * @param string $column The column name.
     * @param Operators $operator The comparison operator (e.g., EQUAL, GREATER_THAN).
     * @param int|float|string|array|null $value The value to compare.
     * @return self The current query builder instance.
     */
    public function andWhere(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self {
        $this->where[] = $this->where(
            LogicalOperators::AND,
            $column,
            $operator,
            $value,
        );

        return $this;
    }

    /**
     * Adds a WHERE condition with an OR logical operator.
     *
     * @param string $column The column name.
     * @param Operators $operator The comparison operator.
     * @param int|float|string|array|null $value The value to compare.
     * @return self The current query builder instance.
     */
    public function orWhere(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self {
        $this->where[] = $this->where(
            LogicalOperators::OR,
            $column,
            $operator,
            $value,
        );
        return $this;
    }

    /**
     * Private helper method to create a WHERE clause.
     *
     * @param LogicalOperators $type The logical operator type (AND/OR).
     * @param string $column The column name.
     * @param Operators $operator The comparison operator.
     * @param int|float|string|array|null $value The value to compare.
     * @return WhereDto The WHERE condition DTO.
     */
    private function where(
        LogicalOperators $type,
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): WhereDto {
        return new WhereDto($column, $operator, $value, $type);
    }

    /**
     * Adds a HAVING condition with an AND logical operator.
     *
     * @param string $column The column name.
     * @param Operators $operator The comparison operator.
     * @param int|float|string|array|null $value The value to compare.
     * @return self The current query builder instance.
     */
    public function andHaving(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self {
        $this->having[] = $this->having(
            LogicalOperators::AND,
            $column,
            $operator,
            $value,
        );
        return $this;
    }

    /**
     * Adds a HAVING condition with an OR logical operator.
     *
     * @param string $column The column name.
     * @param Operators $operator The comparison operator.
     * @param int|float|string|array|null $value The value to compare.
     * @return self The current query builder instance.
     */
    public function orHaving(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self {
        $this->having[] = $this->having(
            LogicalOperators::OR,
            $column,
            $operator,
            $value,
        );
        return $this;
    }

    /**
     * Private helper method to create a HAVING clause.
     *
     * @param LogicalOperators $type The logical operator type (AND/OR).
     * @param string $column The column name.
     * @param Operators $operator The comparison operator.
     * @param int|float|string|array|null $value The value to compare.
     * @return HavingDto The HAVING condition DTO.
     */
    private function having(
        LogicalOperators $type,
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): HavingDto {
        return new HavingDto($type, $column, $operator, $value);
    }

    /**
     * Adds an ORDER BY clause.
     *
     * @param string $column The column name for sorting.
     * @param SortDirection $direction The sorting direction (ASC or DESC).
     * @return self The current query builder instance.
     */
    public function orderBy(
        string $column,
        SortDirection $direction = SortDirection::ASC,
    ): self {
        $this->orderBy[] = new OrderByDto($column, $direction->value);
        return $this;
    }

    /**
     * Adds one or more columns to the GROUP BY clause.
     *
     * @param string ...$columns Column names for grouping.
     * @return self The current query builder instance.
     */
    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->groupBy[] = $column;
        }
        return $this;
    }

    /**
     * Builds a conditional clause (WHERE or HAVING) based on the provided conditions.
     *
     * @param string $clauseType The clause type (e.g., 'WHERE', 'HAVING').
     * @param array<WhereDto|HavingDto> $conditions An array of conditions.
     * @return string The SQL string for the conditional clause.
     * @throws InvalidArgumentException If values for IN/NOT IN or BETWEEN/NOT BETWEEN are invalid.
     */
    private function buildConditionClause(
        string $clauseType,
        array $conditions,
    ): string {
        if (empty($conditions)) {
            return "";
        }

        $expressions = [];
        $localValues = [];

        foreach ($conditions as $index => $clause) {
            [$expression, $values] = $this->buildConditionExpression($clause);
            $localValues = array_merge($localValues, $values);

            $expressions[] =
                $index === 0
                    ? $expression
                    : "{$clause->type->value} {$expression}";
        }

        $this->values = array_merge($this->values, $localValues);

        return " {$clauseType} " . implode(" ", $expressions);
    }

    /**
     * Builds a single condition expression and collects bound values.
     *
     * @param WhereDto|HavingDto $clause
     * @return array{0: string, 1: array<mixed>}
     */
    private function buildConditionExpression(WhereDto|HavingDto $clause): array
    {
        $operator = $clause->operator;
        $columnIdentifier = IdentifierQuoter::quoteIdentifier($clause->column);
        $baseExpression = $columnIdentifier . " " . $operator->value;

        return match (true) {
            $operator === Operators::IN || $operator === Operators::NOT_IN
                => $this->buildInExpression($baseExpression, $clause->value),

            $operator === Operators::BETWEEN ||
                $operator === Operators::NOT_BETWEEN
                => $this->buildBetweenExpression(
                $baseExpression,
                $clause->value,
            ),

            $operator === Operators::IS_NULL ||
                $operator === Operators::IS_NOT_NULL
                => [$baseExpression, []],

            default => [$baseExpression . " ?", [$clause->value]],
        };
    }

    /**
     * @param string $baseExpression
     * @param array<int|float|string> $value
     * @return array{0: string, 1: array<mixed>}
     */
    private function buildInExpression(
        string $baseExpression,
        array $value,
    ): array {
        if (empty($value)) {
            throw new InvalidArgumentException(
                "Value for IN/NOT IN must be a non-empty array.",
            );
        }

        $placeholders = implode(", ", array_fill(0, count($value), "?"));

        return [$baseExpression . " ({$placeholders})", $value];
    }

    /**
     * @param string $baseExpression
     * @param array{0: int|float|string, 1: int|float|string} $value
     * @return array{0: string, 1: array<mixed>}
     */
    private function buildBetweenExpression(
        string $baseExpression,
        array $value,
    ): array {
        if (count($value) !== 2) {
            throw new InvalidArgumentException(
                "Value for BETWEEN/NOT BETWEEN must be an array of two elements.",
            );
        }

        return [$baseExpression . " ? AND ?", [$value[0], $value[1]]];
    }

    /**
     * Builds the WHERE clause of the query.
     *
     * @return string The SQL string for the WHERE clause.
     */
    private function buildWhereClause(): string
    {
        return $this->buildConditionClause("WHERE", $this->where);
    }

    /**
     * Builds the complete SQL string for the SELECT query.
     *
     * @return string The generated SQL string.
     */
    private function buildQuery(): string
    {
        $this->values = [];
        $selectColumns = implode(
            ", ",
            IdentifierQuoter::quoteIdentifiers($this->columns->getColumns()),
        );

        $sql = "SELECT " . $selectColumns;
        $sql .= " FROM " . IdentifierQuoter::quoteIdentifier($this->tableName);

        if (!empty($this->joins)) {
            $sql .=
                " " .
                implode(
                    " ",
                    array_map(
                        fn(JoinDto $join) => $this->renderJoinClause($join),
                        $this->joins,
                    ),
                );
        }

        $sql .= $this->buildWhereClause();

        if (!empty($this->groupBy)) {
            $sql .=
                " GROUP BY " .
                implode(
                    ", ",
                    IdentifierQuoter::quoteIdentifiers($this->groupBy),
                );
        }

        $sql .= $this->buildConditionClause("HAVING", $this->having);

        if (!empty($this->orderBy)) {
            $orderByParts = [];
            foreach ($this->orderBy as $orderByClause) {
                $orderByParts[] =
                    IdentifierQuoter::quoteIdentifier($orderByClause->column) .
                    " " .
                    $orderByClause->direction;
            }
            $sql .= " ORDER BY " . implode(", ", $orderByParts);
        }

        if (!is_null($this->limit)) {
            $sql .= " LIMIT " . $this->limit;
            if ($this->offset > 0) {
                $sql .= " OFFSET " . $this->offset;
            }
        }

        return $sql;
    }

    public function join(
        string $table,
        string $first,
        Operators $operator,
        string $second,
    ): self {
        return $this->addJoin(
            JoinKind::INNER,
            $table,
            $first,
            $operator,
            $second,
        );
    }

    public function leftJoin(
        string $table,
        string $first,
        Operators $operator,
        string $second,
    ): self {
        return $this->addJoin(
            JoinKind::LEFT,
            $table,
            $first,
            $operator,
            $second,
        );
    }

    public function rightJoin(
        string $table,
        string $first,
        Operators $operator,
        string $second,
    ): self {
        return $this->addJoin(
            JoinKind::RIGHT,
            $table,
            $first,
            $operator,
            $second,
        );
    }

    private function addJoin(
        JoinKind $kind,
        string $table,
        string $first,
        Operators $operator,
        string $second,
    ): self {
        $this->joins[] = new JoinDto($kind, $table, $first, $operator, $second);
        return $this;
    }

    private function renderJoinClause(JoinDto $join): string
    {
        $safeTable = IdentifierQuoter::quoteIdentifier($join->table);
        $safeFirst = IdentifierQuoter::quoteIdentifier($join->onColumn);
        $safeSecond = IdentifierQuoter::quoteIdentifier($join->targetColumn);

        return "{$join->kind->value} JOIN {$safeTable} ON {$safeFirst} {$join->operator->value} {$safeSecond}";
    }

    /**
     * Sets the limit and offset for the query (pagination).
     *
     * @param int $limit The maximum number of records to return.
     * @param int $offset The offset from which to start retrieving records.
     * @return self The current query builder instance.
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Allows UPDATE/DELETE statements without a WHERE clause.
     * Use with caution.
     *
     * @param bool $allow Whether to allow unsafe write operations.
     * @return self
     */
    public function allowUnsafeWrites(bool $allow = true): self
    {
        $this->allowUnsafeWrites = $allow;
        return $this;
    }

    /**
     * Returns the SQL string of the built query.
     *
     * @return string The SQL string.
     */
    public function toSql(): string
    {
        return $this->buildQuery();
    }

    /**
     * Executes an INSERT query.
     *
     * @param array<string, mixed> $columns An associative array with column names and their values.
     * @return int The ID of the last inserted record.
     */
    public function insert(array $columns): int
    {
        $keys = implode(
            ", ",
            IdentifierQuoter::quoteIdentifiers(array_keys($columns)),
        );
        $quotedTable = IdentifierQuoter::quoteIdentifier($this->tableName);
        $placeholders = implode(", ", array_fill(0, count($columns), "?"));
        $sql = "INSERT INTO $quotedTable ({$keys}) VALUES ({$placeholders})";

        $this->values = array_values($columns);
        $stmt = self::$connection->prepare($sql);
        $stmt->execute($this->values);

        $lastId = (int) self::$connection->lastInsertId();
        $this->reset();
        return $lastId;
    }

    /**
     * Executes an UPDATE query.
     *
     * @param array<string, mixed> $columns An associative array with column names and new values.
     * @return bool True if the update was successful, false otherwise.
     */
    public function update(array $columns): bool
    {
        if (empty($this->where) && !$this->allowUnsafeWrites) {
            throw new InvalidArgumentException(
                "Unsafe UPDATE blocked: missing WHERE clause. Call allowUnsafeWrites(true) to override.",
            );
        }

        $setParts = [];
        $this->values = [];
        foreach ($columns as $key => $value) {
            $quotedColumn = IdentifierQuoter::quoteIdentifier($key);
            $setParts[] = "{$quotedColumn} = ?";
            $this->values[] = $value;
        }
        $setClause = implode(", ", $setParts);

        $sql =
            "UPDATE " .
            IdentifierQuoter::quoteIdentifier($this->tableName) .
            " SET {$setClause}";

        $sql .= $this->buildWhereClause();
        $stmt = self::$connection->prepare($sql);
        $result = $stmt->execute($this->values);
        $this->reset();
        return $result;
    }

    /**
     * Executes a DELETE query.
     *
     * @return bool True if the deletion was successful, false otherwise.
     */
    public function delete(): bool
    {
        if (empty($this->where) && !$this->allowUnsafeWrites) {
            throw new InvalidArgumentException(
                "Unsafe DELETE blocked: missing WHERE clause. Call allowUnsafeWrites(true) to override.",
            );
        }

        $sql =
            "DELETE FROM " .
            IdentifierQuoter::quoteIdentifier($this->tableName);
        $this->values = [];

        $sql .= $this->buildWhereClause();

        $stmt = self::$connection->prepare($sql);
        $result = $stmt->execute($this->values);
        $this->reset();
        return $result;
    }

    /**
     * Executes the query and returns the first result as an object, or null if there are no results.
     *
     * @return object|null The first result of the query as an object, or null.
     */
    public function first(): ?object
    {
        $this->limit = 1;
        $query = $this->buildQuery();
        $stmt = self::$connection->prepare($query);
        $stmt->execute($this->values);
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        $this->reset();
        return $result === false ? null : $result;
    }

    /**
     * Executes the query and returns all results as an array of associative arrays.
     *
     * @return array<array<string, mixed>> All results of the query.
     */
    public function get(): array
    {
        $query = $this->buildQuery();
        $stmt = self::$connection->prepare($query);
        $stmt->execute($this->values);
        $results = $stmt->fetchAll();
        $this->reset();
        return $results;
    }

    /**
     * Executes a COUNT query.
     *
     * @param string $column The column to perform the COUNT on (default '*').
     * @return int The number of records.
     */
    public function count(string $column = "*"): int
    {
        return (int) $this->aggregate("COUNT", $column);
    }

    /**
     * Executes a SUM query.
     *
     * @param string $column The column on which to perform the sum.
     * @return float|int The sum of the column values, or 0 if there are no results.
     */
    public function sum(string $column): float|int
    {
        $result = $this->aggregate("SUM", $column);
        return $result ?? 0;
    }

    /**
     * Executes an AVG query.
     *
     * @param string $column The column on which to perform the average.
     * @return float|int The average of the column values, or 0 if there are no results.
     */
    public function avg(string $column): float|int
    {
        $result = $this->aggregate("AVG", $column);
        return $result ?? 0;
    }

    /**
     * Executes a MIN query.
     *
     * @param string $column The column on which to find the minimum value.
     * @return mixed The minimum value of the column, or null if there are no results.
     */
    public function min(string $column): mixed
    {
        return $this->aggregate("MIN", $column);
    }

    /**
     * Executes a MAX query.
     *
     * @param string $column The column on which to find the maximum value.
     * @return mixed The maximum value of the column, or null if there are no results.
     */
    public function max(string $column): mixed
    {
        return $this->aggregate("MAX", $column);
    }

    /**
     * Private helper method to execute aggregate functions (COUNT, SUM, AVG, MIN, MAX).
     *
     * @param string $functionName The aggregate function name (e.g., 'COUNT', 'SUM').
     * @param string $column The column to which to apply the function.
     * @return mixed The result of the aggregate function.
     */
    private function aggregate(string $functionName, string $column): mixed
    {
        $this->values = [];
        $columnIdentifier =
            $column === "*" ? "*" : IdentifierQuoter::quoteIdentifier($column);

        $selectClause = "{$functionName}({$columnIdentifier})";
        $sql =
            "SELECT {$selectClause} FROM " .
            IdentifierQuoter::quoteIdentifier($this->tableName);

        if (!empty($this->joins)) {
            $sql .=
                " " .
                implode(
                    " ",
                    array_map(
                        fn(JoinDto $join) => $this->renderJoinClause($join),
                        $this->joins,
                    ),
                );
        }

        $sql .= $this->buildWhereClause();

        if (!empty($this->groupBy)) {
            $sql .=
                " GROUP BY " .
                implode(
                    ", ",
                    IdentifierQuoter::quoteIdentifiers($this->groupBy),
                );
        }

        $sql .= $this->buildConditionClause("HAVING", $this->having);

        $stmt = self::$connection->prepare($sql);
        $stmt->execute($this->values);
        $result = $stmt->fetchColumn();

        $this->reset();

        return $result;
    }
}
