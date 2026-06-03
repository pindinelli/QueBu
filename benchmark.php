<?php

declare(strict_types=1);

require __DIR__ . "/autoload.php";

use Pindinelli\Quebu\DB;
use Pindinelli\Quebu\DatabaseConfig;
use Pindinelli\Quebu\EnvLoader;
use Pindinelli\Quebu\Enums\Operators;

function stats(array $samplesUs): array
{
    sort($samplesUs);
    $n = count($samplesUs);
    $sum = array_sum($samplesUs);
    $avg = $n > 0 ? $sum / $n : 0.0;
    $min = $n > 0 ? $samplesUs[0] : 0.0;
    $max = $n > 0 ? $samplesUs[$n - 1] : 0.0;

    $p95Index = (int) floor(($n - 1) * 0.95);
    $p99Index = (int) floor(($n - 1) * 0.99);

    return [
        "avg" => $avg,
        "min" => $min,
        "max" => $max,
        "p95" => $samplesUs[$p95Index] ?? 0.0,
        "p99" => $samplesUs[$p99Index] ?? 0.0,
    ];
}

function runTimed(callable $fn, int $iterations): array
{
    $samplesUs = [];

    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $fn();
        $elapsedNs = hrtime(true) - $start;
        $samplesUs[] = $elapsedNs / 1000.0;
    }

    return $samplesUs;
}

EnvLoader::load(__DIR__);
$config = DatabaseConfig::fromEnvironment();
DB::connect($config->dsn, $config->user, $config->password);

$pdo = DB::getConnection();
if ($pdo === null) {
    fwrite(STDERR, "No PDO connection.\n");
    exit(1);
}

$warmup = 300;
$iterations = 3000;

$bench = function (string $title, callable $rawFn, callable $quebuFn) use (
    $warmup,
    $iterations,
): void {
    for ($i = 0; $i < $warmup; $i++) {
        $quebuFn();
    }

    $rawSamples = runTimed($rawFn, $iterations);
    $quebuSamples = runTimed($quebuFn, $iterations);

    $rawStats = stats($rawSamples);
    $quebuStats = stats($quebuSamples);

    printf("=== %s ===\n", $title);
    printf("Iterations: %d\n", $iterations);

    printf(
        "PDO raw (us): avg=%.2f min=%.2f p95=%.2f p99=%.2f max=%.2f\n",
        $rawStats["avg"],
        $rawStats["min"],
        $rawStats["p95"],
        $rawStats["p99"],
        $rawStats["max"],
    );
    printf(
        "Quebu   (us): avg=%.2f min=%.2f p95=%.2f p99=%.2f max=%.2f\n",
        $quebuStats["avg"],
        $quebuStats["min"],
        $quebuStats["p95"],
        $quebuStats["p99"],
        $quebuStats["max"],
    );

    $overheadAvg = $quebuStats["avg"] - $rawStats["avg"];
    $overheadPct =
        $rawStats["avg"] > 0 ? ($overheadAvg / $rawStats["avg"]) * 100.0 : 0.0;
    printf(
        "Average overhead: %.2f us/op (%.2f%%)\n\n",
        $overheadAvg,
        $overheadPct,
    );
};

// 1) Simple SELECT + WHERE + LIMIT 1
$rawSimple = function () use ($pdo): void {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id > ? LIMIT 1");
    $stmt->execute([1]);
    $stmt->fetch();
};

$quebuSimple = function (): void {
    DB::from("users")->andWhere("id", Operators::GREATER_THAN, 1)->first();
};

$bench("Simple SELECT", $rawSimple, $quebuSimple);

// 2) JOIN + GROUP BY + HAVING
$rawJoinGroup = function () use ($pdo): void {
    $stmt = $pdo->prepare(
        'SELECT u1.registration_date, COUNT(*) as total_users
         FROM users u1
         LEFT JOIN users u2 ON u1.id = u2.id
         GROUP BY u1.registration_date
         HAVING COUNT(*) >= ?
         ORDER BY u1.registration_date ASC
         LIMIT 5',
    );
    $stmt->execute([1]);
    $stmt->fetchAll();
};

$quebuJoinGroup = function (): void {
    DB::from("users as u1")
        ->select("u1.registration_date", "COUNT(*) as total_users")
        ->leftJoin("users as u2", "u1.id", Operators::EQUAL, "u2.id")
        ->groupBy("u1.registration_date")
        ->andHaving("COUNT(*)", Operators::GREATER_THAN_OR_EQUAL, 1)
        ->orderBy("u1.registration_date")
        ->limit(5)
        ->get();
};

$bench("JOIN + GROUP BY + HAVING", $rawJoinGroup, $quebuJoinGroup);

// 3) Write ops (UPDATE + DELETE rollbacked in transaction)
$rawWrite = function () use ($pdo): void {
    $pdo->beginTransaction();

    $update = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
    $update->execute(["Bench Raw", 1]);

    $delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $delete->execute([999999]);

    $pdo->rollBack();
};

$quebuWrite = function () use ($pdo): void {
    $pdo->beginTransaction();

    DB::from("users")
        ->andWhere("id", Operators::EQUAL, 1)
        ->update(["name" => "Bench Quebu"]);

    DB::from("users")->andWhere("id", Operators::EQUAL, 999999)->delete();

    $pdo->rollBack();
};

$bench("WRITE (UPDATE + DELETE in TX rollback)", $rawWrite, $quebuWrite);
