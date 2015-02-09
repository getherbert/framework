<?php namespace Herbert\Framework;

use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @see http://getherbert.com
 */
class Panel {

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
     * @var array
     */
    protected $panels = [];

    /**
     * Adds the WordPress hook during construction.
     *
     * @param \Herbert\Framework\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        add_action('admin_menu', [$this, 'boot']);
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
            'manage_options',
            $panel['slug'],
            $this->makeCallable($panel['uses']),
            isset($panel['icon']) ? $this->fetchIcon($panel['icon']) : ''
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
        add_submenu_page(
            $panel['parent'],
            $panel['title'],
            $panel['title'],
            'manage_options',
            $panel['slug'],
            isset($panel['rename']) && $panel['rename'] ? null : $this->makeCallable($panel['uses'])
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

        if (substr($icon, 0, 9) === "dashicons" || substr($icon, 0, 5) === "data:"
            || substr($icon, 0, 2) === "//" || $icon == 'none')
        {
            return $icon;
        }

        return $icon;
    }

    /**
     * Makes a callable for the panel hook.
     *
     * @param $callable
     * @return callable
     */
    protected function makeCallable($callable)
    {
        return function () use ($callable) {
            $this->call($callable);
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

        if ($response instanceof Response)
        {
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
    }

}
