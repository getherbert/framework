<?php namespace Herbert\Framework\Exceptions;

class HttpErrorException extends \Exception {

    /**
     * The status code.
     *
     * @var integer
     */
    protected $status = 500;

    /**
     * The response.
     *
     * @var \Herbert\Framework\Response
     */
    protected $response = null;

    /**
     * Constructs the HttpErrorException.
     *
     * @param integer $status
     * @param string|mixed  $message
     */
    public function __construct($status = 500, $message = null)
    {
        parent::__construct(is_string($message) ? $message : null);

        $this->status = $status;

        if ( ! is_string($message))
        {
            $this->response = $message;
        }
    }

    /**
     * Gets the Http status code.
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets the response.
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

}
