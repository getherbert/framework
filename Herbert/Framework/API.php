<?php namespace Herbert\Framework;

use WP_Error;

/**
 * @see http://getherbert.com
 */
class API {

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @var \Herbert\Framework\Application
     */
    protected $app;

    /**
     * @param \Herbert\Framework\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Add a method.
     *
     * @param $method
     * @param $fn
     */
    public function add($method, $fn)
    {
        $this->methods[$method] = $fn;
    }

    /**
     * Magic call from the function collection.
     *
     * @param $method
     * @param $params
     * @return mixed
     * @throws \WP_Error
     */
    public function __call($method, $params)
    {
        if (!isset($this->methods[$method]))
        {
            throw new WP_Error('broke', "Method '{$method}' not set!");
        }

        return $this->app->call(
            $this->methods[$method], $params
        );
    }

}
