<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Database\TCriteria;
use Adianti\Widget\Form\TArrowStep;
use Adianti\Widget\Wrapper\AdiantiDatabaseWidgetTrait;

/**
 * Database Arrow Step Widget
 *
 * This widget represents an arrow step component that fetches data from a database.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Lucas Tomasi
 * @author     Matheus Agnes Dias
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBArrowStep extends TArrowStep
{
    protected $items; // array containing the combobox options
    
    protected $database;
    protected $model;
    protected $key;
    protected $value;
    protected $ordercolumn;
    protected $colorcolumn;
    protected $criteria;

    use AdiantiDatabaseWidgetTrait;
    
    /**
     * Class Constructor
     *
     * Initializes the arrow step widget and configures the database connection.
     *
     * @param string     $name        Widget name
     * @param string     $database    Database name
     * @param string     $model       Model class name
     * @param string     $key         Table field to be used as key in the widget
     * @param string     $value       Table field to be displayed in the widget
     * @param string|null $ordercolumn Column to order the fields (optional)
     * @param TCriteria|null $criteria Criteria to filter the model (optional)
     */
    public function __construct($name, $database, $model, $key, $value, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);

        $this->database = $database;
        $this->model = $model;
        $this->key = $key;
        $this->value = $value;
        $this->ordercolumn = $ordercolumn;
        $this->criteria = $criteria;
    }

    /**
     * Sets the color column for the arrow step widget.
     *
     * @param string $colorcolumn Column name containing color values
     */
    public function setColorColumn($colorcolumn)
    {
        $this->colorcolumn = $colorcolumn;
    }

    /**
     * Displays the arrow step widget.
     * 
     * Fetches data from the database and applies the color settings if provided.
     */
    public function show()
    {
        parent::setItems( self::getItemsFromModel($this->database, $this->model, $this->key, $this->value, $this->ordercolumn, $this->criteria) );

        if ($this->colorcolumn)
        {
            parent::setColorItems( self::getItemsFromModel($this->database, $this->model, $this->key, $this->colorcolumn, $this->ordercolumn, $this->criteria) );
        }

        parent::show();
    }
}