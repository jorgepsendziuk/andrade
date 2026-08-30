<?php
namespace Adianti\Registry;

use Adianti\Registry\AdiantiRegistryInterface;

/**
 * Adianti APC Record Cache
 *
 * This class provides an implementation of the AdiantiRegistryInterface using
 * APCu as a caching mechanism for storing and retrieving values.
 *
 * @version    7.5
 * @package    registry
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TAPCache implements AdiantiRegistryInterface
{
    /**
     * Checks if the APCu extension is enabled.
     *
     * @return bool Returns TRUE if APCu is available, FALSE otherwise.
     */
    public static function enabled()
    {
        return extension_loaded('apcu');
    }
    
    /**
     * Stores a value in the APCu cache.
     *
     * @param string $key   The key under which the value will be stored.
     * @param mixed  $value The value to be stored.
     *
     * @return bool Returns TRUE on success, FALSE on failure.
     */
    public static function setValue($key, $value)
    {
        return apcu_store(APPLICATION_NAME . '_' . $key, serialize($value));
    }
    
    /**
     * Retrieves a value from the APCu cache.
     *
     * @param string $key The key of the stored value.
     *
     * @return mixed The stored value or FALSE if the key does not exist.
     */
    public static function getValue($key)
    {
        return unserialize(apcu_fetch(APPLICATION_NAME . '_' . $key));
    }
    
    /**
     * Deletes a value from the APCu cache.
     *
     * @param string $key The key of the value to be deleted.
     *
     * @return bool Returns TRUE on success, FALSE on failure.
     */
    public static function delValue($key)
    {
        return apcu_delete(APPLICATION_NAME . '_' . $key);
    }
    
    /**
     * Clears all stored values from the APCu cache.
     *
     * @return bool Returns TRUE on success, FALSE on failure.
     */
    public static function clear()
    {
        return apcu_clear_cache();
    }
}
