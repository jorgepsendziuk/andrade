<?php

namespace Mad\Rest;

use Adianti\Core\AdiantiApplicationConfig;

class Router
{
    /**
     * Registered routes collection
     * 
     * @var array
     */
    protected static $routes = [];

    /**
     * Current route group URL prefix
     * 
     * @var string
     */
    protected static $urlPrefix = '';

    /**
     * Current route group middleware
     * 
     * @var string|null
     */
    protected static $middlewarePrefix = null;

    private static $requestId;
    private static $debug;

    /**
     * Create a route group with shared attributes
     * 
     * @param array $parameters Group parameters (prefix, middleware)
     * @param callable|null $callable Callback function to define routes
     * @return void
     */
    public static function group($parameters = [], ?callable $callable = null)
    {
        // Save current prefixes to restore them after the group
        $oldUrlPrefix = static::$urlPrefix;
        $oldMiddlewarePrefix = static::$middlewarePrefix;

        // Set URL prefix if provided
        if (!empty($parameters['prefix'])) {
            $prefix = ltrim($parameters['prefix'], '/');
            static::$urlPrefix = $oldUrlPrefix 
                ? $oldUrlPrefix . '/' . $prefix 
                : '/' . $prefix;
        }

        if (!empty($parameters['middleware'])) {
            static::$middlewarePrefix = $parameters['middleware'];
        }

        // Execute the group callback
        if ($callable) {
            call_user_func($callable);
        }

        // Restore old prefixes
        static::$urlPrefix = $oldUrlPrefix;
        static::$middlewarePrefix = $oldMiddlewarePrefix;
    }

    /**
     * Register a new GET route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function get($uri, $action)
    {
        return static::addRoute('GET', $uri, $action);
    }

    /**
     * Register a new POST route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function post($uri, $action)
    {
        return static::addRoute('POST', $uri, $action);
    }

    /**
     * Register a new PUT route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function put($uri, $action)
    {
        return static::addRoute('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function patch($uri, $action)
    {
        return static::addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function delete($uri, $action)
    {
        return static::addRoute('DELETE', $uri, $action);
    }

    /**
     * Add a route to the routes collection
     *
     * @param string $method HTTP method
     * @param string $uri Route URI
     * @param mixed $action Route action
     * @return void
     */
    protected static function addRoute($method, $uri, $action)
    {
        // Make sure URI starts with a slash
        $uri = '/' . ltrim($uri, '/');
        
        // Apply URL prefix if set
        if (static::$urlPrefix) {
            $fullUri = rtrim(static::$urlPrefix, '/') . $uri;
        } else {
            $fullUri = $uri;
        }
        
        // For debugging
        // echo "Registering route: {$method} {$fullUri}\n";
        
        $route = new Route();
        $route->setMethod($method);
        $route->setUrl($fullUri);
        $route->setAction($action);
        
        if (static::$middlewarePrefix) {
            $route->setMiddleware(static::$middlewarePrefix);
        }
        
        static::$routes[] = $route;

        return $route;
    }
    
    /**
     * Get matching route for the request
     *
     * @param Request $request
     * @return Route|null
     */
    public static function getRoute(Request $request)
    {
        $url = $request->getUrl();
        $method = $request->getMethod();
        
        // Se já tem uma rota definida, retorna ela
        if ($request->getMatchedRoute()) {
            return $request->getMatchedRoute();
        }
        
        $matchingRoutes = [];
        
        // Coleta todas as rotas que fazem match
        foreach (static::$routes as $route) {
            if ($route->getMethod() == $method && $route->matchUrl($url)) {
                $matchingRoutes[] = $route;
            }
        }
        
        if (empty($matchingRoutes)) {
            return null;
        }
        
        // Se só tem uma rota, retorna ela
        if (count($matchingRoutes) === 1) {
            $route = $matchingRoutes[0];
            $request->setMatchedRoute($route);
            return $route;
        }
        
        // Ordena as rotas por prioridade (mais segmentos fixos primeiro)
        usort($matchingRoutes, function($a, $b) {
            $aFixedSegments = static::countFixedSegments($a->getUrl());
            $bFixedSegments = static::countFixedSegments($b->getUrl());
            
            // Mais segmentos fixos = maior prioridade (ordem decrescente)
            if ($aFixedSegments !== $bFixedSegments) {
                return $bFixedSegments - $aFixedSegments;
            }
            
            // Se mesmo número de segmentos fixos, rotas sem parâmetros têm prioridade
            $aIsVariable = $a->isVariable();
            $bIsVariable = $b->isVariable();
            
            if ($aIsVariable !== $bIsVariable) {
                return $aIsVariable ? 1 : -1;
            }
            
            return 0;
        });
        
        // Retorna a rota com maior prioridade
        $route = $matchingRoutes[0];
        $request->setMatchedRoute($route);
        return $route;
    }
    
    /**
     * Count fixed (non-parameter) segments in a route URL
     *
     * @param string $url
     * @return int
     */
    protected static function countFixedSegments($url)
    {
        $segments = explode('/', trim($url, '/'));
        $fixedCount = 0;
        
        foreach ($segments as $segment) {
            if (!empty($segment) && !preg_match('/^{.+}$/', $segment)) {
                $fixedCount++;
            }
        }
        
        return $fixedCount;
    }
    
