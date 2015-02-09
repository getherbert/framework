<?php namespace Herbert\Framework;

use Illuminate\Contracts\Container\Container;

/**
 * @see http://getherbert.com
 */
interface Plugin {

    /**
     * Activate the plugin.
     *
     * @return void
     */
    public function activate();

    /**
     * Deactivate the plugin.
     *
     * @return void
     */
    public function deactivate();

    /**
     * Get the configuration.
     *
     * @return array
     */
    public function getConfig();

    /**
     * Set the base path.
     *
     * @param $path
     */
    public function setBasePath($path);

    /**
     * Get the base path.
     *
     * @return mixed
     */
    public function getBasePath();

    /**
     * Sets the IoC Container.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function setContainer(Container $container);

    /**
     * Gets the IoC Container.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer();

}
