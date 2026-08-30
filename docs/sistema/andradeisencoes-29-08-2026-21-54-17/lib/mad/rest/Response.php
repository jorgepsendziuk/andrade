<?php

namespace Mad\Rest;

/**
 * Response
 * 
 * Main Response class for handling HTTP responses
 */
class Response
{
    /**
     * Create a JSON response
     * 
     * @param mixed $result The result to be returned
     * @param int $code HTTP status code
     * @return ResponseInterface A JSON response object
     */
    public function json($result, $code = 200)
    {
        return new JSONResponse($result, $code);
    }
    
    /**
     * Create an HTML response
     * 
     * @param string $content The HTML content to be returned
     * @param int $code HTTP status code
     * @return ResponseInterface An HTML response object
     */
    public function html($content, $code = 200)
    {
        return new HtmlResponse($content, $code);
    }
}