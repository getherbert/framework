<?php namespace Herbert\Framework;

use Illuminate\Support\ServiceProvider;
use vierbergenlars\SemVer\version as SemVersion;
use vierbergenlars\SemVer\expression as SemVersionExpression;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Schema\Blueprint as SchemaBlueprint;

/**
 * @see http://getherbert.com
 */
class Application extends \Illuminate\Container\Container implements \Illuminate\Contracts\Foundation\Application {

    /**
     * The application's version.
     */
    const VERSION = '0.9.13';

    /**
     * The application's version.
     *
     * @var \vierbergenlars\SemVer\version
     */
    protected $version;

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
     * The mismatched plugins.
     *
     * @var array
     */
    protected $mismatched = [];

    /**
     * The matched plugins.
     *
     * @var array
     */
    protected $matched = [];

    /**
     * The plugin apis.
     *
     * @var array
     */
    protected $apis = [];

    /**
     * The plugin configurations.
     *
     * @var array
     */
    protected $configurations = [];

    /**
     * The view composers.
     *
     * @var array
     */
    protected $viewComposers = [];

    /**
     * The view globals.
     *
     * @var array
     */
    protected $viewGlobals = [];

    /**
     * The built view globals.
     *
     * @var array
     */
    protected $builtViewGlobals = null;

    /**
     * Constructs the application and ensures it's correctly setup.
     */
    public function __construct()
    {
        static::$instance = $this;

        $this->version = new SemVersion(self::VERSION);

        $this->instance('app', $this);
        $this->instance('Illuminate\Container\Container', $this);

        $this->registerBaseProviders();
        $this->registerCoreContainerAliases();
        $this->registerConfiguredProviders();
    }

