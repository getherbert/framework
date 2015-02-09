<?php namespace Herbert\Framework;

use Illuminate\Support\ServiceProvider;

/**
 * @see http://getherbert.com
 */
class Application extends \Illuminate\Container\Container implements \Illuminate\Contracts\Foundation\Application {

    /**
     * The application's version.
     */
    const VERSION = '1.0.0-dev';

    /**
     * @var \Herbert\Framework\Application
     */
    protected static $instance;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = array();

    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = array();

    /**
     * The array of terminating callbacks.
     *
     * @var array
     */
    protected $terminatingCallbacks = array();

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = array();

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = array();

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = array();

    /**
     * The registered plugins.
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * Constructs the application and ensures it's correctly setup.
     */
    public function __construct()
    {
        static::$instance = $this;

        $this->instance('app', $this);
        $this->instance('Illuminate\Container\Container', $this);

        $this->registerBaseProviders();
        $this->registerCoreContainerAliases();
        $this->registerConfiguredProviders();
    }

    /**
     * Get all loaded plugins.
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Loads a plugin.
     *
     * @param $root
     */
    public function loadPlugin($root)
    {
        $config = @require_once "$root/herbert.config.php";

        $this->loadPluginRequires(
            array_get($config, 'requires', [])
        );

        $this->loadPluginX(
            'router',
            array_get($config, 'routes', [])
        );

        $this->loadPluginX(
            'enqueue',
            array_get($config, 'enqueue', [])
        );

        $this->loadPluginX(
            'panel',
            array_get($config, 'panels', [])
        );

        $this->loadPluginX(
            'shortcode',
            array_get($config, 'shortcodes', [])
        );

        $this->loadPluginX(
            'widget',
            array_get($config, 'widgets', [])
        );

        $this->loadPluginAPIs(
            array_get($config, 'apis', [])
        );

        $this->addPluginTwigNamespaces(
            array_get($config, 'views', [])
        );
    }

    /**
     * Load all a plugin's requires.
     *
     * @param array $requires
     * @return void
     */
    protected function loadPluginRequires($requires = [])
    {
        $container = $this;

        foreach ($requires as $require)
        {
            @require_once "$require";
        }
    }

    /**
     * Load all a plugin's :x.
     *
     * @param array $requires
     * @return void
     */
    protected function loadPluginX($x, $requires = [])
    {
        $container = $this;
        $$x = $this[$x];

        foreach ($requires as $require)
        {
            @require_once "$require";
        }
    }

    /**
     * Add all a plugin's twig namespaces.
     *
     * @param array $namespaces
     * @return void
     */
    protected function addPluginTwigNamespaces($namespaces = [])
    {
        $loader = $this['twig.loader'];

        foreach ($namespaces as $namespace => $paths)
        {
            foreach ((array) $paths as $path)
            {
                $loader->addPath($path, $namespace);
            }
        }
    }

    /**
     * Load the plugin's apis.
     *
     * @param array $requires
     * @return void
     */
    protected function loadPluginAPIs($requires = [])
    {
        $container = $this;

        foreach ($requires as $name => $require)
        {
            global $$name;
            $api = $$name = new API($this);

            require "$require";
        }
    }

    /**
     * Register a plugin.
     *
     * @param \Herbert\Framework\Plugin $plugin
     */
    public function registerPlugin(Plugin $plugin)
    {
        $plugin->setContainer($this);

        $this->plugins[] = $plugin;

        $this->registerPluginProviders($plugin);
        $this->registerPluginAliases($plugin);
    }

    /**
     * Deactivates a plugin.
     *
     * @see register_activation_hook()
     * @param $root
     */
    public function activatePlugin($root)
    {
        $plugins = array_filter($this->plugins, function (Plugin $plugin) use ($root)
        {
            return $plugin->getBasePath() === $root;
        });

        foreach ($plugins as $plugin)
        {
            $plugin->activate();
        }
    }

    /**
     * Deactivates a plugin.
     *
     * @see register_deactivation_hook()
     * @param $root
     */
    public function deactivatePlugin($root)
    {
        $plugins = array_filter($this->plugins, function (Plugin $plugin) use ($root)
        {
            return $plugin->getBasePath() === $root;
        });

        foreach ($plugins as $plugin)
        {
            $plugin->deactivate();
        }
    }

    /**
     * Register all of the plugin's providers.
     *
     * @param \Herbert\Framework\Plugin $plugin
     * @return void
     */
    protected function registerPluginProviders(Plugin $plugin)
    {
        $providers = array_get($plugin->getConfig(), 'providers', []);

        foreach ($providers as $provider)
        {
            $this->register(
                $this->resolveProviderClass($provider)
            );
        }
    }

