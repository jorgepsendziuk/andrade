<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Widget\Form\TCheckGroup;
use Adianti\Database\TCriteria;

use Exception;

/**
 * Database CheckBox Widget
 *
 * This widget represents a checkbox group component that fetches data from a database.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBCheckGroup extends TCheckGroup
{
    protected $items; // array containing the combobox options
    
    use AdiantiDatabaseWidgetTrait;
    
    /**
     * Class Constructor
     *
     * Initializes the checkbox group and loads items from the database.
     *
     * @param string     $name        Widget name
     * @param string     $database    Database name
     * @param string     $model       Model class name
     * @param string     $key         Table field to be used as key in the checkboxes
     * @param string     $value       Table field to be displayed in the checkboxes
     * @param string|null $ordercolumn Column to order the fields (optional)
     * @param TCriteria|null $criteria Criteria to filter the model (optional)
     */
    public function __construct($name, $database, $model, $key, $value, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);
        
        // load items
        parent::addItems( self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria) );
    }

    /**
     * Reloads the checkbox group with data from the database.
     * 
     * @param string     $formname    Form name
     * @param string     $field       Field name
     * @param string     $database    Database name
     * @param string     $model       Model class name
     * @param string     $key         Table field to be used as key in the checkboxes
     * @param string     $value       Table field to be displayed in the checkboxes
     * @param string|null $ordercolumn Column to order the fields (optional)
     * @param TCriteria|null $criteria Criteria to filter the model (optional)
     * @param array      $options     Additional options (layout, breakItems, useButton, etc.)
     */
    public static function reloadFromModel($formname, $field, $database, $model, $key, $value, $ordercolumn = NULL, $criteria = NULL, $options = [])
    {
        // load items
        $items = self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria);

        // reload checkbox
        parent::reload($formname, $field, $items, $options);
    }
}
