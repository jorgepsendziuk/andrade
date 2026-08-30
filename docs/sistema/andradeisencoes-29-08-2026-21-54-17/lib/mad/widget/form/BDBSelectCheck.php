<?php
use Adianti\Widget\Wrapper\AdiantiDatabaseWidgetTrait;

/**
 * BDBSelectCheck Widget
 * A database-driven select check widget that loads items from a database model
 *
 * @version    4.0
 * @package    widget
 * @subpackage form
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */
class BDBSelectCheck extends BSelectCheck
{
    /**
     * Array containing the combobox options
     * @var array
     */
    protected $items;
    
    use AdiantiDatabaseWidgetTrait;
    
    /**
     * Class Constructor
     * @param string $name        widget's name
     * @param string $database    database name
     * @param string $model       model class name
     * @param string $key         table field to be used as key in the select
     * @param string $value       table field to be listed in the select
     * @param string $ordercolumn column to order the fields (optional)
     * @param TCriteria $criteria criteria object to filter the model (optional)
     */
    public function __construct($name, $database, $model, $key, $value, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);
        
        // load items
        parent::addItems( self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria) );
    }
    
    /**
     * Reload select options from model data
     * @param string    $formname     form name
     * @param string    $field        field name
     * @param string    $database     database name
     * @param string    $model        model class name
     * @param string    $key          table field to be used as key in the select
     * @param string    $value        table field to be listed in the select
     * @param string    $ordercolumn  column to order the fields (optional)
     * @param TCriteria $criteria     criteria object to filter the model (optional)
     * @param boolean   $startEmpty   if the select will have an empty first item
     * @param boolean   $fire_events  if change action will be fired
     * @static
     */
    public static function reloadFromModel($formname, $field, $database, $model, $key, $value, $ordercolumn = NULL, $criteria = NULL, $startEmpty = FALSE, $fire_events = TRUE)
    {
        // load items
        $items = self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria);
        
        // reload combo
        parent::reload($formname, $field, $items, $startEmpty, $fire_events);
    }
}
