<?php namespace Herbert\Framework\Exceptions;

class HttpErrorException extends \Exception {

    /**
     * The status code.
     *
     * @var integer
     */
    protected $status = 500;

    /**
     * Constructs the HttpErrorException.
     *
     * @param integer $status
     * @param string  $message
     */
    public function __construct($status = 500, $message = null)
    {
        parent::__construct($message);

        $this->status = $status;
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

}
