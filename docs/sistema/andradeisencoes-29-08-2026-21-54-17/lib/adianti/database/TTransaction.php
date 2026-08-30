<?php
namespace Adianti\Database;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TConnection;
use Adianti\Log\TLogger;
use Adianti\Log\TLoggerSTD;
use Adianti\Log\TLoggerTXT;
use Adianti\Log\DebugLogger;
use Adianti\Log\AdiantiLoggerInterface;

use PDO;
use Closure;
use Exception;

/**
 * Manage Database transactions
 *
 * Manages database transactions.
 * Provides methods to start, commit, rollback, and log database transactions.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TTransaction
{
    private static $conn;     // active connection
    private static $logger;   // Logger object
    private static $database; // database name
    private static $dbinfo;   // database info
    private static $counter;
    private static $uniqid;
    private static $globalLog = false;
    private static $traceLog = [];
    private static $executionTime = 0;
    private static $lastLogId = NULL;
    
    /**
     * Class constructor.
     * Private to prevent instantiation, as this class only provides static methods.
     */
    private function __construct(){}
    
    /**
     * Opens a connection and starts a transaction.
     *
     * @param string $database The name of the database (defined in an INI file).
     * @param array|null $dbinfo Optional array containing database connection details.
     *
     * @return PDO The database connection object.
     * @throws Exception If the connection fails.
     */
    public static function open($database, $dbinfo = NULL)
    {
        if (!isset(self::$counter))
        {
            self::$counter = 0;
        }
        else
        {
            self::$counter ++;
        }
        self::$database[self::$counter] = $database;
        self::$uniqid[self::$counter] = uniqid();

        if ($dbinfo)
        {
            self::$conn[self::$counter]   = TConnection::openArray($dbinfo);
            self::$dbinfo[self::$counter] = $dbinfo;
        }
        else
        {
            $dbinfo = TConnection::getDatabaseInfo($database);
            self::$conn[self::$counter]   = TConnection::open($database);
            self::$dbinfo[self::$counter] = $dbinfo;
        }
        
        $driver = self::$conn[self::$counter]->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        $fake = isset($dbinfo['fake']) ? $dbinfo['fake'] : FALSE;
        
        if (!$fake && ! self::$conn[self::$counter]->inTransaction())
        {
            // begins transaction
            self::$conn[self::$counter]->beginTransaction();
        }
        else if (in_array($dbinfo['type'], ['ibase', 'fbird']))
        {
            self::$conn[self::$counter]->setAttribute( PDO::ATTR_AUTOCOMMIT, 1);
        }
        
        if (!empty(self::$dbinfo[self::$counter]['slog']))
        {
            $logClass = self::$dbinfo[self::$counter]['slog'];
            if (class_exists($logClass))
            {
                self::setLogger(new $logClass);
            }
        }
        else
        {
            // turn OFF the log
            self::$logger[self::$counter] = NULL;
        }

        if(self::$globalLog)
        {
            self::dumpGlobalLog();
        }
        
        return self::$conn[self::$counter];
    }
    
    /**
     * Opens a fake transaction for the specified database.
     * Fake transactions do not actually begin transactions at the database level.
     *
     * @param string $database The name of the database (defined in an INI file).
     */
    public static function openFake($database)
    {
        $info = TConnection::getDatabaseInfo($database);
        $info['fake'] = 1;
        
        TTransaction::open($database, $info);
    }
    
    /**
     * Returns the current active database connection.
     *
     * @return PDO|null The active PDO connection, or null if no connection is open.
     */
    public static function get()
    {
        if (isset(self::$conn[self::$counter]))
        {
            return self::$conn[self::$counter];
        }
    }
    
    /**
     * Rolls back all pending operations in the current transaction.
     *
     * @return bool Returns true if the rollback was successful, false otherwise.
     */
    public static function rollback()
    {
        if (isset(self::$conn[self::$counter]))
        {
            $driver = self::$conn[self::$counter]->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $info = self::getDatabaseInfo();
            $fake = isset($info['fake']) ? $info['fake'] : FALSE;
            
            if (!$fake)
            {
                // rollback
                self::$conn[self::$counter]->rollBack();
            }
            self::$conn[self::$counter] = NULL;
            self::$uniqid[self::$counter] = NULL;
            self::$counter --;
            
            return true;
        }
    }
    
    /**
     * Commits all pending operations and closes the current transaction.
     *
     * @return bool Returns true if the commit was successful, false otherwise.
     */
    public static function close()
    {
        if (isset(self::$conn[self::$counter]))
        {
            $driver = self::$conn[self::$counter]->getAttribute(PDO::ATTR_DRIVER_NAME);
            $info = self::getDatabaseInfo();
            $fake = isset($info['fake']) ? $info['fake'] : FALSE;
            
            if (!$fake && self::$conn[self::$counter]->inTransaction())
            {
                // apply the pending operations
                self::$conn[self::$counter]->commit();
            }
            
            self::$conn[self::$counter] = NULL;
            self::$uniqid[self::$counter] = NULL;
            self::$counter --;
            
            return true;
        }
    }
    
    /**
     * Closes all active transactions by committing them.
     */
    public static function closeAll()
    {
        $has_connection = true;
        
        while ($has_connection)
        {
            $has_connection = self::close();
        }
    }
    
    /**
     * Rolls back all active transactions.
     */
    public static function rollbackAll()
    {
        $has_connection = true;
        
        while ($has_connection)
        {
            $has_connection = self::rollback();
        }
    }
    
    /**
     * Assigns a logger function to log transaction messages.
     *
     * @param Closure $logger A Closure function used for logging messages.
     *
     * @throws Exception If no active transaction exists.
     */
    public static function setLoggerFunction(Closure $logger)
    {
        if (isset(self::$conn[self::$counter]))
        {
            self::$logger[self::$counter] = $logger;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__);
        }
    }
    
    /**
     * Assigns a logger strategy to log transaction messages.
     *
     * @param AdiantiLoggerInterface $logger An instance of a logger implementing AdiantiLoggerInterface.
     *
     * @throws Exception If no active transaction exists.
     */
    public static function setLogger(AdiantiLoggerInterface $logger)
    {
        if (isset(self::$conn[self::$counter]))
        {
            self::$logger[self::$counter] = $logger;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__);
        }
    }
    
    /**
     * Logs a message using the assigned logger strategy.
     *
     * @param string $message The message to be logged.
     */
    public static function log($message)
    {
        // check if exist a logger
        if (!empty(self::$logger[self::$counter]))
        {
            $log = self::$logger[self::$counter];
            
            // avoid recursive log
            self::$logger[self::$counter] = NULL;
            
            if(self::$globalLog)
            {
                self::$lastLogId = uniqid();

                self::$executionTime = microtime(true);
                $log->write($message);
            }
            else if ($log instanceof AdiantiLoggerInterface)
            {
                // call log method
                $log->write($message);
            }
            else if ($log instanceof Closure)
            {
                $log($message);
            }
            
            // restore logger
            self::$logger[self::$counter] = $log;
        }
    }
    
    /**
     * Returns the name of the current active database.
     *
     * @return string|null The database name, or null if no database is set.
     */
    public static function getDatabase()
    {
        if (!empty(self::$database[self::$counter]))
        {
            return self::$database[self::$counter];
        }
    }
    
    /**
     * Returns the database connection details of the active transaction.
     *
     * @return array|null The database connection information, or null if not available.
     */
    public static function getDatabaseInfo()
    {
        if (!empty(self::$dbinfo[self::$counter]))
        {
            return self::$dbinfo[self::$counter];
        }
    }
    
    /**
     * Returns the unique transaction identifier.
     *
     * @return string|null The unique transaction ID, or null if not available.
     */
    public static function getUniqId()
    {
        if (!empty(self::$uniqid[self::$counter]))
        {
            return self::$uniqid[self::$counter];
        }
    }
    
    /**
     * Enables transaction logging to a file or standard output.
     *
     * @param string|null $file Optional file path to write logs. If not specified, logs will be printed to STDOUT.
     */
    public static function dump( $file = null )
    {
        if(self::$globalLog)
        {
            self::setLogger( new DebugLogger() );
        }
        else if ($file)
        {
            self::setLogger( new TLoggerTXT($file) );
        }
        else
        {
            self::setLogger( new TLoggerSTD );
        }
    }

    /**
     * Checks if a transaction is open for a specific database.
     *
     * @param string $database The name of the database to check.
     *
     * @return bool True if the transaction is open, false otherwise.
     */
    public static function isOpen($database, $fake = false)
    {   
        $cur_conn = serialize(TTransaction::getDatabaseInfo());
        $new_conn = serialize(TConnection::getDatabaseInfo($database));
        
        if($fake)
        {
            $new_conn['fake'] = 1;
        }
        
        return ($cur_conn === $new_conn);
    }

    /**
     * Checks if a fake transaction is open for a specific database.
     *
     * @param string $database The name of the database to check.
     *
     * @return bool True if the fake transaction is open, false otherwise.
     */
    public static function isOpenFake($database)
    {   
        $cur_conn = serialize(TTransaction::getDatabaseInfo());
        $new_conn = TConnection::getDatabaseInfo($database);
        $new_conn['fake'] = 1;

        $new_conn = serialize($new_conn);
        
        return ($cur_conn === $new_conn);
    }

    public static function hasConnection($database)
    {
        return self::isOpen($database) || self::isOpenFake($database);
    }

    public static function enableGlobalLog()
    {
        self::$globalLog = true;
    }

    public static function logExecutionTime()
    {
        // check if exist a logger
        if (!empty(self::$logger[self::$counter]))
        {
            $log = self::$logger[self::$counter];
            
            // avoid recursive log
            self::$logger[self::$counter] = NULL;
            
            if (self::$globalLog)
            {
                $log->writeExecutionTime((microtime(true) - self::$executionTime) * 1000, self::$lastLogId);
            }
            
            // restore logger
            self::$logger[self::$counter] = $log;
        }
    }

     /**
     * Returns the Transaction uniqid
     */
    public static function getLastLogId()
    {
        return self::$lastLogId;
    }

    public static function getTraceLog()
    {
        return self::$traceLog[self::$uniqid[max(0, self::$counter)] ?? ''] ?? 'Unknown';
    }

    public static function dumpGlobalLog()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if($backtrace){
            foreach($backtrace as $trace){
                if(!empty($trace['file']) && preg_match("/app\//", $trace['file'])){
                    self::$traceLog[self::$uniqid[self::$counter]] = basename($trace['file']).'('.$trace['line'].')';
                    break;
                }
            }
        }

        if(empty(self::$traceLog[self::$uniqid[self::$counter]]))
        {
            self::$traceLog[self::$uniqid[self::$counter]] = 'Unknown';
        }
        
        self::dump();
    }

    /**
     * Returns the current active connection
     * @return PDO
     */
    public static function getConnectionId()
    {
        if (isset(self::$conn[self::$counter]))
        {
            return spl_object_hash(self::$conn[self::$counter]);
        }

        return uniqid();
    }
}
