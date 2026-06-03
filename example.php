<?php

declare(strict_types=1);

use Pindinelli\Quebu\DB;
use Pindinelli\Quebu\DatabaseConfig;
use Pindinelli\Quebu\EnvResolver;
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

$debug = EnvResolver::get("APP_DEBUG", "0") === "1";
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
    $sqlPreview = DB::from("test_items")
        ->select("id", "name", "value")
        ->andWhere("value", Operators::GREATER_THAN, 75)
        ->orderBy("id", SortDirection::DESC)
        ->limit(5)
        ->toSql();
    printBlock("1) SQL Preview (toSql)", $sqlPreview);

    // 2) SELECT + AND WHERE + OR WHERE + ORDER BY + LIMIT/OFFSET + get()
    $itemsFiltered = DB::from("test_items")
        ->select("id", "name", "description", "value", "category_id")
        ->andWhere("value", Operators::GREATER_THAN, 75)
        ->orWhere("name", Operators::EQUAL, "Item B")
        ->orderBy("id", SortDirection::ASC)
        ->limit(10, 0)
        ->get();
    printBlock("2) Filtered items (get)", $itemsFiltered);

    // 3) first()
    $firstItem = DB::from("test_items")
        ->orderBy("id", SortDirection::ASC)
        ->first();
    printBlock("3) First item (first)", $firstItem);

    // 4) JOIN
    $joinedItems = DB::from("test_items")
        ->select(
            "test_items.name as item_name",
            "categories.name as category_name",
        )
        ->join(
            "categories",
            "test_items.category_id",
            Operators::EQUAL,
            "categories.id",
        )
        ->orderBy("test_items.id", SortDirection::ASC)
        ->limit(5)
        ->get();
    printBlock("4) JOIN demo", $joinedItems);

    // 5) GROUP BY + HAVING
    $groupedByCategory = DB::from("test_items")
        ->select("category_id", "SUM(value) as total_value")
        ->groupBy("category_id")
        ->andHaving("SUM(value)", Operators::GREATER_THAN, 100)
        ->orderBy("category_id", SortDirection::ASC)
        ->get();
    printBlock("5) Group by category + having", $groupedByCategory);

    // 6) HAVING with OR condition (orHaving)
    $havingOrResults = DB::from("test_items")
        ->select("category_id", "SUM(value) as total_value")
        ->groupBy("category_id")
        ->andHaving("SUM(value)", Operators::LESS_THAN, 100)
        ->orHaving("SUM(value)", Operators::GREATER_THAN, 200)
        ->orderBy("category_id", SortDirection::ASC)
        ->get();
    printBlock("6) Group by + orHaving", $havingOrResults);

    // 7) LEFT JOIN / RIGHT JOIN (SQL preview)
    $leftJoinSql = DB::from("test_items")
        ->select("test_items.id", "categories.id as category_id")
        ->leftJoin(
            "categories",
            "test_items.category_id",
            Operators::EQUAL,
            "categories.id",
        )
        ->limit(1)
        ->toSql();
    printBlock("7) LEFT JOIN SQL", $leftJoinSql);

    $rightJoinSql = DB::from("categories")
        ->select("categories.id", "test_items.id as item_id")
        ->rightJoin(
            "test_items",
            "categories.id",
            Operators::EQUAL,
            "test_items.category_id",
        )
        ->limit(1)
        ->toSql();
    printBlock("8) RIGHT JOIN SQL", $rightJoinSql);

    // 9) Aggregates
    $aggregates = [
        "count(*)" => DB::from("test_items")->count(),
        "sum(value)" => DB::from("test_items")->sum("value"),
        "avg(value)" => DB::from("test_items")->avg("value"),
        "min(value)" => DB::from("test_items")->min("value"),
        "max(value)" => DB::from("test_items")->max("value"),
    ];
    printBlock("9) Aggregates", $aggregates);

    // 10) INSERT
    $newId = DB::from("test_items")->insert([
        "name" => "Quebu Demo",
        "description" => "Inserted from example.php",
        "value" => 200,
        "category_id" => 1,
    ]);
    printBlock("10) Insert", "Inserted item with ID: {$newId}");

    // 11) UPDATE (safe write with WHERE)
    $updated = DB::from("test_items")
        ->andWhere("id", Operators::EQUAL, $newId)
        ->update(["name" => "Quebu Demo Updated"]);
    printBlock("11) Update with WHERE", $updated);

    // 12) DELETE (safe write with WHERE)
    $deleted = DB::from("test_items")
        ->andWhere("id", Operators::EQUAL, $newId)
        ->delete();
    printBlock("12) Delete with WHERE", $deleted);

    // 13) Unsafe write override (disabled by default)
    // USE WITH EXTREME CAUTION.
    // Example (commented on purpose):
    // DB::from('test_items')->allowUnsafeWrites(true)->delete();
    printBlock(
        "13) Unsafe writes",
        "By default, update/delete without WHERE are blocked. Use allowUnsafeWrites(true) only when intentional.",
    );
} catch (\Throwable $e) {
    error_log("[Quebu example] " . $e->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
    }

    if ($debug) {
        die("<h3>Error</h3><pre>" . $e->getMessage() . "</pre>");
    }

    die("<h3>Error</h3><pre>Internal server error.</pre>");
}
