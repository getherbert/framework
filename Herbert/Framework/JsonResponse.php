<?php namespace Herbert\Framework;

use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * @see http://getherbert.com
 */
class JsonResponse extends Response {

    /**
     * @var array
     */
    protected $defaultHeaders = [
        'Content-Type' => 'application/json'
    ];

    /**
     * @var string
     */
    protected $json;

    /**
     * @var bool
     */
    protected $dirty = true;

    /**
     * @param       $jsonable
     * @param int   $status
     * @param array $headers
     */
    public function __construct($jsonable, $status = 200, $headers = null)
    {
        parent::__construct($jsonable, $status, $headers);

        $this->headers = array_merge($this->defaultHeaders, $this->headers);
    }

    /**
     * Gets the response body.
     *
     * @return string
     */
    public function getBody()
    {
        if ($this->dirty)
        {
            $this->jsonify();
        }

        return $this->json;
    }

    /**
     * Jsonifies the body.
     *
     * @return void
     */
    protected function jsonify()
    {
        $json = '';
        $jsonable = $this->body instanceof JsonSerializable
            ? $this->body->jsonSerialize()
            : $this->body;

        if (is_array($jsonable))
        {
            $json = json_encode($jsonable);
        }
        elseif ($jsonable instanceof Jsonable)
        {
            $json = $jsonable->toJson();
        }

        $this->json = $json;
        $this->dirty = false;
    }

}
