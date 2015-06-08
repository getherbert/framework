<?php namespace Herbert\Framework;

/**
 * @see http://getherbert.com
 */
class RedirectResponse extends Response {

    /**
     * The target url.
     *
     * @var string
     */
    protected $target;

    /**
     * The session data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * @param       $route
     * @param int   $status
     * @param array $headers
     */
    public function __construct($url, $status = 302, $headers = null)
    {
        parent::__construct(null, $status, $headers);

        $this->target = $url;
        $this->updateBody();
        $this->updateHeaders();
    }

    /**
     * Update the body.
     *
     * @return void
     */
    protected function updateBody()
    {
        $this->body = sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0;url=%1$s" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.

        <script>window.location = "%1$s"</script>
    </body>
</html>', str_replace('"', '\\"', $this->target));
    }

    /**
     * Update the headers.
     *
     * @return void
     */
    protected function updateHeaders()
    {
        array_set($this->headers, 'Location', $this->target);
    }

    /**
     * Flashes the :key of value :val to the session.
     *
     * @param  string $key
     * @param  mixed  $val
     * @return \Herbert\Framework\RedirectResponse
     */
    public function with($key, $val = null)
    {
        if ( ! is_array($key))
        {
            $key = [$key => $val];
        }

        foreach ($key as $k => $v)
        {
            array_set($this->data, $k, $v);
        }

        return $this;
    }

    /**
     * Actually flashes in the session data.
     *
     * @return \Herbert\Framework\RedirectResponse
     */
    public function flash()
    {
        $bag = session()->getFlashBag();

        foreach ($this->data as $key => $val)
        {
            $bag->add($key, $val);
        }

        return $this;
    }

}
