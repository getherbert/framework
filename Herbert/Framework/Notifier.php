<?php namespace Herbert\Framework;

/**
 * @see http://getherbert.com
 */
class Notifier {

    /**
     * The notifier instance.
     *
     * @var \Herbert\Framework\Notifier
     */
    protected static $instance;

    /**
     * The accumulated notices.
     *
     * @var array
     */
    protected $notices = [];

    /**
     * Constructs the Notifier.
     */
    public function __construct()
    {
        if ( ! self::$instance)
        {
            self::$instance = $this;

            $this->gatherFlashed();
        }

        add_action('admin_notices', [$this, 'sendNotices']);
        add_action('shutdown', [$this, 'sendNotices']);
    }

    /**
     * Adds a notice.
     *
     * @param string  $message
     * @param string  $class
     * @param boolean $flash
     */
    protected function notify($message, $class = 'updated', $flash = false)
    {
        $notification = [
            'message' => $message,
            'class'   => $class
        ];

        if ( ! $flash)
        {
            $this->notices[] = $notification;

            return;
        }

        $notices = session('__notifier_flashed', []);
        $notices[] = $notification;
        session()->getFlashBag()->set('__notifier_flashed', $notices);
    }

    /**
     * Adds a success notice.
     *
     * @param string  $message
     * @param boolean $flash
     */
    protected function success($message, $flash = false)
    {
        $this->notify($message, 'updated', $flash);
    }

    /**
     * Adds a warning notice.
     *
     * @param string  $message
     * @param boolean $flash
     */
    protected function warning($message, $flash = false)
    {
        $this->notify($message, 'update-nag', $flash);
    }

    /**
     * Adds an error notice.
     *
     * @param string  $message
     * @param boolean $flash
     */
    protected function error($message, $flash = false)
    {
        $this->notify($message, 'error', $flash);
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

        $this->notices = [];
    }

    /**
     * Gathers all the flashed notify messages.
     *
     * @return void
     */
    protected function gatherFlashed()
    {
        $this->notices = session()->getFlashBag()->get('__notifier_flashed', []);
    }

    /**
     * Allow static calls — akin to a facade.
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if ( ! self::$instance)
        {
            new self;
        }

        return call_user_func_array([self::$instance, $name], $arguments);
    }

}
