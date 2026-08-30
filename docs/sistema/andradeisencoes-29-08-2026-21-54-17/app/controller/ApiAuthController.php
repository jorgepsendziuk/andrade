<?php

use \Firebase\JWT\JWT;
use Mad\Rest\Request;
use Mad\Rest\Response;

class ApiAuthController
{

    /**
     * Authenticate a user and return a JWT token
     *
     * This method authenticates a user using login and password credentials.
     * Upon successful authentication, it generates a JWT token that expires in 3 hours
     * and returns user information along with the token.
     *
     * @param object $request The request object containing login and password parameters
     *                         Expected JSON structure:
     *                         {
     *                           "login": string (required) - User login/username (e.g., "admin")
     *                           "password": string (required) - User password (e.g., "password123")
     *                         }
     *
     * @return Response JSON response with the following structure:
     *                  Success (HTTP 200):
     *                  {
     *                    "status": string - "success"
     *                    "data": {
     *                      "token": string - JWT token (expires in 3 hours)
     *                      "user": {
     *                        "id": integer - User ID
     *                        "name": string - User full name
     *                        "login": string - User login
     *                        "email": string - User email address
     *                      }
     *                    }
     *                  }
     *                  Error (HTTP 500):
     *                  {
     *                    "error": string - Error message description
     *                  }
     *
     * @throws Exception When application seed is not defined
     * @throws Exception When login field is missing or empty
     * @throws Exception When password field is missing or empty
     * @throws Exception When authentication fails (invalid credentials)
     */
    public function authenticate(Request $request)
    {
        try 
        {
            $ini = AdiantiApplicationConfig::get();
            $key = APPLICATION_NAME . $ini['general']['seed'];
            
            if (empty($ini['general']['seed']))
            {
                throw new Exception('Application seed not defined');
            }

            if(!$request->get('login'))
            {
                throw new Exception(_t('The field ^1 is required', 'login'));
            }
            
            if(!$request->get('password'))
            {
                throw new Exception(_t('The field ^1 is required', 'password'));
            }
            
            $user = ApplicationAuthenticationService::authenticate($request->get('login'), $request->get('password'));
            
            $token = array(
                "user" => $request->get('login'),
                "userid" => $user->id,
                "username" => $user->name,
                "usermail" => $user->email,
                "expires" => strtotime("+ 3 hours")
            );
            
            $response = [
                'status' => 'success',
                'data' => [
                    'token' => JWT::encode($token, $key, 'HS256'),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'login' => $user->login,
                        'email' => $user->email
                    ]
                ]
            ];

            return (new Response())->json($response);
        }
        catch (Exception $e) {
            return (new Response())->json(['error' => $e->getMessage()], 500);
        }
    }


}
