<?php namespace Herbert\Framework;

use Exception;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;
use Herbert\Framework\Exceptions\HttpErrorException;

/**
 * @see http://getherbert.com
 */
class Panel {

    /**
     * @var array
     */
    protected static $methods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE'
    ];

    /**
     * @var array
     */
    protected static $wpPanels = [
        'index.php', 'edit.php', 'upload.php',
        'link-manager.php', 'edit.php?post_type=*',
        'edit-comments.php', 'themes.php',
        'plugins.php', 'users.php', 'tools.php',
        'options-general.php', 'settings.php'
    ];

    /**
     * @var \Herbert\Framework\Application
     */
    protected $app;

    /**
     * @var \Herbert\Framework\Http
     */
    protected $http;

    /**
     * @var array
     */
    protected $panels = [];

    /**
     * The current namespace.
     *
     * @var string|null
     */
    protected $namespace = null;

    /**
     * Adds the WordPress hook during construction.
     *
     * @param \Herbert\Framework\Application $app
     * @param \Herbert\Framework\Http        $http
     */
    public function __construct(Application $app, Http $http)
    {
        $this->app = $app;
        $this->http = $http;

        if ( ! is_admin())
        {
            return;
        }

        add_action('admin_menu', [$this, 'boot']);

        $http->setMethod($http->get('_method'), $old = $http->method());

        if ( ! in_array($http->method(), self::$methods))
        {
            $http->setMethod($old);
        }

        if ($http->method() !== 'GET')
        {
            add_action('init', [$this, 'bootEarly']);
        }
    }

    /**
     * Boots the panels.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->panels as $panel)
        {
            switch ($panel['type'])
            {
                case 'panel':
                    $this->addPanel($panel);

                    break;

                case 'wp-sub-panel':
                case 'sub-panel':
                    $this->addSubPanel($panel);

                    break;
            }
        }
    }

    /**
     * Boots early.
     *
     * @return void
     */
    public function bootEarly()
    {
        if (($slug = $this->http->get('page')) === null)
        {
            return;
        }

        if (($panel = $this->getPanel($slug, true)) === null)
        {
            return;
        }

        if ( ! $this->handler($panel, true))
        {
            return;
        }

        die;
    }

    /**
     * Adds a panel.
     *
     * @param array $data
     * @param $uses
     */
    public function add($data, $uses = null)
    {
        if (!is_null($uses))
        {
            $data['uses'] = $uses;
        }

        foreach (['type', 'uses', 'title', 'slug'] as $key)
        {
            if (isset($data[$key]))
            {
                continue;
            }

            throw new InvalidArgumentException("Missing {$key} definition for panel");
        }

        if (!in_array($data['type'], ['panel', 'sub-panel', 'wp-sub-panel']))
        {
            throw new InvalidArgumentException("Unknown panel type '{$data['type']}'");
        }

        if (in_array($data['type'], ['sub-panel', 'wp-sub-panel']) && !isset($data['parent']))
        {
            throw new InvalidArgumentException("Missing parent definition for sub-panel");
        }

        if ($data['type'] === 'wp-sub-panel')
        {
            $arr = array_filter(static::$wpPanels, function ($value) use ($data)
            {
                return str_is($value, $data['parent']);
            });

            if (count($arr) === 0)
            {
                throw new InvalidArgumentException("Unknown WP panel '{$data['parent']}'");
            }
        }

        if (isset($data['as']))
        {
            $data['as'] = $this->namespaceAs($data['as']);
        }

        if ($data['type'] === 'sub-panel' && isset($data['parent']))
        {
            $data['parent'] = $this->namespaceAs($data['parent']);
        }

        $this->panels[] = $data;
    }

    /**
     * Adds a panel.
     *
     * @param $panel
     * @return void
     */
    protected function addPanel($panel)
    {
        add_menu_page(
            $panel['title'],
            $panel['title'],
            isset($panel['capability']) && $panel['capability'] ? $panel['capability'] : 'manage_options',
            $panel['slug'],
            $this->makeCallable($panel),
            isset($panel['icon']) ? $this->fetchIcon($panel['icon']) : '',
            isset($panel['order']) ? $panel['order'] : null
        );

        if (isset($panel['rename']) && !empty($panel['rename']))
        {
            $this->addSubPanel([
                'title'  => $panel['rename'],
                'rename' => true,
                'slug'   => $panel['slug'],
                'parent' => $panel['slug']
            ]);
        }
    }

    /**
     * Adds a sub panel.
     *
     * @param $panel
     * @return void
     */
    protected function addSubPanel($panel)
    {
        foreach ($this->panels as $parent)
        {
            if (array_get($parent, 'as') !== $this->namespaceAs($panel['parent']))
            {
                continue;
            }

            $panel['parent'] = $parent['slug'];
        }

        add_submenu_page(
            $panel['parent'],
            $panel['title'],
            $panel['title'],
            isset($panel['capability']) && $panel['capability'] ? $panel['capability'] : 'manage_options',
            $panel['slug'],
            isset($panel['rename']) && $panel['rename'] ? null : $this->makeCallable($panel)
        );
    }

    /**
     * Fetches an icon for a panel.
     *
     * @param $icon
     * @return string
     */
    protected function fetchIcon($icon)
    {
        if (empty($icon))
        {
            return '';
        }

        if (substr($icon, 0, 9) === 'dashicons' || substr($icon, 0, 5) === 'data:'
            || substr($icon, 0, 2) === '//' || $icon == 'none')
        {
            return $icon;
        }

        return $icon;
    }

    /**
     * Makes a callable for the panel hook.
     *
     * @param $panel
     * @return callable
     */
    protected function makeCallable($panel)
    {
        return function () use ($panel) {
            return $this->handler($panel);
        };
    }

    /**
     * Calls the panel's callable.
     *
     * @param $callable
     * @return void
     */
    protected function call($callable)
    {
        $response = $this->app->call(
            $callable,
            ['app' => $this->app]
        );

        if ($response instanceof RedirectResponse)
        {
            $response->flash();
        }

        if ($response instanceof Response)
        {
            status_header($response->getStatusCode());

            foreach ($response->getHeaders() as $key => $value)
            {
                @header($key . ': ' . $value);
            }

            echo $response->getBody();

            return;
        }

        if (is_null($response) || is_string($response))
        {
            echo $response;

            return;
        }

        if (is_array($response) || $response instanceof Jsonable || $response instanceof JsonSerializable)
        {
            echo (new JsonResponse($response))->getBody();

            return;
        }

        throw new Exception('Unknown response type!');
    }

    /**
     * Gets a panel.
     *
     * @param  string  $name
     * @param  boolean $slug
     * @return array
     */
    protected function getPanel($name, $slug = false)
    {
        $slug = $slug ? 'slug' : 'as';

        foreach ($this->panels as $panel)
        {
            if (array_get($panel, $slug) !== $name)
            {
                continue;
            }

            return $panel;
        }

        return null;
    }

    /**
     * Gets the panels.
     *
     * @return array
     */
    public function getPanels()
    {
        return array_values($this->panels);
    }

    /**
     * Get the URL to a panel.
     *
     * @param  string $name
     * @return string
     */
    public function url($name)
    {
        if (($panel = $this->getPanel($name)) === null)
        {
            return null;
        }

        $slug = array_get($panel, 'slug');

        if (array_get($panel, 'type') === 'wp-sub-panel')
        {
            return admin_url(add_query_arg('page', $slug, array_get($panel, 'parent')));
        }

        return admin_url('admin.php?page=' . $slug);
    }

    /**
     * Sets the current namespace.
     *
     * @param  string $namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Unsets the current namespace.
     *
     * @return void
     */
    public function unsetNamespace()
    {
        $this->namespace = null;
    }

    /**
     * Namespaces a name.
     *
     * @param  string $as
     * @return string
     */
    protected function namespaceAs($as)
    {
        if ($this->namespace === null)
        {
            return $as;
        }

        return $this->namespace . '::' . $as;
    }


    /**
     * Return the correct callable based on action
     *
     * @param  array   $panel
     * @param  boolean $strict
     * @return void
     */
    protected function handler($panel, $strict = false)
    {
        $callable = $uses = $panel['uses'];
        $method = strtolower($this->http->method());
        $action = strtolower($this->http->get('action', 'uses'));

        $callable = array_get($panel, $method, false) ?: $callable;

        if ($callable === $uses || is_array($callable))
        {
            $callable = array_get($panel, $action, false) ?: $callable;
        }

        if ($callable === $uses || is_array($callable))
        {
            $callable = array_get($panel, "{$method}.{$action}", false) ?: $callable;
        }

        if (is_array($callable))
        {
            $callable = $uses;
        }

        if ($strict && $uses === $callable)
        {
            return false;
        }

        try {
            $this->call($callable);
        } catch (HttpErrorException $e) {
            if ($e->getStatus() === 301 || $e->getStatus() === 302)
            {
                $this->call(function () use (&$e)
                {
                    return $e->getResponse();
                });
            }

            global $wp_query;
            $wp_query->set_404();

            status_header($e->getStatus());

            define('HERBERT_HTTP_ERROR_CODE', $e->getStatus());
            define('HERBERT_HTTP_ERROR_MESSAGE', $e->getMessage());

            Notifier::error('<strong>' . $e->getStatus() . '</strong>: ' . $e->getMessage());

            do_action('admin_notices');
        }

        return true;
    }

}
