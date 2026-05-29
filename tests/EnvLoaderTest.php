<?php

namespace Pindinelli\Quebu\Tests;

use PHPUnit\Framework\TestCase;
use Pindinelli\Quebu\EnvLoader;

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

        unset($_ENV["TEST_VAR"], $_SERVER["TEST_VAR"]);
        unset($_ENV["TEST_VAR_QUOTED"], $_SERVER["TEST_VAR_QUOTED"]);
    }

    public function testLoadThrowsExceptionIfFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        EnvLoader::load("/non/existent/path");
    }

    public function testLoadParsesSimpleVariables()
    {
        file_put_contents($this->envFile, "TEST_VAR=test_value");
        EnvLoader::load($this->envFilePath);

        $this->assertEquals("test_value", $_ENV["TEST_VAR"]);
        $this->assertEquals("test_value", $_SERVER["TEST_VAR"]);
    }

    public function testLoadParsesQuotedVariablesAndIgnoresComments()
    {
        $content = <<<EOT
            # This is a comment
            TEST_VAR_QUOTED="a quoted value"
        EOT;
        file_put_contents($this->envFile, $content);
        EnvLoader::load($this->envFilePath);

        $this->assertEquals("a quoted value", $_ENV["TEST_VAR_QUOTED"]);
        $this->assertEquals("a quoted value", $_SERVER["TEST_VAR_QUOTED"]);
    }
}
