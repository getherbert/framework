<?php namespace Herbert\Framework;

/**
 * @see http://getherbert.com
 */
class Http {

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
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $bag;

    /**
     * Builds up the bag and sets the method.
     *
     * @param array $bag
     */
    public function __construct($bag = null)
    {
        $this->bag = $bag ?: $this->collectBag();
        $this->method = $_SERVER['REQUEST_METHOD'];

        if (isset($_POST['_method']) && in_array(strtoupper($_POST['_method']), static::$methods))
        {
            $this->method = strtoupper($_POST['_method']);
        }
    }

    /**
     * Gets the HTTP method.
     *
     * @return mixed
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * Checks if a key exists.
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->bag[$key]);
    }

    /**
     * Get all the keys and values.
     *
     * @return array
     */
    public function all()
    {
        return array_merge($this->bag, []);
    }

    /**
     * Gets a key's value.
     *
     * @param       $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->has($key))
        {
            return $default;
        }

        return $this->bag[$key];
    }

    /**
     * Sets a key's value.
     *
     * @param $key
     * @param $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->bag[$key] = $value;
    }

    /**
     * Merges two bags.
     *
     * @param array $bag
     */
    public function put($bag = [])
    {
        $this->bag = array_merge($this->bag, $bag);
    }

    /**
     * Forgets a key.
     *
     * @param $key
     * @return mixed
     */
    public function forget($key)
    {
        if (!$this->has($key))
        {
            return null;
        }

        $value = $this->get($key);
        unset($this->bag[$key]);

        return $value;
    }

    /**
     * Collects the current bag.
     *
     * @return mixed
     */
    protected function collectBag()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    }

}
