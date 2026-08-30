<?php

namespace Mad\Service;

use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Exception;
use Adianti\Registry\TSession;
use Adianti\Widget\Util\TScript;

/**
 * @package    service
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

class MadLogService
{
    private static $executionTime;
    private static $debugData = [];
    private static $sqlLogs = [];
    private static $connectionTime = [];
    public static function enableDebugConsole($param = [])
    {
        self::checkPermission();

        TSession::setValue('mad_debug_console', true);

        TScript::create('location.href = "index.php" ');
    }

    public static function disableDebugConsole($param = []){
        self::checkPermission();

        TSession::setValue('mad_debug_console', false);

        TScript::create('location.href = "index.php" ');
    }

    public static function initializeDebugLogging()
    {   
        self::$executionTime = microtime(true);
        define('REQUEST_ID', AdiantiCoreApplication::getRequestId() ?? uniqid());
        $requestId = REQUEST_ID;

        if (TSession::getValue('mad_debug_console') === true)
        {
            header("Mad-Req-Id: {$requestId}");
            TTransaction::enableGlobalLog($requestId);
        }
    }

    public static function finalizeDebugLogging()
    {
        $executionTime = number_format((microtime(true) - self::$executionTime) * 1000, 6);

        if (TSession::getValue('mad_debug_console') === true)
        {   
            echo "<script data-log='mad-debug-console-log'>";

            $request = json_encode([
                'type'=> 'http_request', 
                'id'=>REQUEST_ID, 
                'time'=>$executionTime, 
                'url'=> 'index.php',
                'body'=> $_REQUEST
            ]);
            echo "System.addDebug({$request});";

            if(self::$sqlLogs)
            {
                foreach(self::$sqlLogs as $log)
                {
                    $log['type'] = 'sql';
                    $log = json_encode($log);
                    echo "System.addDebug({$log});";
                }                
            }

            $requestId = REQUEST_ID;

            foreach(self::$debugData as $dump)
            {
                $dump['type'] = 'var_dump';
                $dump['request_id'] = $requestId;
                $dump = json_encode($dump);
                echo "System.addDebug({$dump});";
            }

            echo "</script>";
        }
    }

    public static function isDebugConsoleEnabled()
    {
        return TSession::getValue('mad_debug_console') === true;
    }

    public static function checkPermission()
    {
        if (TSession::getValue('login') !== 'admin')
        {
            throw new Exception(_bt('Permission denied'));
        }
    }

    public static function addDebugData($data)
    {
        self::$debugData[] = $data;
    }

    public static function addSqlLog($log)
    {
        self::$sqlLogs[$log['id']] = $log;
    }

    public static function logConnection($db, $id)
    {
        if(!self::isDebugConsoleEnabled())
        {
            return;
        }
        self::$connectionTime[$id] = microtime(true);

        $db['host'] = $db['host'] ?? '';
        $message = base64_encode("{$db['type']}:{$db['name']}[{$db['host']}]");
        $transactionId = TTransaction::getUniqId();
        $logId = $id;
        $transactionTrace = TTransaction::getTraceLog();
        $requestId = defined('REQUEST_ID') ? REQUEST_ID : uniqid();
        $backtraces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = 'Unknown';
        if($backtraces){
            foreach($backtraces as $backtrace){

                if(!empty($backtrace['file']) && preg_match("/app\//", $backtrace['file'])){
                    $trace = basename($backtrace['file']).'('.$backtrace['line'].')';
                    break;
                }
            }
        }
        // mad_dump(TTransaction::getDatabase());

        // Adiciona ao array em vez de fazer echo
        MadLogService::addSqlLog([
            'requestId' => $requestId,
            'transactionId' => $transactionId,
            'id' => $logId,
            'queryType'=> 'connection',
            'logData' => [
                'id' => $logId,
                'query' => $message,
                'queryType'=> 'connection',
                'params' => null,
                'results' => null,
                'transactionTrace' => $transactionTrace,
                'trace' => $trace,
                'database' => TTransaction::getDatabase()
            ]
        ]);
    }

    public static function logExecutionTimeConnection($db, $id, $newId)
    {
        if(!self::isDebugConsoleEnabled())
        {
            return;
        }
        $executionTime = (microtime(true) - self::$connectionTime[$id]) * 1000;
        
        $log = self::$sqlLogs[$id];

        unset(self::$sqlLogs[$id]);

        $log['id'] = $newId;
        $log['connectionId'] = $newId;
        $log['logData']['time'] = $executionTime;
        self::$sqlLogs[$newId] = $log;
    }

    public static function logExecutionTime($time, $logId)
    {
        self::$sqlLogs[$logId]['logData']['time'] = $time;
    }

}
