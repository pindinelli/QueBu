<?php

namespace Pindinelli\Quebu\Dtos;

use Pindinelli\Quebu\Enums\LogicalOperators;
use Pindinelli\Quebu\Enums\Operators;

class HavingDto
{
    public function __construct(
        public readonly LogicalOperators $type,
        public readonly string $column,
        public readonly Operators $operator,
        public readonly int|float|string|array|null $value = null,
    ) {}
}
