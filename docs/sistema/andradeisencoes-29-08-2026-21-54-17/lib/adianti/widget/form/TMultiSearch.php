<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;

use Exception;

/**
 * Multi Search Widget
 *
 * A widget that allows multiple selection and searching capabilities.
 * It extends TSelect and implements AdiantiWidgetInterface.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Matheus Agnes Dias
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMultiSearch extends TSelect implements AdiantiWidgetInterface
{
    protected $id;
    protected $items;
    protected $size;
    protected $height;
    protected $minLength;
    protected $maxSize;
    protected $editable;
    protected $changeAction;
    protected $changeFunction;
    protected $allowClear;
    protected $allowSearch;
    protected $separator;
    protected $value;
    
    /**
     * Class Constructor
     *
     * Initializes the widget with a unique ID and default configurations.
     *
     * @param string $name The widget's name
     */
    public function __construct($name)
    {
        // executes the parent class constructor
        parent::__construct($name);
        $this->id   = 'tmultisearch_'.mt_rand(1000000000, 1999999999);

        $this->height = 100;
        $this->minLength = 3;
        $this->maxSize = 0;
        $this->allowClear = TRUE;
        $this->allowSearch = TRUE;
        
        parent::setDefaultOption(FALSE);
        $this->tag->{'component'} = 'multisearch';
        $this->tag->{'widget'} = 'tmultisearch';
    }
    
    /**
     * Disable multiple selection
     *
     * Removes the "multiple" attribute from the field.
     */
    public function disableMultiple()
    {
        unset($this->tag->{'multiple'});
    }
    
    /**
     * Disable the clear button
     *
     * Prevents the user from clearing the selected values.
     */
    public function disableClear()
    {
        $this->allowClear = FALSE;
    }
    
    /**
     * Disable the search functionality
     *
     * Prevents the user from searching for items in the selection list.
     */
    public function disableSearch()
    {
        $this->allowSearch = FALSE;
    }
    
    /**
     * Define the widget's size
     *
     * Sets the width and optional height for the widget.
     *
     * @param int|string $width  The widget's width in pixels or percentage
     * @param int|null   $height (Optional) The widget's height in pixels
     */
    public function setSize($width, $height = NULL)
    {
        $this->size   = $width;
        if ($height)
        {
            $this->height = $height;
        }
    }

    /**
     * Get the widget's size
     *
     * Returns the width and height of the widget.
     *
     * @return array An array containing width and height values
     */
    public function getSize()
    {
        return array( $this->size, $this->height );
    }
    
    /**
     * Define the minimum length for search
     *
     * Sets the minimum number of characters required before triggering a search.
     *
     * @param int $length The minimum length of characters for search
     */
    public function setMinLength($length)
    {
        $this->minLength = $length;
    }

    /**
     * Define the maximum number of items that can be selected
     *
     * If set to 1, it disables multiple selection and enables the default option.
     *
     * @param int $maxsize The maximum number of selectable items
     */
    public function setMaxSize($maxsize)
    {
        $this->maxSize = $maxsize;
        
        if ($maxsize == 1)
        {
            unset($this->height);
            parent::setDefaultOption(TRUE);
        }
    }
    
    /**
     * Define the value separator
     *
     * Specifies the character used to separate multiple values when stored.
     *
     * @param string $sep The separator character
     */
    public function setValueSeparator($sep)
    {
        $this->separator = $sep;
    }
    
    /**
     * Set the field's value
     *
     * Assigns the selected values to the widget, handling compatibility settings.
     *
     * @param string|array $value The value(s) to be set in the field
     */
    public function setValue($value)
    {
        $ini = AdiantiApplicationConfig::get();
        
        if (isset($ini['general']['compat']) AND $ini['general']['compat'] ==  '4')
        {
            if ($value)
            {
                parent::setValue(array_keys((array)$value));
            }
        }
        else
        {
            parent::setValue($value);
        }
    }
    
    /**
     * Retrieve the post data
     *
     * Returns the submitted values from the widget.
     * If compatibility mode is enabled, it maps values to item labels.
     *
     * @return string|array The selected values or an empty string if none selected
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
                    foreach ($values as $item)
                    {
                        $return[$item] = $this->items[$item];
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
     * Enable the field
     *
     * Makes the specified field editable in a given form.
     *
     * @param string $form_name The form name
     * @param string $field The field name
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tmultisearch_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the field
     *
     * Makes the specified field read-only in a given form.
     *
     * @param string $form_name The form name
     * @param string $field The field name
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tmultisearch_disable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Clear the field
     *
     * Removes all selected values from the specified field in a given form.
     *
     * @param string $form_name The form name
     * @param string $field The field name
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tmultisearch_clear_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Render the widget
     *
     * Displays the multi-search field with all its properties and functionalities.
     * Includes JavaScript initialization for handling selection and search.
     *
     * @throws Exception If the change action is set without a valid form
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'id'}    = $this->id; // tag id
        
        if (empty($this->tag->{'name'})) // may be defined by child classes
        {
            $this->tag->{'name'}  = $this->name.'[]'; // tag name
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
        $search_word = !empty($this->getProperty('placeholder'))? $this->getProperty('placeholder') : AdiantiCoreTranslator::translate('Search');
        $change_action = 'function() {}';
        $allowclear  = $this->allowClear  ? 'true' : 'false';
        $allowsearch = $this->allowSearch ? '1' : 'Infinity';
        $with_titles = $this->withTitles ? 'true' : 'false';

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
        parent::renderItems( false );
        $this->tag->show();
        
        TScript::create(" tmultisearch_start( '{$this->id}', '{$this->minLength}', '{$this->maxSize}', '{$search_word}', $multiple, '{$size}', '{$this->height}px', {$allowclear}, {$allowsearch}, $change_action, {$with_titles} ); ");
        
        if (!$this->editable)
        {
            TScript::create(" tmultisearch_disable_field( '{$this->formName}', '{$this->name}'); ");
        }
    }
}
