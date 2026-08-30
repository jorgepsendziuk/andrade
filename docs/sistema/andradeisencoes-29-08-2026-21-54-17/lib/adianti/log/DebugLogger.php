<?php
namespace Adianti\Log;

use Adianti\Database\TTransaction;
use Adianti\Log\TLogger;
use Mad\Service\MadLogService;

/**
 * Register LOG in Standard Output
 *
 * @version    4.0
 * @package    log
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */
class DebugLogger extends TLogger
{
    /**
     * Array para armazenar logs SQL
     * @var array
     */
    private static $sqlLogs = [];

    /**
     * Writes an message in the LOG file
     * @param  $message Message to be written
     */
    public function write($message)
    {
        $message = base64_encode($message);
        $transactionId = TTransaction::getUniqId();
        $logId = TTransaction::getLastLogId();
        $transactionTrace = TTransaction::getTraceLog();
        $requestId = REQUEST_ID;
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = 'Unknown';
        if($backtrace){
            foreach($backtrace as $backtrace){
                if(!empty($backtrace['file']) && preg_match("/app\//", $backtrace['file'])){
                    $trace = basename($backtrace['file']).'('.$backtrace['line'].')';
                    break;
                }
            }
        }

        // Adiciona ao array em vez de fazer echo
        MadLogService::addSqlLog([
            'requestId' => $requestId,
            'transactionId' => $transactionId,
            'connectionId' => TTransaction::getConnectionId(),
            'queryType' => 'sql',
            'id' => $logId,
            'logData' => [
                'queryType' => 'sql',
                'id' => $logId,
                'query' => $message,
                'params' => null,
                'results' => null,
                'transactionTrace' => $transactionTrace,
                'trace' => $trace,
                'database' => TTransaction::getDatabase(),
            ]
        ]);
    }

    public function writeExecutionTime($time, $logId)
    {
        MadLogService::logExecutionTime($time, $logId);
    }
    
    /**
     * Retorna todos os logs SQL coletados
     * @return array
     */
    public static function getSqlLogs()
    {
        return self::$sqlLogs;
    }
        
    /**
     * Limpa os logs coletados
     */
    public static function clearLogs()
    {
        self::$sqlLogs = [];
    }
}
