<?php namespace Herbert\Framework\Providers;

use Illuminate\Support\ServiceProvider;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Extension_Debug;
use Twig_SimpleFunction;

/**
 * @see http://getherbert.com
 */
class TwigServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('twig.loader', function ()
        {
            $loader = new Twig_Loader_Filesystem();

            foreach ($this->app->getPlugins() as $plugin)
            {
                $loader->addPath($plugin->getBasePath() . '/views', $plugin->getTwigNamespace());
            }

            return $loader;
        });

        $this->app->bind('twig.options', function ()
        {
            return [
                'debug' => $this->app->environment() === 'local',
                'charset' => 'utf-8',
                'cache' => content_directory() . '/twig-cache',
                'auto_reload' => true,
                'strict_variables' => false,
                'autoescape' => true,
                'optimizations' => -1
            ];
        });

        $this->app->bind('twig.functions', function ()
        {
            return [
                'dd',
                'herbert',
                'view',
                'content_directory',
                'plugin_directory',
                'panel_url',
                'route_url',
                'session',
                'session_flashed',
                'errors'
            ];
        });

        $this->app->singleton('twig', function ()
        {
            return $this->constructTwig();
        });

        $this->app->alias(
            'twig',
            'Twig_Environment'
        );
    }

    /**
     * Constructs Twig.
     *
     * @return Twig_Environment
     */
    public function constructTwig()
    {
        $twig = new Twig_Environment($this->app['twig.loader'], $this->app['twig.options']);

        if ($this->app->environment() === 'local')
        {
            $twig->addExtension(new Twig_Extension_Debug);
        }

        foreach ($this->app->getViewGlobals() as $key => $value)
        {
            $twig->addGlobal($key, $value);
        }

        $twig->addGlobal('errors', $this->app['errors']);

        foreach ((array) $this->app['twig.functions'] as $function)
        {
            $twig->addFunction(new Twig_SimpleFunction($function, $function));
        }

        return $twig;
    }

}
