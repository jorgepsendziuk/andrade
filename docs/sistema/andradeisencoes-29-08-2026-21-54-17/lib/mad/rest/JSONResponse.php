<?php

namespace Mad\Rest;

use Adianti\Database\TRecord;

/**
 * JSONResponse
 * 
 * Handles JSON response formatting
 */
class JSONResponse implements ResponseInterface
{
    /**
     * The result to be returned
     * 
     * @var mixed
     */
    private $result;

    /**
     * Create a new JSON response instance
     * 
     * @param mixed $result The result to be returned
     * @param int $code HTTP status code
     */
    public function __construct($result, $code)
    {
        http_response_code((int) $code);
        header('Content-Type: application/json; charset=utf-8');
        $this->result = $result;
    }

    /**
     * Parse array values for JSON output
     * 
     * @param array $array Array to parse
     * @return array Parsed array
     */
    private function parseArray($array)
    {
        $result = [];

        foreach($array as $key => $item)
        {
            if( is_array($item) )
            {
                $result[$key] = $this->parseArray($item);
            }
            else if( is_scalar($item) || empty($item))
            {
                $result[$key] = $item;
            }
            else
            {
                $result[$key] = $this->parseObject($item);
            }
        }

        return $result;
    }

    /**
     * Parse object values for JSON output
     * 
     * @param object $object Object to parse
     * @return mixed Parsed object
     */
    private function parseObject($object)
    {
        $response = null;

        if($object instanceof TRecord)
        {
            $response = $object->toArray();
        }
        else
        {
            $response = clone $object;
            foreach($object as $key => $value)
            {
                if($value instanceof TRecord)
                {
                    $response->{$key} = $value->toArray();
                }
                else if ( is_array($value) )
                {
                    $response->{$key} = $this->parseArray($value);
                }
            }
        }

        return $response;
    }

    /**
     * Parse the result to JSON format
     * 
     * @return string JSON string
     */
    public function parse()
    {
        $response = null;
        if( is_array($this->result) )
        {
            $response = $this->parseArray($this->result);
        }
        else if( is_scalar($this->result) || empty($this->result) )
        {
            $response = $this->result;
        }
        else
        {
            $response = $this->parseObject($this->result);
        }

        return json_encode($response);
    }
}
