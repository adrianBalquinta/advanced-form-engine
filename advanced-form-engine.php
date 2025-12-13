<?php
/**
 * Plugin Name: Advanced Form Engine
 * Description: Gutenberg-compatible form engine with conditional logic & API integrations.
 * Version: 0.1.0
 * Author: Adrian Balquinta
 * Text Domain: advanced-form-engine
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * PSR-4 style autoloader for the AFE\ namespace.
 */
spl_autoload_register(static function (string $class): void {
    $prefix   = 'AFE\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Not a namespace, ignore.
        return;
    }

    $relative_class = substr($class, $len);
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use AFE\Core\Plugin;

function afe(): Plugin {
    static $plugin = null;

    if ($plugin === null) {
        $plugin = new Plugin(__FILE__);
    }

    return $plugin;
}

use AFE\Core\Activator;

register_activation_hook(__FILE__, [Activator::class, 'activate']);

register_activation_hook(__FILE__, ['AFE\Core\Activator', 'activate']);

afe()->init();
