<?php

namespace Pindinelli\Quebu;

use InvalidArgumentException;

class EnvResolver
{
    /** @var array<string, string> */
    private static array $values = [];

    /**
     * Hydrate the resolver with environment values loaded by the library.
     *
     * @param array<string, string> $values
     * @return void
     */
    public static function hydrate(array $values): void
    {
        self::$values = array_replace(self::$values, $values);
    }

    /**
     * Remove all internally hydrated values.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$values = [];
    }

    /**
     * Get an environment value from the available sources.
     *
     * Lookup order:
     * 1. Values hydrated into the resolver
     * 2. getenv()
     * 3. $_ENV
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

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
