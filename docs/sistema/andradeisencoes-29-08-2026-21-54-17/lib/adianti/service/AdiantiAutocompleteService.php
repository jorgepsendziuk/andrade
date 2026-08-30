<?php
namespace Adianti\Service;

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Util\AdiantiStringConversion;

use StdClass;
use Exception;

/**
 * Autocomplete backend
 *
 * This service performs a search based on a given query string inside a specific database model,
 * applying filters, sorting, and returning suggestions in JSON format.
 *
 * @version    7.5
 * @package    service
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiAutocompleteService
{
    /**
     * Searches for a query string within a specified database model.
     *
     * This method validates the provided parameters, connects to the database, applies filters,
     * retrieves matching records, formats them, and returns the results as JSON.
     *
     * @param array|null $param An associative array containing the search parameters:
     *                          - 'database' (string): The database connection name.
     *                          - 'model' (string): The model class name to perform the search.
     *                          - 'column' (string): The column in the database table to search in.
     *                          - 'query' (string): The search term entered by the user.
     *                          - 'mask' (string): A formatting mask for the result values.
     *                          - 'hash' (string): A security hash to validate the request.
     *                          - 'orderColumn' (string): The column name used for ordering results.
     *                          - 'operator' (string, optional): The comparison operator (default is 'like' or 'ilike' for PostgreSQL).
     *                          - 'criteria' (string, optional): A serialized and base64-encoded filter criteria.
     *
     * @return void Outputs a JSON response containing:
     *              - 'query' (string): The search query term.
     *              - 'suggestions' (array|null): An array of search suggestions or an error message.
     */
	public static function onSearch($param = null)
	{
        $seed = APPLICATION_NAME.'s8dkld83kf73kf094';
        $hash = md5("{$seed}{$param['database']}{$param['column']}{$param['model']}");
        $mask = $param['mask'];
        
        if ($hash == $param['hash'])
        {
            try
            {
                TTransaction::open($param['database']);
                $info = TTransaction::getDatabaseInfo();
                $default_op = $info['type'] == 'pgsql' ? 'ilike' : 'like';
                $operator   = !empty($param['operator']) ? $param['operator'] : $default_op;
                
                $repository = new TRepository($param['model']);
                $criteria = new TCriteria;
                if ($param['criteria'])
                {
                    $criteria = unserialize(base64_decode($param['criteria']));
                }
    
                $column = $param['column'];
                if (stristr(strtolower($operator),'like') !== FALSE)
                {
                    $filter = new TFilter($column, $operator, "%{$param['query']}%");
                }
                else
                {
                    $filter = new TFilter($column, $operator, $param['query']);
                }
                
                $criteria->add($filter);
                $criteria->setProperty('order', $param['orderColumn']);
                $criteria->setProperty('limit', 1000);
                $collection = $repository->load($criteria, FALSE);
                
                $items = array();
                
                if ($collection)
                {
                    foreach ($collection as $object)
                    {
                        $maskvalues = $mask;
                        $maskvalues = $object->render($maskvalues);
                        
                        $c = $maskvalues;
                        if ($c != null )
                        {
                            $c = AdiantiStringConversion::assureUnicode($c);
                            
                            if (!empty($c))
                            {
                                $items[] = $c;
                            }
                        }
                    }
                }
                
                $ret = array();
                $ret['query'] = 'Unit';
                $ret['suggestions'] = $items;
                
                echo json_encode($ret);
                TTransaction::close();
            }
            catch (Exception $e)
            {
                $ret = array();
                $ret['query'] = 'Unit';
                $ret['suggestions'] = array($e->getMessage());
                
                echo json_encode($ret);
            }
        }
        else
        {
            $ret = array();
            $ret['query'] = 'Unit';
            $ret['suggestions'] = NULL;
            echo json_encode($ret);
        }
	}
}
