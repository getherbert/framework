<?php namespace Herbert\Framework\Providers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cookie\CookieJar;
use Herbert\Framework\Session;

/**
 * @see http://getherbert.com
 */
class HerbertServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEloquent();

        $this->app->instance(
            'env',
            defined('HERBERT_ENV') ? HERBERT_ENV
                : (defined('WP_DEBUG') ? 'local'
                    : 'production')
        );

        $this->app->instance(
            'http',
            \Herbert\Framework\Http::capture()
        );

        $this->app->alias(
            'http',
            'Herbert\Framework\Http'
        );

        $this->app->instance(
            'router',
            $this->app->make('Herbert\Framework\Router', ['app' => $this->app])
        );

        $this->app->bind(
            'route',
            'Herbert\Framework\Route'
        );

        $this->app->instance(
            'enqueue',
            $this->app->make('Herbert\Framework\Enqueue', ['app' => $this->app])
        );

        $this->app->alias(
            'enqueue',
            'Herbert\Framework\Enqueue'
        );

        $this->app->instance(
            'panel',
            $this->app->make('Herbert\Framework\Panel', ['app' => $this->app])
        );

        $this->app->alias(
            'panel',
            'Herbert\Framework\Panel'
        );

        $this->app->instance(
            'shortcode',
            $this->app->make('Herbert\Framework\Shortcode', ['app' => $this->app])
        );

        $this->app->alias(
            'shortcode',
            'Herbert\Framework\Shortcode'
        );

        $this->app->instance(
            'widget',
            $this->app->make('Herbert\Framework\Widget', ['app' => $this->app])
        );

        $this->app->alias(
            'widget',
            'Herbert\Framework\Widget'
        );

        $this->app->instance(
            'session',
            $this->app->make('Herbert\Framework\Session', ['app' => $this->app])
        );

        $this->app->alias(
            'session',
            'Herbert\Framework\Session'
        );

        $this->app->instance(
            'notifier',
            $this->app->make('Herbert\Framework\Notifier', ['app' => $this->app])
        );

        $this->app->alias(
            'notifier',
            'Herbert\Framework\Notifier'
        );

        $this->app->singleton(
            'errors',
            function ()
            {
                return session_flashed('__validation_errors', []);
            }
        );

        $_GLOBALS['errors'] = $this->app['errors'];
    }

    /**
     * Registers Eloquent.
     *
     * @return void
     */
    protected function registerEloquent()
    {
        global $wpdb;

        $capsule = new Capsule($this->app);

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            'collation' => DB_COLLATE ?: 'utf8_general_ci',
            'prefix' => $wpdb->prefix
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    /**
     * Boots the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['session']->start();
    }

}
