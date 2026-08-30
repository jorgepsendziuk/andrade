<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Util\TImage;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;

use Exception;

/**
 * A Sortable list widget.
 *
 * This widget allows the user to create a sortable list with drag-and-drop functionality.
 * It supports item icons, orientation settings, item limits, and connectivity to other lists.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSortList extends TField implements AdiantiWidgetInterface
{
    private $initialItems;
    private $items;
    private $valueSet;
    private $connectedTo;
    private $itemIcon;
    private $changeAction;
    private $orientation;
    private $limit;
    protected $id;
    protected $changeFunction;
    protected $width;
    protected $height;
    protected $separator;
    
    /**
     * Class Constructor.
     *
     * Initializes the sortable list with a unique identifier and default settings.
     *
     * @param string $name The widget's name.
     */
    public function __construct($name)
    {
        // executes the parent class constructor
        parent::__construct($name);
        $this->id   = 'tsortlist_'.mt_rand(1000000000, 1999999999);
        
        $this->initialItems = array();
        $this->items = array();
        $this->limit = -1;
        
        // creates a <ul> tag
        $this->tag = new TElement('ul');
        $this->tag->{'class'} = 'tsortlist';
        $this->tag->{'itemname'} = $name;
    }
    
    /**
     * Sets the list orientation.
     *
     * @param string $orientation The orientation mode ('horizontal' or 'vertical').
     */
    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;
    }
    
    /**
     * Sets the maximum number of items allowed in the list.
     *
     * @param int $limit The maximum number of items (-1 for no limit).
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
    
    /**
     * Sets the icon for list items.
     *
     * @param TImage $icon The image icon to be displayed with items.
     */
    public function setItemIcon(TImage $icon)
    {
        $this->itemIcon = $icon;
    }
    
    /**
     * Sets the size of the sortable list.
     *
     * @param int|string $width  The width of the list (px or %).
     * @param int|string|null $height The height of the list (px or %), optional.
     */
    public function setSize($width, $height = NULL)
    {
        $this->width = $width;
        $this->height = $height;
    }
    
    /**
     * Sets the separator for multiple values.
     *
     * @param string $sep The separator string.
     */
    public function setValueSeparator($sep)
    {
        $this->separator = $sep;
    }
    
    /**
     * Sets the selected values in the list.
     *
     * @param array|string $value The selected values as an array or a delimited string.
     */
    public function setValue($value)
    {
        if (!empty($this->separator))
        {
            $value = explode($this->separator, $value);
        }
        
        $items = $this->initialItems;
        if (is_array($value))
        {
            $this->items = array();
            foreach ($value as $index)
            {
                if (isset($items[$index]))
                {
                    $this->items[$index] = $items[$index];
                }
                else if (isset($this->connectedTo) AND is_array($this->connectedTo))
                {
                    foreach ($this->connectedTo as $connectedList)
                    {
                        if (isset($connectedList->initialItems[$index] ) )
                        {
                            $this->items[$index] = $connectedList->initialItems[$index];
                        }
                    }
                }
            }
        	$this->valueSet = TRUE;
        }
    }
    
    /**
     * Connects this list to another `TSortList`.
     *
     * Items from the connected list will be considered when setting values.
     *
     * @param TSortList $list Another instance of `TSortList`.
     */
    public function connectTo(TSortList $list)
    {
        $this->connectedTo[] = $list;
    }
    
    /**
     * Adds items to the sortable list.
     *
     * @param array $items An associative array of items where keys are identifiers and values are labels.
     */
    public function addItems($items)
    {
        if (is_array($items))
        {
            $this->initialItems += $items;
            $this->items += $items;
        }
    }
    
    /**
     * Retrieves the list of initial items.
     *
     * @return array The associative array of initial items.
     */
    public function getItems()
    {
        return $this->initialItems;
    }
    
    /**
     * Retrieves the submitted data from the form.
     *
     * @return array|string The selected items as an array or a delimited string.
     */
    public function getPostData()
    {
        if (isset($_POST[$this->name]))
        {
            if (empty($this->separator))
            {
                return $_POST[$this->name];
            }
            else
            {
                return implode($this->separator, $_POST[$this->name]);
            }
        }
        else
        {
            return array();
        }
    }
    
    /**
     * Sets an action to be executed when the list order is changed.
     *
     * @param TAction $action The action object to be triggered.
     *
     * @throws Exception If the action is not static.
     */
    public function setChangeAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->changeAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Sets a JavaScript function to be executed when the list order is changed.
     *
     * @param string $function The JavaScript function code.
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }
    
    /**
     * Enables the sortable list field in the form.
     *
     * @param string $form_name The name of the form.
     * @param string $field The name of the field.
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tsortlist_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables the sortable list field in the form.
     *
     * @param string $form_name The name of the form.
     * @param string $field The name of the field.
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tsortlist_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clears the values in the sortable list field.
     *
     * @param string $form_name The name of the form.
     * @param string $field The name of the field.
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tsortlist_clear_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Reloads the list items dynamically.
     *
     * @param string $form_name The name of the form.
     * @param string $field The name of the field.
     * @param array $items An associative array of items to populate the list.
     */
    public static function reload($form_name, $field, $items)
    {
        self::clearField($form_name, $field);

        if (!empty($items))
        {
            $index = 1;
            foreach ($items as $key => $value)
            {
                $value = htmlspecialchars( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                TScript::create("tsortlist_add_item_field('{$form_name}', '{$field}', '{$key}', '{$value}', '{$index}');");
                $index ++;
            }
        }
    }
    
    /**
     * Renders the sortable list widget.
     *
     * Generates the HTML structure and initializes JavaScript behavior for drag-and-drop sorting.
     *
     * @throws Exception If the change action is set but no form is associated.
     */
    public function show()
    {
        $this->tag->{'id'} = $this->id;
        
        $this->setProperty('style', (strstr($this->width, '%') !== FALSE)  ? "width:{$this->width};"   : "width:{$this->width}px;",   false); //aggregate style info
        $this->setProperty('style', (strstr($this->height, '%') !== FALSE) ? "height:{$this->height};" : "height:{$this->height}px;", false); //aggregate style info
        
        if ($this->orientation == 'horizontal')
        {
            $this->tag->{'itemdisplay'} = 'inline-block';
        }
        else
        {
            $this->tag->{'itemdisplay'} = 'block';
        }
        
        if ($this->items)
        {
            $i = 1;
            // iterate the checkgroup options
            foreach ($this->items as $index => $label)
            {
                // control to reduce available options when they are present
                // in another connected list as a post value
	            if ($this->connectedTo AND is_array($this->connectedTo))
	            {
	                foreach ($this->connectedTo as $connectedList)
	                {
                        if (isset($connectedList->items[$index]) AND $connectedList->valueSet )
                        {
                            continue 2;
                        }
	                }
	            }

                // instantiates a new Item
                $item = new TElement('li');
                
                if ($this->itemIcon)
                {
                    $item->add($this->itemIcon);
                }

                $label = new TLabel($label);
                $label->{'style'} = 'width: 100%;';

                $item->add($label);
                $item->{'class'} = 'tsortlist_item btn btn-default';
                $item->{'style'} = 'display:block;';
                $item->{'id'} = "tsortlist_{$this->name}_item_{$i}_li";
                $item->{'title'} = $this->tag->title;
                
                if ($this->orientation == 'horizontal')
                {
                    $item->{'style'} = 'display:inline-block';
                }
                
                $input = new TElement('input');
                $input->{'id'}   = "tsortlist_{$this->name}_item_{$i}_li_input";
                $input->{'type'} = 'hidden';
                $input->{'name'} = $this->name . '[]';
                $input->{'value'} = $index;
                $item->add($input);
                
                $this->tag->add($item);
                $i ++;
            }
        }
        
        if (parent::getEditable())
        {
            $change_action = 'function() {}';
            if (isset($this->changeAction))
            {
                if (!TForm::getFormByName($this->formName) instanceof TForm)
                {
                    throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
                }            
                $string_action = $this->changeAction->serialize(FALSE);
                $change_action = "function() { __adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback'); }";
            }
            
            if (isset($this->changeFunction))
            {
                $change_action = "function() { $this->changeFunction }";
            }
            
            $connect = 'false';
            if ($this->connectedTo AND is_array($this->connectedTo))
            {
                foreach ($this->connectedTo as $connectedList)
                {
                    $connectIds[] =  '#'.$connectedList->getId();
                }
                $connect = implode(', ', $connectIds);
            }
            TScript::create(" tsortlist_start( '#{$this->id}', '{$connect}', $change_action, $this->limit ) ");
        }
        $this->tag->show();
    }
}
