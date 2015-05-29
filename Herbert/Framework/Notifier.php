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
        add_action('admin_notices', [$this, 'sendNotices']);
    }

    /**
     * Adds a notice.
     *
     * @param        $message
     * @param string $class
     */
    protected function notify($message, $class = 'updated')
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
    protected function success($message)
    {
        $this->notify($message, 'updated');
    }

    /**
     * Adds a warning notice.
     *
     * @param $message
     */
    protected function warning($message)
    {
        $this->notify($message, 'update-nag');
    }

    /**
     * Adds an error notice.
     *
     * @param $message
     */
    protected function error($message)
    {
        $this->notify($message, 'error');
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
            self::$instance = new self;
        }

        return call_user_func_array([self::$instance, $name], $arguments);
    }

}
