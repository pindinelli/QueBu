<?php
/**
 * QueBu - Simple PHP Query Builder
 *
 * A lightweight PSR-4 autoloader for environments where Composer is not available.
 * This allows the library to be used by simply including this file.
 */
if (!defined("QUEBU_AUTOLOAD_REGISTERED")) {
    define("QUEBU_AUTOLOAD_REGISTERED", true);

    spl_autoload_register(function ($class) {
        $prefix = "Pindinelli\\Quebu\\";
        $base_dir = __DIR__ . "/src/";

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace("\\", "/", $relative_class) . ".php";

        if (file_exists($file)) {
            require_once $file;
        }
    });
}
