<?php
namespace Adianti\Base;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TRecord;
use Adianti\Database\TFilter;
use Adianti\Database\TExpression;
use Adianti\Database\TCriteria;
use Adianti\Registry\TSession;

use Exception;
use DomDocument;

/**
 * Standard Collection Trait
 *
 * This trait provides standard functionalities for managing collections of database records,
 * including filtering, ordering, pagination, and callbacks for transformation and post-processing.
 * It is designed to be used in conjunction with Adianti Framework's components.
 *
 * @version    7.5
 * @package    base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
trait AdiantiStandardCollectionTrait
{
    protected $filterFields;
    protected $formFilters;
    protected $filterTransformers;
    protected $loaded;
    protected $limit;
    protected $operators;
    protected $logic_operators;
    protected $order;
    protected $direction;
    protected $criteria;
    protected $transformCallback;
    protected $afterLoadCallback;
    protected $afterSearchCallback;
    protected $orderCommands;
    
    use AdiantiStandardControlTrait;
    
    /**
     * Sets the record limit.
     *
     * @param int $limit The maximum number of records to be retrieved.
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
    
    /**
     * Sets the collection object (datagrid).
     *
     * @param object $object The collection object (typically a datagrid).
     */
    public function setCollectionObject($object)
    {
        $this->datagrid = $object;
    }
    
    /**
     * Sets a custom order command for a specific column.
     *
     * @param string $order_column  The name of the column.
     * @param string $order_command The SQL command to be executed for ordering.
     */
    public function setOrderCommand($order_column, $order_command)
    {
        if (empty($this->orderCommands))
        {
            $this->orderCommands = [];
        }
        
        $this->orderCommands[$order_column] = $order_command;
    }
    
    /**
     * Defines the default order for records.
     *
     * @param string $order     The field name to be used for ordering.
     * @param string $direction The order direction ('asc' or 'desc'). Defaults to 'asc'.
     */
    public function setDefaultOrder($order, $direction = 'asc')
    {
        $this->order = $order;
        $this->direction = $direction;
    }
    
    /**
     * Sets a filter field for backward compatibility.
     *
     * @param string $filterField The field name to be used for filtering.
     * @deprecated Use addFilterField() instead.
     */
    public function setFilterField($filterField)
    {
        $this->addFilterField($filterField);
    }
    
    /**
     * Sets a filtering operator for backward compatibility.
     *
     * @param string $operator The comparison operator (e.g., '=', 'like', '>', '<').
     * @deprecated Use addFilterField() instead.
     */
    public function setOperator($operator)
    {
        $this->operators[] = $operator;
    }
    
    /**
     * Adds a filter field for searching records.
     *
     * @param string $filterField              The field name to be used for filtering.
     * @param string $operator                 The comparison operator (default: 'like').
     * @param string|null $formFilter          The form field name associated with the filter (default: same as filterField).
     * @param callable|null $filterTransformer A function to transform the filter value before applying.
     * @param string $logic_operator           The logical operator ('AND' or 'OR'). Default: 'AND'.
     */
    public function addFilterField($filterField, $operator = 'like', $formFilter = NULL, $filterTransformer = NULL, $logic_operator = TExpression::AND_OPERATOR)
    {
        $this->filterFields[] = $filterField;
        $this->operators[] = $operator;
        $this->logic_operators[] = $logic_operator;
        $this->formFilters[] = isset($formFilter) ? $formFilter : $filterField;
        $this->filterTransformers[] = $filterTransformer;
    }
    
    /**
     * Sets the criteria for filtering records.
     *
     * @param TCriteria $criteria The filtering criteria.
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Defines a callback function to transform objects before loading them into the datagrid.
     *
     * @param callable $callback The transformation callback function.
     */
    public function setTransformer($callback)
    {
        $this->transformCallback = $callback;
    }
    
    /**
     * Defines a callback function to be executed after loading records into the datagrid.
     *
     * @param callable $callback The callback function.
     */
    public function setAfterLoadCallback($callback)
    {
        $this->afterLoadCallback = $callback;
    }
    
    /**
     * Defines a callback function to be executed after performing a search action.
     *
     * @param callable $callback The callback function.
     */
    public function setAfterSearchCallback($callback)
    {
        $this->afterSearchCallback = $callback;
    }
    
    /**
     * Performs a search and stores the filters in the session.
     *
     * @param array|null $param Parameters for the search action.
     */
    public function onSearch( $param = null )
    {
        // get the search form data
        $data = $this->form->getData();
        
        if ($this->formFilters)
        {
            foreach ($this->formFilters as $filterKey => $formFilter)
            {
                $operator       = isset($this->operators[$filterKey]) ? $this->operators[$filterKey] : 'like';
                $filterField    = isset($this->filterFields[$filterKey]) ? $this->filterFields[$filterKey] : $formFilter;
                $filterFunction = isset($this->filterTransformers[$filterKey]) ? $this->filterTransformers[$filterKey] : null;
                
                // check if the user has filled the form
                if (!empty($data->{$formFilter}) OR (isset($data->{$formFilter}) AND $data->{$formFilter} == '0'))
                {
                    // $this->filterTransformers
                    if ($filterFunction)
                    {
                        $fieldData = $filterFunction($data->{$formFilter});
                    }
                    else
                    {
                        $fieldData = $data->{$formFilter};
                    }
                    
                    // creates a filter using what the user has typed
                    if (stristr($operator, 'like'))
                    {
                        $filter = new TFilter($filterField, $operator, "%{$fieldData}%");
                    }
                    else
                    {
                        $filter = new TFilter($filterField, $operator, $fieldData);
                    }
                    
                    // stores the filter in the session
                    TSession::setValue($this->activeRecord.'_filter', $filter); // BC compatibility
                    TSession::setValue($this->activeRecord.'_filter_'.$formFilter, $filter);
                    TSession::setValue($this->activeRecord.'_filter_'.$filterKey, $filter);
                    TSession::setValue($this->activeRecord.'_'.$formFilter, $data->{$formFilter});
                }
                else
                {
                    TSession::setValue($this->activeRecord.'_filter', NULL); // BC compatibility
                    TSession::setValue($this->activeRecord.'_filter_'.$formFilter, NULL);
                    TSession::setValue($this->activeRecord.'_filter_'.$filterKey, NULL);
                    TSession::setValue($this->activeRecord.'_'.$formFilter, '');
                }
            }
        }
        
        TSession::setValue($this->activeRecord.'_filter_data', $data);
        TSession::setValue(get_class($this).'_filter_data', $data);
        
        // fill the form with data again
        $this->form->setData($data);
        
        if (is_callable($this->afterSearchCallback))
        {
            call_user_func($this->afterSearchCallback, $this->datagrid, $data);
        }
        
        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            AdiantiCoreApplication::loadPage($class, 'onReload', ['offset'=>0, 'first_page'=>1] );
        }
        else
        {
            $this->onReload( ['offset'=>0, 'first_page'=>1] );
        }
    }
    
    /**
     * Clears all filters stored in the session and resets the search form.
     */
    public function clearFilters()
    {
        TSession::setValue($this->activeRecord.'_filter_data', null);
        TSession::setValue(get_class($this).'_filter_data', null);
        $this->form->clear();
        
        if ($this->formFilters)
        {
            foreach ($this->formFilters as $filterKey => $formFilter)
            {
                TSession::setValue($this->activeRecord.'_filter', NULL); // BC compatibility
                TSession::setValue($this->activeRecord.'_filter_'.$formFilter, NULL);
                TSession::setValue($this->activeRecord.'_filter_'.$filterKey, NULL);
                TSession::setValue($this->activeRecord.'_'.$formFilter, '');
            }
        }
    }
    
    /**
     * Reloads the datagrid with records from the database.
     *
     * @param array|null $param Optional parameters for reloading data.
     *
     * @return array|null Returns the list of loaded objects or null if an error occurs.
     */
    public function onReload($param = NULL)
    {
        if (!isset($this->datagrid))
        {
            return;
        }
        
        try
        {
            if (empty($this->database))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', AdiantiCoreTranslator::translate('Database'), 'setDatabase()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (empty($this->activeRecord))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', 'Active Record', 'setActiveRecord()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            $param_criteria = $param;
            
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
            

            if (is_array($this->orderCommands) && !empty($param['order']) && !empty($this->orderCommands[$param['order']]))
            {
                $param_criteria['order'] = $this->orderCommands[$param['order']];
            }
            
            $criteria->setProperties($param_criteria); // order, offset
            $criteria->setProperty('limit', $limit);
            
            $subcriteria = new TCriteria;
            if ($this->formFilters)
            {
                foreach ($this->formFilters as $filterKey => $filterField)
                {
                    $logic_operator = isset($this->logic_operators[$filterKey]) ? $this->logic_operators[$filterKey] : TExpression::AND_OPERATOR;
                    
                    if (TSession::getValue($this->activeRecord.'_filter_'.$filterKey))
                    {
                        // add the filter stored in the session to the criteria
                        $subcriteria->add(TSession::getValue($this->activeRecord.'_filter_'.$filterKey), $logic_operator);
                    }
                }
                
                if (!$subcriteria->isEmpty())
                {
                    $criteria->add($subcriteria);
                }
            }
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
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
            $count = $repository->count($criteria);
            
            if (isset($this->pageNavigation))
            {
                $this->pageNavigation->setCount($count); // count of records
                $this->pageNavigation->setProperties($param); // order, page
                $this->pageNavigation->setLimit($limit); // limit
            }
            
            if (is_callable($this->afterLoadCallback))
            {
                $information = ['count' => $count];
                call_user_func($this->afterLoadCallback, $this->datagrid, $information);
            }
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
            
            return $objects;
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
     * Prompts the user with a confirmation dialog before deleting a record.
     *
     * @param array $param Parameters containing the record key.
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
     * @param array $param Parameters containing the record key.
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
            $object = new $class($key, FALSE);
            
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
     * Displays the page, ensuring that the datagrid is loaded.
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }
}
