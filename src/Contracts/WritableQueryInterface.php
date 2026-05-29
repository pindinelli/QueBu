<?php

namespace Pindinelli\Quebu\Contracts;

interface WritableQueryInterface
{
    /**
     * Inserts a new row into the table.
     * @param array $data
     * @return int The number of rows inserted.
     */
    public function insert(array $data): int;

    /**
     * Updates existing records.
     * @param array<string, mixed> $columns
     * @return bool
     */
    public function update(array $columns): bool;

    /**
     * Deletes records.
     * @return bool
     */
    public function delete(): bool;

    /**
     * Allows UPDATE/DELETE statements without a WHERE clause.
     * @param bool $allow
     * @return self
     */
    public function allowUnsafeWrites(bool $allow = true): self;
}
