<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Form\TCheckList;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;

use Exception;

/**
 * Database Checklist Widget.
 *
 * This widget represents a checklist component that fetches data from a database.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBCheckList extends TCheckList
{
    protected $items; // array containing the combobox options
    protected $keyColumn;
    protected $valueColumn;
    
    use AdiantiDatabaseWidgetTrait;
    
    /**
     * Class Constructor
     *
     * Initializes the checklist widget, loads items from the database, and configures columns.
     *
     * @param string     $name        Widget name
     * @param string     $database    Database name
     * @param string     $model       Model class name
     * @param string     $key         Table field to be used as key in the checklist
     * @param string     $value       Table field to be displayed in the checklist
     * @param string|null $ordercolumn Column to order the fields (optional)
     * @param TCriteria|null $criteria Criteria to filter the model (optional)
     */
    public function __construct($name, $database, $model, $key, $value, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);
        
        // define the ID column por set/get values from component
        parent::setIdColumn($key);
        
        // value column
        $this->valueColumn = parent::addColumn($value,  '',    'left',  '100%');
        
        // get objects
        $collection = ( $this->getObjectsFromModel($database, $model, $key, $ordercolumn, $criteria) );
        
        if (strpos($value, '{') !== FALSE)
        {
            // iterate objects to render the value when needed
            TTransaction::open($database);
            if ($collection)
            {
                foreach ($collection as $key => $object)
                {
                    if (!isset($object->$value))
                    {
                        $collection[$key]->$value = $object->render($value);
                    }
                }
            }
            TTransaction::close();
        }
        
        parent::addItems($collection);
        
        $head = parent::getHead();
        $head->{'style'} = 'display:none';
    }
}
