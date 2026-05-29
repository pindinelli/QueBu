<?php

declare(strict_types=1);

use Pindinelli\Quebu\DB;
use Pindinelli\Quebu\DatabaseConfig;
use Pindinelli\Quebu\EnvLoader;
use Pindinelli\Quebu\Enums\Operators;
use Pindinelli\Quebu\Enums\SortDirection;

/*
 * Quebu full example script.
 *
 * This file demonstrates the main API surface of the library.
 * It is intended for local demos only.
 */

require __DIR__ . "/autoload.php";
// If your project uses Composer, you can use:
// require __DIR__ . '/vendor/autoload.php';

EnvLoader::load(__DIR__);

$debug = ($_ENV["APP_DEBUG"] ?? "0") === "1";
ini_set("display_errors", $debug ? "1" : "0");
ini_set("display_startup_errors", $debug ? "1" : "0");
error_reporting(E_ALL);

function printBlock(string $title, mixed $data): void
{
    echo "<h3>{$title}</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
}

try {
    $config = DatabaseConfig::fromEnvironment();
    DB::connect($config->dsn, $config->user, $config->password);

    // 1) toSql()
    $sqlPreview = DB::from("users")
        ->select("id", "name", "email")
        ->andWhere("name", Operators::LIKE, "M%")
        ->orderBy("id", SortDirection::DESC)
        ->limit(5)
        ->toSql();
    printBlock("1) SQL Preview (toSql)", $sqlPreview);

    // 2) SELECT + AND WHERE + OR WHERE + ORDER BY + LIMIT/OFFSET + get()
    $usersFiltered = DB::from("users")
        ->select("id", "name", "email", "registration_date")
        ->andWhere("name", Operators::LIKE, "M%")
        ->orWhere("name", Operators::LIKE, "A%")
        ->orderBy("id", SortDirection::ASC)
        ->limit(10, 0)
        ->get();
    printBlock("2) Filtered users (get)", $usersFiltered);

    // 3) first()
    $firstUser = DB::from("users")->orderBy("id", SortDirection::ASC)->first();
    printBlock("3) First user (first)", $firstUser);

    // 4) JOIN (self-join demo, so no extra table is required)
    // Pair each user with users having a greater id.
    $joinedUsers = DB::from("users as u1")
        ->select(
            "u1.id as left_id",
            "u1.name as left_name",
            "u2.id as right_id",
            "u2.name as right_name",
        )
        ->join("users as u2", "u1.id", Operators::LESS_THAN, "u2.id")
        ->orderBy("u1.id", SortDirection::ASC)
        ->limit(5)
        ->get();
    printBlock("4) Self JOIN demo", $joinedUsers);

    // 5) GROUP BY + HAVING (aggregation by registration_date)
    $groupedByDate = DB::from("users")
        ->select("registration_date", "COUNT(*) as total_users")
        ->groupBy("registration_date")
        ->andHaving("COUNT(*)", Operators::GREATER_THAN, 0)
        ->orderBy("registration_date", SortDirection::ASC)
        ->get();
    printBlock("5) Group by registration_date + having", $groupedByDate);

    // 6) HAVING with OR condition (orHaving)
    $havingOrResults = DB::from("users")
        ->select("registration_date", "COUNT(*) as total_users")
        ->groupBy("registration_date")
        ->andHaving("COUNT(*)", Operators::LESS_THAN, 0)
        ->orHaving("COUNT(*)", Operators::GREATER_THAN_OR_EQUAL, 1)
        ->orderBy("registration_date", SortDirection::ASC)
        ->get();
    printBlock("6) Group by + orHaving", $havingOrResults);

    // 7) LEFT JOIN / RIGHT JOIN (SQL preview)
    $leftJoinSql = DB::from("users as u1")
        ->select("u1.id", "u2.id as joined_id")
        ->leftJoin("users as u2", "u1.id", Operators::EQUAL, "u2.id")
        ->limit(1)
        ->toSql();
    printBlock("7) LEFT JOIN SQL", $leftJoinSql);

    $rightJoinSql = DB::from("users as u1")
        ->select("u1.id", "u2.id as joined_id")
        ->rightJoin("users as u2", "u1.id", Operators::EQUAL, "u2.id")
        ->limit(1)
        ->toSql();
    printBlock("8) RIGHT JOIN SQL", $rightJoinSql);

    // 8) Aggregates
    $aggregates = [
        "count(*)" => DB::from("users")->count(),
        "sum(id)" => DB::from("users")->sum("id"),
        "avg(id)" => DB::from("users")->avg("id"),
        "min(id)" => DB::from("users")->min("id"),
        "max(id)" => DB::from("users")->max("id"),
    ];
    printBlock("9) Aggregates", $aggregates);

    // 9) INSERT
    $demoEmail = "quebu.demo@example.com";
    try {
        $newId = DB::from("users")->insert([
            "name" => "Quebu Demo",
            "email" => $demoEmail,
            "registration_date" => date("Y-m-d"),
        ]);
        printBlock("9) Insert", "Inserted user with ID: {$newId}");
    } catch (\PDOException $e) {
        // SQLSTATE 23000: integrity constraint violation (e.g. duplicate key)
        if ($e->getCode() === "23000") {
            printBlock(
                "9) Insert",
                "User with email {$demoEmail} already exists.",
            );
        } else {
            throw $e;
        }
    }

    // 10) UPDATE (safe write with WHERE)
    $updated = DB::from("users")
        ->andWhere("email", Operators::EQUAL, $demoEmail)
        ->update(["name" => "Quebu Demo Updated"]);
    printBlock("10) Update with WHERE", $updated);

    // 11) DELETE (safe write with WHERE)
    $deleted = DB::from("users")
        ->andWhere("email", Operators::EQUAL, $demoEmail)
        ->delete();
    printBlock("11) Delete with WHERE", $deleted);

    // 12) Unsafe write override (disabled by default)
    // USE WITH EXTREME CAUTION.
    // Example (commented on purpose):
    // DB::from('users')->allowUnsafeWrites(true)->delete();
    printBlock(
        "12) Unsafe writes",
        "By default, update/delete without WHERE are blocked. Use allowUnsafeWrites(true) only when intentional.",
    );
} catch (\Throwable $e) {
    error_log("[Quebu example] " . $e->getMessage());
    http_response_code(500);

    if ($debug) {
        die("<h3>Error</h3><pre>" . $e->getMessage() . "</pre>");
    }

    die("<h3>Error</h3><pre>Internal server error.</pre>");
}
