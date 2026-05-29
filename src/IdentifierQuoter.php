<?php

namespace Pindinelli\Quebu;

use InvalidArgumentException;

final class IdentifierQuoter
{
    /**
     * Quotes an identifier (column or table name) to prevent SQL injection and handle aliases.
     *
     * @param string $identifier The identifier to quote.
     * @return string The quoted identifier.
     */
    public static function quoteIdentifier(string $identifier): string
    {
        // Handles aliases like 'column as alias' or 'table.column as alias'
        if (preg_match("/(.+)\s+as\s+(.+)/i", $identifier, $matches)) {
            $columnPart = self::quoteIdentifier(trim($matches[1]));
            $aliasPart = self::quoteIdentifier(trim($matches[2]));
            return "{$columnPart} AS {$aliasPart}";
        }

        // Allows only a limited set of aggregate SQL functions.
        if (
            preg_match(
                '/^(COUNT|SUM|AVG|MIN|MAX)\((\*|[A-Za-z_][A-Za-z0-9_\.]*)\)$/i',
                trim($identifier),
                $matches,
            )
        ) {
            $function = strtoupper($matches[1]);
            $arg = $matches[2];

            if ($arg === "*") {
                return "{$function}(*)";
            }

            return "{$function}(" . self::quoteIdentifier($arg) . ")";
        }

        $parts = explode(".", $identifier);
        $quotedParts = [];
        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === "*") {
                $quotedParts[] = $part;
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new InvalidArgumentException(
                    "Invalid SQL identifier part '{$part}'.",
                );
            }

            $quotedParts[] = "`{$part}`";
        }

        return implode(".", $quotedParts);
    }

    /**
     * Quotes an array of identifiers.
     *
     * @param array<string> $identifiers
     * @return array<string>
     */
    public static function quoteIdentifiers(array $identifiers): array
    {
        $quoted = [];
        foreach ($identifiers as $identifier) {
            $quoted[] = self::quoteIdentifier($identifier);
        }
        return $quoted;
    }
}
