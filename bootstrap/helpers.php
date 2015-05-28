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
