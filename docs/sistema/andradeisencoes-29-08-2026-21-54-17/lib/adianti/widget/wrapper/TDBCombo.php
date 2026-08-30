<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Widget\Form\TCombo;
use Adianti\Database\TCriteria;

use Exception;

/**
 * Database ComboBox Widget
 *
 * This widget represents a dropdown (combo box) component that fetches data from a database.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBCombo extends TCombo
{
    protected $items; // array containing the combobox options
    protected $model;
    protected $database;
    protected $key;
    protected $column;
    protected $orderColumn;
    protected $criteria;
    
    use AdiantiDatabaseWidgetTrait;
    
    /**
     * Class Constructor
     *
     * Initializes the combo box and loads items from the database.
     *
     * @param string     $name        Widget name
     * @param string     $database    Database name
     * @param string     $model       Model class name
     * @param string     $key         Table field to be used as key in the combo
     * @param string     $value       Table field to be displayed in the combo
     * @param string|null $ordercolumn Column to order the fields (optional)
     * @param TCriteria|null $criteria Criteria to filter the model (optional)
     */
    public function __construct($name, $database, $model, $key, $value, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);
        $this->model = $model;
        $this->database = $database;
        $this->key = $key;
        $this->column = $value;
        $this->orderColumn = $ordercolumn;
        $this->criteria = $criteria;
        
        // load items
        parent::addItems( self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria) );
    }
    
    /**
     * Reloads the combo box with data from the database.
     * 
     * @param string     $formname    Form name
     * @param string     $field       Field name
     * @param string     $database    Database name
     * @param string     $model       Model class name
     * @param string     $key         Table field to be used as key in the combo
     * @param string     $value       Table field to be displayed in the combo
     * @param string|null $ordercolumn Column to order the fields (optional)
     * @param TCriteria|null $criteria Criteria to filter the model (optional)
     * @param bool       $startEmpty  Whether the combo should start with an empty option
     * @param bool       $fire_events Whether change actions should be fired
     */
    public static function reloadFromModel($formname, $field, $database, $model, $key, $value, $ordercolumn = NULL, $criteria = NULL, $startEmpty = FALSE, $fire_events = TRUE)
    {
        // load items
        $items = self::getItemsFromModel($database, $model, $key, $value, $ordercolumn, $criteria);
        
        // reload combo
        parent::reload($formname, $field, $items, $startEmpty, $fire_events);
    }
}
