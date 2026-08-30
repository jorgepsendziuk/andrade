<?php

namespace Mad\Rest;

/**
 * RouteServiceProvider
 * 
 * Core framework component that handles loading and registering route files
 */
class RouteServiceProvider
{
    /**
     * Base path for route files
     *
     * @var string
     */
    protected $routesPath;
    
    /**
     * Create a new route service provider
     * 
     * @param string $routesPath Optional custom path for routes
     */
    public function __construct($routesPath = null)
    {
        // Default to app/routes if not specified
        $this->routesPath = $routesPath ?: dirname(dirname(dirname(__DIR__))) . '/app/routes';
    }
    
    /**
     * Boot the route service
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutes();
    }
    
    /**
     * Load all route files from the routes directory
     *
     * @return void
     */
    protected function loadRoutes()
    {
        // Load API routes
        if (file_exists($this->routesPath . '/api.php')) {
            require_once $this->routesPath . '/api.php';
        }
        
        // Load web routes if they exist (for future use)
        if (file_exists($this->routesPath . '/web.php')) {
            require_once $this->routesPath . '/web.php';
        }
        
        // You can add more route types here (admin, console, etc.)
    }
    
    /**
     * Get routes path
     *
     * @return string
     */
    public function getRoutesPath()
    {
        return $this->routesPath;
    }
    
    /**
     * Set custom routes path
     *
     * @param string $path
     * @return self
     */
    public function setRoutesPath(string $path)
    {
        $this->routesPath = $path;
        return $this;
    }
}
