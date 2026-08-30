<?php
namespace Adianti\Database;

use Adianti\Database\TSqlStatement;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Exception;
use PDO;

/**
 * Provides an Interface to create an INSERT statement
 *
 * Provides an interface to create an INSERT statement.
 * This class allows building and executing SQL INSERT queries dynamically.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSqlInsert extends TSqlStatement
{
    protected $sql;
    private $columnValues;
    private $preparedVars;
    
    /**
     * Initializes the TSqlInsert object.
     * Sets up storage for column values and prepared variables.
     */
    public function __construct()
    {
        $this->columnValues = [];
        $this->preparedVars = [];
    }
    
    /**
     * Assigns values to the specified database column.
     *
     * @param string $column The name of the database column.
     * @param mixed $value The value to be inserted into the column. Only scalar values and null are allowed.
     */
    public function setRowData($column, $value)
    {
        if (is_scalar($value) OR is_null($value))
        {
            $this->columnValues[$column] = $value;
        }
    }
    
    /**
     * Removes a previously assigned value from a specified database column.
     *
     * @param string $column The name of the database column to unset.
     */
    public function unsetRowData($column)
    {
        if (isset($this->columnValues[$column]))
        {
            unset($this->columnValues[$column]);
        }
    }
    
    /**
     * Transforms the given value according to its PHP type before sending it to the database.
     * This method ensures proper quoting and type conversion.
     *
     * @param mixed $value The value to be transformed.
     * @param bool $prepared Whether to use a prepared statement parameter instead of direct value substitution.
     *
     * @return mixed The transformed value or a prepared statement placeholder.
     */
    private function transform($value, $prepared = FALSE)
    {
        // store just scalar values (string, integer, ...)
        if (is_scalar($value))
        {
            // if is a string
            if (is_string($value) and (!empty($value)))
            {
                if ($prepared)
                {
                    $preparedVar = ':par_'.self::getRandomParameter();
                    $this->preparedVars[ $preparedVar ] = $value;
                    $result = $preparedVar;
                }
                else
                {
                    $conn = TTransaction::get();
                    $result = $conn->quote($value);
                }
            }
            else if (is_bool($value)) // if is a boolean
            {
                $info = TTransaction::getDatabaseInfo();
                
                if (in_array($info['type'], ['sqlsrv', 'dblib', 'mssql']))
                {
                    $result = $value ? '1': '0';
                }
                else
                {
                    $result = $value ? 'TRUE': 'FALSE';
                }
            }
            else if ($value !== '') // if its another data type
            {
                if ($prepared)
                {
                    $preparedVar = ':par_'.self::getRandomParameter();
                    $this->preparedVars[ $preparedVar ] = $value;
                    $result = $preparedVar;
                }
                else
                {
                    $result = $value;
                }
            }
            else
            {
                $result = "NULL";
            }
        }
        else if (is_null($value))
        {
            $result = "NULL";
        }
        
        return $result;
    }
    
    /**
     * Throws an exception since criteria are not applicable to INSERT statements.
     *
     * @param TCriteria $criteria A TCriteria object specifying the filters (not used in INSERT statements).
     *
     * @throws Exception Always throws an exception because criteria cannot be applied to an INSERT query.
     */
    public function setCriteria(TCriteria $criteria)
    {
        throw new Exception("Cannot call setCriteria from " . __CLASS__);
    }
    
    /**
     * Retrieves the prepared variables used in the INSERT statement.
     *
     * @return array An associative array where keys are placeholders and values are actual data.
     */
    public function getPreparedVars()
    {
        return $this->preparedVars;
    }
    
    /**
     * Generates and returns the SQL INSERT statement.
     *
     * @param bool $prepared Whether to return a prepared statement with placeholders instead of actual values.
     *
     * @return string The SQL INSERT statement.
     */
    public function getInstruction( $prepared = FALSE )
    {
        $conn = TTransaction::get();
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        $this->preparedVars = array();
        $columnValues = $this->columnValues;
        if ($columnValues)
        {
            foreach ($columnValues as $key => $value)
            {
                $columnValues[$key] = $this->transform($value, $prepared);
            }
        }
        
        $this->sql = "INSERT INTO {$this->entity} (";
        $columns = implode(', ', array_keys($columnValues));   // concatenates the column names
        $values  = implode(', ', array_values($columnValues)); // concatenates the column values
        $this->sql .= $columns . ')';
        $this->sql .= " VALUES ({$values})";
        
        if ($driver == 'firebird')
        {
            $this->sql .= " RETURNING {{primary_key}}";
        }
        
        // returns the string
        return $this->sql;
    }
}
