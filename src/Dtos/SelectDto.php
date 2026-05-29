<?php

namespace Pindinelli\Quebu\Dtos;

final class SelectDto
{
    /** @var array<string> */
    private readonly array $columns;

    public function __construct(string ...$columns)
    {
        if (empty($columns)) {
            $this->columns = ["*"];
            return;
        }

        foreach ($columns as $column) {
            if (trim($column) === "") {
                throw new \InvalidArgumentException("Column cannot be empty.");
            }
        }

        $this->columns = $columns;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
