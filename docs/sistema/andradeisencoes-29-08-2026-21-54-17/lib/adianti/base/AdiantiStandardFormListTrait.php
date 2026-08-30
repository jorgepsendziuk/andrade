<?php
namespace Adianti\Base;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TRecord;
use Adianti\Database\TFilter;
use Adianti\Database\TCriteria;
use Adianti\Registry\TSession;
use Exception;

/**
 * Standard Form List Trait
 *
 * This trait provides standard functionalities for handling form-based lists,
 * including CRUD operations, pagination, and record transformation.
 *
 * @version    7.5
 * @package    base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
trait AdiantiStandardFormListTrait
{
    protected $afterSaveAction;
    
    use AdiantiStandardControlTrait;
    
    /**
     * Sets an action to be executed after saving a record.
     *
     * @param TAction $action The action to be executed after saving.
     */
    public function setAfterSaveAction($action)
    {
        $this->afterSaveAction = $action;
    }
    
    /**
     * Sets the record limit for data retrieval.
     *
     * @param int $limit The maximum number of records to be retrieved.
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
    
    /**
     * Sets the default order for data retrieval.
     *
     * @param string $order The field to order the results by.
     * @param string $direction The sorting direction ('asc' for ascending, 'desc' for descending).
     */
    public function setDefaultOrder($order, $direction = 'asc')
    {
        $this->order = $order;
        $this->direction = $direction;
    }
    
    /**
     * Sets a criteria object to filter the retrieved data.
     *
     * @param TCriteria $criteria The criteria object to be applied.
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Defines a callback function to transform objects before loading them into the data grid.
     *
     * @param callable $callback The callback function that processes the objects.
     */
    public function setTransformer($callback)
    {
        $this->transformCallback = $callback;
    }
    
    /**
     * Reloads the data grid with records from the database.
     *
     * This method applies filtering, sorting, pagination, and optional transformation
     * before displaying the records in the data grid.
     *
     * @param array|null $param Optional parameters such as pagination settings.
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database
            TTransaction::open($this->database);
            
            // instancia um repositório
            $repository = new TRepository($this->activeRecord);
            $limit = isset($this->limit) ? ( $this->limit > 0 ? $this->limit : NULL) : 10;
            // creates a criteria
            $criteria = isset($this->criteria) ? clone $this->criteria : new TCriteria;
            if ($this->order)
            {
                $criteria->setProperty('order',     $this->order);
                $criteria->setProperty('direction', $this->direction);
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $this->datagrid->addItem($object);
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            if (isset($this->pageNavigation))
            {
                $this->pageNavigation->setCount($count); // count of records
                $this->pageNavigation->setProperties($param); // order, page
                $this->pageNavigation->setLimit($limit); // limit
            }
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Saves the form data to the database.
     *
     * This method validates the form data, stores the record in the database,
     * executes an optional post-save action, and reloads the data grid.
     *
     * @return TRecord|null The saved record instance on success, null on failure.
     */
    public function onSave()
    {
        try
        {
            // open a transaction with database
            TTransaction::open($this->database);
            
            // get the form data
            $object = $this->form->getData($this->activeRecord);
            
            // validate data
            $this->form->validate();
            
            // stores the object
            $object->store();
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            // close the transaction
            TTransaction::close();
            
            // shows the success message
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction ?? null);
            
            // reload the listing
            $this->onReload();
            
            return $object;
        }
        catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData($this->activeRecord);
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Prompts the user for confirmation before deleting a record.
     *
     * @param array $param The parameters, including the record key to delete.
     */
    public function onDelete($param)
    {
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
    }
    
    /**
     * Deletes a record from the database.
     *
     * This method removes the record associated with the given key and reloads
     * the data grid upon successful deletion.
     *
     * @param array $param The parameters containing the record key.
     */
    public function Delete($param)
    {
        try
        {
            // get the parameter $key
            $key=$param['key'];
            // open a transaction with database
            TTransaction::open($this->database);
            
            $class = $this->activeRecord;
            
            // instantiates object
            $object = new $class($key);
            
            // deletes the object from the database
            $object->delete();
            
            // close the transaction
            TTransaction::close();
            
            // reload the listing
            $this->onReload( $param );
            // shows the success message
            new TMessage('info', AdiantiCoreTranslator::translate('Record deleted'));
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Clears the form fields.
     *
     * @param array $param Optional parameters.
     */
    public function onClear($param)
    {
        $this->form->clear();
    }
    
    /**
     * Loads a record into the form for editing.
     *
     * This method retrieves the record by its key and populates the form with its data.
     * If no key is provided, the form is cleared.
     *
     * @param array $param The parameters containing the record key.
     *
     * @return TRecord|null The loaded record instance or null if not found.
     */
    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                // get the parameter $key
                $key=$param['key'];
                
                // open a transaction with database
                TTransaction::open($this->database);
                
                $class = $this->activeRecord;
                
                // instantiates object
                $object = new $class($key);
                
                // fill the form with the active record data
                $this->form->setData($object);
                
                // close the transaction
                TTransaction::close();
                
                $this->onReload( $param );
                
                return $object;
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Displays the page and ensures the data grid is loaded.
     *
     * This method checks whether the data grid needs to be reloaded based on
     * the request method parameters.
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }
}
