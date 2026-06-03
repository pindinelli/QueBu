<?php

namespace Pindinelli\Quebu\Tests;

use PHPUnit\Framework\TestCase;
use Pindinelli\Quebu\EnvLoader;
use Pindinelli\Quebu\EnvResolver;

class EnvLoaderTest extends TestCase
{
    private string $envFilePath;
    private string $envFile;

    protected function setUp(): void
    {
        $this->envFilePath = __DIR__;
        $this->envFile = $this->envFilePath . "/.env";
    }

    protected function tearDown(): void
    {
        // Clean up the created .env file
        if (file_exists($this->envFile)) {
            unlink($this->envFile);
        }

        EnvResolver::clear();
        putenv("TEST_VAR");
        putenv("TEST_VAR_QUOTED");

        unset($_ENV["TEST_VAR"]);
        unset($_ENV["TEST_VAR_QUOTED"]);
    }

    public function testLoadThrowsExceptionIfFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        EnvLoader::load("/non/existent/path");
    }

    public function testLoadParsesSimpleVariables()
    {
        file_put_contents($this->envFile, "TEST_VAR=test_value");
        $values = EnvLoader::load($this->envFilePath);

        $this->assertEquals(["TEST_VAR" => "test_value"], $values);
        $this->assertEquals("test_value", EnvResolver::get("TEST_VAR"));
        $this->assertArrayNotHasKey("TEST_VAR", $_ENV);
        $this->assertFalse(getenv("TEST_VAR"));
    }

    public function testLoadParsesQuotedVariablesAndIgnoresComments()
    {
        $content = <<<EOT
            # This is a comment
            TEST_VAR_QUOTED="a quoted value"
        EOT;
        file_put_contents($this->envFile, $content);
        $values = EnvLoader::load($this->envFilePath);

        $this->assertEquals(["TEST_VAR_QUOTED" => "a quoted value"], $values);
        $this->assertEquals(
            "a quoted value",
            EnvResolver::get("TEST_VAR_QUOTED"),
        );
        $this->assertArrayNotHasKey("TEST_VAR_QUOTED", $_ENV);
        $this->assertFalse(getenv("TEST_VAR_QUOTED"));
    }
}
