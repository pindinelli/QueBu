<?php

namespace Pindinelli\Quebu\Contracts;

interface ReadableQueryInterface
{
    /**
     * Executes the query and returns all results.
     * @return array<array<string, mixed>>
     */
    public function get(): array;

    /**
     * Executes the query and returns the first result.
     * @return object|null
     */
    public function first(): ?object;

    /**
     * Returns count of records.
     * @param string $column
     * @return int
     */
    public function count(string $column = "*"): int;

    /**
     * Returns sum of values in a column.
     * @param string $column
     * @return float|int
     */
    public function sum(string $column): float|int;

    /**
     * Returns average of values in a column.
     * @param string $column
     * @return float|int
     */
    public function avg(string $column): float|int;

    /**
     * Executes the query and returns the minimum value of a column.
     * @return mixed
     */
    public function min(string $column): mixed;

    /**
     * Executes the query and returns the maximum value of a column.
     * @return mixed
     */
    public function max(string $column): mixed;
}
