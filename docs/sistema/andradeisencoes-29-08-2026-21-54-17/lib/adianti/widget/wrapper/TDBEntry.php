<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;
use Adianti\Database\TCriteria;
use Adianti\Widget\Form\TForm;

use Exception;

/**
 * Database Entry Widget
 *
 * This widget is an enhanced text entry that integrates with a database model.
 * It supports autocomplete functionality based on a specified model column.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBEntry extends TEntry
{
    protected $minLength;
    protected $service;
    protected $displayMask;
    private $database;
    private $model;
    private $column;
    private $operator;
    private $orderColumn;
    private $criteria;
    
    /**
     * Class Constructor
     *
     * Initializes a database entry widget with autocomplete functionality.
     *
     * @param string     $name       Widget name
     * @param string     $database   Database connection name
     * @param string     $model      Model class name
     * @param string     $value      Column name used for autocomplete search
     * @param string|null $orderColumn Column used to order results (optional)
     * @param TCriteria|null $criteria Filtering criteria (optional)
     *
     * @throws Exception If any of the required parameters is missing
     */
    public function __construct($name, $database, $model, $value, $orderColumn = NULL, ?TCriteria $criteria = NULL)
    {
        // executes the parent class constructor
        parent::__construct($name);
        
        $value = trim($value);
        
        if (empty($database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }
        
        if (empty($model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }
        
        if (empty($value))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'value', __CLASS__));
        }
        
        $this->minLength = 1;
        $this->database = $database;
        $this->model = $model;
        $this->column = $value;
        $this->displayMask = '{'.$value.'}';
        $this->operator = null;
        $this->orderColumn = isset($orderColumn) ? $orderColumn : NULL;
        $this->criteria = $criteria;
        $this->service = 'AdiantiAutocompleteService';
    }
    
    /**
     * Sets the display mask for the autocomplete results.
     *
     * @param string $mask Mask format for display
     */
    public function setDisplayMask($mask)
    {
        $this->displayMask = $mask;
    }
    
    /**
     * Defines the autocomplete search service to be used.
     *
     * @param string $service Name of the search service
     */
    public function setService($service)
    {
        $this->service = $service;
    }
    
    /**
     * Sets the minimum length of input required to trigger the search.
     *
     * @param int $length Minimum number of characters before searching
     */
    public function setMinLength($length)
    {
        $this->minLength = $length;
    }
    
    /**
     * Sets the search operator (e.g., '=', 'LIKE', etc.).
     *
     * @param string $operator SQL operator used for filtering
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }
    
    /**
     * Renders the widget and initializes the autocomplete functionality.
     */
    public function show()
    {
        if (isset($this->exitAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->exitAction->serialize(FALSE);
            $this->setProperty('exitaction', "setTimeout( function(){ __adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback'); }, 250)");

            // just aggregate onBlur, if the previous one does not have return clause
            if (strstr((string) $this->getProperty('onBlur'), 'return') == FALSE)
            {
                $this->setProperty('onBlur', $this->getProperty('exitaction'), FALSE);
            }
            else
            {
                $this->setProperty('onBlur', $this->getProperty('exitaction'), TRUE);
            }

            $this->exitAction = null;
        }

        parent::show();
        
        
        $min = $this->minLength;
        $orderColumn = isset($this->orderColumn) ? $this->orderColumn : $this->column;
        $criteria = '';
        if ($this->criteria)
        {
            $criteria = base64_encode(serialize($this->criteria));
        }
        
        $seed = APPLICATION_NAME.'s8dkld83kf73kf094';
        $hash = md5("{$seed}{$this->database}{$this->column}{$this->model}");
        $length = $this->minLength;
        
        $class = $this->service;
        $callback = array($class, 'onSearch');
        $method = $callback[1];
        $url = "engine.php?class={$class}&method={$method}&static=1&database={$this->database}&column={$this->column}&model={$this->model}&orderColumn={$orderColumn}&criteria={$criteria}&operator={$this->operator}&hash={$hash}&mask={$this->displayMask}";
        
        TScript::create(" tdbentry_start( '{$this->name}', '{$url}', '{$min}' );");
    }
}
