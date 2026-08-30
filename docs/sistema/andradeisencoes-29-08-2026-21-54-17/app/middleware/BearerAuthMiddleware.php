<?php

use Adianti\Database\TTransaction;
use Mad\Rest\Request;
use Mad\Rest\Response;

/**
 * Middleware para autenticação Bearer Token
 * 
 * Usa o ApplicationAuthenticationService para validar o token
 * Formato esperado: Bearer {token}
 */
class BearerAuthMiddleware
{
    /**
     * Processa a requisição e verifica o token Bearer
     * 
     * @param Request $request
     * @param callable $next
     * @return ResponseInterface|null
     */
    public function handle(Request $request, callable $next)
    {
        try {
            $token = $request->getAuthToken();
            
            if (!$token) {
                throw new Exception('Token não fornecido');
            }
            
            // Verifica se o token começa com "Bearer "
            if (substr($token, 0, 6) !== 'Bearer') {
                throw new Exception('Formato de autorização inválido. Formato esperado: Bearer {token}');
            }
            
            // Remove o prefixo "Bearer " do token
            $token = substr($token, 7);
            
            // Valida o token usando o ApplicationAuthenticationService
            ApplicationAuthenticationService::fromToken($token);

            $ini  = AdiantiApplicationConfig::get();
        
            if (!empty($ini['general']['multiunit']) && $ini['general']['multiunit'] == '1' && !empty($unit_id))
            {
                TTransaction::openFake('permission');

                $user = new SystemUsers(TSession::getValue('userid'));
                if($user->system_unit_id)
                {
                    ApplicationAuthenticationService::setUnit( $user->system_unit_id );
                }
                
                TTransaction::close();
            }
            
            // Se chegou aqui, o token é válido
            return $next($request);
        }
        catch (Exception $e) {
            throw new Exception('Erro na autenticação: ' . $e->getMessage());
        }
    }
} 