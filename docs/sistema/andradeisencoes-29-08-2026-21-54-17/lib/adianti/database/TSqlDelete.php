<?php
namespace Adianti\Database;

use Adianti\Database\TSqlStatement;

/**
 * Provides an Interface to create DELETE statements
 *
 * This class provides an interface to construct and retrieve DELETE SQL statements
 * with optional filtering criteria.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSqlDelete extends TSqlStatement
{
    protected $sql;
    protected $criteria;    // stores the select criteria
    
    /**
     * Returns the DELETE SQL statement as a string.
     *
     * This method generates and returns a DELETE SQL statement. If a criteria is set,
     * it appends a WHERE clause to filter the deletion. Additionally, it checks the 
     * database configuration for case insensitivity and adjusts the criteria accordingly.
     *
     * @param bool $prepared Whether to return a prepared statement format.
     *
     * @return string The DELETE SQL statement.
     */
    public function getInstruction( $prepared = FALSE )
    {
        // creates the DELETE instruction
        $this->sql  = "DELETE FROM {$this->entity}";
        
        // concatenates with the criteria (WHERE)
        if ($this->criteria)
        {
            $dbInfo = TTransaction::getDatabaseInfo();
            if (isset($dbInfo['case']) && $dbInfo['case'] == 'insensitive')
            {
                $this->criteria->setCaseInsensitive(TRUE);
            }

            $expression = $this->criteria->dump( $prepared );
            if ($expression)
            {
                $this->sql .= ' WHERE ' . $expression;
            }
        }
        return $this->sql;
    }
}
