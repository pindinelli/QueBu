<?php

namespace Pindinelli\Quebu;

use InvalidArgumentException;

/**
 * DatabaseConfig class for parsing and storing database connection settings.
 */
class DatabaseConfig
{
    /** @var string The database driver (e.g., mysql) */
    public readonly string $driver;
    /** @var string The database host address */
    public readonly string $host;
    /** @var string The database port number */
    public readonly string $port;
    /** @var string The name of the database */
    public readonly string $dbname;
    /** @var string The username for the connection */
    public readonly string $user;
    /** @var string|null The password for the connection */
    public readonly ?string $password;
    /** @var string The character set for the connection */
    public readonly string $charset;
    /** @var string The constructed PDO DSN string */
    public readonly string $dsn;

    /**
     * Private constructor to prevent direct instantiation.
     * Use factory methods like fromEnvironment() instead.
     *
     * @param string $driver The DB driver.
     * @param string $host The DB host.
     * @param string $port The DB port.
     * @param string $dbname The DB name.
     * @param string $user The DB username.
     * @param string|null $password The DB password.
     * @param string $charset The DB charset.
     */
    private function __construct(
        string $driver,
        string $host,
        string $port,
        string $dbname,
        string $user,
        ?string $password,
        string $charset,
    ) {
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->user = $user;
        $this->password = $password;
        $this->charset = $charset;
        $this->dsn = sprintf(
            "%s:host=%s;port=%s;dbname=%s;charset=%s",
            $driver,
            $host,
            $port,
            $dbname,
            $charset,
        );
    }

    /**
     * Creates a new instance of DatabaseConfig from global environment variables.
     *
     * @return self
     * @throws InvalidArgumentException If any required environment variable is missing.
     */
    public static function fromEnvironment(): self
    {
        return new self(
            EnvResolver::require("DB_CONNECTION"),
            EnvResolver::require("DB_HOST"),
            EnvResolver::require("DB_PORT"),
            EnvResolver::require("DB_DATABASE"),
            EnvResolver::require("DB_USERNAME"),
            EnvResolver::get("DB_PASSWORD"),
            EnvResolver::require("DB_CHARSET"),
        );
    }
}
