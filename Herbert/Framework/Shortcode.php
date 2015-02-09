<?php namespace Herbert\Framework;

/**
 * @see http://getherbert.com
 */
class Shortcode {

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
     * Add a new shortcode.
     *
     * @param       $name
     * @param       $callable
     * @param array $arguments
     */
    public function add($name, $callable, $arguments = [])
    {
        add_shortcode($name, function ($attributes = [], $content = null) use ($callable, $arguments)
        {
            if (!is_array($attributes))
            {
                $attributes = [];
            }

            if (!empty($arguments))
            {
                $attributes = $this->renameArguments($arguments, $attributes);
            }

            return $this->app->call(
                $callable,
                array_merge([
                    '_attributes' => $attributes,
                    '_content'    => $content
                ], $attributes)
            );
        });
    }

    /**
     * Renames shortcode arguments in a 'from => to' format
     * eg: my_name => myName
     *
     * @param $arguments
     * @param $attributes
     * @return array
     */
    protected function renameArguments($arguments, $attributes)
    {
        $output = [];
        array_walk($attributes, function ($value, $key) use ($arguments, &$output)
        {
            if (!isset($arguments[$key]))
            {
                return;
            }

            $output[$arguments[$key]] = $value;
        });

        return $output;
    }

}
