<?php

namespace Mad\Rest;

class Route
{
    /**
     * HTTP method for this route
     *
     * @var string
     */
    protected $method;
    
    /**
     * URL pattern for this route
     *
     * @var string
     */
    protected $url;
    
    /**
     * Controller class or callable action
     *
     * @var mixed
     */
    protected $action;
    
    /**
     * Route name
     *
     * @var string|null
     */
    protected $name;
    
    /**
     * Middleware(s) for this route
     *
     * @var array|string|callable|null
     */
    protected $middleware = [];
    
    /**
     * Create a new route instance
     * 
     * @param string $method HTTP method
     * @param string $url URL pattern
     * @param mixed $action Controller action
     * @param string|null $name Route name
     * @param string|callable|null $middleware Middleware
     */
    public function __construct($method = null, $url = null, $action = null, $name = null, $middleware = null)
    {
        $this->method = $method;
        $this->url = $url;
        $this->action = $action;
        $this->name = $name;
        $this->middleware = $middleware;
    }
    
    /**
     * Set the HTTP method
     *
     * @param string $method
     * @return self
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }
    
    /**
     * Set the URL pattern
     *
     * @param string $url
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Set the action
     *
     * @param mixed $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
    
    /**
     * Set the route name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Set middleware
     *
     * @param string|array|callable $middleware
     * @return self
     */
    public function setMiddleware($middleware)
    {
        if (is_string($middleware) || is_callable($middleware)) {
            $this->middleware = $middleware;
        } elseif (is_array($middleware)) {
            $this->middleware = $middleware;
        }
        return $this;
    }
    
    /**
     * Check if route has variable segments
     *
     * @return bool
     */
    public function isVariable()
    {
        return strpos($this->url, '{') !== false && strpos($this->url, '}') !== false;
    }
    
    /**
     * Check if the URL matches this route's pattern
     *
     * @param string $url
     * @return bool
     */
    public function matchUrl($url)
    {
        // Direct match
        if (trim($this->url, '/') === trim($url, '/')) {
            return true;
        }
        
        // Pattern match for routes with parameters
        if ($this->isVariable()) {
            $pattern = preg_replace('/{([0-9a-zA-Z_-]+)}/', '([0-9a-zA-Z_-]+)', trim($this->url, '/'));
            $pattern = str_replace('/', '\/', $pattern);
            
            return preg_match('/^'. $pattern . '$/', trim($url, '/'));
        }
        
        return false;
    }

    /**
     * Get the URL
     *
     * @return string
     */
    public function getUrl()
    {
        return trim($this->url, '/');
    }

    /**
     * Get the HTTP method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Get the action
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Get route name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get middleware
     *
     * @return array|string|callable|null
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
    
    /**
     * Helper to get the controller class from a string action
     * 
     * @return string|null
     */
    public function getClass()
    {
        if (is_string($this->action) && strpos($this->action, '::') !== false) {
            list($class, $_) = explode('::', $this->action);
            return $class;
        }
        
        return null;
    }
    
    /**
     * Helper to get the controller method from a string action
     * 
     * @return string|null
     */
    public function getActionMethod()
    {
        if (is_string($this->action) && strpos($this->action, '::') !== false) {
            list($_, $method) = explode('::', $this->action);
            return $method;
        }
        
        return null;
    }
}