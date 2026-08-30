<?php
namespace Adianti\Service;

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Util\AdiantiStringConversion;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TExpression;

use StdClass;
use Exception;

/**
 * MultiSearch backend
 *
 * This service searches for a given keyword inside a specified model and returns matching results.
 * It supports different database operators and criteria, allowing for flexible search customization.
 *
 * @version    7.5
 * @package    service
 * @author     Pablo Dall'Oglio
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiMultiSearchService
{
    /**
     * Executes a search query based on the given parameters and returns matching results.
     *
     * This method retrieves records from a specified model using a combination of filtering criteria.
     * It supports JSON decoding, dynamic criteria construction, ID-based searching, and different database operators.
     * The results are returned in a JSON format with key-value pairs.
     *
     * @param array|null $param An array of search parameters, including:
     *   - 'key' (string): The primary key column for the model.
     *   - 'database' (string): The database connection identifier.
     *   - 'column' (string): The column(s) to search within, separated by commas.
     *   - 'model' (string): The model class name to perform the search on.
     *   - 'hash' (string): A security hash for validation.
     *   - 'mask' (string): The format used to display search results.
     *   - 'jsonvalue' (int): Whether to decode the search value as JSON (1 for true, 0 for false).
     *   - 'operator' (string, optional): The comparison operator (default: 'like' or 'ilike' based on database type).
     *   - 'criteria' (string, optional): A base64-encoded, serialized `TCriteria` object for additional filtering.
     *   - 'value' (mixed, optional): The value to search for.
     *   - 'onlyidsearch' (bool, optional): Whether to search only by ID.
     *   - 'idsearch' (int, optional): Whether to include ID-based searching (1 for true).
     *   - 'idtextsearch' (int, optional): Whether to treat the ID as text for searching.
     *   - 'operator_idsearch' (string, optional): The operator to use when searching by ID.
     *   - 'orderColumn' (string, optional): The column used for ordering the results.
     *   - 'minlength' (int, optional): The minimum length of the search query to trigger a search.
     *
     * @return void Outputs a JSON response containing the search results in the format:
     *   ```json
     *   {
     *     "result": [
     *       "1::Record Name",
     *       "2::Another Record"
     *     ]
     *   }
     *   ```
     *   If an exception occurs, the response will contain an error message.
     *
     * @throws Exception If an error occurs during the database transaction or query execution.
     */
	public static function onSearch($param = null)
	{
        $key  = $param['key'];
        $ini  = AdiantiApplicationConfig::get();
        $seed = APPLICATION_NAME . ( !empty($ini['general']['seed']) ? $ini['general']['seed'] : 's8dkld83kf73kf094' );
        $hash = md5("{$seed}{$param['database']}{$param['key']}{$param['column']}{$param['model']}");
        $mask = $param['mask'];
        $json = ! empty($param['jsonvalue']) && $param['jsonvalue'] == 1;
        
        if ($hash == $param['hash'])
        {
            try
            {
                TTransaction::openFake($param['database']);
                $info = TTransaction::getDatabaseInfo();
                $default_op = $info['type'] == 'pgsql' ? 'ilike' : 'like';
                $operator   = !empty($param['operator']) ? $param['operator'] : $default_op;
                
                $repository = new TRepository($param['model']);
                $criteria = new TCriteria;
                if ($param['criteria'])
                {
                    $criteria = unserialize( base64_decode(str_replace(array('-', '_'), array('+', '/'), $param['criteria'])) );
                }
    
                $columns = explode(',', $param['column']);
                
                if (!isset($param['value']))
                {
                    $param['value'] = '';
                }

                if ($json)
                {
                    $param['value'] = json_decode($param['value']);
                }
                
                if ($columns)
                {
                    $dynamic_criteria = new TCriteria;
                    
                    if (empty($param['onlyidsearch']))
                    {
                        foreach ($columns as $column)
                        {
                            $column = trim($column);
                            
                            if (!empty($param['value']))
                            {
                                if (stristr(strtolower($operator),'like') !== FALSE)
                                {
                                    $param['value'] = str_replace(' ', '%', $param['value']);
                                    
                                    if (in_array($info['type'], ['mysql', 'oracle', 'mssql', 'dblib', 'sqlsrv']))
                                    {
                                        $filter = new TFilter("lower({$column})", $operator, strtolower("%{$param['value']}%"));
                                    }
                                    else
                                    {
                                        $filter = new TFilter($column, $operator, "%{$param['value']}%");
                                    }
                                }
                                else
                                {
                                    $filter = new TFilter($column, $operator, $param['value']);
                                }
            
                                $dynamic_criteria->add($filter, TExpression::OR_OPERATOR);
                            }
                        }
                    }
                    
                    $id_search_value = ((!empty($param['idtextsearch']) && $param['idtextsearch'] == '1') || ((defined("{$param['model']}::IDPOLICY")) AND (constant("{$param['model']}::IDPOLICY") == 'uuid')) || is_array($param['value']) ) ? $param['value'] : (int) $param['value'];
                    
                    if ($param['idsearch'] == '1' and !empty( $id_search_value ))
                    {
                        $operator_idsearch = empty($param['operator_idsearch']) ? '=' :  $param['operator_idsearch'];
                        $dynamic_criteria->add( new TFilter($key, $operator_idsearch, $id_search_value), TExpression::OR_OPERATOR);
                    }
                }
                
                if (!$dynamic_criteria->isEmpty())
                {
                    $criteria->add($dynamic_criteria, TExpression::AND_OPERATOR);
                }
                $criteria->setProperty('order', $param['orderColumn']);
                $criteria->setProperty('limit', 1000);
                
                $items = array();
                
                if (!empty($param['value']) || $param['minlength'] == '0')
                {
                    $collection = $repository->load($criteria, FALSE);
                    
                    foreach ($collection as $object)
                    {
                        $k = $object->$key;
                        $maskvalues = $mask;
                        
                        $maskvalues = $object->render($maskvalues);
                        
                        // replace methods
                        $methods = get_class_methods($object);
                        if ($methods)
                        {
                            foreach ($methods as $method)
                            {
                                if (stristr($maskvalues, "{$method}()") !== FALSE)
                                {
                                    $maskvalues = str_replace('{'.$method.'()}', $object->$method(), $maskvalues);
                                }
                            }
                        }
                        
                        $c = $maskvalues;
                        if ( $k != null && $c != null )
                        {
                            $c = AdiantiStringConversion::assureUnicode($c);
                            
                            if (!empty($k) && !empty($c))
                            {
                                $items[] = "{$k}::{$c}";
                            }
                        }
                    }
                }
                
                $ret = array();
                $ret['result'] = $items;
                echo json_encode($ret);
                TTransaction::close();
            }
            catch (Exception $e)
            {
                $ret = array();
                $ret['result'] = array("1::".$e->getMessage());
                
                echo json_encode($ret);
            }
        }
	}
}