    /**
     * Validate the request against registered routes
     * 
     * @param Request $request The request to validate
     * @return mixed Response if middleware returns one, or null to continue
     */
    public static function validate(Request $request)
    {
        $route = static::getRoute($request);
        
        if (!$route) {
            $response = new Response();
            return $response->json(['error' => 'Route not found'], 404);
        }
        
        if (!empty($route->getMiddleware())) {
            $middlewares = $route->getMiddleware();
            
            // Normalize middleware to array for consistent handling
            if (!is_array($middlewares)) {
                $middlewares = [$middlewares];
            }
            
            // Process each middleware in sequence (pipeline)
            $pipeline = function ($request, $middlewares, $index = 0) use (&$pipeline) {
                // If we've processed all middlewares, continue
                if ($index >= count($middlewares)) {
                    return null;
                }
                
                $middleware = $middlewares[$index];
                
                // Check if middleware is a string (class name)
                if (is_string($middleware)) {
                    // Check if middleware class exists
                    if (!class_exists($middleware)) {
                        $response = new Response();
                        return $response->json(['error' => 'Middleware not found: ' . $middleware], 500);
                    }
                    
                    $middlewareInstance = new $middleware();
                    
                    // Create a next closure that calls the next middleware
                    $next = function ($request) use ($pipeline, $middlewares, $index) {
                        return $pipeline($request, $middlewares, $index + 1);
                    };
                    
                    return $middlewareInstance->handle($request, $next);
                } 
                elseif (is_callable($middleware)) {
                    // Callable middleware
                    return call_user_func($middleware, $request, function ($request) use ($pipeline, $middlewares, $index) {
                        return $pipeline($request, $middlewares, $index + 1);
                    });
                }
                
                // Skip invalid middleware
                return $pipeline($request, $middlewares, $index + 1);
            };
            
            // Start the middleware pipeline
            $result = $pipeline($request, $middlewares);
            
            // If any middleware returns a response, return it immediately
            if ($result instanceof ResponseInterface) {
                return $result;
            }
        }
        
        return null; // Validation passed
    }
    
    
    /**
     * Get all registered routes
     * 
     * @return array Array of Route objects
     */
    public static function getRoutes()
    {
        return static::$routes;
    }
    
    /**
     * Execute the matched route
     *
     * @param Request $request
     * @return mixed
     */
    public static function execute(Request $request)
    {
        $route = static::getRoute($request);
        
        if (!$route) {
            $response = new Response();
            return $response->json(['error' => 'Route not found'], 404);
        }
        
        $action = $route->getAction();
        
        if (is_string($action) && strpos($action, '::') !== false) {
            list($controller, $method) = explode('::', $action);

            try {
                self::$requestId = uniqid();
        
                $ini = AdiantiApplicationConfig::get();
                $service = isset($ini['general']['request_log_service']) ? $ini['general']['request_log_service'] : '\SystemRequestLogService'; 
            
                if (!empty($ini['general']['request_log']) && $ini['general']['request_log'] == '1')
                {
                    if ( empty($ini['general']['request_log_types']) || strpos($ini['general']['request_log_types'], 'rest') !== false)
                    {
                        $_REQUEST['class'] = $controller;
                        $_REQUEST['method'] = $method;
                        self::$requestId = $service::register( 'rest' );
                    }
                }
            }
            catch (\Exception $e) {
                
            }
            
            try {
                $reflection = new \ReflectionMethod($controller, $method);
                $methodParams = [];
                
                // Obtém os parâmetros do método (estático ou de instância)
                foreach ($reflection->getParameters() as $param) {
                    $paramName = $param->getName();
                    
                    // Se o parâmetro for do tipo Request, passa o objeto request
                    if ($param->getType() && $param->getType()->getName() === 'Mad\Rest\Request') {
                        $methodParams[] = $request;
                        continue;
                    }
                    
                    // Tenta obter o valor do parâmetro do objeto Request que já deve conter
                    // todos os parâmetros da URL após a chamada de populateRouteParams
                    $value = $request->get($paramName);
                    
                    // Se não encontrou o valor e o parâmetro é obrigatório, lança erro
                    if ($value === null && !$param->isOptional()) {
                        $response = new Response();
                        return $response->json(['error' => "Missing required parameter: {$paramName}"], 400);
                    }
                    
                    $methodParams[] = $value;
                }
                
                if ($reflection->isStatic()) {
                    return call_user_func_array([$controller, $method], $methodParams);
                }
                
                // Método de instância: cria a instância e injeta os parâmetros
                $instance = new $controller();
                return call_user_func_array([$instance, $method], $methodParams);
            } catch (\ReflectionException $e) {
                // Fallback: comportamento anterior para métodos não refletíveis (__call, etc.)
                $instance = new $controller();
                return call_user_func([$instance, $method], $request);
            }
        }
        
        $response = new Response();
        return $response->json(['error' => 'Invalid route action'], 500);
    }
}