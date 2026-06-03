<?php
namespace Pindinelli\Quebu;

use InvalidArgumentException;

/**
 * A utility class for loading environment variables from a .env file.
 * Designed to be a lightweight alternative to `phpdotenv` for environments without Composer.
 */
class EnvLoader
{
    /**
     * Loads and parses a .env file from the specified path.
     *
     * @param string $path The directory path containing the .env file.
     * @return array<string, string>
     * @throws InvalidArgumentException If the .env file is not found or is not readable.
     */
    public static function load(string $path): array
    {
        $envFile = rtrim($path, "/") . "/.env";
        if (!file_exists($envFile) || !is_readable($envFile)) {
            throw new InvalidArgumentException(
                "'.env' file not found or not readable at path: {$envFile}",
            );
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $values = [];

        foreach ($lines as $line) {
            if (
                str_starts_with(trim($line), "#") ||
                strpos($line, "=") === false
            ) {
                continue;
            }

            [$key, $value] = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            $values[$key] = $value;
        }

        EnvResolver::hydrate($values);

        return $values;
    }
}