    /**
     * Register all of the plugin's aliases.
     *
     * @param \Herbert\Framework\Plugin $plugin
     * @return void
     */
    protected function registerPluginAliases(Plugin $plugin)
    {
        $aliases = array_get($plugin->getConfig(), 'aliases', []);

        foreach ($aliases as $key => $aliases)
        {
            foreach ((array) $aliases as $alias)
            {
                $this->alias($key, $alias);
            }
        }
    }

//    /**
//     * Register the plugin's configured requires.
//     *
//     * @param \Herbert\Framework\Plugin $plugin
//     */
//    protected function registerPluginRequires(Plugin $plugin)
//    {
//        $requires = array_get($plugin->getConfig(), 'requires', []);
//        $container = $this;
//
//        foreach ($requires as $require)
//        {
//            require "$require";
//        }
//    }

//    /**
//     * Register the plugin's configured routes.
//     *
//     * @param \Herbert\Framework\Plugin $plugin
//     */
//    protected function registerPluginRoutes(Plugin $plugin)
//    {
//        $requires = array_get($plugin->getConfig(), 'routes', []);
//        $router = $this['router'];
//
//        foreach ($requires as $require)
//        {
//            require "$require";
//        }
//    }

//    /**
//     * Register the plugin's configured shortcodes.
//     *
//     * @param \Herbert\Framework\Plugin $plugin
//     */
//    protected function registerPluginShortcodes(Plugin $plugin)
//    {
//        $requires = array_get($plugin->getConfig(), 'shortcodes', []);
//        $shortcode = $this['shortcode'];
//
//        foreach ($requires as $require)
//        {
//            require "$require";
//        }
//    }

    /**
     * Register the base providers.
     *
     * @return void
     */
    protected function registerBaseProviders()
    {
        $this->register($this->resolveProviderClass(
            'Herbert\Framework\Providers\HerbertServiceProvider'
        ));

        $this->register($this->resolveProviderClass(
            'Herbert\Framework\Providers\TwigServiceProvider'
        ));
    }

    /**
     * Register the core aliases.
     *
     * @return void
     */
    protected function registerCoreContainerAliases()
    {
        $aliases = [
            'app' => [
                'Illuminate\Foundation\Application',
                'Illuminate\Contracts\Container\Container',
                'Illuminate\Contracts\Foundation\Application'
            ]
        ];

        foreach ($aliases as $key => $aliases)
        {
            foreach ((array) $aliases as $alias)
            {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Register all of the configured providers.
     *
     * @todo
     * @return void
     */
    public function registerConfiguredProviders()
    {
        //
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @param  array                                      $options
     * @param  bool                                       $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = array(), $force = false)
    {
        if ($registered = $this->getProvider($provider) && ! $force)
        {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider))
        {
            $provider = $this->resolveProviderClass($provider);
        }

        $provider->register();

        // Once we have registered the service we will iterate through the options
        // and set each of them on the application so they will be available on
        // the actual loading of the service objects and for developer usage.
        foreach ($options as $key => $value)
        {
            $this[$key] = $value;
        }

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by the developer's application logics.
        if ($this->booted)
        {
            $this->bootProvider($provider);
        }

        return $provider;
    }


    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return array_first($this->serviceProviders, function($key, $value) use ($name)
        {
            return $value instanceof $name;
        });
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return $this->make($provider, ['app' => $this]);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  \Illuminate\Support\ServiceProvider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;

        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach ($this->deferredServices as $service => $provider)
        {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = array();
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string  $service
     * @return void
     */
    public function loadDeferredProvider($service)
    {
        if ( ! isset($this->deferredServices[$service]))
        {
            return;
        }

        $provider = $this->deferredServices[$service];

        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
        if ( ! isset($this->loadedProviders[$provider]))
        {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string $provider
     * @param  string $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        // Once the provider that provides the deferred service has been registered we
        // will remove it from our local list of the deferred services with related
        // providers so that this container does not try to resolve it out again.
        if ($service) unset($this->deferredServices[$service]);

        $this->register($instance = new $provider($this));

        if ( ! $this->booted)
        {
            $this->booting(function() use ($instance)
            {
                $this->bootProvider($instance);
            });
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, $parameters = array())
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->deferredServices[$abstract]))
        {
            $this->loadDeferredProvider($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * (Overriding Container::bound)
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->deferredServices[$abstract]) || parent::bound($abstract);
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) return;

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p)
        {
            $this->bootProvider($p);
        });

        array_walk($this->plugins, function ($p)
        {
            if (!method_exists($p, 'boot'))
            {
                return;
            }

            $this->call([$p, 'boot'], ['app' => $this]);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Boot the given service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot'))
        {
            return $this->call([$provider, 'boot']);
        }

        return null;
    }

    /**
     * Register a new boot listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) $this->fireAppCallbacks(array($callback));
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  array  $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback)
        {
            call_user_func($callback, $this);
        }
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Get or check the current application environment.
     *
     * @param  mixed
     * @return string
     */
    public function environment()
    {
        if (func_num_args() > 0)
        {
            $patterns = is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args();

            foreach ($patterns as $pattern)
            {
                if (str_is($pattern, $this['env']))
                {
                    return true;
                }
            }

            return false;
        }

        return $this['env'];
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @todo
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return false;
    }

    /**
     * Get the global container instance.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance))
        {
            static::$instance = new static;
        }

        return static::$instance;
    }

}
