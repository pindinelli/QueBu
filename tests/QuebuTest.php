<?php

namespace Pindinelli\Quebu\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pindinelli\Quebu\DB;
use Pindinelli\Quebu\DatabaseConfig;
use Pindinelli\Quebu\Enums\Operators;
use Pindinelli\Quebu\Enums\SortDirection;

class QuebuTest extends TestCase
{
    protected function setUp(): void
    {
        \Pindinelli\Quebu\EnvResolver::clear();
        \Pindinelli\Quebu\EnvLoader::load(__DIR__ . "/..");
        try {
            $config = DatabaseConfig::fromEnvironment();
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(
                $exception->getMessage() . " for tests.",
                previous: $exception,
            );
        }

        DB::connect($config->dsn, $config->user, $config->password);

        $pdo = DB::getConnection();

        if ($pdo) {
            $pdo->exec("DROP TABLE IF EXISTS `test_items`");
            $pdo->exec("DROP TABLE IF EXISTS `categories`");

            // Create tables with MySQL specific syntax
            $pdo->exec("CREATE TABLE `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE `test_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `value` INT,
                `category_id` INT,
                FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Insert category data first
            $pdo->exec(
                "INSERT INTO `categories` (`id`, `name`) VALUES (1, 'Category 1');",
            );
            $pdo->exec(
                "INSERT INTO `categories` (`id`, `name`) VALUES (2, 'Category 2');",
            );

            // Corrected and completed INSERT statements for test_items
            $pdo->exec(
                "INSERT INTO `test_items` (`id`, `name`, `description`, `value`, `category_id`) VALUES (1, 'Item A', 'Description A', 100, 1);",
            );
            $pdo->exec(
                "INSERT INTO `test_items` (`id`, `name`, `description`, `value`, `category_id`) VALUES (2, 'Item B', 'Description B', 50, 2);",
            );
            $pdo->exec(
                "INSERT INTO `test_items` (`id`, `name`, `description`, `value`, `category_id`) VALUES (3, 'Item C', 'Description C', 150, 1);",
            );
        }
    }

    public function testSelectOrderBy()
    {
        $items = DB::from("test_items")
            ->orderBy("value", SortDirection::ASC)
            ->get();

        $this->assertCount(3, $items);

        $this->assertEquals("Item B", $items[0]["name"]);
        $this->assertEquals("Description A", $items[1]["description"]);
        $this->assertEquals(150, $items[2]["value"]);
    }

    public function testToSql()
    {
        $sql = DB::from("test_items")->select("id", "name")->toSql();
        $this->assertEquals("SELECT `id`, `name` FROM `test_items`", $sql);
    }

    public function testWhereFirst()
    {
        $item = DB::from("test_items")
            ->andWhere("value", Operators::GREATER_THAN, 100)
            ->first();

        $this->assertIsObject($item);
        $this->assertEquals("Item C", $item->name);
    }

    public function testOrWhere()
    {
        $items = DB::from("test_items")
            ->andWhere("value", Operators::EQUAL, 50)
            ->orWhere("name", Operators::EQUAL, "Item C")
            ->orderBy("value", SortDirection::ASC)
            ->get();

        $this->assertCount(2, $items);
        $this->assertEquals("Item B", $items[0]["name"]);
        $this->assertEquals("Item C", $items[1]["name"]);
    }

    public function testWhereInjectionPrevention()
    {
        $item = DB::from("test_items")
            ->andWhere("name", Operators::EQUAL, "' OR 1=1 --'")
            ->first();
        $this->assertNull(
            $item,
            "SQL injection is possible via WHERE clause values. Prepared statements might not be used correctly.",
        );
    }

    public function testIdentifierInjectionPrevention()
    {
        $this->expectException(\InvalidArgumentException::class);
        DB::from("test_items")
            ->orderBy("value; DROP TABLE test_items; --", SortDirection::ASC)
            ->get();
    }

    public function testUnsafeDeleteWithoutWhereIsBlocked()
    {
        $this->expectException(\InvalidArgumentException::class);
        DB::from("test_items")->delete();
    }

    public function testUnsafeUpdateWithoutWhereIsBlocked()
    {
        $this->expectException(\InvalidArgumentException::class);
        DB::from("test_items")->update(["name" => "Nope"]);
    }

    public function testAllowUnsafeWritesDelete()
    {
        $success = DB::from("test_items")->allowUnsafeWrites(true)->delete();
        $this->assertTrue($success);

        $remaining = DB::from("test_items")->count();
        $this->assertSame(0, $remaining);
    }

    public function testInsert()
    {
        $newId = DB::from("test_items")->insert([
            "name" => "Item D",
            "description" => "A new item",
            "value" => 200,
        ]);

        $this->assertEquals(4, $newId);

        $item = DB::from("test_items")
            ->andWhere("id", Operators::EQUAL, $newId)
            ->first();
        $this->assertNotNull($item);
        $this->assertEquals("Item D", $item->name);
        $this->assertEquals(200, $item->value);
    }

    public function testUpdate()
    {
        $success = DB::from("test_items")
            ->andWhere("id", Operators::EQUAL, 1)
            ->update(["name" => "Updated Item A", "value" => 111]);

        $this->assertTrue($success);

        $item = DB::from("test_items")
            ->andWhere("id", Operators::EQUAL, 1)
            ->first();
        $this->assertEquals("Updated Item A", $item->name);
        $this->assertEquals(111, $item->value);
    }

    public function testDelete()
    {
        $success = DB::from("test_items")
            ->andWhere("id", Operators::EQUAL, 2)
            ->delete();
        $this->assertTrue($success);
        $item = DB::from("test_items")
            ->andWhere("id", Operators::EQUAL, 2)
            ->first();
        $this->assertNull($item);
    }

    public function testJoin()
    {
        $item = DB::from("test_items")
            ->select(
                "test_items.name as item_name",
                "categories.name as category_name",
            )
            ->join("categories", "test_items.category_id", "categories.id")
            ->andWhere("test_items.id", Operators::EQUAL, 1)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals("Item A", $item->item_name);
        $this->assertEquals("Category 1", $item->category_name);
    }

    public function testLeftJoin()
    {
        $item = DB::from("test_items")
            ->select(
                "test_items.name as item_name",
                "categories.name as category_name",
            )
            ->leftJoin("categories", "test_items.category_id", "categories.id")
            ->andWhere("test_items.id", Operators::EQUAL, 1)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals("Item A", $item->item_name);
        $this->assertEquals("Category 1", $item->category_name);
    }

    public function testRightJoin()
    {
        $item = DB::from("categories")
            ->select(
                "categories.name as category_name",
                "test_items.name as item_name",
            )
            ->rightJoin("test_items", "categories.id", "test_items.category_id")
            ->andWhere("test_items.id", Operators::EQUAL, 1)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals("Category 1", $item->category_name);
        $this->assertEquals("Item A", $item->item_name);
    }

    public function testCount()
    {
        $count = DB::from("test_items")
            ->andWhere("value", Operators::GREATER_THAN, 75)
            ->count();
        $this->assertEquals(2, $count);
    }

    public function testSum()
    {
        $sum = DB::from("test_items")->sum("value");
        $this->assertEquals(300, $sum);
    }

    public function testAvg()
    {
        $avg = DB::from("test_items")->avg("value");
        $this->assertEquals(100, $avg);
    }

    public function testMinMax()
    {
        $min = DB::from("test_items")->min("value");
        $this->assertEquals(50, $min);

        $max = DB::from("test_items")->max("value");
        $this->assertEquals(150, $max);
    }

    public function testGroupByHaving()
    {
        $results = DB::from("test_items")
            ->select("category_id", "SUM(value) as total_value")
            ->groupBy("category_id")
            ->andHaving("SUM(value)", Operators::GREATER_THAN, 100)
            ->orderBy("category_id", SortDirection::ASC) // Added for diagnostics
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(1, $results[0]["category_id"]);
        $this->assertEquals(250, $results[0]["total_value"]);
    }

    public function testOrHaving()
    {
        $results = DB::from("test_items")
            ->select("category_id", "SUM(value) as total_value")
            ->groupBy("category_id")
            ->andHaving("SUM(value)", Operators::LESS_THAN, 100)
            ->orHaving("SUM(value)", Operators::GREATER_THAN, 200)
            ->orderBy("category_id")
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals(1, $results[0]["category_id"]);
        $this->assertEquals(2, $results[1]["category_id"]);
    }

    public function testLimitOffset()
    {
        $item = DB::from("test_items")
            ->orderBy("value", SortDirection::ASC)
            ->limit(1, 1)
            ->first();
        $this->assertEquals("Item A", $item->name);
    }

    public function testConnectionRequired()
    {
        DB::disconnect();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            "Database not connected. Call DB::connect() first.",
        );
        DB::from("test_items");
    }

    protected function tearDown(): void
    {
        DB::disconnect();
    }
}
