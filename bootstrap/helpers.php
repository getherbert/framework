<?php

if ( ! function_exists('dd'))
{
    /**
     * Dies and dumps.
     *
     * @return string
     */
    function dd()
    {
        call_user_func_array('dump', func_get_args());

        die;
    }
}

if ( ! function_exists('response'))
{
    /**
     * Generates a response.
     *
     * @param  string  $body
     * @param  integer $status
     * @param  array   $headers
     * @return \Herbert\Framework\Response
     */
    function response($body, $status = 200, $headers = null)
    {
        return new Herbert\Framework\Response($body, $status, $headers);
    }
}

if ( ! function_exists('json_response'))
{
    /**
     * Generates a json response.
     *
     * @param  mixed   $jsonable
     * @param  integer $status
     * @param  array   $headers
     * @return \Herbert\Framework\Response
     */
    function json_response($jsonable, $status = 200, $headers = null)
    {
        return new Herbert\Framework\JsonResponse($jsonable, $status, $headers);
    }
}

if ( ! function_exists('herbert'))
{
    /**
     * Gets the herbert container.
     *
     * @param  string $binding
     * @return string
     */
    function herbert($binding = null)
    {
        $instance = Herbert\Framework\Application::getInstance();

        if ( ! $binding)
        {
            return $instance;
        }

        return $instance[$binding];
    }
}

if ( ! function_exists('view'))
{
    /**
     * Renders a twig view.
     *
     * @param  string $name
     * @param  array  $context
     * @return string
     */
    function view($name, $context = [])
    {
        return herbert('Twig_Environment')->render($name, $context);
    }
}

if ( ! function_exists('panel_url'))
{
    /**
     * Gets the url to a panel.
     *
     * @param  string $name
     * @return string
     */
    function panel_url($name)
    {
        return herbert('panel')->url($name);
    }
}

if ( ! function_exists('route_url'))
{
    /**
     * Gets the url to a route.
     *
     * @param  string $name
     * @param  array  $args
     * @return string
     */
    function route_url($name, $args = [])
    {
        return herbert('router')->url($name, $args);
    }
}
