<?php
namespace Herbert\Framework;

/**
 * Class Middleware
 * @package Plugin\library
 */
class Middleware extends Route
{
    /**
     * @var $request
     */
    protected $request;

    /**
     * Middleware constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Init Middleware Handler.
     *
     * @param $http
     * @return bool
     */
    public function init($http)
    {
        $this->request = $http;
        return $this->handleRequest();
    }

    /**
     * To Handling the Request with Specific Middlewares.
     */
    protected function handleRequest()
    {
        $request = $this->request;
        if ($this->hasMiddleware()) {
            $middleware = $this->request->middleware;

            if (class_exists($middleware)) {
                $request = new $middleware;
                $request = $request->handle($this->request);
            }
        }
        return $request;
    }


    /**
     * To Check Middleware Existence.
     *
     * @return bool
     */
    public function hasMiddleware()
    {
        return (isset($this->request->middleware) && !empty($this->request->middleware));
    }
}