    /**
     *  Added to satisfy interface
     *
     *  @return string
     */
    public function basePath()
    {
        return content_directory() . '/herbert-cache';
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
     * Gets a plugin's configuration.
     *
     * @param  string $root
     * @return array
     */
    public function getPluginConfig($root)
    {
        if ( ! isset($this->configurations[$root]))
        {
            $this->configurations[$root] = @require_once "$root/herbert.config.php" ?: [];
        }

        return $this->configurations[$root];
    }

    /**
     * Checks if a plugin version is matches.
     *
     * @param  array $config
     * @return bool
     */
    public function pluginMatches($config)
    {
        $constraint = array_get($config, 'constraint', self::VERSION);

        return $this->version->satisfies(new SemVersionExpression($constraint));
    }

    /**
     * Logs a plugin as incompatable.
     *
     * @param  string $root
     * @return void
     */
    public function pluginMismatched($root)
    {
        $this->mismatched[] = $root;
    }

    /**
     * Logs a plugin as compatable.
     *
     * @param  string $root
     * @return void
     */
    public function pluginMatched($root)
    {
        $this->matched[] = $root;
    }

    /**
     * Notifies the user of mismatched plugins.
     *
     * @return void
     */
    protected function notifyMismatched()
    {
        $matched = array_map(function ($value)
        {
            return basename($value);
        }, $this->matched);

        $mismatched = array_map(function ($value)
        {
            return basename($value);
        }, $this->mismatched);

        $message = 'Unfortunately plugin(s) '
                . implode(', ', $mismatched)
                . ' can’t work with the following plugin(s) '
                . implode(', ', $matched)
                . '. Please disable and try updating all of the above plugins before reactivating.';

        Notifier::error($message);
    }

    /**
     * Loads a plugin.
     *
     * @param  array $config
     * @return void
     */
    public function loadPlugin($config)
    {
        $this->loadPluginRequires(
            array_get($config, 'requires', [])
        );

        $this->loadPluginRoutes(
            'router',
            array_get($config, 'routes', [])
        );

        $this->loadPluginPanels(
            'panel',
            array_get($config, 'panels', [])
        );

        $this->loadPluginX(
            'enqueue',
            array_get($config, 'enqueue', [])
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

        $this->addPluginViewGlobals(
            array_get($config, 'viewGlobals', [])
        );

        $this->addPluginComposers(
            array_get($config, 'viewComposers', [])
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
     * Load all a plugin's routes.
     *
     * @param array $routes
     * @return void
     */
    protected function loadPluginRoutes($x, $routes = [])
    {
        $container = $this;
        $router = $this['router'];

        foreach ($routes as $namespace => $requires)
        {
            $router->setNamespace($namespace);

            foreach ((array) $requires as $require)
            {
                @require_once "$require";
            }

            $router->unsetNamespace();
        }
    }

    /**
     * Load all a plugin's panels.
     *
     * @param array $panels
     * @return void
     */
    protected function loadPluginPanels($x, $panels = [])
    {
        $container = $this;
        $panel = $this['panel'];

        foreach ($panels as $namespace => $requires)
        {
            $panel->setNamespace($namespace);

            foreach ((array) $requires as $require)
            {
                @require_once "$require";
            }

            $panel->unsetNamespace();
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
     * Add all a plugin's view globals.
     *
     * @param array $globals
     * @return void
     */
    protected function addPluginViewGlobals($globals = [])
    {
        foreach ($globals as $key => $_globals)
        {
            if (is_numeric($key))
            {
                $key = null;
            }

            $this->viewGlobals[] = [$key, $_globals];
        }

        $this->builtViewGlobals = null;
    }

    /**
     * Add all a plugin's view composers.
     *
     * @param array $composers
     * @return void
     */
    protected function addPluginComposers($composers = [])
    {
        foreach ($composers as $match => $_composers)
        {
            $this->viewComposers[] = [$match, (array) $_composers];
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
            $this->apis[] = [$name, $api];

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

        $config = $this->getPluginConfig($root);

        foreach (array_get($config, 'tables', []) as $table => $class)
        {
            if ( ! class_exists($class))
            {
                continue;
            }

            if (CapsuleManager::schema()->hasTable($table))
            {
                continue;
            }

            CapsuleManager::schema()->create($table, function (SchemaBlueprint $table) use ($class)
            {
                $this->call($class . '@activate', ['table' => $table, 'app' => $this]);
            });
        }

        foreach (array_get($config, 'activators', []) as $activator)
        {
            if ( ! file_exists($activator))
            {
                continue;
            }

            $this->loadWith($activator, [
                'http',
                'router',
                'enqueue',
                'panel',
                'shortcode',
                'widget'
            ]);
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

        $config = $this->getPluginConfig($root);

        foreach (array_get($config, 'deactivators', []) as $deactivator)
        {
            if ( ! file_exists($deactivator))
            {
                continue;
            }

            $this->loadWith($deactivator, [
                'http',
                'router',
                'enqueue',
                'panel',
                'shortcode',
                'widget'
            ]);
        }

        foreach (array_get($config, 'tables', []) as $table => $class)
        {
            if ( ! class_exists($class))
            {
                continue;
            }

            if ( ! CapsuleManager::schema()->hasTable($table))
            {
                continue;
            }

            CapsuleManager::schema()->table($table, function (SchemaBlueprint $table) use ($class)
            {
                $this->call($class . '@deactivate', ['table' => $table, 'app' => $this]);
            });
        }
    }

    /**
     * Deletes a plugin.
     *
     * @see register_uninstall_hook
     * @param $root
     */
    public function deletePlugin($root)
    {
        $plugins = array_filter($this->plugins, function (Plugin $plugin) use ($root)
        {
            return $plugin->getBasePath() === $root;
        });

        foreach ($plugins as $plugin)
        {
            if ( ! method_exists($plugin, 'delete'))
            {
                continue;
            }

            $plugin->deactivate();
        }

        $config = $this->getPluginConfig($root);

        foreach (array_get($config, 'deleters', []) as $deleter)
        {
            if ( ! file_exists($deleter))
            {
                continue;
            }

            $this->loadWith($deleter, [
                'http',
                'router',
                'enqueue',
                'panel',
                'shortcode',
                'widget'
            ]);
        }

        foreach (array_get($config, 'tables', []) as $table => $class)
        {
            if ( ! class_exists($class))
            {
                continue;
            }

            if ( ! CapsuleManager::schema()->hasTable($table))
            {
                continue;
            }

            CapsuleManager::schema()->table($table, function (SchemaBlueprint $table) use ($class)
            {
                $this->call($class . '@delete', ['table' => $table, 'app' => $this]);
            });
        }
    }

    /**
     * Loads a file with variables in scope.
     *
     * @param  string $file
     * @param  array  $refs
     * @return void
     */
    protected function loadWith($file, $refs = [])
    {
        $container = $this;

        foreach ($refs as $ref)
        {
            $$ref = $this[$ref];
        }

        @require $file;
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
    public function make($abstract, Array $parameters = array())
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
            if ( ! method_exists($p, 'boot'))
            {
                return;
            }

            $this->call([$p, 'boot'], ['app' => $this]);
        });

        if (count($this->mismatched) !== 0)
        {
            $this->notifyMismatched();
        }

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
     * Get all the view globals.
     *
     * @return array
     */
    public function getViewGlobals()
    {
        if ($this->builtViewGlobals === null)
        {
            $this->buildViewGlobals();
        }

        return $this->builtViewGlobals;
    }

    /**
     * Builds the view globals.
     *
     * @return void
     */
    protected function buildViewGlobals()
    {
        $globals = [];

        foreach ($this->viewGlobals as $global)
        {
            list($key, $val) = $global;

            try {
                $val = $this->call($val, ['app' => $this]);
            }
            catch (\Exception $e)
            {
                if ((is_numeric($key) || $key === null) && is_string($val))
                {
                    continue;
                }
            }

            if ($key !== null)
            {
                $val = [$key => $val];
            }

            $val = (array) $val;

            $globals = array_merge($globals, $val);
        }

        foreach ($this->apis as $api)
        {
            list($name, $instance) = $api;

            $globals[$name] = $instance;
        }

        $this->builtViewGlobals = $globals;
    }

    /**
     * Get the view globals.
     *
     * @param  string $view
     * @return array
     */
    public function getViewsGlobals($view)
    {
        $globals = [];

        foreach ($this->viewComposers as $match => $composers)
        {
            if ( ! str_is($match, $view))
            {
                continue;
            }

            foreach ($composers as $composer)
            {
                if (is_array($composer))
                {
                    $globals = array_merge($globals, $composer);

                    continue;
                }

                $globals = array_merge((array) $this->call($composer, ['app' => $this, 'view' => $view]));
            }
        }

        return $globals;
    }

    /**
     * Sets the view globals.
     *
     * @param array $globals
     */
    public function setViewGlobals($globals)
    {
        $this->viewGlobals = $globals;
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

    /**
     * Get the path to the cached "compiled.php" file.
     *
     * @return string
     */
    public function getCachedCompilePath()
    {
        return $this->basePath() . '/vendor/compiled.php';
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return $this->basePath() . '/vendor/services.json';
    }

}
