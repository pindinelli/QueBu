<?php

namespace Pindinelli\Quebu\Dtos;

class OrderByDto
{
    public function __construct(
        public readonly string $column,
        public readonly string $direction,
    ) {}
}
