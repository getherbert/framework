<?php namespace Herbert\Framework;

/**
 * @see http://getherbert.com
 */
class Response {

    /**
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $defaultHeaders = [];

    /**
     * @param       $body
     * @param int   $status
     * @param array $headers
     */
    public function __construct($body, $status = 200, $headers = null)
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers ?: $this->defaultHeaders;
    }

    /**
     * Gets the response HTTP status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * Gets the response body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Gets the response headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

}
