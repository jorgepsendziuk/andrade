<?php

namespace Mad\Rest;

/**
 * ResponseInterface
 * 
 * Interface for response classes
 */
interface ResponseInterface
{
    /**
     * Create a new response instance
     * 
     * @param mixed $result The result to be returned
     * @param int $code HTTP status code
     */
    public function __construct($result, $code);
    
    /**
     * Parse the result to the appropriate format
     * 
     * @return mixed
     */
    public function parse();
}
