<?php

namespace Mad\Rest;
use Adianti\Core\AdiantiCoreApplication;

class Request
{
    public static $route;

    protected $url;
    protected $method;
    protected $host;
    protected $cookies;
    protected $params;
    protected $headers;
    protected $matchedRoute;

    public function __construct()
    {
        // Remover a dependência do PREFIX
        // Obter o diretório onde o sistema está rodando
        $baseDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Obter a URL redirecionada
        $redirectUrl = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remover o prefixo do diretório da URL, se necessário
        if (strpos($redirectUrl, $baseDir) === 0) {
            $redirectUrl = substr($redirectUrl, strlen($baseDir));
        }

        // Definir a URL relativa
        $this->url = $redirectUrl ?: '/';

        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->host = $_SERVER['HTTP_HOST'];

        $this->cookies = [];
        if (isset($_SERVER['HTTP_COOKIE']) && $_SERVER['HTTP_COOKIE']) {
            $cookies = explode('; ', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cookie) {
                $exploded = explode('=', $cookie);
                $this->cookies[$exploded[0]] = $exploded[1];
            }
        }

        $input = json_decode(file_get_contents("php://input"), true);

        $this->params = array_merge($_REQUEST, (array) $input);

        $this->headers = AdiantiCoreApplication::getHeaders();
    }

    /**
     * Get a parameter value from the request
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed Parameter value
     */
    public function get($key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get authorization token from headers
     * 
     * @return string|null Authorization token
     */
    public function getAuthToken()
    {
        return $this->headers['Authorization'] ?? $this->headers['authorization'] ?? NULL;
    }

    /**
     * Get request URL
     * 
     * @return string Request URL
     */
    public function getUrl()
    {
        return trim($this->url, '/');
    }

    /**
     * Get request method (GET, POST, etc)
     * 
     * @return string Request method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get request host
     * 
     * @return string Request host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get request cookies
     * 
     * @return array Request cookies
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Get request headers
     * 
     * @return array Request headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get all request parameters
     * 
     * @return array Request parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Populate route parameters from URL variables
     * 
     * @param Route $route Route object
     */
    public function populateRouteParams(Route $route)
    {
        // Usa regex mais permissivo para aceitar mais caracteres nos valores
        $new_route = preg_replace('/{([0-9a-zA-Z_-]+)}/', '([^/]+)', $route->getUrl());
        $new_route = str_replace('/', '\/', $new_route);
        
        preg_match_all('/{([0-9a-zA-Z_-]+)}/', $route->getUrl(), $variables);
        preg_match('/^'. $new_route . '$/', $this->getUrl(), $values);

        if(! empty($values))
        {
            foreach($values as $key => $value)
            {
                if($key == 0)
                {
                    continue;
                }

                $this->params[$variables[1][$key-1]] = $value;
            }
        }
    }

    /**
     * Set the matched route and auto-populate parameters
     * 
     * @param Route $route The matched route
     * @return self
     */
    public function setMatchedRoute(Route $route)
    {
        $this->matchedRoute = $route;
        $this->populateRouteParams($route);
        return $this;
    }

    /**
     * Get the matched route
     * 
     * @return Route|null
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }
}