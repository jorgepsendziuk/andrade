<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Widget\Form\TRadioGroup;
use Adianti\Database\TCriteria;

use Exception;

/**
 * TDBRadioGroup is a wrapper for a radio button group that retrieves its options from a database.
 *
 * This widget extends TRadioGroup and automatically loads its items from a specified database table.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBRadioGroup extends TRadioGroup
{
    protected $items; // array containing the combobox options
    
    use AdiantiDatabaseWidgetTrait;
    
    /**
     * Constructor method
     *
     * Initializes a radio button group with values fetched from a database.
     *
     * @param string      $name        The name of the radio group field
     * @param string      $database    The database connection name
     * @param string      $model       The model class name
     * @param string      $key         The table field to be used as the key
     * @param string      $value       The table field to be listed as value
     * @param string|null $ordercolumn The column name used for ordering the results (optional)
     * @param TCriteria|null $criteria A filtering criteria for selecting the data (optional)
     * 
     * @throws Exception If an error occurs while retrieving data
     */
    public function __construct($name, $database, $model, $key, $value, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);
        
        // load items
        parent::addItems( self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria) );
    }

    /**
     * Reloads the radio button group with new data from the database.
     *
     * This method updates the radio button options dynamically based on the database model data.
     *
     * @param string      $formname    The name of the form containing the radio group
     * @param string      $field       The name of the radio group field
     * @param string      $database    The database connection name
     * @param string      $model       The model class name
     * @param string      $key         The table field to be used as the key
     * @param string      $value       The table field to be listed as value
     * @param string|null $ordercolumn The column used to order the fields (optional)
     * @param TCriteria|null $criteria A filtering criteria for selecting the data (optional)
     * @param array       $options     Additional options such as layout, breakItems, useButton, value, changeAction, changeFunction
     *
     * @throws Exception If an error occurs while retrieving data
     */
    public static function reloadFromModel($formname, $field, $database, $model, $key, $value, $ordercolumn = NULL, $criteria = NULL, $options = [])
    {
        // load items
        $items = self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria);

        // reload radio
        parent::reload($formname, $field, $items, $options);
    }
}
