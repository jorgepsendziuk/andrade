<?php
namespace Adianti\Database;

/**
 * Base class for TCriteria and TFilter (composite pattern implementation)
 *
 * This class defines the logical operators and enforces the implementation of the `dump` method
 * in child classes. It serves as a base for expressions used in database queries.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
abstract class TExpression
{
    // logic operators
    const AND_OPERATOR = 'AND ';
    const OR_OPERATOR  = 'OR ';
    
    /**
     * This method must be implemented by all subclasses to return a valid
     * string representation of the expression to be used in database queries.
     */
    abstract public function dump();
}
