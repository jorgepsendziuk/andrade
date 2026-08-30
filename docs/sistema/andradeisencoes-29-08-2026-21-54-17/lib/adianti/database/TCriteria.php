<?php
namespace Adianti\Database;

use Adianti\Database\TExpression;

/**
 * Provides an interface for filtering criteria definition
 *
 * This class allows building complex query conditions by adding expressions and logical operators.
 * It also manages properties such as ordering, offset, and grouping.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCriteria extends TExpression
{
    private $expressions;  // store the list of expressions
    private $operators;    // store the list of operators
    private $properties;   // criteria properties
    private $caseInsensitive;

    /**
     * Constructor method.
     *
     * Initializes the internal arrays for expressions and operators, 
     * and sets default values for criteria properties.
     */
    public function __construct()
    {
        $this->expressions = array();
        $this->operators   = array();
        
        $this->properties['order']     = '';
        $this->properties['offset']    = 0;
        $this->properties['direction'] = '';
        $this->properties['group']     = '';

        $this->caseInsensitive = FALSE;
    }

    /**
     * Creates a TCriteria instance from an array of filters.
     *
     * @param array $simple_filters Associative array where keys are field names and values are their respective filter values.
     * @param array|null $properties Optional array of properties to set (e.g., order, offset, direction).
     *
     * @return TCriteria The created criteria instance.
     */
    public static function create($simple_filters, $properties = null)
    {
        $criteria = new TCriteria;
        if ($simple_filters)
        {
            foreach ($simple_filters as $left_operand => $right_operand)
            {
                $criteria->add(new TFilter($left_operand, '=', $right_operand));
            }
        }
        
        if ($properties)
        {
            foreach ($properties as $property => $value)
            {
                if (!empty($value))
                {
                    $criteria->setProperty($property, $value);
                }
            }
        }
        
        return $criteria;
    }
    
    /**
     * Clones the criteria object.
     *
     * Ensures that each expression in the criteria is also cloned to avoid reference issues.
     */
    function __clone()
    {
        $newExpressions = array();
        foreach ($this->expressions as $key => $value)
        {
            $newExpressions[$key] = clone $value;
        }
        $this->expressions = $newExpressions;
    }
    
    /**
     * Adds a new expression to the criteria.
     *
     * @param TExpression $expression The expression to add.
     * @param string|null $operator The logical operator (AND, OR) used to concatenate this expression.
     *                              If it's the first expression, the operator is ignored.
     */
    public function add(TExpression $expression, $operator = self::AND_OPERATOR)
    {
        // the first time, we don't need a logic operator to concatenate
        if (empty($this->expressions))
        {
            $operator = NULL;
        }
        
        // aggregates the expression to the list of expressions
        $this->expressions[] = $expression;
        $this->operators[]   = $operator;
    }
    
    /**
     * Checks if the criteria is empty.
     *
     * @return bool True if no expressions have been added, false otherwise.
     */
    public function isEmpty()
    {
        return count($this->expressions) == 0;
    }
    
    /**
     * Retrieves the prepared variables used in the criteria.
     *
     * This method returns an array of all bound variables used in expressions.
     *
     * @return array|null An associative array of prepared variables, or null if there are no expressions.
     */
    public function getPreparedVars()
    {
        $preparedVars = array();
        if (is_array($this->expressions))
        {
            if (count($this->expressions) > 0)
            {
                foreach ($this->expressions as $expression)
                {
                    $preparedVars = array_merge($preparedVars, (array) $expression->getPreparedVars());
                }
                return $preparedVars;
            }
        }
    }
    
    /**
     * Returns the SQL representation of the criteria.
     *
     * @param bool $prepared Whether to return a prepared statement-compatible expression.
     *
     * @return string|null The SQL string representing the criteria, or null if empty.
     */
    public function dump( $prepared = FALSE)
    {
        // concatenates the list of expressions
        if (is_array($this->expressions))
        {
            if (count($this->expressions) > 0)
            {
                $result = '';
                foreach ($this->expressions as $i=> $expression)
                {
                    $operator = $this->operators[$i];
                    $expression->setCaseInsensitive($this->caseInsensitive);

                    // concatenates the operator with its respective expression
                    $result .=  $operator. $expression->dump( $prepared ) . ' ';
                }
                $result = trim($result);
                if ($result)
                {
                    return "({$result})";
                }
            }
        }
    }
    
    /**
     * Sets a property for the criteria.
     *
     * Properties include limit, offset, order, direction, and group.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set for the property.
     */
    public function setProperty($property, $value)
    {
        if (isset($value))
        {
            $this->properties[$property] = $value;
        }
        else
        {
            $this->properties[$property] = NULL;
        }
        
    }
    
    /**
     * Resets all criteria properties.
     *
     * Clears previously set properties such as limit, offset, order, and group.
     */
    public function resetProperties()
    {
        $this->properties['limit']  = NULL;
        $this->properties['order']  = NULL;
        $this->properties['offset'] = NULL;
        $this->properties['group']  = NULL;
    }
    
    /**
     * Sets multiple properties from an associative array.
     *
     * @param array $properties Associative array containing properties such as 'order', 'offset', and 'direction'.
     */
    public function setProperties($properties)
    {
        if (isset($properties['order']) AND $properties['order'])
        {
            $this->properties['order'] = addslashes($properties['order']);
        }
        
        if (isset($properties['offset']) AND $properties['offset'])
        {
            $this->properties['offset'] = (int) $properties['offset'];
        }
        
        if (isset($properties['direction']) AND $properties['direction'])
        {
            $this->properties['direction'] = $properties['direction'];
        }
    }
    
    /**
     * Retrieves the value of a criteria property.
     *
     * @param string $property The property name (e.g., limit, offset, order).
     *
     * @return mixed|null The property value if set, or null otherwise.
     */
    public function getProperty($property)
    {
        if (isset($this->properties[$property]))
        {
            return $this->properties[$property];
        }
    }

    /**
     * Enables or disables case-insensitive searches in the criteria.
     *
     * @param bool $value True to enable case-insensitive search, false otherwise.
     */
    public function setCaseInsensitive(bool $value) : void
    {
        $this->caseInsensitive = $value;
    }

    /**
     * Checks if case-insensitive search is enabled.
     *
     * @return bool True if case-insensitive search is enabled, false otherwise.
     */
    public function getCaseInsensitive() : bool
    {
        return $this->caseInsensitive;
    }
}
