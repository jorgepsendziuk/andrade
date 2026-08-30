<?php
namespace Adianti\Database;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TSqlSelect;
use Adianti\Database\TSqlInsert;
use Adianti\Database\TSqlUpdate;
use Adianti\Database\TSqlDelete;

use PDO;
use Exception;
use SplFileObject;
use Closure;

/**
 * Database Task manager
 *
 * This class includes static methods for handling various database operations,
 * such as creating and dropping tables, inserting and updating data, copying data between tables,
 * and importing/exporting data to and from CSV files.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2018 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDatabase
{
    /**
     * Drops a table from the database.
     *
     * @param PDO    $conn     The database connection.
     * @param string $table    The name of the table to drop.
     * @param bool   $ifexists Whether to drop the table only if it exists (default: false).
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during the operation.
     */
    public static function dropTable($conn, $table, $ifexists = false)
    {
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if (in_array($driver, ['oci', 'dblib', 'sqlsrv']))
        {
            $list = [];
            $table_upper    = strtoupper($table);
            $list['oci']    = "SELECT * FROM cat WHERE table_type in ('TABLE', 'VIEW') AND table_name = '{$table_upper}'";
            $list['dblib']  = "SELECT * FROM sysobjects WHERE (type = 'U' or type='V') AND name = '{$table}'";
            $list['sqlsrv'] = $list['dblib'];
            
            if ($ifexists)
            {
                $sql = $list[$driver];
                $result = $conn->query($sql);
                if (count($result->fetchAll()) > 0)
                {
                    $sql = "DROP TABLE {$table}";
                    TTransaction::log($sql);
                    return $conn->query($sql);
                }
            }
            else
            {
                $sql = "DROP TABLE {$table}";
                TTransaction::log($sql);
                return $conn->query($sql);
            }
        }
        else
        {
            $ife = $ifexists ? ' IF EXISTS ' : '';
            $sql = "DROP TABLE {$ife} {$table}";
            TTransaction::log($sql);
            return $conn->query($sql);
        }
    }
    
    /**
     * Creates a table in the database.
     *
     * @param PDO    $conn    The database connection.
     * @param string $table   The name of the table to create.
     * @param array  $columns An associative array where keys are column names and values are their types.
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during the operation.
     */
    public static function createTable($conn, $table, $columns)
    {
        $columns_list = [];
        foreach ($columns as $column => $type)
        {
            $columns_list[] = "{$column} {$type}";
        }
        
        $sql = "CREATE TABLE {$table} (" . implode(',', $columns_list) . ")";
        
        TTransaction::log($sql);
        return $conn->query($sql);
    }
    
    /**
     * Drops a column from a table.
     *
     * @param PDO    $conn   The database connection.
     * @param string $table  The name of the table.
     * @param string $column The name of the column to drop.
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during the operation.
     */
    public static function dropColumn($conn, $table, $column)
    {
        $sql = "ALTER TABLE {$table} DROP COLUMN {$column}";
        TTransaction::log($sql);
        return $conn->query($sql);
    }
    
    /**
     * Adds a new column to a table.
     *
     * @param PDO    $conn    The database connection.
     * @param string $table   The name of the table.
     * @param string $column  The name of the column to add.
     * @param string $type    The data type of the new column.
     * @param string $options Additional options for the column definition.
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during the operation.
     */
    public static function addColumn($conn, $table, $column, $type, $options)
    {
        $sql = "ALTER TABLE {$table} ADD {$column} {$type} {$options}";
        TTransaction::log($sql);
        return $conn->query($sql);
    }
    
    /**
     * Inserts data into a table.
     *
     * @param PDO        $conn           The database connection.
     * @param string     $table          The name of the table.
     * @param array      $values         An associative array of column names and values to insert.
     * @param TCriteria|null $avoid_criteria A criteria object to check if data should be inserted.
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during the operation.
     */
    public static function insertData($conn, $table, $values, $avoid_criteria = null)
    {
        if (!empty($avoid_criteria))
        {
            if (self::countData($conn, $table, $avoid_criteria) > 0)
            {
                return;
            }
        }
        
        $sql = new TSqlInsert;
        $sql->setEntity($table);
        
        foreach ($values as $key => $value)
        {
            $sql->setRowData($key, $value);
        }
        
        TTransaction::log($sql->getInstruction());
        
        $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
        if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
        {
            $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $result-> execute ( $sql->getPreparedVars() );
        }
        else
        {
            // execute the query
            $result = $conn-> query($sql->getInstruction());
        }
        
        return $result;
    }


    /**
     * Updates data in a table.
     *
     * @param PDO        $conn    The database connection.
     * @param string     $table   The name of the table.
     * @param array      $values  An associative array of column names and new values.
     * @param TCriteria|null $criteria A criteria object defining which rows to update.
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during the operation.
     */
    public static function updateData($conn, $table, $values, $criteria = null)
    {
        $sql = new TSqlUpdate;
        $sql->setEntity($table);
        
        if ($criteria)
        {
            $sql->setCriteria($criteria);
        }
        
        foreach ($values as $key => $value)
        {
            $sql->setRowData($key, $value);
        }
        
        TTransaction::log($sql->getInstruction());
        
        $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
        if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
        {
            $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $result-> execute ( $sql->getPreparedVars() );
        }
        else
        {
            // execute the query
            $result = $conn-> query($sql->getInstruction());
        }
        
        return $result;
    }
    
    /**
     * Deletes data from a table based on criteria.
     *
     * @param PDO        $conn     The database connection.
     * @param string     $table    The name of the table.
     * @param TCriteria|null $criteria A criteria object defining which rows to delete.
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during the operation.
     */
    public static function clearData($conn, $table, $criteria = null)
    {
        $sql = new TSqlDelete;
        $sql->setEntity($table);
        if ($criteria)
        {
            $sql->setCriteria($criteria);
        }
        
        TTransaction::log( $sql->getInstruction() );
        return $conn->query( $sql->getInstruction() );
    }
    
    /**
     * Executes a raw SQL query.
     *
     * @param PDO    $conn  The database connection.
     * @param string $query The SQL query to execute.
     *
     * @return mixed The result of the query execution.
     * @throws Exception If an error occurs during execution.
     */
    public static function execute($conn, $query)
    {
        TTransaction::log($query);
        return $conn->query($query);
    }
    
    /**
     * Retrieves data from a SQL query.
     *
     * @param PDO         $conn            The database connection.
     * @param string      $query           The SQL query to execute.
     * @param array|null  $mapping         An array defining field mappings.
     * @param array|null  $prepared_values An array of parameters for prepared statements.
     * @param Closure|null $action         A closure function to process each row.
     *
     * @return array|null An array of retrieved data, or null if a closure function is used.
     * @throws Exception If an error occurs during execution.
     */
    public static function getData($conn, $query, $mapping = null, $prepared_values = null, Closure $action = null)
    {
        $data = [];
        
        $result  = $conn->prepare($query);
        $result->execute($prepared_values);
        
        foreach ($result as $row)
        {
            $values = [];
            if ($mapping)
            {
                foreach ($mapping as $map)
                {
                    $newcolumn = $map[1];
                    $values[$newcolumn] = self::transform($row, $map);
                }
            }
            else
            {
                $values = $row;
            }
            
            if (empty($action))
            {
                $data[] = $values;
            }
            else
            {
                $action($values);
            }
        }
        
        if (empty($action))
        {
            return $data;
        }
    }
    
    /**
     * Retrieves a single row of data from a table.
     *
     * @param PDO         $conn     The database connection.
     * @param string      $table    The name of the table.
     * @param TCriteria|null $criteria A criteria object defining the conditions for selection.
     *
     * @return array|null The retrieved row as an associative array, or null if not found.
     * @throws Exception If an error occurs during execution.
     */
    public static function getRowData(PDO $conn, $table, $criteria = null)
    {
        $sql = new TSqlSelect;
        $sql->setEntity($table);
        
        if (empty($criteria))
        {
            $criteria = new TCriteria;
        }
        $criteria->setProperty('limit', 1);
        $sql->setCriteria($criteria);
        
        $sql->addColumn('*');
        
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
        
        if ($result)
        {
            $row = $result->fetch();
            return $row;
        }
        
        return null;
    }
    
    /**
     * Counts the number of rows matching a given criteria in a table.
     *
     * @param PDO         $conn     The database connection.
     * @param string      $table    The name of the table.
     * @param TCriteria|null $criteria A criteria object defining the conditions for counting.
     *
     * @return int The number of rows matching the criteria.
     * @throws Exception If an error occurs during execution.
     */
    public static function countData(PDO $conn, $table, $criteria = null)
    {
        $sql = new TSqlSelect;
        $sql->setEntity($table);
        
        if ($criteria)
        {
            $sql->setCriteria($criteria);
        }
        $sql->addColumn('count(*)');
        
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
        
        if ($result)
        {
            $row = $result->fetch();
            return (int) $row[0];
        }
        
        return 0;
    }
    
    /**
     * Copies data from one table to another.
     *
     * @param PDO         $source_conn  The source database connection.
     * @param PDO         $target_conn  The target database connection.
     * @param string      $source_table The source table.
     * @param string      $target_table The target table.
     * @param array       $mapping      An array defining field mappings.
     * @param TCriteria|null $criteria  A criteria object defining data filtering conditions.
     * @param int         $bulk_inserts Number of records to insert per batch.
     * @param bool        $auto_commit  Whether to commit after a certain number of inserts.
     *
     * @throws Exception If an error occurs during execution.
     */
    public static function copyData(PDO $source_conn, PDO $target_conn, $source_table, $target_table, $mapping, $criteria = null, $bulk_inserts = 1, $auto_commit = false)
    {
        $driver = $target_conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        $bulk_inserts = $driver == 'oci' ? 1 : $bulk_inserts;
        
        $source_columns = [];
        $target_columns = [];
        
        foreach ($mapping as $map)
        {
            if (!empty($map[0]) AND substr($map[0],0,4) !== 'VAL:')
            {
                $source_columns[] = $map[0];
            }
            $target_columns[] = $map[1];
        }
        
        $sel = new TSqlSelect;
        $sel->setEntity($source_table);
        if ($criteria)
        {
            $sel->setCriteria($criteria);
        }
        
        foreach ($source_columns as $source_column)
        {
            $sel->addColumn($source_column);
        }
        
        $result = $source_conn->query($sel->getInstruction());
        
        $ins = new TSqlMultiInsert;
        $ins->setEntity($target_table);
        $buffer_counter = 0;
        $commit_counter = 0;
        
        foreach ($result as $row)
        {
            $values = [];
            foreach ($mapping as $map)
            {
                $newcolumn = $map[1];
                $values[$newcolumn] = self::transform($row, $map);
            }
            $ins->addRowValues($values);
            
            $buffer_counter ++;
            $commit_counter ++;
            
            if ($buffer_counter == $bulk_inserts)
            {
                TTransaction::log( $ins->getInstruction() );
                $target_conn->query($ins->getInstruction());
                $buffer_counter = 0;
                
                // restart bulk insert
                $ins = new TSqlMultiInsert;
                $ins->setEntity($target_table);
                
                if ($auto_commit)
                {
                    if ($commit_counter == $auto_commit)
                    {
                        $target_conn->commit();
                        $target_conn->beginTransaction();
                        TTransaction::log( 'COMMIT' );
                        $commit_counter = 0;
                    }
                }
            }
        }
        
        if ($buffer_counter > 0)
        {
            TTransaction::log( $ins->getInstruction() );
            $target_conn->query($ins->getInstruction());
        }
    }
    
    /**
     * Copies data from a SQL query to a table.
     *
     * @param PDO         $source_conn     The source database connection.
     * @param PDO         $target_conn     The target database connection.
     * @param string      $query           The SQL query to fetch data.
     * @param string      $target_table    The target table for insertion.
     * @param array       $mapping         An array defining field mappings.
     * @param array|null  $prepared_values Parameters for the prepared statement.
     * @param int         $bulk_inserts    Number of records to insert per batch.
     * @param bool        $auto_commit     Whether to commit after a certain number of inserts.
     *
     * @throws Exception If an error occurs during execution.
     */
    public static function copyQuery(PDO $source_conn, PDO $target_conn, $query, $target_table, $mapping, $prepared_values = null, $bulk_inserts = 1, $auto_commit = false)
    {
        $driver = $target_conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        $bulk_inserts = $driver == 'oci' ? 1 : $bulk_inserts;
        
        $target_columns = [];
        
        foreach ($mapping as $map)
        {
            $target_columns[] = $map[1];
        }
        
        $result = $source_conn->prepare($query);
        $result->execute($prepared_values);
        
        $ins = new TSqlMultiInsert;
        $ins->setEntity($target_table);
        $buffer_counter = 0;
        $commit_counter = 0;
        
        foreach ($result as $row)
        {
            $values = [];
            foreach ($mapping as $map)
            {
                $newcolumn = $map[1];
                $values[$newcolumn] = self::transform($row, $map);
            }
            $ins->addRowValues($values);
            
            $buffer_counter ++;
            $commit_counter ++;
            
            if ($buffer_counter == $bulk_inserts)
            {
                TTransaction::log( $ins->getInstruction() );
                $target_conn->query($ins->getInstruction());
                $buffer_counter = 0;
                
                // restart bulk insert
                $ins = new TSqlMultiInsert;
                $ins->setEntity($target_table);
                
                if ($auto_commit)
                {
                    if ($commit_counter == $auto_commit)
                    {
                        $target_conn->commit();
                        $target_conn->beginTransaction();
                        TTransaction::log( 'COMMIT' );
                        $commit_counter = 0;
                    }
                }
            }
        }
        
        if ($buffer_counter > 0)
        {
            TTransaction::log( $ins->getInstruction() );
            $target_conn->query($ins->getInstruction());
        }
    }
    
    /**
     * Imports data from a CSV file into a table.
     *
     * @param string $filename     The path to the CSV file.
     * @param PDO    $target_conn  The target database connection.
     * @param string $target_table The target table for insertion.
     * @param array  $mapping      An array defining field mappings.
     * @param string $separator    The CSV column separator (default: ',').
     * @param int    $bulk_inserts Number of records to insert per batch.
     *
     * @throws Exception If the file cannot be read or written.
     */
    public static function importFromFile($filename, $target_conn, $target_table, $mapping, $separator = ',', $bulk_inserts = 1)
    {
        $driver = $target_conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        $bulk_inserts = $driver == 'oci' ? 1 : $bulk_inserts;
        
        $counter = 1;
        
        if (!file_exists($filename))
        {
            throw new Exception(AdiantiCoreTranslator::translate('File not found' . ': ' . $filename));
        }
        
        if (!is_readable($filename))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Permission denied' . ': ' . $filename));
        }
        
        $data   = file($filename);
        $header = str_getcsv($data[0], $separator);
        
        $ins = new TSqlMultiInsert;
        $ins->setEntity($target_table);
        $buffer_counter = 0;
        
        while (isset($data[$counter]))
        {
            $row = str_getcsv($data[$counter ++], $separator);
            foreach ($row as $key => $value)
            {
                $row[ $header[$key] ] = $value;
            }
            
            $values = [];
            foreach ($mapping as $map)
            {
                $newcolumn = $map[1];
                $values[$newcolumn] = self::transform($row, $map);
            }
            
            $ins->addRowValues($values);
            
            $buffer_counter ++;
            if ($buffer_counter == $bulk_inserts)
            {
                TTransaction::log( $ins->getInstruction() );
                $target_conn->query($ins->getInstruction());
                $buffer_counter = 0;
                
                // restart bulk insert
                $ins = new TSqlMultiInsert;
                $ins->setEntity($target_table);
            }
        }
        if ($buffer_counter > 0)
        {
            TTransaction::log( $ins->getInstruction() );
            $target_conn->query($ins->getInstruction());
        }
    }
    
    /**
     * Exports data from a table to a CSV file.
     *
     * @param PDO         $source_conn  The source database connection.
     * @param string      $source_table The source table.
     * @param string      $filename     The path to the output CSV file.
     * @param array       $mapping      An array defining field mappings.
     * @param TCriteria|null $criteria  A criteria object defining data filtering conditions.
     * @param string      $separator    The CSV column separator (default: ',').
     *
     * @throws Exception If the file cannot be written.
     */
    public static function exportToFile($source_conn, $source_table, $filename, $mapping, $criteria = null, $separator = ',')
    {
        $source_columns = [];
        $target_columns = [];
        
        if ( (file_exists($filename) AND !is_writable($filename)) OR (!is_writable(dirname($filename))) )
        {
            throw new Exception(AdiantiCoreTranslator::translate('Permission denied' . ': ' . $filename));
        }
        
        foreach ($mapping as $map)
        {
            if (!empty($map[0]) AND substr($map[0],0,4) !== 'VAL:')
            {
                $source_columns[] = $map[0];
            }
            $target_columns[] = $map[1];
        }
        
        $sel = new TSqlSelect;
        $sel->setEntity($source_table);
        if ($criteria)
        {
            $sel->setCriteria($criteria);
        }
        
        foreach ($source_columns as $source_column)
        {
            $sel->addColumn($source_column);
        }
        
        $result = $source_conn->query($sel->getInstruction());
        
        $file = new SplFileObject($filename, 'w');
        $file->setCsvControl(',');
        $file->fputcsv($target_columns);
        
        foreach ($result as $row)
        {
            $values = [];
            foreach ($mapping as $map)
            {
                $newcolumn = $map[1];
                $values[$newcolumn] = self::transform($row, $map);
            }
            
            $file->fputcsv(array_values($values));
        }
        $file = null; // close
    }
    
    /**
     * Transforms a row value according to mapping rules.
     *
     * @param array $row The row data.
     * @param array $map An array containing mapping instructions.
     *
     * @return mixed The transformed value.
     */
    private static function transform($row, $map)
    {
        $column   = $map[0];
        $callback = isset($map[2]) ? $map[2] : null;
        $value    = (substr($column,0,4)== 'VAL:') ? substr($column,4) : $row[$column];
        
        if (is_string($value))
        {
            $value = preg_replace('/[[:cntrl:]]/', '', $value);
        }
        
        if (is_callable($callback))
        {
            $value = call_user_func($callback, $value, $row);
        }
        
        return $value;
    }
}
