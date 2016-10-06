<?php
namespace Herbert\Framework;

/**
 * Class Middleware
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
     */
    public function init($http)
    {
        $this->request = $http;
        $this->handleRequest();
    }

    /**
     * To Handling the Request with Specific Middlewares.
     */
    protected function handleRequest()
    {
        if ($this->hasMiddleware()) {
            $middleware = $this->request->middleware;

            if (class_exists($middleware)) {
                $request = new $middleware;
                $request->handle($this->request);
            }
        }
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
