<?php
namespace Adianti\Database;

use Adianti\Database\TCriteria;

/**
 * Provides an abstract Interface to create a SQL statement
 *
 * This class defines methods to set and retrieve the database entity, apply selection criteria,
 * and enforce implementation of the SQL instruction generation in child classes.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
abstract class TSqlStatement
{
    protected $sql;         // stores the SQL instruction
    protected $criteria;    // stores the select criteria
    protected $entity;
    
    /**
     * Defines the database entity name.
     *
     * @param string $entity The name of the database entity (table or view).
     *
     * @return void
     */
    final public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    
    /**
     * Retrieves the name of the database entity.
     *
     * @return string|null Returns the entity name if set, otherwise null.
     */
    final public function getEntity()
    {
        return $this->entity;
    }
    
    /**
     * Sets a selection criteria for the SQL statement.
     *
     * @param TCriteria $criteria An instance of TCriteria specifying filtering conditions.
     *
     * @return void
     */
    public function setCriteria(TCriteria $criteria)
    {
        $this->criteria = $criteria;
    }
    
    /**
     * Generates and returns a random numeric parameter.
     *
     * This method provides a random integer within a predefined range to be used as a parameter identifier.
     *
     * @return int A random integer between 1,000,000,000 and 1,999,999,999.
     */
    protected function getRandomParameter()
    {
        return mt_rand(1000000000, 1999999999);
    }
    
    /**
     * Must be implemented by subclasses to return the SQL instruction.
     */
    abstract function getInstruction();
}
