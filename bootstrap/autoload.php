<?php

/**
 * Ensure this is only ran once.
 */
if (defined('HERBERT_AUTOLOAD'))
{
    return;
}

define('HERBERT_AUTOLOAD', microtime(true));

@require 'helpers.php';

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
$iterator = new DirectoryIterator(plugin_directory());


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

    $plugin = substr($root . '/plugin.php', strlen(plugin_directory()));
    $plugin = ltrim($plugin, '/');

    register_activation_hook($plugin, function () use ($herbert, $config, $root)
    {
        if ( ! $herbert->pluginMatches($config))
        {
            $herbert->pluginMismatched($root);
        }

        $herbert->pluginMatched($root);
        $herbert->loadPlugin($config);
        $herbert->activatePlugin($root);
    });

    register_deactivation_hook($plugin, function () use ($herbert, $root)
    {
        $herbert->deactivatePlugin($root);
    });

    // Ugly hack to make the install hook work correctly
    // as WP doesn't allow closures to be passed here
    register_uninstall_hook($plugin, create_function('', 'herbert()->deletePlugin(\'' . $root . '\');'));

    if ( ! is_plugin_active($plugin))
    {
        continue;
    }

    if ( ! $herbert->pluginMatches($config))
    {
        $herbert->pluginMismatched($root);

        continue;
    }

    $herbert->pluginMatched($root);
    $herbert->loadPlugin($config);
}

/**
 * Boot Herbert.
 */
$herbert->boot();
