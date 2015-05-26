<?php

/**
 * Ensure this is only ran once.
 */
if (defined('HERBERT_AUTOLOAD'))
{
    return;
}

define('HERBERT_AUTOLOAD', microtime(true));

/**
 * Load the WP plugin system.
 */
if (array_search(ABSPATH . 'wp-admin/includes/plugin.php', get_included_files()) === false)
{
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Get Herbert.
 */
$herbert = Herbert\Framework\Application::getInstance();

/**
 * Load all herbert.php files in plugin roots.
 */
$iterator = new DirectoryIterator(ABSPATH . 'wp-content/plugins');

foreach ($iterator as $directory)
{
    if ( ! $directory->valid() || $directory->isDot() || ! $directory->isDir())
    {
        continue;
    }

    $root = $directory->getPath() . '/' . $directory->getFilename();

    if ( ! file_exists($root . '/herbert.config.php'))
    {
        continue;
    }

    $config = $herbert->getPluginConfig($root);

    if ( ! $herbert->shouldLoadPlugin($config))
    {
        continue;
    }

    register_activation_hook($root . '/plugin.php', function () use ($herbert, $config, $root)
    {
        $herbert->loadPlugin($config);
        $herbert->activatePlugin($root);
    });

    register_deactivation_hook($root . '/plugin.php', function () use ($herbert, $root)
    {
        $herbert->deactivatePlugin($root);
    });

    if ( ! is_plugin_active(substr($root, strlen(ABSPATH . 'wp-content/plugins/')) . '/plugin.php'))
    {
        continue;
    }

    $herbert->loadPlugin($config);
}

/**
 * Boot Herbert.
 */
$herbert->boot();
