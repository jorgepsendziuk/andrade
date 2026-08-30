<?php
use Adianti\Database\TSqlStatement;
use Adianti\Database\TTransaction;

/**
 * Class BChartSqlSelect
 *
 * This class extends TSqlStatement to construct and generate SELECT SQL statements
 * compatible with multiple database drivers, including standard open-source databases,
 * SQL Server, Interbase, and Oracle.
 *
 * @version    7.3
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class BChartSqlSelect extends TSqlStatement
{
    private $columns;   // array with the column names to be returned
    
    /**
     * Adds a column name to be returned in the SELECT statement.
     *
     * @param string $column The name of the column to be included in the query.
     */
    public function addColumn($column)
    {
        // add the column name to the array
        $this->columns[] = $column;
    }
    
    /**
     * Generates the SELECT statement according to the database driver in use.
     *
     * @param bool $prepared Whether to return a prepared statement.
     *
     * @return string The generated SQL SELECT statement.
     * @throws Exception If the database connection is not available.
     */
    public function getInstruction( $prepared = FALSE)
    {
        $conn = TTransaction::get();
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if (in_array($driver, array('mssql', 'dblib', 'sqlsrv')))
        {
            return $this->getSqlServerInstruction( $prepared );
        }
        else if (in_array($driver, array('oci', 'oci8')))
        {
            return $this->getOracleInstruction( $prepared );
        }
        else if (in_array($driver, array('firebird')))
        {
            return $this->getInterbaseInstruction( $prepared );
        }
        else
        {
            return $this->getStandardInstruction( $prepared );
        }
    }
    
    /**
     * Generates the standard SQL SELECT statement for open-source database drivers.
     *
     * @param bool $prepared Whether to return a prepared statement.
     *
     * @return string The generated SQL SELECT statement.
     */
    public function getStandardInstruction( $prepared )
    {
        // creates the SELECT instruction
        $this->sql  = 'SELECT ';
        // concatenate the column names
        $this->sql .= implode(',', $this->columns);
        // concatenate the entity name
        $this->sql .= ' FROM ' . $this->entity;
        
        // concatenate the criteria (WHERE)
        if ($this->criteria)
        {
            $expression = $this->criteria->dump( $prepared );
            if ($expression)
            {
                $this->sql .= ' WHERE ' . $expression;
            }
            
            // get the criteria properties
            $order     = $this->criteria->getProperty('order');
            $group     = $this->criteria->getProperty('group');
            $limit     = (int) $this->criteria->getProperty('limit');
            $offset    = (int) $this->criteria->getProperty('offset');
            $direction = in_array($this->criteria->getProperty('direction'), array('asc', 'desc')) ? $this->criteria->getProperty('direction') : '';
            
            if ($group)
            {
                $this->sql .= ' GROUP BY ' . $group;
            }
            if ($order)
            {
                $this->sql .= ' ORDER BY ' . $order . ' ' . $direction;
            }
            if ($limit)
            {
                $this->sql .= ' LIMIT ' . $limit;
            }
            if ($offset)
            {
                $this->sql .= ' OFFSET ' . $offset;
            }
        }
        // return the SQL statement
        return $this->sql;
    }
    
    /**
     * Generates the SQL SELECT statement for Firebird/Interbase databases.
     *
     * @param bool $prepared Whether to return a prepared statement.
     *
     * @return string The generated SQL SELECT statement.
     */
    public function getInterbaseInstruction( $prepared )
    {
        // creates the SELECT instruction
        $this->sql  = 'SELECT ';
        
        if ($this->criteria)
        {
            $limit     = (int) $this->criteria->getProperty('limit');
            $offset    = (int) $this->criteria->getProperty('offset');
            
            if ($limit)
            {
                $this->sql .= ' FIRST ' . $limit;
            }
            if ($offset)
            {
                $this->sql .= ' SKIP ' . $offset;
            }
        }
        
        // concatenate the column names
        $this->sql .= implode(',', $this->columns);
        
        // concatenate the entity name
        $this->sql .= ' FROM ' . $this->entity;
        
        // concatenate the criteria (WHERE)
        if ($this->criteria)
        {
            $expression = $this->criteria->dump( $prepared );
            if ($expression)
            {
                $this->sql .= ' WHERE ' . $expression;
            }
            
            // get the criteria properties
            $group     = $this->criteria->getProperty('group');
            $order     = $this->criteria->getProperty('order');
            $direction = in_array($this->criteria->getProperty('direction'), array('asc', 'desc')) ? $this->criteria->getProperty('direction') : '';
            
            if ($group)
            {
                $this->sql .= ' GROUP BY ' . $group;
            }
            
            if ($order)
            {
                $this->sql .= ' ORDER BY ' . $order . ' ' . $direction;
            }
        }
        
        // return the SQL statement
        return $this->sql;
    }
    
    /**
     * Generates the SQL SELECT statement for SQL Server (mssql/dblib/sqlsrv).
     *
     * @param bool $prepared Whether to return a prepared statement.
     *
     * @return string The generated SQL SELECT statement.
     */
    public function getSqlServerInstruction( $prepared )
    {
        // obtém a cláusula WHERE do objeto criteria.
        if ($this->criteria)
        {
            $expression = $this->criteria->dump( $prepared );
            
            // obtém as propriedades do critério
            $group    = $this->criteria->getProperty('group');
            $order    = $this->criteria->getProperty('order');
            $limit    = (int) $this->criteria->getProperty('limit');
            $offset   = (int) $this->criteria->getProperty('offset');
            $direction= in_array($this->criteria->getProperty('direction'), array('asc', 'desc')) ? $this->criteria->getProperty('direction') : '';
        }
        $columns = implode(',', $this->columns);
        
        if ((isset($limit) OR isset($offset)) AND ($limit>0 OR $offset>0))
        {
            if (empty($order))
            {
                $order = '(SELECT NULL)';
            }

            $columnsPrimary = array_map(function($column){ $columnParts = explode(' as ', $column); return $columnParts[count($columnParts) - 1]; }, $this->columns);
            $columnsPrimary = implode(',', $columnsPrimary);

            $this->sql = "SELECT {$columnsPrimary}
                      FROM
                      (
                             SELECT ROW_NUMBER() OVER (order by {$order} {$direction}) AS __ROWNUMBER__,
                             {$columns}
                             FROM {$this->entity}";
            if (!empty($expression))
            {
                $this->sql.= "    WHERE {$expression} ";
            }

            if (isset($group) AND !empty($group))
            {
                $this->sql .= ' GROUP BY ' . $group;
            }

            $this->sql .= " ) AS TAB2";
            if ((isset($limit) OR isset($offset)) AND ($limit>0 OR $offset>0))
            {
                $this->sql .= " WHERE";
            }
            
            if ($limit >0 )
            {
                $total = $offset + $limit;
                $this->sql .= " __ROWNUMBER__ <= {$total} ";
                
                if ($offset)
                {
                    $this->sql .= " AND ";
                }
            }
            if ($offset > 0)
            {
                $this->sql .= " __ROWNUMBER__ > {$offset} ";
            }
        }
        else
        {
            $this->sql  = 'SELECT ';
            $this->sql .= $columns;
            $this->sql .= ' FROM ' . $this->entity;
            if (!empty($expression))
            {
                $this->sql .= ' WHERE ' . $expression;
            }
            
            if (isset($group) AND !empty($group))
            {
                $this->sql .= ' GROUP BY ' . $group;
            }
            if (isset($order) AND !empty($order))
            {
                $this->sql .= ' ORDER BY ' . $order . ' ' . $direction;
            }
        }
        return $this->sql;
    }
    
    /**
     * Generates the SQL SELECT statement for Oracle (oci/oci8).
     *
     * @param bool $prepared Whether to return a prepared statement.
     *
     * @return string The generated SQL SELECT statement.
     */
    public function getOracleInstruction( $prepared )
    {
        // obtém a cláusula WHERE do objeto criteria.
        if ($this->criteria)
        {
            $expression = $this->criteria->dump( $prepared );
            
            // obtém as propriedades do critério
            $group    = $this->criteria->getProperty('group');
            $order    = $this->criteria->getProperty('order');
            $limit    = (int) $this->criteria->getProperty('limit');
            $offset   = (int) $this->criteria->getProperty('offset');
            $direction= in_array($this->criteria->getProperty('direction'), array('asc', 'desc')) ? $this->criteria->getProperty('direction') : '';
        }
        $columns = implode(',', $this->columns);
        
        $basicsql  = 'SELECT ';
        $basicsql .= $columns;
        $basicsql .= ' FROM ' . $this->entity;
        
        if (!empty($expression))
        {
            $basicsql .= ' WHERE ' . $expression;
        }
        
        if (isset($group) AND !empty($group))
        {
            $basicsql .= ' GROUP BY ' . $group;
        }
        if (isset($order) AND !empty($order))
        {
            $basicsql .= ' ORDER BY ' . $order . ' ' . $direction;
        }
        
        if ((isset($limit) OR isset($offset)) AND ($limit>0 OR $offset>0))
        {
            $this->sql = "SELECT {$columns} ";
            $this->sql.= "  FROM (";
            $this->sql.= "       SELECT rownum \"__ROWNUMBER__\", A.{$columns} FROM ({$basicsql}) A";
            
            if ($limit >0 )
            {
                $total = $offset + $limit;
                $this->sql .= " WHERE rownum <= {$total} ";
            }
            $this->sql.= ")";
            if ($offset > 0)
            {
                $this->sql .= " WHERE \"__ROWNUMBER__\" > {$offset} ";
            }
        }
        else
        {
            $this->sql = $basicsql;
        }
        
        return $this->sql;
    }
}
