<?php namespace Herbert\Framework\Base;

use Herbert\Framework\Plugin as PluginContract;
use Illuminate\Contracts\Container\Container;

/**
 * @see http://getherbert.com
 */
class Plugin implements PluginContract {

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var array
     */
    protected $config = null;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $notices = [];

    /**
     * @param $path
     */
    public function __construct($path)
    {
        $this->setBasePath($path);

        add_action('admin_notices', [$this, 'sendNotices']);
    }

    /**
     * Adds a notice.
     *
     * @param        $message
     * @param string $class
     */
    public function notify($message, $class = 'updated')
    {
        $this->notices[] = [
            'message' => $message,
            'class'   => $class
        ];
    }

    /**
     * Adds a success notice.
     *
     * @param $message
     */
    public function notifySuccess($message)
    {
        $this->notify($message, 'updated');
    }

    /**
     * Adds a warning notice.
     *
     * @param $message
     */
    public function notifyWarning($message)
    {
        $this->notify($message, 'update-nag');
    }

    /**
     * Adds an error notice.
     *
     * @param $message
     */
    public function notifyError($message)
    {
        $this->notify($message, 'update-error');
    }

    /**
     * Sends all the accumulated notices.
     *
     * @return void
     */
    public function sendNotices()
    {
        foreach ($this->notices as $notice)
        {
            echo "<div class=\"{$notice['class']}\"><p>{$notice['message']}</p></div>";
        }
    }

    /**
     * Activate the plugin.
     *
     * @return void
     */
    public function activate()
    {
        //
    }

    /**
     * Deactivate the plugin.
     *
     * @return void
     */
    public function deactivate()
    {
        //
    }

    /**
     * Get the configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        if (is_null($this->config))
        {
            $this->config = file_exists("{$this->getBasePath()}/herbert.config.php")
                ? require "{$this->getBasePath()}/herbert.config.php"
                : [];
        }

        return $this->config;
    }

    /**
     * Set the base path.
     *
     * @param $path
     */
    public function setBasePath($path)
    {
        $this->path = $path;
    }

    /**
     * Get the base path.
     *
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->path;
    }

    /**
     * Sets the IoC Container.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Gets the IoC Container.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

}
