<?php
namespace Adianti\Registry;

use SessionHandlerInterface;
use Adianti\Registry\AdiantiRegistryInterface;

/**
 * Session Data Handler
 *
 * This class provides an implementation of the AdiantiRegistryInterface using 
 * PHP sessions for storing and retrieving values.
 *
 * @version    7.5
 * @package    registry
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSession implements AdiantiRegistryInterface
{
    /**
     * Initializes a new session.
     *
     * @param SessionHandlerInterface|null $handler A custom session handler (optional).
     * @param string|null $path The session save path (optional).
     */
    public function __construct(?SessionHandlerInterface $handler = NULL, $path = NULL)
    {
        if ($path)
        {
            session_save_path($path);
        }
        
        if ($handler)
        {
            session_set_save_handler($handler, true);
        }
		
        // if there's no opened session
        if (!session_id())
        {
            session_start();
        }
    }
    
    /**
     * Checks if the session service is active.
     *
     * @return bool Returns TRUE if the session is active, starts the session if necessary.
     */
    public static function enabled()
    {
        if (!session_id())
        {
            return session_start();
        }
        return TRUE;
    }
    
    /**
     * Stores a value in the session.
     *
     * @param string $var   The session variable name.
     * @param mixed  $value The value to be stored.
     */
    public static function setValue($var, $value)
    {
        if (defined('APPLICATION_NAME'))
        {
            $_SESSION[APPLICATION_NAME][$var] = $value;
        }
        else
        {
            $_SESSION[$var] = $value;
        }
    }
    
    /**
     * Retrieves a value from the session.
     *
     * @param string $var The session variable name.
     * @return mixed The stored value or NULL if the variable is not set.
     */
    public static function getValue($var)
    {
        if (defined('APPLICATION_NAME'))
        {
            if (isset($_SESSION[APPLICATION_NAME][$var]))
            {
                return $_SESSION[APPLICATION_NAME][$var];
            }
        }
        else
        {
            if (isset($_SESSION[$var]))
            {
                return $_SESSION[$var];
            }
        }
    }
    
    /**
     * Deletes a variable from the session.
     *
     * @param string $var The session variable name.
     */
    public static function delValue($var)
    {
        if (defined('APPLICATION_NAME'))
        {
            unset($_SESSION[APPLICATION_NAME][$var]);
        }
        else
        {
            unset($_SESSION[$var]);
        }
    }
    
    /**
     * Regenerates the session ID to prevent session fixation attacks.
     */
    public static function regenerate()
    {
        session_regenerate_id();
    }
    
    /**
     * Clears all session data.
     */
    public static function clear()
    {
        self::freeSession();
    }
    
    /**
     * Destroys the session data while maintaining session integrity.
     */
    public static function freeSession()
    {
        if (defined('APPLICATION_NAME'))
        {
            $_SESSION[APPLICATION_NAME] = array();
        }
        else
        {
            $_SESSION[] = array();
        }
    }
}
