<?php
namespace Adianti\Database;

use Adianti\Database\TSqlStatement;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Exception;

/**
 * Provides an Interface to create an MULTI INSERT statement
 *
 * Provides an interface to create a MULTI INSERT SQL statement.
 * This class extends TSqlStatement and allows batch insert operations.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSqlMultiInsert extends TSqlStatement
{
    protected $sql;
    private $rows;
    
    /**
     * Initializes the TSqlMultiInsert object.
     * This constructor initializes an empty array to store multiple rows for insertion.
     */
    public function __construct()
    {
        $this->rows = [];
    }
    
    /**
     * Adds a row of data to the insert statement.
     *
     * @param array $row An associative array containing the column names as keys and their respective values.
     */
    public function addRowValues($row)
    {
        $this->rows[] = $row;
    }
    
    /**
     * Transforms a value according to its PHP type before sending it to the database.
     * This method ensures proper formatting of scalar values such as strings, booleans, and nulls.
     *
     * @param mixed $value The value to be transformed.
     *
     * @return mixed The transformed value, properly formatted for SQL insertion.
     */
    private function transform($value)
    {
        // store just scalar values (string, integer, ...)
        if (is_scalar($value))
        {
            // if is a string
            if (is_string($value) and (!empty($value)))
            {
                $conn = TTransaction::get();
                $result = $conn->quote($value);
            }
            else if (is_bool($value)) // if is a boolean
            {
                $result = $value ? 'TRUE': 'FALSE';
            }
            else if ($value !== '') // if its another data type
            {
                $result = $value;
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
     * Throws an exception, as criteria are not applicable for multi-insert statements.
     *
     * @param TCriteria $criteria A TCriteria object, specifying filters (not applicable in this context).
     *
     * @throws Exception Always throws an exception since criteria cannot be set in a multi-insert statement.
     */
    public function setCriteria(TCriteria $criteria)
    {
        throw new Exception("Cannot call setCriteria from " . __CLASS__);
    }
    
    /**
     * Builds and returns the SQL INSERT statement.
     * If multiple rows are provided, they are formatted into a batch insert statement.
     *
     * @param bool $prepared Whether to return a prepared statement (not currently implemented).
     *
     * @return string|null The generated SQL INSERT statement, or null if no rows are provided.
     */
    public function getInstruction( $prepared = FALSE )
    {
        if ($this->rows)
        {
            $buffer = [];
            $target_columns = implode(',', array_keys($this->rows[0]));
            
            foreach ($this->rows as $row)
            {
                foreach ($row as $key => $value)
                {
                    $row[$key] = $this->transform($value);
                }
                
                $values_list = implode(',', $row);
                $buffer[] = "($values_list)";
            }
            
            $this->sql = "INSERT INTO {$this->entity} ($target_columns) VALUES " . implode(',', $buffer);
            return $this->sql;
        }
    }
}
