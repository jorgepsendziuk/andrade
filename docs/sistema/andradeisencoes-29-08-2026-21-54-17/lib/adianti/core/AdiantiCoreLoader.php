<?php
namespace Adianti\Core;

use Adianti\Core\AdiantiApplicationLoader;
use Adianti\Core\AdiantiClassMap;

/**
 * Framework class autoloader
 *
 * This class is responsible for loading class mappings, setting class paths,
 * and handling the autoloading of classes within the framework.
 *
 * @version    7.5
 * @package    core
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiCoreLoader
{
    private static $classMap;
    
    /**
     * Loads the class map from AdiantiClassMap.
     *
     * This method retrieves the class mapping and sets up aliases for classes 
     * that have been renamed, ensuring compatibility with older class names.
     *
     * @return void
     */
    public static function loadClassMap()
    {
        self::$classMap = AdiantiClassMap::getMap();
        $aliases = AdiantiClassMap::getAliases();
        
        if ($aliases)
        {
            foreach ($aliases as $old_class => $new_class)
            {
                if (class_exists($new_class))
                {
                    class_alias($new_class, $old_class);
                }
            }
        }
    }
    
    /**
     * Defines a custom path for a specific class.
     *
     * This method allows defining a manual mapping between a class name 
     * and its corresponding file path.
     *
     * @param string $class The name of the class.
     * @param string $path  The file path where the class is located.
     *
     * @return void
     */
    public static function setClassPath($class, $path)
    {
        self::$classMap[$class] = $path;
    }
    
    /**
     * Core autoloader method.
     *
     * Attempts to load a class based on its namespace and name. If the class 
     * is not found using the primary method, it falls back to the legacy 
     * autoloader and the Adianti application loader.
     *
     * @param string $className The fully qualified name of the class.
     *
     * @return void
     */
    public static function autoload($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if (strrpos($className, '\\') !== FALSE)
        {
            $pieces    = explode('\\', $className);
            $className = array_pop($pieces);
            $namespace = implode('\\', $pieces);
        }
        $fileName = 'lib'.'\\'.strtolower($namespace).'\\'.$className.'.php';
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $fileName);
        
        if (file_exists($fileName))
        {
            //echo "PSR: $className <br>";
            require_once $fileName;
            self::globalScope($className);
        }
        else
        {
            if (!self::legacyAutoload($className))
            {
                if (!AdiantiApplicationLoader::autoload($className))
                {
                    if (file_exists('vendor/autoload_extras.php'))
                    {
                        require_once 'vendor/autoload_extras.php';
                    }
                }
            }
        }
    }
    
    /**
     * Legacy autoloader for classes defined in the class map.
     *
     * Checks if a class exists in the pre-defined class map and loads it if available.
     *
     * @param string $class The name of the class to be loaded.
     *
     * @return bool Returns TRUE if the class was successfully loaded, FALSE otherwise.
     */
    public static function legacyAutoload($class)
    {
        if (isset(self::$classMap[$class]))
        {
            if (file_exists(self::$classMap[$class]))
            {
                //echo 'Classmap '.self::$classMap[$class] . '<br>';
                require_once self::$classMap[$class];
                
                self::globalScope($class);
                return TRUE;
            }
        }
    }
    
    /**
     * Maps a class to the global scope.
     *
     * Ensures that a class can be referenced globally by mapping it 
     * from its fully qualified namespace to a global alias if necessary.
     *
     * @param string $class The name of the class.
     *
     * @return void
     */
    public static function globalScope($class)
    {
        if (isset(self::$classMap[$class]) AND self::$classMap[$class])
        {
            if (!class_exists($class, FALSE))
            {
                $ns = self::$classMap[$class];
                $ns = str_replace('/', '\\', $ns);
                $ns = str_replace('lib\\adianti', 'Adianti', $ns);
                $ns = str_replace('lib\\mad', 'Mad', $ns);
                $ns = str_replace('.class.php', '', $ns);
                $ns = str_replace('.php', '', $ns);
                
                //echo "&nbsp;&nbsp;&nbsp;&nbsp;Mapping: $ns, $class<br>";
                if (class_exists($ns) OR interface_exists($ns))
                {
                    class_alias($ns, $class, FALSE);
                }
            }
        }
    }
}
