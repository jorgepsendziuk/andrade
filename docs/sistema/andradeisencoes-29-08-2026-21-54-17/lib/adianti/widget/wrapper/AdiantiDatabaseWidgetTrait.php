<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TConnection;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;

use Exception;

/**
 * Provides methods to retrieve items and objects from a database to populate widgets.
 *
 * This trait is designed to be used with widgets that require dynamic data loading from a database.
 * It facilitates fetching key-value pairs and objects from database models while handling transactions automatically.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
trait AdiantiDatabaseWidgetTrait
{
    /**
     * Retrieves key-value pairs from a database model to populate a widget.
     *
     * This method fetches records from the specified model and returns an associative array
     * where the keys are retrieved from the specified key column and the values from the value column.
     *
     * @param string        $database    The name of the database connection.
     * @param string        $model       The fully qualified name of the model class.
     * @param string        $key         The column name used as the key in the returned array.
     * @param string        $value       The column name used as the value in the returned array.
     * @param string|null   $ordercolumn The column name used for sorting (optional, defaults to key column).
     * @param TCriteria|null $criteria    An optional criteria object to filter results.
     *
     * @return array An associative array of key-value pairs fetched from the database.
     *
     * @throws Exception If any required parameter (database, model, key, value) is missing.
     */
    public static function getItemsFromModel($database, $model, $key, $value, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        $items = [];
        $key   = trim($key);
        $value = trim($value);
        
        if (empty($database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }
        
        if (empty($model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }
        
        if (empty($key))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'key', __CLASS__));
        }
        
        if (empty($value))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'value', __CLASS__));
        }
        
        $cur_conn = serialize(TTransaction::getDatabaseInfo());
        $new_conn = serialize(TConnection::getDatabaseInfo($database));
        
        $open_transaction = ($cur_conn !== $new_conn);
        
        if ($open_transaction)
        {
            TTransaction::openFake($database);
        }
        
        // creates repository
        $repository = new TRepository($model);
        if (is_null($criteria))
        {
            $criteria = new TCriteria;
        }
        $criteria->setProperty('order', isset($ordercolumn) ? $ordercolumn : $key);
        
        // load all objects
        $collection = $repository->load($criteria, FALSE);
        
        // add objects to the options
        if ($collection)
        {
            foreach ($collection as $object)
            {
                if (isset($object->$value))
                {
                    $items[$object->$key] = $object->$value;
                }
                else
                {
                    $items[$object->$key] = $object->render($value);
                }
            }
            
            if (strpos($value, '{') !== FALSE AND is_null($ordercolumn))
            {
                asort($items);
            }
        }
        
        if ($open_transaction)
        {
            TTransaction::close();
        }
        
        return $items;
    }
    
    /**
     * Retrieves objects from a database model to populate a widget.
     *
     * This method fetches records from the specified model and returns an associative array
     * where the keys are retrieved from the specified key column, and the values are the corresponding objects.
     *
     * @param string        $database    The name of the database connection.
     * @param string        $model       The fully qualified name of the model class.
     * @param string        $key         The column name used as the key in the returned array.
     * @param string|null   $ordercolumn The column name used for sorting (optional, defaults to key column).
     * @param TCriteria|null $criteria    An optional criteria object to filter results.
     *
     * @return array An associative array where keys are extracted from the key column and values are model objects.
     *
     * @throws Exception If any required parameter (database, model, key) is missing.
     */
    public static function getObjectsFromModel($database, $model, $key, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        $items = [];
        $key   = trim($key);
        
        if (empty($database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }
        
        if (empty($model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }
        
        if (empty($key))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'key', __CLASS__));
        }
        
        $cur_conn = serialize(TTransaction::getDatabaseInfo());
        $new_conn = serialize(TConnection::getDatabaseInfo($database));
        
        $open_transaction = ($cur_conn !== $new_conn);
        
        if ($open_transaction)
        {
            TTransaction::openFake($database);
        }
        
        // creates repository
        $repository = new TRepository($model);
        if (is_null($criteria))
        {
            $criteria = new TCriteria;
        }
        $criteria->setProperty('order', isset($ordercolumn) ? $ordercolumn : $key);
        
        // load all objects
        $collection = $repository->load($criteria, FALSE);
        
        // add objects to the options
        if ($collection)
        {
            foreach ($collection as $object)
            {
                $items[$object->$key] = $object;
            }
        }
        
        if ($open_transaction)
        {
            TTransaction::close();
        }
        
        return $items;
    }
}
