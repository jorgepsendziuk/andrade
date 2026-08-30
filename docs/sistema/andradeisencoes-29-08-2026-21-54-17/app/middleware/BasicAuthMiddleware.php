<?php

use Mad\Rest\Request;
use Mad\Rest\Response;

/**
 * Middleware para autenticação Basic Auth
 * 
 * Usa a chave REST definida no arquivo de configuração
 * Formato esperado: Basic {rest_key}
 */
class BasicAuthMiddleware
{
    /**
     * Processa a requisição e verifica a chave REST
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
                return (new Response())->json([
                    'error' => 'Chave de autorização não fornecida'
                ], 401);
            }
            
            // Verifica se o token começa com "Basic "
            if (substr($token, 0, 5) !== 'Basic') {
                return (new Response())->json([
                    'error' => 'Formato de autorização inválido. Formato esperado: Basic {rest_key}'
                ], 401);
            }
            
            // Remove o prefixo "Basic " do token
            $token = substr($token, 6);
            
            // Obtém a chave REST do arquivo de configuração
            $ini = AdiantiApplicationConfig::get();
            
            if (empty($ini['general']['rest_key'])) {
                return (new Response())->json([
                    'error' => 'Chave REST não definida no arquivo de configuração'
                ], 500);
            }
            
            // Verifica se a chave REST corresponde
            if ($ini['general']['rest_key'] !== $token) {
                return (new Response())->json([
                    'error' => 'Chave de autorização inválida'
                ], 401);
            }
            
            // Se chegou aqui, a chave é válida
            return $next($request);
        }
        catch (Exception $e) {
            return (new Response())->json([
                'error' => 'Erro na autenticação: ' . $e->getMessage()
            ], 500);
        }
    }
} 