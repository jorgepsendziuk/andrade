<?php

namespace Mad\Rest;

/**
 * HtmlResponse
 * 
 * Handles HTML response formatting
 */
class HtmlResponse implements ResponseInterface
{
    /**
     * The HTML content to be returned
     * 
     * @var string
     */
    private $content;
    
    /**
     * Create a new HTML response instance
     * 
     * @param string $content The HTML content to be returned
     * @param int $code HTTP status code
     */
    public function __construct($content, $code = 200)
    {
        http_response_code((int) $code);
        header('Content-Type: text/html; charset=utf-8');
        $this->content = $content;
    }
    
    /**
     * Parse the result to HTML format
     * 
     * @return string
     */
    public function parse()
    {
        return (string) $this->content;
    }
}
