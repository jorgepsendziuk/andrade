<?php
namespace Adianti\Database;

use Adianti\Database\TExpression;
use Adianti\Database\TSqlStatement;
use Adianti\Registry\TSession;
use Adianti\Util\AdiantiStringConversion;

/**
 * Provides an interface to define filters to be used inside a criteria
 *
 * Represents a database filter used in SQL queries.
 * This class provides an interface to define filters that can be used within a criteria.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TFilter extends TExpression
{
    private $variable;
    private $operator;
    private $value;
    private $value2;
    private $preparedVars;
    private $caseInsensitive;
    private $bindParams;
    private static $paramCounter;
    
    /**
     * Initializes a new instance of the TFilter class.
     *
     * @param string $variable The column or field name to be filtered.
     * @param string $operator The comparison operator (e.g., >, <, =, BETWEEN).
     * @param mixed $value The value to compare against.
     * @param mixed|null $value2 The second value to compare (used in BETWEEN operations).
     * @param array|null $bindParams Parameters to bind in subqueries that contain ? placeholders.
     */
    public function __construct($variable, $operator, $value, $value2 = NULL, $bindParams = NULL)
    {
        // store the properties
        $this->variable = $variable;
        $this->operator = $operator;
        $this->preparedVars = array();
        $this->bindParams = $bindParams;
        
        // transform the value according to its type
        $this->value = $value;
        
        if ($value2)
        {
            $this->value2 = $value2;
        }
        $this->caseInsensitive = FALSE;
    }
    
    /**
     * Transforms a value based on its PHP type before sending it to the database.
     *
     * @param mixed $value The value to be transformed.
     * @param bool $prepared Whether the value should be prepared as a parameterized query.
     *
     * @return string The transformed value, ready for inclusion in an SQL query.
     */
    private function transform($value, $prepared = FALSE)
    {
        // if the value is an array
        if (is_array($value))
        {
            $foo = array();
            // iterate the array
            foreach ($value as $x)
            {
                // if the value is an integer
                if (is_numeric($x))
                {
                    if ($prepared)
                    {
                        $preparedVar = ':par_'.$this->getRandomParameter();
                        $this->preparedVars[ $preparedVar ] = $x;
                        $foo[] = $preparedVar;
                    }
                    else
                    {
                        $foo[] = $x;
                    }
                }
                else if (is_string($x))
                {
                    // if the value is an string, add quotes
                    if ($prepared)
                    {
                        $preparedVar = ':par_'.$this->getRandomParameter();
                        $this->preparedVars[ $preparedVar ] = $x;
                        $foo[] = $preparedVar;
                    }
                    else
                    {
                        $foo[] = "'$x'";
                    }
                }
                else if (is_bool($x))
                {
                    $foo[] = ($x) ? 'TRUE' : 'FALSE';
                }
            }
            // convert the array into a string, splitted by ","
            $result = '(' . implode(',', $foo) . ')';
        }
        // if the value is a subselect and we have bind parameters
        else if (substr(strtoupper( (string) $value),0,7) == '(SELECT' && $this->bindParams)
        {
            // Clean the query from potentially harmful SQL
            $value = str_replace(['#', '--', '/*'], ['', '', ''], $value);
            
            if ($prepared)
            {
                // Replace ? placeholders with named parameters
                $result = $this->prepareSubqueryWithBindParams($value, $this->bindParams);
            }
            else
            {
                // For non-prepared, manually interpolate the values
                $result = $this->interpolateSubqueryParams($value, $this->bindParams);
            }

            // Handle session variables if present
            if (strpos((string) $result, '{session.') !== false)
            {
                $session_var = AdiantiStringConversion::getBetween($result, '{session.', '}');
                $result = str_replace("{session.{$session_var}}", TSession::getValue($session_var), $result);
            }
        }
        // if the value is a subselect (must not be escaped as string)
        else if (substr(strtoupper( (string) $value),0,7) == '(SELECT')
        {
            $value  = str_replace(['#', '--', '/*'], ['', '', ''], $value);
            $result = $value;

            if (strpos((string) $result, '{session.') !== false)
            {
                $session_var = AdiantiStringConversion::getBetween($result, '{session.', '}');
                $result = str_replace("{session.{$session_var}}", TSession::getValue($session_var), $result);
            }
        }
        // if the value is a session variable
        else if (strpos((string) $value, '{session.') !== false)
        {
            $session_var = AdiantiStringConversion::getBetween($value, '{session.', '}');
            $result = str_replace("{session.{$session_var}}", TSession::getValue($session_var), $value);
        }
        // if the value must not be escaped (NOESC in front)
        else if (substr( (string) $value,0,6) == 'NOESC:')
        {
            $value  = str_replace(['#', '--', '/*'], ['', '', ''], $value);
            $result = substr($value,6);
        }
        // if the value is a string
        else if (is_string($value))
        {
            if ($prepared)
            {
                $preparedVar = ':par_'.$this->getRandomParameter();
                $this->preparedVars[ $preparedVar ] = $value;
                $result = $preparedVar;
            }
            else
            {
                // add quotes
                $result = "'$value'";
            }
        }
        // if the value is NULL
        else if (is_null($value))
        {
            // the result is 'NULL'
            $result = 'NULL';
        }
        // if the value is a boolean
        else if (is_bool($value))
        {
            // the result is 'TRUE' of 'FALSE'
            $result = $value ? 'TRUE' : 'FALSE';
        }
        // if the value is a TSqlStatement object
        else if ($value instanceof TSqlStatement)
        {
            // the result is the return of the getInstruction()
            $result = '(' . $value->getInstruction() . ')';
        }
        else
        {
            if ($prepared)
            {
                $preparedVar = ':par_'.$this->getRandomParameter();
                $this->preparedVars[ $preparedVar ] = $value;
                $result = $preparedVar;
            }
            else
            {
                $result = $value;
            }
        }
        
        // returns the result
        return $result;
    }
    
    /**
     * Prepares a subquery with bind parameters
     * 
     * @param string $subquery The subquery with ? placeholders
     * @param array $params The parameters to bind
     * @return string The subquery with prepared parameters
     */
    private function prepareSubqueryWithBindParams($subquery, $params)
    {
        $paramIndex = 0;
        
        // Replace each ? with a named parameter
        $preparedQuery = preg_replace_callback('/\?/', function($matches) use (&$paramIndex, $params) {
            if (isset($params[$paramIndex])) {
                $preparedVar = ':par_'.$this->getRandomParameter();
                $this->preparedVars[$preparedVar] = $params[$paramIndex];
                $paramIndex++;
                return $preparedVar;
            }
            return '?'; // If no parameter is available, leave as is
        }, $subquery);
        
        return $preparedQuery;
    }
    
    /**
     * Interpolates parameters into a subquery for non-prepared statement use
     * 
     * @param string $subquery The subquery with ? placeholders
     * @param array $params The parameters to interpolate
     * @return string The interpolated query
     */
    private function interpolateSubqueryParams($subquery, $params)
    {
        $paramIndex = 0;
        
        // Replace each ? with the actual value
        $interpolatedQuery = preg_replace_callback('/\?/', function($matches) use (&$paramIndex, $params) {
            if (isset($params[$paramIndex])) {
                $value = $params[$paramIndex];
                $paramIndex++;
                
                // Format the value based on its type
                if (is_null($value)) {
                    return 'NULL';
                } elseif (is_bool($value)) {
                    return $value ? 'TRUE' : 'FALSE';
                } elseif (is_numeric($value)) {
                    return $value;
                } elseif (is_string($value)) {
                    return "'" . addslashes($value) . "'";
                } elseif (is_array($value)) {
                    $items = [];
                    foreach ($value as $item) {
                        if (is_numeric($item)) {
                            $items[] = $item;
                        } else {
                            $items[] = "'" . addslashes($item) . "'";
                        }
                    }
                    return '(' . implode(',', $items) . ')';
                }
                
                // For other types, convert to string
                return "'" . addslashes((string)$value) . "'";
            }
            return '?'; // If no parameter is available, leave as is
        }, $subquery);
        
        return $interpolatedQuery;
    }
    
    /**
     * Retrieves the prepared variables used in parameterized queries.
     *
     * @return array An associative array of prepared variables.
     */
    public function getPreparedVars()
    {
        return $this->preparedVars;
    }
    
    /**
     * Converts the filter into a SQL string expression.
     *
     * @param bool $prepared Whether the query should use prepared statements.
     *
     * @return string The SQL expression representing the filter.
     */
    public function dump( $prepared = FALSE )
    {
        $this->preparedVars = array();
        $value = $this->transform($this->value, $prepared);
        if ($this->value2)
        {
            $value2 = $this->transform($this->value2, $prepared);
            // concatenated the expression
            return "{$this->variable} {$this->operator} {$value} AND {$value2}";
        }
        else
        {
            $variable = $this->variable;
            $operator = $this->operator;

            if ($this->caseInsensitive && stristr(strtolower($operator),'like') !== FALSE)
            {
                $variable = "UPPER({$variable})";
                $value = "UPPER({$value})";
                $operator = str_ireplace('ilike', 'LIKE', $operator);
            }

            // concatenated the expression
            return "{$variable} {$operator} {$value}";
        }
    }
    
    /**
     * Generates a unique parameter identifier for use in prepared statements.
     *
     * @return int A unique parameter number.
     */
    private function getRandomParameter()
    {
        if (!isset(self::$paramCounter)) {
            self::$paramCounter = 0;
        }
        self::$paramCounter++;
        return self::$paramCounter;
    }

    /**
     * Enables or disables case-insensitive searches.
     *
     * @param bool $value If true, enables case-insensitive searches.
     */
    public function setCaseInsensitive(bool $value) : void
    {
        $this->caseInsensitive = $value;
    }

    /**
     * Checks whether case-insensitive searches are enabled.
     *
     * @return bool True if case-insensitive search is enabled, false otherwise.
     */
    public function getCaseInsensitive() : bool
    {
        return $this->caseInsensitive;
    }
}
