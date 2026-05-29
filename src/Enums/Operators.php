<?php
namespace Pindinelli\Quebu\Enums;

enum Operators: string
{
    case EQUAL = "=";
    case NOT_EQUAL = "!=";
    case LESS_THAN = "<";
    case GREATER_THAN = ">";
    case LESS_THAN_OR_EQUAL = "<=";
    case GREATER_THAN_OR_EQUAL = ">=";
    case LIKE = "LIKE";
    case IN = "IN";
    case NOT_IN = "NOT IN";
    case IS_NULL = "IS NULL";
    case IS_NOT_NULL = "IS NOT NULL";
    case BETWEEN = "BETWEEN";
    case NOT_BETWEEN = "NOT BETWEEN";

    public function isComparison(): bool
    {
        return in_array($this, [
            self::EQUAL,
            self::NOT_EQUAL,
            self::LESS_THAN,
            self::GREATER_THAN,
            self::LESS_THAN_OR_EQUAL,
            self::GREATER_THAN_OR_EQUAL,
            self::BETWEEN,
            self::NOT_BETWEEN,
        ]);
    }
}
