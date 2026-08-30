<?php
namespace Adianti\Database;

use Adianti\Core\AdiantiCoreTranslator;
use PDO;
use Exception;
use Mad\Service\MadLogService;

/**
 * Singleton manager for database connections
 *
 * This class is responsible for managing database connections using PDO.
 * It supports multiple database types, reads configurations from INI or PHP files,
 * and provides a caching mechanism for keeping persistent connections.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TConnection
{
    private static $config_path;
    private static $conn_cache;
    private static $keep_connections;
    private static $last_connection_id;
    
    /**
     * Private constructor to prevent instantiation.
     *
     * This class is a singleton and should not be instantiated directly.
     * All database connections must be accessed through static methods.
     */
    private function __construct() {}
    
    /**
     * Opens a database connection based on the given database name.
     *
     * This method reads the database configuration from an INI or PHP file,
     * retrieves the connection settings, and establishes a PDO connection.
     *
     * @param string $database The name of the database configuration file (without extension).
     *
     * @return PDO The PDO object representing the database connection.
     * @throws Exception If the database configuration file is not found.
     */
    public static function open($database)
    {
        $dbinfo = self::getDatabaseInfo($database);
        
        if (!$dbinfo)
        {
            // if the database doesn't exists, throws an exception
            throw new Exception(AdiantiCoreTranslator::translate('File not found') . ': ' ."'{$database}.ini'");
        }
        
        return self::openArray( $dbinfo );
    }
    
    /**
     * Sets the path for database configuration files.
     *
     * This method allows specifying a custom directory where database
     * configuration files are located.
     *
     * @param string $path The path to the configuration directory.
     */
    public static function setConfigPath($path)
    {
        self::$config_path = $path;
    }
    
    /**
     * Opens a database connection using an array of database parameters.
     *
     * This method supports different database drivers (e.g., MySQL, PostgreSQL, SQLite),
     * applies specific connection settings, and enables persistent connections if configured.
     *
     * @param array $db An associative array containing database connection details:
     *                  - user: Database username.
     *                  - pass: Database password.
     *                  - name: Database name.
     *                  - host: Database host.
     *                  - type: Database type (e.g., mysql, pgsql, sqlite, etc.).
     *                  - port: Database port (optional).
     *                  - char: Character set (optional).
     *                  - flow, fupp, fnat, fkey: Additional database settings (optional).
     *                  - zone: Time zone setting (optional).
     *                  - keep: Whether to keep the connection persistent (optional).
     *                  - opts: Additional connection options (optional).
     *
     * @return PDO The PDO object representing the database connection.
     * @throws Exception If the database driver is not supported.
     */
    public static function openArray($db)
    {
        // read the database properties
        $user  = isset($db['user']) ? $db['user'] : NULL;
        $pass  = isset($db['pass']) ? $db['pass'] : NULL;
        $name  = isset($db['name']) ? $db['name'] : NULL;
        $host  = isset($db['host']) ? $db['host'] : NULL;
        $type  = isset($db['type']) ? $db['type'] : NULL;
        $port  = isset($db['port']) ? $db['port'] : NULL;
        $char  = isset($db['char']) ? $db['char'] : NULL;
        $flow  = isset($db['flow']) ? $db['flow'] : NULL;
        $fupp  = isset($db['fupp']) ? $db['fupp'] : NULL;
        $fnat  = isset($db['fnat']) ? $db['fnat'] : NULL;
        $fkey  = isset($db['fkey']) ? $db['fkey'] : NULL;
        $zone  = isset($db['zone']) ? $db['zone'] : NULL;
        $keep  = isset($db['keep']) ? $db['keep'] : NULL;
        $opts  = isset($db['opts']) ? ';' . $db['opts'] : '';
        $type  = strtolower($type);
        
        if(!empty($db['fake']))
        {
            unset($db['fake']);
        }
        
        if($keep && !empty(self::$keep_connections[md5(serialize($db))]))
        {
            return self::$keep_connections[md5(serialize($db))];
        }
        else{
            self::$last_connection_id = uniqid();
        }
        MadLogService::logConnection($db, self::$last_connection_id);

        // each database driver has a different instantiation process
        switch ($type)
        {
            case 'pgsql':
                $port = $port ? $port : '5432';
                $conn = new PDO("pgsql:dbname={$name};user={$user}; password={$pass};host=$host;port={$port}{$opts}");
                if(!empty($char))
                {
                    $conn->exec("SET CLIENT_ENCODING TO '{$char}';");
                }
                break;
            case 'mysql':
                $port = $port ? $port : '3306';
                if ($char == 'ISO')
                {
                    $options = array();

                    if ($zone)
                    {
                        $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '{$zone}'");
                    }

                    $conn = new PDO("mysql:host={$host};port={$port};dbname={$name}{$opts}", $user, $pass, $options);
                }
                elseif ($char == 'utf8mb4')
                {
                    $zone = $zone ? ";SET time_zone = '{$zone}'" : "";
                    $conn = new PDO("mysql:host={$host};port={$port};dbname={$name}{$opts}", $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4{$zone}"));
                }
                else
                {
                    $zone = $zone ? ";SET time_zone = '{$zone}'" : "";
                    $conn = new PDO("mysql:host={$host};port={$port};dbname={$name}{$opts}", $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8{$zone}"));
                }
                break;
            case 'sqlite':
                $conn = new PDO("sqlite:{$name}{$opts}");
                if (is_null($fkey) OR $fkey == '1')
                {
                    $conn->query('PRAGMA foreign_keys = ON'); // referential integrity must be enabled
                }
                break;
            case 'ibase':
            case 'fbird':
                $db_string = empty($port) ? "{$host}:{$name}" : "{$host}/{$port}:{$name}";
                $charset = $char ? ";charset={$char}" : '';
                $conn = new PDO("firebird:dbname={$db_string}{$charset}{$opts}", $user, $pass);
                $conn->setAttribute( PDO::ATTR_AUTOCOMMIT, 0);
                break;
            case 'oracle':
                $port    = $port ? $port : '1521';
                $charset = $char ? ";charset={$char}" : '';
                $tns     = isset($db['tns']) ? $db['tns'] : NULL;
                
                if ($tns)
                {
                    $conn = new PDO("oci:dbname={$tns}{$charset}{$opts}", $user, $pass);
                }
                else
                {
                    $conn = new PDO("oci:dbname={$host}:{$port}/{$name}{$charset}{$opts}", $user, $pass);
                }
                
                if (isset($db['date']))
                {
                    $date = $db['date'];
                    $conn->query("ALTER SESSION SET NLS_DATE_FORMAT = '{$date}'");
                }
                if (isset($db['time']))
                {
                    $time = $db['time'];
                    $conn->query("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = '{$time}'");
                }
                if (isset($db['nsep']))
                {
                    $nsep = $db['nsep'];
                    $conn->query("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '{$nsep}'");
                }
                break;
            case 'mssql':
                if (OS == 'WIN')
                {
                    if ($port)
                    {
                        $conn = new PDO("sqlsrv:Server={$host},{$port};Database={$name}{$opts}", $user, $pass);
                    }
                    else
                    {
                        $conn = new PDO("sqlsrv:Server={$host};Database={$name}{$opts}", $user, $pass);
                    }
                }
                else
                {
                    $charset = $char ? ";charset={$char}" : '';
                    
                    if ($port)
                    {
                        $conn = new PDO("dblib:host={$host}:{$port};dbname={$name}{$charset}{$opts}", $user, $pass);
                    }
                    else
                    {
                        $conn = new PDO("dblib:host={$host};dbname={$name}{$charset}{$opts}", $user, $pass);
                    }
                }
                break;
            case 'dblib':
                $charset = $char ? ";charset={$char}" : '';
                
                if ($port)
                {
                    $conn = new PDO("dblib:host={$host}:{$port};dbname={$name}{$charset}{$opts}", $user, $pass);
                }
                else
                {
                    $conn = new PDO("dblib:host={$host};dbname={$name}{$charset}{$opts}", $user, $pass);
                }
                break;
            case 'sqlsrv':
                if ($port)
                {
                    $conn = new PDO("sqlsrv:Server={$host},{$port};Database={$name}{$opts}", $user, $pass);
                }
                else
                {
                    $conn = new PDO("sqlsrv:Server={$host};Database={$name}{$opts}", $user, $pass);
                }
                if (!empty($db['ntyp']))
                {
                    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
                }
                break;
            case 'odbc':
                $conn = new PDO("odbc:".substr($opts,1));
                break;
            default:
                throw new Exception(AdiantiCoreTranslator::translate('Driver not found') . ': ' . $type);
                break;
        }
        
        // define wich way will be used to report errors (EXCEPTION)
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($flow == '1')
        {
            $conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        }

        if ($fupp == '1')
        {
            $conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        }

        if ($fnat == '1')
        {
            $conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        }
        
        if($keep)
        {
            self::$keep_connections[md5(serialize($db))] = $conn;
        }

        MadLogService::logExecutionTimeConnection($db, self::$last_connection_id, spl_object_hash($conn));

        // return the PDO object
        return $conn;
    }
    
    /**
     * Retrieves the database connection settings from an INI or PHP file.
     *
     * This method checks if the configuration for a given database is already cached.
     * If not, it reads the database settings from a file and returns them as an array.
     *
     * @param string $database The name of the database configuration file (without extension).
     *
     * @return array|false An associative array with database settings or FALSE if the file is not found.
     */
    public static function getDatabaseInfo($database)
    {
        $path  = empty(self::$config_path) ? 'app/config' : self::$config_path;
        $filei = "{$path}/{$database}.ini";
        $filep = "{$path}/{$database}.php";
        
        if (!empty(self::$conn_cache[ $database ]))
        {
            return self::$conn_cache[ $database ];
        }
        
        // check if the database configuration file exists
        if (file_exists($filei))
        {
            // read the INI and retuns an array
            $ini = parse_ini_file($filei);
            self::$conn_cache[ $database ] = $ini;
            return $ini;
        }
        else if (file_exists($filep))
        {
            $ini = require $filep;
            self::$conn_cache[ $database ] = $ini;
            return $ini;
        }
        else
        {
            return FALSE;
        }
    }
    
    /**
     * Stores database connection settings in the internal cache.
     *
     * This method allows dynamically setting database connection parameters
     * without modifying configuration files.
     *
     * @param string $database The name of the database.
     * @param array $info An associative array containing database connection details.
     */
    public static function setDatabaseInfo($database, $info)
    {
        self::$conn_cache[ $database ] = $info;
    }

    public static function getLastConnectionId()
    {
        return self::$last_connection_id;
    }
}
