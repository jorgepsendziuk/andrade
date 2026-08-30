<?php
namespace Adianti\Database;

use Adianti\Database\TSqlStatement;
use Adianti\Database\TTransaction;

/**
 * Provides an Interface to create UPDATE statements
 *
 * This class extends TSqlStatement and allows setting column values, managing prepared variables,
 * transforming values for database compatibility, and constructing the final SQL update statement.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSqlUpdate extends TSqlStatement
{
    protected $sql;         // stores the SQL statement
    private $columnValues;
    private $preparedVars;
    
    /**
     * Assigns a value to a specified database column.
     *
     * This method stores scalar or null values in an internal column value array
     * to be used in an UPDATE SQL statement.
     *
     * @param string $column The name of the database column.
     * @param mixed $value The value to be assigned to the column. Only scalar or null values are allowed.
     *
     * @return void
     */
    public function setRowData($column, $value)
    {
        if (is_scalar($value) OR is_null($value))
        {
            $this->columnValues[$column] = $value;
        }
    }
    
    /**
     * Removes a column from the update statement.
     *
     * If the specified column exists in the columnValues array, it is removed.
     *
     * @param string $column The name of the database column to remove.
     *
     * @return void
     */
    public function unsetRowData($column)
    {
        if (isset($this->columnValues[$column]))
        {
            unset($this->columnValues[$column]);
        }
    }
    
    /**
     * Transforms a value according to its PHP type before being used in a SQL statement.
     *
     * This method ensures proper formatting and escaping of values depending on their type.
     * It handles booleans, nulls, strings, and numeric values, and supports prepared statements.
     *
     * @param mixed $value The value to be transformed.
     * @param bool $prepared Indicates whether to use a prepared statement (default: FALSE).
     *
     * @return mixed The transformed value, ready for use in a SQL statement.
     */
    private function transform($value, $prepared = FALSE)
    {
        // store just scalar values (string, integer, ...)
        if (is_scalar($value))
        {
            if (substr(strtoupper($value),0,7) == '(SELECT')
            {
                $value  = str_replace(['#', '--', '/*'], ['', '', ''], $value);
                $result = $value;
            }
            // if the value must not be escaped (NOESC in front)
            else if (substr($value,0,6) == 'NOESC:')
            {
                $value  = str_replace(['#', '--', '/*'], ['', '', ''], $value);
                $result = substr($value,6);
            }
            // if is a string
            else if (is_string($value) and (!empty($value)))
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
     * Retrieves an array of prepared variables used in the SQL statement.
     *
     * If a criteria object is set, this method merges column value prepared variables
     * with the WHERE clause prepared variables.
     *
     * @return array An associative array of prepared variables.
     */
    public function getPreparedVars()
    {
        if ($this->criteria)
        {
            // "column values" prepared vars + "where" prepared vars
            return array_merge($this->preparedVars, $this->criteria->getPreparedVars());
        }
        else
        {
            return $this->preparedVars;
        }
    }
    
    /**
     * Generates and returns the SQL UPDATE statement.
     *
     * This method constructs an SQL UPDATE statement with column assignments and an optional WHERE clause.
     * It supports both plain and prepared statement formats.
     *
     * @param bool $prepared Whether to return a prepared statement (default: FALSE).
     *
     * @return string The SQL UPDATE statement.
     */
    public function getInstruction( $prepared = FALSE)
    {
        $this->preparedVars = array();
        // creates the UPDATE statement
        $this->sql = "UPDATE {$this->entity}";
        
        // concatenate the column pairs COLUMN=VALUE
        if ($this->columnValues)
        {
            foreach ($this->columnValues as $column => $value)
            {
                $value = $this->transform($value, $prepared);
                $set[] = "{$column} = {$value}";
            }
        }
        $this->sql .= ' SET ' . implode(', ', $set);
        
        // concatenates the criteria (WHERE)
        if ($this->criteria)
        {
            $dbInfo = TTransaction::getDatabaseInfo();
            if (isset($dbInfo['case']) AND $dbInfo['case'] == 'insensitive')
            {
                $this->criteria->setCaseInsensitive(TRUE);
            }

            $this->sql .= ' WHERE ' . $this->criteria->dump( $prepared );
        }
        
        // returns the SQL statement
        return $this->sql;
    }
}
