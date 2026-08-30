<?php
namespace Adianti\Core;

/**
 * Application config
 *
 * This class allows the application to load configuration settings from an array,
 * apply specific settings that affect environment variables, and export the stored configuration.
 *
 * @version    7.5
 * @package    core
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiApplicationConfig
{
    private static $config;
    
    /**
     * Loads configuration settings from an array.
     *
     * This method sets the application configuration using an associative array.
     *
     * @param array $config An associative array containing configuration settings.
     *
     * @return void
     */
    public static function load($config)
    {
        if (is_array($config))
        {
            self::$config = $config;
        }
    }
    
    /**
     * Applies configuration settings that modify environment variables.
     *
     * If debugging is enabled in the configuration, this method adjusts PHP error display
     * settings to show detailed error messages.
     *
     * @return void
     */
    public static function apply()
    {
        if (!empty(self::$config['general']['debug']) && self::$config['general']['debug'] == '1')
        {
            ini_set('display_errors', '1');
            ini_set('error_reporting', E_ALL);
            ini_set("html_errors", 1); 
            ini_set("error_prepend_string", "<pre>"); 
            ini_set("error_append_string ", "</pre>"); 
        }
    }
    
    /**
     * Retrieves the currently loaded configuration.
     *
     * @return array|null Returns the configuration array if loaded, or null if no configuration has been set.
     */
    public static function get()
    {
        return self::$config;
    }
}