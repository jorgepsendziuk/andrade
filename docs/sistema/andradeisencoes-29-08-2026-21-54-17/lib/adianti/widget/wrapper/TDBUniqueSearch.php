<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Widget\Form\TMultiSearch;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TUniqueSearch;
use Adianti\Widget\Wrapper\TDBMultiSearch;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;

use Exception;

/**
 * DBUnique Search Widget
 *
 * This widget extends TDBMultiSearch and allows selecting a single value retrieved from a database table.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBUniqueSearch extends TDBMultiSearch implements AdiantiWidgetInterface
{
    protected $database;
    protected $model;
    protected $mask;
    protected $key;
    protected $column;
    protected $items;
    protected $size;
    
    /**
     * Class Constructor
     *
     * Initializes the database-driven unique search widget.
     *
     * @param string     $name        Widget's name
     * @param string     $database    Database connection name
     * @param string     $model       Model class name
     * @param string     $key         Table field to be used as the key in the search
     * @param string     $value       Table field to be displayed in the search options
     * @param string|null $orderColumn Column name to order the values (optional)
     * @param TCriteria|null $criteria Criteria object to filter the model records (optional)
     *
     * @throws Exception If any error occurs during instantiation
     */
    public function __construct($name, $database, $model, $key, $value, $orderColumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name, $database, $model, $key, $value, $orderColumn, $criteria);
        parent::setMaxSize(1);
        parent::setDefaultOption(TRUE);
        parent::disableMultiple();
        
        $this->tag->{'widget'} = 'tdbuniquesearch';
    }
    
    /**
     * Set the field's value
     *
     * This method assigns a value to the field and retrieves its description from the database.
     *
     * @param mixed $value The value to be set
     *
     * @throws Exception If the database transaction fails
     */
    public function setValue($value)
    {
        if (is_scalar($value) && !empty($value))
        {   
            
            $close = false;
            if (!TTransaction::hasConnection($this->database))
            {
                TTransaction::openFake($this->database);
                $close = true;
            }
            
            $model = $this->model;
            
            $pk = constant("{$model}::PRIMARYKEY");
            
            if ($pk === $this->key) // key is the primary key (default)
            {
                // use find because it uses cache
                $object = $model::find( $value );
            }
            else // key is an alternative key (uses where->first)
            {
                $object = $model::where( $this->key, '=', $value )->first();
            }
            
            if ($object)
            {
                $description = $object->render($this->mask);
                $this->value = $value; // avoid use parent::setValue() because compat mode
                parent::addItems( [$value => $description ] );
            }
            
            if ($close)
            {
                TTransaction::close();
            }
        }
        else
        {
            $this->value = $value;
        }
    }
    
    /**
     * Retrieve the posted data
     *
     * This method extracts the data submitted through the form.
     *
     * @return string The submitted value or an empty string if no value was provided
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            $val = $_POST[$name];
            
            if ($val == '') // empty option
            {
                return '';
            }
            else
            {
                return $val;
            }
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Get the field size
     *
     * @return mixed The field size
     */
    public function getSize()
    {
        return $this->size;
    }
    
    /**
     * Render the widget
     *
     * This method outputs the HTML representation of the widget.
     */
    public function show()
    {
        $this->tag->{'name'}  = $this->name; // tag name
        parent::show();
    }
}
