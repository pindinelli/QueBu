<?php

namespace Pindinelli\Quebu;

use InvalidArgumentException;

class Env
{
    /**
     * Get an environment value from the available sources.
     *
     * Lookup order:
     * 1. getenv()
     * 2. $_ENV
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        return $default;
    }

    /**
     * Store an environment value across the supported sources.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public static function set(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }

    /**
     * Get a required environment value.
     *
     * @param string $key
     * @return string
     * @throws InvalidArgumentException If the environment variable is not set.
     */
    public static function require(string $key): string
    {
        $value = self::get($key);

        if ($value === null || $value === "") {
            throw new InvalidArgumentException(
                "The {$key} environment variable is not set.",
            );
        }

        return $value;
    }
}
