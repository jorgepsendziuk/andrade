<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TMultiSearch;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Widget\Form\TForm;

use Exception;

/**
 * Database Multisearch Widget
 *
 * This widget provides a multi-selection search interface integrated with a database model.
 * It supports filtering, ordering, and multiple selection of values.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBMultiSearch extends TMultiSearch
{
    protected $id;
    protected $initialItems;
    protected $items;
    protected $size;
    protected $height;
    protected $minLength;
    protected $maxSize;
    protected $database;
    protected $model;
    protected $key;
    protected $column;
    protected $operator;
    protected $orderColumn;
    protected $criteria;
    protected $mask;
    protected $service;
    protected $seed;
    protected $editable;
    protected $changeFunction;
    protected $idSearch;
    protected $idTextSearch;
    
    /**
     * Class Constructor
     *
     * Initializes a multi-selection search widget linked to a database.
     *
     * @param string       $name       Widget name
     * @param string       $database   Database connection name
     * @param string       $model      Model class name
     * @param string       $key        Column used as the key for selection
     * @param string       $value      Column displayed in the search results
     * @param string|null  $orderColumn Column used to order results (optional)
     * @param TCriteria|null $criteria Filtering criteria (optional)
     *
     * @throws Exception If any required parameter is missing
     */
    public function __construct($name, $database, $model, $key, $value, $orderColumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);
        $this->id   = 'tdbmultisearch_'.mt_rand(1000000000, 1999999999);
        
        $key   = trim($key);
        $value = trim($value);
        
        if (empty($database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }
        
        if (empty($model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }
        
        if (empty($key))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'key', __CLASS__));
        }
        
        if (empty($value))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'value', __CLASS__));
        }
        
        $ini = AdiantiApplicationConfig::get();
        
        $this->database = $database;
        $this->model = $model;
        $this->key = $key;
        $this->column = $value;
        $this->operator = null;
        $this->orderColumn = isset($orderColumn) ? $orderColumn : NULL;
        $this->criteria = $criteria;
        
        if (strpos($value,',') !== false)
        {
            $columns = explode(',', $value);
            $this->mask = '{'.$columns[0].'}';
        }
        else
        {
            $this->mask = '{'.$value.'}';
        }
        
        $this->service = 'AdiantiMultiSearchService';
        $this->seed = APPLICATION_NAME . ( !empty($ini['general']['seed']) ? $ini['general']['seed'] : 's8dkld83kf73kf094' );
        $this->tag->{'widget'} = 'tdbmultisearch';
        $this->idSearch = true;
        $this->idTextSearch = false;
        
        if ((defined("{$model}::IDPOLICY")) AND (constant("{$model}::IDPOLICY") == 'uuid'))
        {
            $this->idTextSearch = true;
        }
    }
    
    /**
     * Sets the search service to be used.
     *
     * @param string $service Name of the search service
     */
    public function setService($service)
    {
        $this->service = $service;
    }
    
    /**
     * Disables search by ID, allowing only text-based searches.
     */
    public function disableIdSearch()
    {
        $this->idSearch = false;
    }
    
    /**
     * Enables ID-based textual search.
     */
    public function enableIdTextualSearch()
    {
        $this->idTextSearch = true;
    }
    
    /**
     * Sets the search operator used for filtering.
     *
     * @param string $operator SQL operator used for search queries
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }
    
    /**
     * Defines the display mask for search results.
     *
     * @param string $mask Display format for results
     */
    public function setMask($mask)
    {
        $this->mask = $mask;
    }

    /**
     * Defines which columns should be used as filters in the search.
     *
     * @param array|string $columns Column names to be used as filters
     */
    public function setFilterColumns($columns)
    {
        if (is_array($columns))
        {
            $columns = implode(',', $columns);
        }

        $this->column = $columns;
    }
    
    /**
     * Sets the field values.
     *
     * @param array|string $values Array or string containing selected values
     */
    public function setValue($values)
    {
        $original_values = $values;
        $ini = AdiantiApplicationConfig::get();
        
        if (isset($ini['general']['compat']) AND $ini['general']['compat'] ==  '4')
        {
            if ($values)
            {
                parent::setValue( $values );
                parent::addItems( $values );
            }
        }
        else
        {
            $items = [];
            if ($values)
            {
                if (!empty($this->separator))
                {
                    $values = explode($this->separator, $values);
                }
                
                if(is_array($values))
                {
                    $close = false;
                    if (!TTransaction::hasConnection($this->database))
                    {
                        TTransaction::openFake($this->database);
                        $close = true;
                    }
                    
                    foreach ($values as $value)
                    {
                        if ($value)
                        {
                            $model = $this->model;
                            
                            $pk = constant("{$model}::PRIMARYKEY");
                            
                            if ($pk === $this->key) // key is the primary key (default)
                            {
                                // use find because it uses cache
                                $object = $model::find( $value );
                            }
                            else // key is an alternative key (uses where->first)
                            {
                                $object = $model::where( $this->key, '=', $value )->first();
                            }
                            
                            if ($object)
                            {
                                $description = $object->render($this->mask);
                                $items[$value] = $description;
                            }
                        }
                    }
                    
                    if ($close)
                    {
                        TTransaction::close();
                    }
                }
                
                parent::addItems( $items );
            }
            parent::setValue( $original_values );
        }
    }
    
    /**
     * Retrieves the submitted data from the widget.
     *
     * @return array|string Returns an array of selected values or a string if single selection is enabled
     */
    public function getPostData()
    {
        $ini = AdiantiApplicationConfig::get();
        
        if (isset($_POST[$this->name]))
        {
            $values = $_POST[$this->name];
            
            if (isset($ini['general']['compat']) AND $ini['general']['compat'] ==  '4')
            {
                $return = [];
                if (is_array($values))
                {
                    $close = false;
                    if (!TTransaction::hasConnection($this->database))
                    {
                        TTransaction::openFake($this->database);
                        $close = true;
                    }
                    
                    foreach ($values as $value)
                    {
                        if ($value)
                        {
                            $model = $this->model;
                            $pk = constant("{$model}::PRIMARYKEY");
                            
                            if ($pk === $this->key) // key is the primary key (default)
                            {
                                // use find because it uses cache
                                $object = $model::find( $value );
                            }
                            else // key is an alternative key (uses where->first)
                            {
                                $object = $model::where( $this->key, '=', $value )->first();
                            }
                            
                            if ($object)
                            {
                                $description = $object->render($this->mask);
                                $return[$value] = $description;
                            }
                        }
                    }
                    
                    if ($close)
                    {
                        TTransaction::close();
                    }
                }
                return $return;
            }
            else
            {
                if (empty($this->separator))
                {
                    return $values;
                }
                else
                {
                    return implode($this->separator, $values);
                }
            }
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Renders the widget and initializes the search functionality.
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'id'}    = $this->id; // tag id
        
        if (empty($this->tag->{'name'})) // may be defined by child classes
        {
            $this->tag->{'name'}  = $this->name.'[]';  // tag name
        }
        
        if (strstr( (string) $this->size, '%') !== FALSE)
        {
            $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
            $size  = "{$this->size}";
        }
        else
        {
            $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
            $size  = "{$this->size}px";
        }
        
        $multiple = $this->maxSize == 1 ? 'false' : 'true';
        $orderColumn = isset($this->orderColumn) ? $this->orderColumn : $this->column;
        $criteria = '';
        if ($this->criteria)
        {
            $criteria = str_replace(array('+', '/'), array('-', '_'), base64_encode(serialize($this->criteria)));
        }
        
        $hash = md5("{$this->seed}{$this->database}{$this->key}{$this->column}{$this->model}");
        $length = $this->minLength;
        
        $class = $this->service;
        $callback = array($class, 'onSearch');
        $method = $callback[1];
        $id_search_string = $this->idSearch ? '1' : '0';
        $id_text_search = $this->idTextSearch ? '1' : '0';
        $with_titles = $this->withTitles ? 'true' : 'false';

        $search_word = !empty($this->getProperty('placeholder'))? $this->getProperty('placeholder') : AdiantiCoreTranslator::translate('Search');
        $url = "engine.php?class={$class}&method={$method}&static=1&database={$this->database}&key={$this->key}&column={$this->column}&model={$this->model}&orderColumn={$orderColumn}&criteria={$criteria}&operator={$this->operator}&mask={$this->mask}&idsearch={$id_search_string}&idtextsearch={$id_text_search}&minlength={$length}";
        
        if ($router = AdiantiCoreApplication::getRouter())
        {
	        $url = $router($url, false);
        }

        $change_action = 'function() {}';
        
        if (isset($this->changeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            
            $string_action = $this->changeAction->serialize(FALSE);
            $change_action = "function() { __adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback'); }";
            $this->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
        }
        else if (isset($this->changeFunction))
        {
            $change_action = "function() { $this->changeFunction }";
            $this->setProperty('changeaction', $this->changeFunction, FALSE);
        }
        
        // shows the component
        parent::prepareNoResultsActions();
        parent::renderItems( false );
        $this->tag->show();

        TScript::create(" tdbmultisearch_start( '{$this->id}', '{$length}', '{$this->maxSize}', '{$search_word}', $multiple, '{$url}', '{$size}', '{$this->height}px', '{$hash}', {$change_action}, {$with_titles} ); ");
        
        if (!$this->editable)
        {
            TScript::create(" tmultisearch_disable_field( '{$this->formName}', '{$this->name}', '{$this->tag->{'title'}}'); ");
        }
    }
}
