<?php


use Mad\Rest\Request;
use Mad\Rest\Response;
use Adianti\Registry\TSession;

/**
 * BuilderAdminAuth Middleware
 * 
 * Checks if the user is logged in and if the user has 'admin' as their login
 */
class BuilderAdminAuth
{
    /**
     * Handle the middleware execution
     * 
     * @param Request $request The request object
     * @param callable $next The next middleware in the chain
     * @return mixed
     */
    public function handle(Request $request, callable $next)
    {
        new TSession();
        
        // Check if the user is logged in using TSession
        if (!TSession::getValue('logged')) {            
            throw new Exception('Unauthorized access');
        }
        
        // Verify if the logged user has login equal to 'admin'
        $login = TSession::getValue('login');
        
        if ($login !== 'admin') {
            throw new Exception('Forbidden');
        }
        
        // User is authenticated and is an admin, proceed to the next middleware or controller
        return $next($request);
    }
}
