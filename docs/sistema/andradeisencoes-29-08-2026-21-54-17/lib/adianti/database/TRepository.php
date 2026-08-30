<?php
namespace Adianti\Database;

use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TRecord;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TSqlSelect;
use Adianti\Widget\Util\TExceptionView;
use PDO;
use Exception;
use ReflectionMethod;
use ReflectionClass;

/**
 * Implements the Repository Pattern to deal with collections of Active Records
 *
 * This class provides a fluent interface for building queries using criteria, 
 * filtering, sorting, and grouping data. It also includes methods for retrieving, 
 * updating, and deleting records while supporting soft-deletion.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TRepository
{
    protected $class; // Active Record class to be manipulated
    protected $trashed;
    protected $criteria; // buffered criteria to use with fluent interfaces
    protected $setValues;
    protected $columns;
    protected $aggregates;
    protected $withoutGlobalScopes = [];
    protected $onlyGlobalScopes = [];
    protected $defaultFiltersApplied = [];
    
    /**
     * Class Constructor
     *
     * Initializes a repository for the specified Active Record class.
     *
     * @param string $class The Active Record class name.
     * @param bool $withTrashed Whether to include soft-deleted records.
     *
     * @throws Exception If the class does not exist or is not a subclass of TRecord.
     */
    public function __construct($class, $withTrashed = FALSE)
    {
        if (class_exists($class))
        {
            if (is_subclass_of($class, 'TRecord'))
            {
                $this->class = $class;
                $this->trashed = $withTrashed;
                $this->criteria = new TCriteria;

                $this->applyGlobalScopes($this->criteria);
            }
            else
            {
                throw new Exception(AdiantiCoreTranslator::translate('The class ^1 was not accepted as argument. The class informed as parameter must be subclass of ^2.', $class, 'TRecord'));
            }
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('The class ^1 was not found. Check the class name or the file name. They must match', '"' . $class . '"'));
        }
        
        $this->aggregates = [];
    }

    /**
     * Disables specified global scopes for this repository instance.
     *
     * This method allows you to temporarily disable global scopes that would normally
     * be applied to all queries. If no scopes are specified, all global scopes will be disabled.
     *
     * @param array|string|null $scopes Optional. The names of specific scopes to disable.
     *                                  If null, all global scopes will be disabled.
     *                                  Can be a string for a single scope or an array for multiple scopes.
     *
     * @return $this Returns the repository instance for method chaining.
     */
    public function withoutGlobalScopes($scopes = null)
    {
        // Implementation for disabling global scopes
        if (is_null($scopes)) {
            $this->withoutGlobalScopes = ['*'];
        } else {
            $this->withoutGlobalScopes = is_array($scopes) ? $scopes : [$scopes];
        }
        
        return $this;
    }
    
    /**
     * Applies global scope filters to the given criteria.
     *
     * This method automatically applies all registered global scopes to the criteria,
     * except for those that have been explicitly disabled via withoutGlobalScopes().
     * It ensures that global scopes are applied only once per criteria instance.
     *
     * @param TCriteria $criteria The criteria object to which global scopes will be applied.
     *
     * @return void
     */
    protected function applyGlobalScopes(TCriteria $criteria)
    {
        $class = $this->class ?? static::class;
        $globalScopes = $class::getGlobalScopes();
        
        if (in_array('*', $this->withoutGlobalScopes)) {
             return; // Do not apply any global filters
        }

        $criteriaId = spl_object_id($criteria);
        
        if (!isset($this->defaultFiltersApplied[$criteriaId]))
        {
            $this->defaultFiltersApplied[$criteriaId] = true;
            
            foreach ($globalScopes as $name => $filter) 
            {
                if (!in_array($name, $this->withoutGlobalScopes)) 
                {
                    $criteria->add($filter);
                }
            }
        }
    }
    
    /**
     * Set the filtering criteria for the repository.
     *
     * @param TCriteria $criteria The criteria object to apply to queries.
     *
     * @return void
     */
    public function setCriteria(TCriteria $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Include soft-deleted records in the query.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function withTrashed()
    {
        $this->trashed = true;
        return $this;
    }

    /**
     * Get the database entity name associated with the Active Record.
     *
     * @return string The entity name in the database.
     */
    protected function getEntity()
    {  
        $object = new $this->class();
        
        return $object->getEntity();
    }
    
    /**
     * Retrieve the list of attributes for the entity.
     *
     * @return string A comma-separated list of entity attributes.
     */
    protected function getAttributeList()
    {
        if (!empty($this->columns))
        {
            return implode(', ', $this->columns);
        }
        else
        {
            $object = new $this->class;
            return $object->getAttributeList();
        }
    }
    
    /**
     * Define the columns to be selected in the query.
     *
     * @param array $columns Array of column names to be retrieved.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function select($columns)
    {
        $this->columns = $columns;
        return $this;
    }
    
    /**
     * Add a filtering condition to the query.
     *
     * @param string $variable Database column name.
     * @param string $operator Comparison operator (>, <, =, LIKE, IN, IS, etc.).
     * @param mixed $value Value to be compared.
     * @param string $logicOperator Logical operator (TExpression::AND_OPERATOR, TExpression::OR_OPERATOR).
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function where($variable, $operator, $value, $logicOperator = TExpression::AND_OPERATOR)
    {
        $this->criteria->add(new TFilter($variable, $operator, $value), $logicOperator);
        
        return $this;
    }
    
    /**
     * Assign a value to a database column.
     *
     * @param string $column Database column name.
     * @param mixed $value Value to be assigned to the column.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function set($column, $value)
    {
        if (is_scalar($value) OR is_null($value))
        {
            $this->setValues[$column] = $value;
        }
        
        return $this;
    }
    
    /**
     * Add an OR filtering condition to the query.
     *
     * @param string $variable Database column name.
     * @param string $operator Comparison operator (>, <, =, LIKE, IN, IS, etc.).
     * @param mixed $value Value to be compared.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function orWhere($variable, $operator, $value)
    {
        $this->criteria->add(new TFilter($variable, $operator, $value), TExpression::OR_OPERATOR);
        
        return $this;
    }
    
    /**
     * Define the ordering criteria for the query.
     *
     * @param string $order Column name to order by.
     * @param string $direction Order direction ('asc' or 'desc').
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function orderBy($order, $direction = 'asc')
    {
        $this->criteria->setProperty('order', $order);
        $this->criteria->setProperty('direction', $direction);
        
        return $this;
    }
    
    /**
     * Define the grouping criteria for the query.
     *
     * @param string $group Column name to group by.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function groupBy($group)
    {
        $this->criteria->setProperty('group', $group);
        
        return $this;
    }
    
    /**
     * Limit the number of records returned by the query.
     *
     * @param int $limit Maximum number of records to return.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function take($limit)
    {
        $this->criteria->setProperty('limit', $limit);
        
        return $this;
    }
    
    /**
     * Skip a number of records before returning results.
     *
     * @param int $offset Number of records to skip.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function skip($offset)
    {
        $this->criteria->setProperty('offset', $offset);
        
        return $this;
    }
    
    /**
     * Load a collection of Active Record objects based on criteria.
     *
     * @param TCriteria|null $criteria Optional criteria object specifying filters.
     * @param bool $callObjectLoad Whether to call the load() method on retrieved objects.
     *
     * @return array An array of Active Record objects.
     * @throws Exception If there is no active transaction.
     */
    public function load(?TCriteria $criteria = NULL, $callObjectLoad = TRUE)
    {
        if (!$criteria)
        {
            $criteria = isset($this->criteria) ? $this->criteria : new TCriteria;
        }
        else
        {
            $this->applyGlobalScopes($criteria);
        }
        
        $class = $this->class;
        $deletedat = $class::getDeletedAtColumn();
        
        if (!$this->trashed && $deletedat)
        {
            $criteria->add(new TFilter($deletedat, 'IS', NULL));
        }

        // creates a SELECT statement
        $sql = new TSqlSelect;
        $sql->addColumn($this->getAttributeList());
        $sql->setEntity($this->getEntity());
        // assign the criteria to the SELECT statement
        $sql->setCriteria($criteria);
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            try
            {
                // register the operation in the LOG file
                TTransaction::log($sql->getInstruction());
                $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
                if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
                {
                    $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                    $result-> execute ( $criteria->getPreparedVars() );
                }
                else
                {
                    // execute the query
                    $result= $conn-> query($sql->getInstruction());
                }

                TTransaction::logExecutionTime();
                $results = array();
                
                $class = $this->class;
                $callback = array($class, 'load'); // bypass compiler
                
                // Discover if load() is overloaded
                $rm = new ReflectionMethod($class, $callback[1]);
                
                if ($result)
                {
                    // iterate the results as objects
                    while ($raw = $result-> fetchObject())
                    {
                        $object = new $this->class;
                        if (method_exists($object, 'onAfterLoadCollection'))
                        {
                            $object->onAfterLoadCollection($raw);
                        }
                        $object->fromArray( (array) $raw);
                        
                        if ($callObjectLoad)
                        {
                            // reload the object because its load() method may be overloaded
                            if ($rm->getDeclaringClass()-> getName () !== 'Adianti\Database\TRecord')
                            {
                                $object->reload();
                            }
                        }
                        
                        if ( ($cache = $object->getCacheControl()) && empty($this->columns))
                        {
                            $pk = $object->getPrimaryKey();
                            $record_key = $class . '['. $object->$pk . ']';
                            if ($cache::setValue( $record_key, $object->toArray() ))
                            {
                                TTransaction::log($record_key . ' stored in cache');
                            }
                        }
                        // store the object in the $results array
                        $results[] = $object;
                    }
                }
                return $results;
            }
            catch(Exception $e)
            {
                if(AdiantiCoreApplication::getDebugMode())
                {
                    new TExceptionView($e);
                }
                else
                {
                    throw $e;
                }
            }
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Load a collection of objects without calling their load() method.
     *
     * @return array An array of Active Record objects loaded statically.
     */
    public function loadStatic()
    {
        return $this->load(null, false);
    }
    
    /**
     * Retrieve an associative array using column values as indexes.
     *
     * @param string $indexColumn Column name to use as the key.
     * @param string|null $valueColumn Column name to use as the value (defaults to $indexColumn).
     * @param TCriteria|null $criteria Optional filtering criteria.
     *
     * @return array An associative array where keys are from $indexColumn and values from $valueColumn.
     */
    public function getIndexedArray($indexColumn, $valueColumn = NULL, $criteria = NULL)
    {
        if (is_null($valueColumn))
        {
            $valueColumn = $indexColumn;
        }
        
        $criteria = (empty($criteria)) ? $this->criteria : $criteria;
        $objects = $this->load($criteria, FALSE);
        
        $indexedArray = array();
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $key = (isset($object->$indexColumn)) ? $object->$indexColumn : $object->render($indexColumn);
                $val = (isset($object->$valueColumn)) ? $object->$valueColumn : $object->render($valueColumn);
                
                $indexedArray[ $key ] = $val;
            }
        }
        
        if (empty($criteria) or ( $criteria instanceof TCriteria and empty($criteria->getProperty('order')) ))
        {
            asort($indexedArray);
        }
        return $indexedArray;
    }
    
    /**
     * Update values in the repository based on criteria.
     *
     * @param array|null $setValues Associative array of column-value pairs to update.
     * @param TCriteria|null $criteria Optional criteria to filter which records to update.
     *
     * @return int The number of affected rows.
     * @throws Exception If there is no active transaction.
     */
    public function update($setValues = NULL, ?TCriteria $criteria = NULL)
    {
        if (!$criteria)
        {
            $criteria = isset($this->criteria) ? $this->criteria : new TCriteria;
        }
        else
        {
            $this->applyGlobalScopes($criteria);
        }

        $class = $this->class;
        $deletedat = $class::getDeletedAtColumn();
        
        if (!$this->trashed && $deletedat)
        {
            $criteria->add(new TFilter($deletedat, 'IS', NULL));
        }

        $setValues = isset($setValues) ? $setValues : $this->setValues;
        
        $class = $this->class;
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            
            // creates a UPDATE statement
            $sql = new TSqlUpdate;
            if ($setValues)
            {
                foreach ($setValues as $column => $value)
                {
                    $sql->setRowData($column, $value);
                }
            }
            $sql->setEntity($this->getEntity());
            // assign the criteria to the UPDATE statement
            $sql->setCriteria($criteria);
            
            if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
            {
                $statement = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $result = $statement-> execute ( $sql->getPreparedVars() );
            }
            else
            {
                // execute the UPDATE statement
                $result = $conn->exec($sql->getInstruction());
            }
            
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            
            // update cache
            $record = new $class;
            if ( $cache = $record->getCacheControl() )
            {
                $pk = $record->getPrimaryKey();
                
                // creates a SELECT statement
                $sql = new TSqlSelect;
                $sql->addColumn($this->getAttributeList());
                $sql->setEntity($this->getEntity());
                // assign the criteria to the SELECT statement
                $sql->setCriteria($criteria);
                
                if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
                {
                    $subresult = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                    $subresult-> execute ( $criteria->getPreparedVars() );
                }
                else
                {
                    $subresult = $conn-> query($sql->getInstruction());
                }
                
                if ($subresult)
                {
                    // iterate the results as objects
                    while ($raw = $subresult-> fetchObject())
                    {
                        $object = new $this->class;
                        $object->fromArray( (array) $raw);
                    
                        $record_key = $class . '['. $raw->$pk . ']';
                        if ($cache::setValue( $record_key, $object->toArray() ))
                        {
                            TTransaction::log($record_key . ' stored in cache');
                        }
                    }
                }
            }
            
            TTransaction::logExecutionTime();

            return $result;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Delete records from the repository based on criteria.
     *
     * @param TCriteria|null $criteria Optional filtering criteria.
     * @param bool $callObjectLoad Whether to call the delete() method on each object.
     *
     * @return int The number of affected rows.
     * @throws Exception If there is no active transaction.
     */
    public function delete(?TCriteria $criteria = NULL, $callObjectLoad = FALSE)
    {
        if (!$criteria)
        {
            $criteria = isset($this->criteria) ? $this->criteria : new TCriteria;
        }
        else
        {
            $this->applyGlobalScopes($criteria);
        }

        $class = $this->class;
        $deletedat = $class::getDeletedAtColumn();
        
        if (!$this->trashed && $deletedat)
        {
            $criteria->add(new TFilter($deletedat, 'IS', NULL));
        }
        
        $class = $this->class;
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            
            // first, clear cache
            $record = new $class;
            if ( ($cache = $record->getCacheControl()) OR $callObjectLoad )
            {
                $pk = $record->getPrimaryKey();
                
                // creates a SELECT statement
                $sql = new TSqlSelect;
                $sql->addColumn( $pk );
                $sql->setEntity($this->getEntity());
                // assign the criteria to the SELECT statement
                $sql->setCriteria($criteria);
                
                if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
                {
                    $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                    $result-> execute ( $criteria->getPreparedVars() );
                }
                else
                {
                    $result = $conn-> query($sql->getInstruction());
                }
                
                if ($result)
                {
                    // iterate the results as objects
                    while ($row = $result-> fetchObject())
                    {
                        if ($cache)
                        {
                            $record_key = $class . '['. $row->$pk . ']';
                            if ($cache::delValue( $record_key ))
                            {
                                TTransaction::log($record_key . ' deleted from cache');
                            }
                        }
                        
                        if ($callObjectLoad)
                        {
                            $object = new $this->class;
                            $object->fromArray( (array) $row);
                            $object->delete();
                        }
                    }
                }
            }
            
            if ($deletedat)
            {
                // creates a Update instruction
                $sql = new TSqlUpdate;
                $sql->setEntity($this->getEntity());

                $info = TTransaction::getDatabaseInfo();
                $date_mask = (in_array($info['type'], ['sqlsrv', 'dblib', 'mssql'])) ? 'Ymd H:i:s' : 'Y-m-d H:i:s';
                $sql->setRowData($deletedat, date($date_mask));
            }
            else
            {
                // creates a DELETE statement
                $sql = new TSqlDelete;
                $sql->setEntity($this->getEntity());
            }

            // assign the criteria to the DELETE statement
            $sql->setCriteria($criteria);
            
            if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
            {
                $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                
                if ($sql instanceof TSqlUpdate)
                {
                    $result-> execute ($sql->getPreparedVars());
                }
                else
                {
                    $result-> execute ($criteria->getPreparedVars());
                }
            }
            else
            {
                // execute the DELETE statement
                $result = $conn->exec($sql->getInstruction());
            }
            
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            
            return $result;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Count the number of records that satisfy the given criteria.
     *
     * @param TCriteria|null $criteria Optional filtering criteria.
     *
     * @return int The number of records matching the criteria.
     * @throws Exception If there is no active transaction.
     */
    public function count(?TCriteria $criteria = NULL)
    {
        if (!$criteria)
        {
            $criteria = isset($this->criteria) ? $this->criteria : new TCriteria;
        }
        else
        {
            $this->applyGlobalScopes($criteria);
        }
        
        $class = $this->class;
        $deletedat = $class::getDeletedAtColumn();
        
        if (!$this->trashed && $deletedat)
        {
            $criteria->add(new TFilter($deletedat, 'IS', NULL));
        }

        // creates a SELECT statement
        $sql = new TSqlSelect;
        $sql->addColumn('count(*)');
        $sql->setEntity($this->getEntity());
        // assign the criteria to the SELECT statement
        $sql->setCriteria($criteria);
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
            {
                $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $result-> execute ( $criteria->getPreparedVars() );
            }
            else
            {
                // executes the SELECT statement
                $result= $conn-> query($sql->getInstruction());
            }

            TTransaction::logExecutionTime();
            
            if ($result)
            {
                $row = $result->fetch();
                return $row[0];
            }
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Count the distinct values of a column.
     *
     * @param string $column Column to be counted distinctly.
     * @param string|null $alias Optional alias for the column.
     *
     * @return mixed The total distinct count or an array of results if group by is used.
     */
    public function countDistinctBy($column, $alias = null)
    {
        $alias = is_null($alias) ? $column : $alias;
        return $this->aggregate('count', 'distinct ' . $column, $alias);
    }
    
    /**
     * Count the occurrences of a specific column.
     *
     * @param string $column Column to be counted.
     * @param string|null $alias Optional alias for the column.
     *
     * @return mixed The total count or an array of results if group by is used.
     */
    public function countBy($column, $alias = null)
    {
        return $this->aggregate('count', $column, $alias);
    }
    
    /**
     * Count aggregate and do another aggregate after
     *
     * @param string $column Column to be aggregated
     * @param string $alias Column alias
     *
     * @return TRepository Returns the current object for chain operations
     */
    public function countByAnd($column, $alias = null)
    {
        $this->aggregates[] = "count({$column}) as \"{$alias}\"";
        return $this;
    }
    
    /**
     * Calculate the sum of a column's values.
     *
     * @param string $column Column to be summed.
     * @param string|null $alias Optional alias for the column.
     *
     * @return mixed The sum value or an array of results if group by is used.
     */
    public function sumBy($column, $alias = null)
    {
        return $this->aggregate('sum', $column, $alias);
    }
    
    /**
     * Add a sum aggregate function for a column and allow additional aggregates.
     *
     * @param string $column Column to be summed.
     * @param string|null $alias Optional alias for the column.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function sumByAnd($column, $alias = null)
    {
        $this->aggregates[] = "sum({$column}) as \"{$alias}\"";
        return $this;
    }
    
    /**
     * Calculate the average value of a column.
     *
     * @param string $column Column to be averaged.
     * @param string|null $alias Optional alias for the column.
     *
     * @return mixed The average value or an array of results if group by is used.
     */
    public function avgBy($column, $alias = null)
    {
        return $this->aggregate('avg', $column, $alias);
    }
    
    /**
     * Add an average aggregate function for a column and allow additional aggregates.
     *
     * @param string $column Column to be averaged.
     * @param string|null $alias Optional alias for the column.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function avgByAnd($column, $alias = null)
    {
        $this->aggregates[] = "avg({$column}) as \"{$alias}\"";
        return $this;
    }
    
    /**
     * Retrieve the minimum value of a column.
     *
     * @param string $column Column to find the minimum value from.
     * @param string|null $alias Optional alias for the column.
     *
     * @return mixed The minimum value or an array of results if group by is used.
     */
    public function minBy($column, $alias = null)
    {
        return $this->aggregate('min', $column, $alias);
    }
    
    /**
     * Add a minimum aggregate function for a column and allow additional aggregates.
     *
     * @param string $column Column to find the minimum value from.
     * @param string|null $alias Optional alias for the column.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function minByAnd($column, $alias = null)
    {
        $this->aggregates[] = "min({$column}) as \"{$alias}\"";
        return $this;
    }
    
    /**
     * Retrieve the maximum value of a column.
     *
     * @param string $column Column to find the maximum value from.
     * @param string|null $alias Optional alias for the column.
     *
     * @return mixed The maximum value or an array of results if group by is used.
     */
    public function maxBy($column, $alias = null)
    {
        return $this->aggregate('max', $column, $alias);
    }
    
    /**
     * Add a maximum aggregate function for a column and allow additional aggregates.
     *
     * @param string $column Column to find the maximum value from.
     * @param string|null $alias Optional alias for the column.
     *
     * @return TRepository Returns the current object for method chaining.
     */
    public function maxByAnd($column, $alias = null)
    {
        $this->aggregates[] = "max({$column}) as \"{$alias}\"";
        return $this;
    }
    
    /**
     * Perform an aggregate function (count, sum, min, max, avg) on a column.
     *
     * @param string $function Aggregate function name.
     * @param string $column Column to be aggregated.
     * @param string|null $alias Optional alias for the column.
     *
     * @return mixed The aggregate result or an array of objects if group by is used.
     * @throws Exception If there is no active transaction.
     */
    protected function aggregate($function, $column, $alias = null)
    {
        $criteria = isset($this->criteria) ? $this->criteria : new TCriteria;
        $alias = $alias ? $alias : $column;

        $class = $this->class;
        $deletedat = $class::getDeletedAtColumn();
        
        if (!$this->trashed && $deletedat)
        {
            $criteria->add(new TFilter($deletedat, 'IS', NULL));
        }
        
        // creates a SELECT statement
        $sql = new TSqlSelect;
        if (!empty( $this->criteria->getProperty('group') ))
        {
            $sql->addColumn( $this->criteria->getProperty('group') );
        }
        
        if ($this->aggregates)
        {
            foreach ($this->aggregates as $aggregate)
            {
                $sql->addColumn($aggregate);
            }
        }
        
        $sql->addColumn("$function({$column}) as \"{$alias}\"");
        
        $sql->setEntity($this->getEntity());
        
        // assign the criteria to the SELECT statement
        $sql->setCriteria($criteria);
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
            {
                $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $result-> execute ( $criteria->getPreparedVars() );
            }
            else
            {
                // executes the SELECT statement
                $result= $conn-> query($sql->getInstruction());
            }
            
            TTransaction::logExecutionTime();
            
            $results = [];
            
            if ($result)
            {
                // iterate the results as objects
                while ($raw = $result-> fetchObject())
                {
                    $results[] = $raw;
                }
            }
            
            if ($results)
            {
                if ( (count($results) > 1) || !empty($this->criteria->getProperty('group')))
                {
                    return $results;
                }
                else
                {
                    return $results[0]->$alias;
                }
            }
            
            return 0;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Alias for load(), retrieves a collection of Active Record objects.
     *
     * @param TCriteria|null $criteria Optional criteria object.
     * @param bool $callObjectLoad Whether to call the load() method on retrieved objects.
     *
     * @return array An array of Active Record objects.
     */
    public function get(?TCriteria $criteria = NULL, $callObjectLoad = TRUE)
    {
        return $this->load($criteria, $callObjectLoad);
    }
    
    /**
     * Retrieve the first record from the collection.
     *
     * @param bool $callObjectLoad Whether to call the load() method on retrieved objects.
     *
     * @return object|null The first object in the collection or NULL if empty.
     */
    public function first($callObjectLoad = TRUE)
    {
        $collection = $this->take(1)->load(null, $callObjectLoad);
        if (isset($collection[0]))
        {
            return $collection[0];
        }
    }
    
    /**
     * Retrieve the last record from the collection.
     *
     * @param bool $callObjectLoad Whether to call the load() method on retrieved objects.
     *
     * @return object|null The last object in the collection or NULL if empty.
     */
    public function last($callObjectLoad = TRUE)
    {
        $class = $this->class;
        $pk = (new $class)->getPrimaryKey();
        
        $collection = $this->orderBy($pk,'desc')->take(1)->load(null, $callObjectLoad);
        if (isset($collection[0]))
        {
            return $collection[0];
        }
    }
    
    /**
     * Apply a transformation to each record in the collection.
     *
     * @param callable $callback Callback function to transform each object.
     * @param bool $callObjectLoad Whether to call the load() method on retrieved objects.
     *
     * @return array The transformed collection of Active Record objects.
     */
    public function transform( Callable $callback, $callObjectLoad = TRUE)
    {
        $collection = $this->load(null, $callObjectLoad);
        
        if ($collection)
        {
            foreach ($collection as $object)
            {
                call_user_func($callback, $object);
            }
        }
        
        return $collection;
    }
    
    /**
     * Filter the collection based on a callback function.
     *
     * @param callable $callback Callback function to test each object.
     * @param bool $callObjectLoad Whether to call the load() method on retrieved objects.
     *
     * @return array The filtered collection of Active Record objects.
     */
    public function filter( Callable $callback, $callObjectLoad = TRUE)
    {
        $collection = $this->load(null, $callObjectLoad);
        $newcollection = [];
        
        if ($collection)
        {
            foreach ($collection as $object)
            {
                if (call_user_func($callback, $object))
                {
                    $newcollection[] = $object;
                }
            }
        }
        
        return $newcollection;
    }
    
    /**
     * Output the SQL criteria for debugging purposes.
     *
     * @param bool $prepared Whether to return criteria with prepared variables.
     *
     * @return string|null The SQL criteria as a string or NULL if no criteria is set.
     */
    public function dump($prepared = FALSE)
    {
        if (isset($this->criteria) AND $this->criteria)
        {
            $criteria = clone $this->criteria;
            
            $class = $this->class;
            $deletedat = $class::getDeletedAtColumn();
            
            if (!$this->trashed && $deletedat)
            {
                $criteria->add(new TFilter($deletedat, 'IS', NULL));
            }

            return $criteria->dump($prepared);
        }

        return NULL;
    }
}
