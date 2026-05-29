<?php

namespace Pindinelli\Quebu\Contracts;

use Pindinelli\Quebu\Enums\Operators;
use Pindinelli\Quebu\Enums\SortDirection;

interface QueryBuilderInterface
{
    /**
     * Specifies the columns to select.
     * @param string ...$columns
     * @return self
     */
    public function select(string ...$columns): self;

    /**
     * Adds an AND WHERE clause.
     * @param string $column
     * @param Operators $operator
     * @param int|float|string|array|null $value
     * @return self
     */
    public function andWhere(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self;

    /**
     * Adds an OR WHERE clause.
     * @param string $column
     * @param Operators $operator
     * @param int|float|string|array|null $value
     * @return self
     */
    public function orWhere(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self;

    /**
     * Adds a JOIN clause.
     * @param string $table
     * @param string $first
     * @param Operators $operator
     * @param string $second
     * @return self
     */
    public function join(
        string $table,
        string $first,
        Operators $operator,
        string $second,
    ): self;

    /**
     * Adds a LEFT JOIN clause.
     * @param string $table
     * @param string $first
     * @param Operators $operator
     * @param string $second
     * @return self
     */
    public function leftJoin(
        string $table,
        string $first,
        Operators $operator,
        string $second,
    ): self;

    /**
     * Adds a RIGHT JOIN clause.
     * @param string $table
     * @param string $first
     * @param Operators $operator
     * @param string $second
     * @return self
     */
    public function rightJoin(
        string $table,
        string $first,
        Operators $operator,
        string $second,
    ): self;

    /**
     * Adds a GROUP BY clause.
     * @param string ...$columns
     * @return self
     */
    public function groupBy(string ...$columns): self;

    /**
     * Adds an AND HAVING clause.
     * @param string $column
     * @param Operators $operator
     * @param int|float|string|array|null $value
     * @return self
     */
    public function andHaving(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self;

    /**
     * Adds an OR HAVING clause.
     * @param string $column
     * @param Operators $operator
     * @param int|float|string|array|null $value
     * @return self
     */
    public function orHaving(
        string $column,
        Operators $operator,
        int|float|string|array|null $value = null,
    ): self;

    /**
     * Adds an ORDER BY clause to the query.
     * @param string $column The column to order by.
     * @param SortDirection $direction The direction to sort by. Defaults to ASC.
     * @return self
     */
    public function orderBy(
        string $column,
        SortDirection $direction = SortDirection::ASC,
    ): self;

    /**
     * Sets the LIMIT and OFFSET.
     * @param int $limit
     * @param int $offset
     * @return self
     */
    public function limit(int $limit, int $offset = 0): self;

    /**
     * Returns the SQL representation of the query.
     */
    public function toSql(): string;
}
