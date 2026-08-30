<?php

use Mad\Service\MadLogService;
use Mad\Rest\Response;

/**
 * @package    util
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

/**
 * Função mad_dump - Realiza var_dump de variáveis e retorna em formato JSON
 * para integração com o painel de debug
 * 
 * @param mixed ...$args Variáveis a serem examinadas
 * @return string JSON representando os dados do var_dump
 */
function mad_dump(...$args) {
    // Obtém informações do contexto de chamada
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : 'unknown';
    $file = explode('/', $file);
    $file = end($file);
    $line = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 0;

    if($file == 'GlobalFunctions.php')
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $file = isset($backtrace[1]['file']) ? $backtrace[1]['file'] : 'unknown';
        $file = explode('/', $file);
        $file = end($file);
        $line = isset($backtrace[1]['line']) ? $backtrace[1]['line'] : 0;
    }
    
    
    // Prepara o resultado
    $dumps = [];
    
    foreach ($args as $index => $arg) {
        // Captura a saída do var_dump
        
        $dump = $arg;
        
        
        $dump = print_r($arg, true);
        
        // Adiciona ao array de dumps
        MadLogService::addDebugData([
            'requestId' => REQUEST_ID,
            'name' => '',
            'type' => gettype($arg),
            'value' => base64_encode($dump),
            'file' => $file,
            'line' => $line,
            'time' => date('Y-m-d H:i:s')
        ]);
    }
}

function md(...$args)
{
    mad_dump(...$args);
}

function mdd(...$args)
{
    mad_dump(...$args);

    MadLogService::finalizeDebugLogging();
    die;
}

function response()
{
    $response = new Response;
    return $response;
}