# QueBu - A Lightweight, Zero-Dependency PHP Query Builder

![QueBu Logo](logo.png)

QueBu is a lightweight, dependency-free SQL query builder for PHP. It offers a clean, fluent API to build complex queries programmatically without the overhead of heavy ORMs.

### 🎯 Why QueBu?
 In many scenarios, such as micro-tools, CLI scripts, or legacy shared hosting—installing a full-blown ORM is often overkill or technically impossible. QueBu bridges this gap by providing:

*   **Zero Dependencies for Production:** No `vendor` bloat. Pure, optimized PHP.
*   **Standalone Autoloader:** Works out-of-the-box in restricted environments without Composer.
*   **Security First:** Automatically uses prepared statements to prevent SQL Injection.
*   **Minimal Memory Footprint:** Built for performance in resource-constrained environments.

---

### 🛠 Installation and Usage

This is the primary, zero-dependency method for using QueBu in any project.

1.  Copy the `src` directory and the `autoload.php` file into your application.
2.  Include the custom autoloader and start building queries.
3.  Create your local environment file from the example template:
    ```bash
    cp .env.example .env
    ```
4.  Edit `.env` and set your database credentials (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, etc.).

```php
<?php
require __DIR__ . '/autoload.php';

use Pindinelli\Quebu\DB;
use Pindinelli\Quebu\EnvLoader;
use Pindinelli\Quebu\Enums\Operators;

// 1. Load environment variables from a .env file
EnvLoader::load(__DIR__);

// 2. Build the DSN string
$dsn = sprintf(
    "%s:host=%s;port=%s;dbname=%s;charset=%s",
    $_ENV['DB_CONNECTION'] ?? 'mysql',
    $_ENV['DB_HOST'] ?? '127.0.0.1',
    $_ENV['DB_PORT'] ?? '3306',
    $_ENV['DB_DATABASE'] ?? null,
    $_ENV['DB_CHARSET'] ?? 'utf8mb4'
);

// 3. Connect to the database
DB::connect($dsn, $_ENV['DB_USERNAME'] ?? null, $_ENV['DB_PASSWORD'] ?? null);

// 4. Start building queries!
$users = DB::from('users')
    ->andWhere('registration_date', Operators::GREATER_THAN, '2023-01-01')
    ->orderBy('name')
    ->get();

print_r($users);
```

#### With Composer (Recommended for Modern Projects)

If your project already uses Composer, this is the standard approach. Once QueBu is published, you can add it as a dependency.

1.  **Install QueBu via Composer:**
    ```bash
    # This command will be available once the package is published on Packagist
    composer require pindinelli/quebu
    ```

2.  **Use Composer's Autoloader:**
    Include `vendor/autoload.php`. This allows you to seamlessly use QueBu alongside other libraries. For loading environment variables, it's common to use a dedicated package like `vlucas/phpdotenv`.

    ```php
    <?php
    require __DIR__ . '/vendor/autoload.php';

    use Pindinelli\Quebu\DB;
    use Pindinelli\Quebu\Enums\Operators;
    use Dotenv\Dotenv;

    // 1. Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // 2. Build DSN and connect
    $dsn = sprintf(
        "%s:host=%s;port=%s;dbname=%s;charset=%s",
        $_ENV['DB_CONNECTION'], $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE'], $_ENV['DB_CHARSET']
    );
    DB::connect($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);

    // 3. Start building queries!
    $users = DB::from('users')->get();
    print_r($users);
    ```

---

### API Examples

**SELECT with a JOIN**
```php
$posts = DB::from('posts')
    ->select('posts.title', 'users.name as author')
    ->join('users', 'posts.user_id', Operators::EQUAL, 'users.id')
    ->limit(5)
    ->get();
```

**INSERT, UPDATE, and DELETE**
```php
DB::from('users')->insert(['name' => 'Ginevra', 'email' => 'ginevra@example.com']);
DB::from('users')->andWhere('name', Operators::EQUAL, 'Ginevra')->update(['name' => 'Ginevra Verdi']);
DB::from('users')->andWhere('name', Operators::EQUAL, 'Ginevra Verdi')->delete();
```

---

### 🔬 Development and Testing

While the library itself has no production dependencies, **Composer is used for development** to manage testing tools like PHPUnit.

To contribute or run the tests locally:

1.  **Clone the repository** and navigate into the directory.
2.  **Install development dependencies:** `composer install`
3.  **Run the test suite:** `composer test`