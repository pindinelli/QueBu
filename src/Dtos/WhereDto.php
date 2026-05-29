<?php

namespace Pindinelli\Quebu\Dtos;

use Pindinelli\Quebu\Enums\LogicalOperators;
use Pindinelli\Quebu\Enums\Operators;

class WhereDto
{
    public function __construct(
        public readonly string $column,
        public readonly Operators $operator,
        public readonly int|float|string|array|null $value = null,
        public readonly LogicalOperators $type = LogicalOperators::AND,
    ) {}
}
