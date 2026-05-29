<?php

namespace Pindinelli\Quebu\Dtos;

use Pindinelli\Quebu\Enums\JoinKind;
use Pindinelli\Quebu\Enums\Operators;

final class JoinDto
{
    public function __construct(
        public readonly JoinKind $kind,
        public readonly string $table,
        public readonly string $onColumn,
        public readonly Operators $operator,
        public readonly string $targetColumn,
    ) {}
}
