<?php
namespace Adianti\Core;

use Adianti\Widget\Dialog\TMessage;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

use Exception;

/**
 * Application loader
 *
 * This class is responsible for managing the autoloading mechanism of application classes,
 * checking predefined folders, and ensuring that required classes are properly loaded.
 *
 * @version    7.5
 * @package    core
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiApplicationLoader
{
    static $loadedClasses;
    
    /**
     * Retrieves the list of classes that have been loaded by the application loader.
     *
     * @return array|null An associative array of loaded classes where keys are class names and values are boolean,
     *                    or null if no classes have been loaded.
     */
    public static function getLoadedClasses()
    {
        return self::$loadedClasses;
    }
    
    /**
     * Checks if a given class has already been loaded by the application loader.
     *
     * @param string $class The fully qualified class name to check.
     *
     * @return bool True if the class has been loaded, false otherwise.
     */
    public static function isLoadedClass($class)
    {
        return !empty(self::$loadedClasses[$class]);
    }
    
    /**
     * Automatically loads the specified class by searching for it in predefined application directories.
     *
     * The method searches for class files within a predefined set of folders (`app/model`, `app/control`,
     * `app/view`, `app/lib`, `app/helpers`, `app/service`). If a matching class file is found, it is included.
     * Additionally, it performs recursive directory traversal when necessary.
     *
     * @param string $class The fully qualified class name to be autoloaded.
     *
     * @return bool True if the class was successfully loaded, false otherwise.
     */
    public static function autoload($class)
    {
        // echo "&nbsp;&nbsp;App loader $class<br>";
        $folders = array();
        $folders[] = 'app/model';
        $folders[] = 'app/control';
        $folders[] = 'app/controller';
        $folders[] = 'app/middleware';
        $folders[] = 'app/view';
        $folders[] = 'app/lib';
        $folders[] = 'app/helpers';
        $folders[] = 'app/service';
        
        // search in app root
        if (file_exists("{$class}.class.php"))
        {
            require_once "{$class}.class.php";
            self::$loadedClasses[$class] = true;
            return TRUE;
        }
        
        // search in app root
        if (file_exists("{$class}.php"))
        {
            require_once "{$class}.php";
            self::$loadedClasses[$class] = true;
            return TRUE;
        }
        
        foreach ($folders as $folder)
        {
            if (file_exists("{$folder}/{$class}.class.php"))
            {
                require_once "{$folder}/{$class}.class.php";
                self::$loadedClasses[$class] = true;
                return TRUE;
            }
            if (file_exists("{$folder}/{$class}.php"))
            {
                require_once "{$folder}/{$class}.php";
                self::$loadedClasses[$class] = true;
                return TRUE;
            }
            else if (file_exists("{$folder}/{$class}.iface.php"))
            {
                require_once "{$folder}/{$class}.iface.php";
                self::$loadedClasses[$class] = true;
                return TRUE;
            }
            else
            {
                try
                {
                    if (file_exists($folder))
                    {
                        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder),
                                                               RecursiveIteratorIterator::SELF_FIRST) as $entry)
                        {
                            if (is_dir($entry))
                            {
                                if (file_exists("{$entry}/{$class}.class.php"))
                                {
                                    require_once "{$entry}/{$class}.class.php";
                                    self::$loadedClasses[$class] = true;
                                    return TRUE;
                                }
                                else if (file_exists("{$entry}/{$class}.php"))
                                {
                                    require_once "{$entry}/{$class}.php";
                                    self::$loadedClasses[$class] = true;
                                    return TRUE;
                                }
                                else if (file_exists("{$entry}/{$class}.iface.php"))
                                {
                                    require_once "{$entry}/{$class}.iface.php";
                                    self::$loadedClasses[$class] = true;
                                    return TRUE;
                                }
                            }
                        }
                    }
                }
                catch(Exception $e)
                {
                    new TMessage('error', $e->getMessage());
                }
            }
        }
    }
}
