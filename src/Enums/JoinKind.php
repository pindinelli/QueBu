<?php

namespace Pindinelli\Quebu\Enums;

enum JoinKind: string
{
    case INNER = "INNER";
    case LEFT = "LEFT OUTER";
    case RIGHT = "RIGHT OUTER";
}
