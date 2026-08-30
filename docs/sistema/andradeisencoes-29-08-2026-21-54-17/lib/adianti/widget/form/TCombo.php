<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Util\TImage;
use Mad\Util\Crypt;
use Exception;

/**
 * TCombo Widget
 *
 * This class represents a ComboBox (drop-down list) widget.
 * It extends TField and implements the AdiantiWidgetInterface.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCombo extends TField implements AdiantiWidgetInterface
{
    protected $id;
    protected $items; // array containing the combobox options
    protected $formName;
    private $searchable;
    private $changeAction;
    private $defaultOption;
    protected $changeFunction;
    protected $is_boolean;
    protected $noResultsButtonAction;
    protected $noResultsButtonActionLabel;
    protected $noResultsButtonActionIcon;
    protected $noResultsButtonActionBtnClass;
    protected $noResultsQuickRegisterAction;
    protected $noResultsQuickRegisterActionLabel;
    protected $noResultsQuickRegisterActionIcon;
    protected $noResultsQuickRegisterActionBtnClass;
    protected $noResultsMessage;

    /**
     * Class Constructor
     *
     * Initializes the ComboBox widget.
     *
     * @param string $name The widget's name
     */
    public function __construct($name)
    {
        // executes the parent class constructor
        parent::__construct($name);
        
        $this->id = 'tcombo_'.mt_rand(1000000000, 1999999999);
        $this->defaultOption = '';

        // creates a <select> tag
        $this->tag = new TElement('select');
        $this->tag->{'class'}  = 'tcombo'; // CSS
        $this->tag->{'widget'} = 'tcombo';
        $this->is_boolean = FALSE;
    }
    
    /**
     * Enable boolean mode
     *
     * When enabled, the combo box only allows selection between "Yes" and "No".
     */
    public function setBooleanMode()
    {
        $this->is_boolean = true;
        $this->addItems( [ '1' => AdiantiCoreTranslator::translate('Yes'),
                           '2' => AdiantiCoreTranslator::translate('No') ] );
                           
        // if setValue() was called previously
        if ($this->value === true)
        {
            $this->value = '1';
        }
        else if ($this->value === false)
        {
            $this->value = '2';
        }
    }
    
    /**
     * Set the field's value
     *
     * If the combo is in boolean mode, it maps `true` to '1' and `false` to '2'.
     *
     * @param mixed $value The value to be set
     */
    public function setValue($value)
    {
        if ($this->is_boolean)
        {
            $this->value = $value ? '1' : '2';
        }
        else
        {
            parent::setValue($value);
        }
    }
    
    /**
     * Get the field's value
     *
     * If the combo is in boolean mode, it returns `true` for '1' and `false` for '2'.
     *
     * @return mixed The field's value
     */
    public function getValue()
    {
        if ($this->is_boolean)
        {
            return $this->value == '1' ? true : false;
        }
        else
        {
            return parent::getValue();
        }
    }
    
    /**
     * Clear the combo box options
     *
     * Removes all items from the combo box.
     */
    public function clear()
    {
        $this->items = array();
    }
    
    /**
     * Add items to the combo box
     *
     * @param array $items An associative array where keys are the option values and values are the option labels
     */
    public function addItems($items)
    {
        if (is_array($items))
        {
            $this->items = $items;
        }
    }
    
    /**
     * Get the combo box items
     *
     * @return array The array of items in the combo box
     */
    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * Enable search functionality
     *
     * Removes the default CSS class and enables the search feature in the combo box.
     */
    public function enableSearch()
    {
        unset($this->tag->{'class'});
        $this->searchable = true;
    }
    
    /**
     * Retrieve the posted value
     *
     * Handles empty values, boolean mode, and special cases with '::' separators.
     *
     * @return mixed The posted value
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            $val = $_POST[$name];
            
            if ($val == '') // empty option
            {
                return '';
            }
            else
            {
                if (is_string($val) and strpos($val, '::'))
                {
                    $tmp = explode('::', $val);
                    return trim($tmp[0]);
                }
                else
                {
                    if ($this->is_boolean)
                    {
                        return $val == '1' ? true : false;
                    }
                    else
                    {
                        return $val;
                    }
                }
            }
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Set the action to be executed when the combo box value changes
     *
     * @param TAction $action The action object to be triggered
     *
     * @throws Exception If the action is not static
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
     * Set a JavaScript function to be executed when the combo box value changes
     *
     * @param string $function The JavaScript function to be triggered
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Configures a create action button that appears when no results are found in the combo search
     * 
     * This method sets up a button that will be displayed when the user's search in the combo
     * returns no results. This allows users to quickly create a new item when they can't find
     * what they're looking for in the existing options.
     * 
     * @param TAction $action The action to be executed when the create button is clicked
     * @param string $label The text label to be displayed on the create button
     * @param string $icon The icon to be shown on the button (e.g., 'fa fa-plus')
     * @param string $btnClass The CSS class for styling the button (e.g., 'btn btn-primary')
     * 
     * @return void
     */
    public function configureNoResultsCreateButton(TAction $action, $label, $icon, $btnClass)
    {
        $this->noResultsButtonAction = $action;
        $this->noResultsButtonActionLabel = $label;
        $this->noResultsButtonActionIcon = $icon;
        $this->noResultsButtonActionBtnClass = $btnClass;
    }

    /**
     * Configures the quick register functionality when there are no results.
     * This feature allows adding a new element through an input field,
     * executing the configured action when the user clicks the adjacent button.
     * 
     * @param TAction $createAction Action to be executed when clicking the confirmation button
     * @param string|null $confirmButtonLabel Confirmation button text (optional)
     * @param string|null $confirmButtonIcon Confirmation button icon (optional)
     * @param string|null $confirmButtonClass Confirmation button CSS classes (optional)
     * 
     * @return void
     */
    public function configureNoResultsQuickRegister(TAction $createAction, $confirmButtonLabel = null, $confirmButtonIcon = null, $confirmButtonClass = null)
    {
        $this->noResultsQuickRegisterAction = $createAction;
        $this->noResultsQuickRegisterActionLabel = $confirmButtonLabel;
        $this->noResultsQuickRegisterActionIcon = $confirmButtonIcon;
        $this->noResultsQuickRegisterActionBtnClass = $confirmButtonClass;
    }

    /**
     * Sets the message to be displayed when no results are found.
     * 
     * @param string $noResultsMessage Message to be shown when the search returns no results
     * 
     * @return void
     */
    public function setNoResultsMessage($noResultsMessage)
    {
        $this->noResultsMessage = $noResultsMessage;
    }

    public function getNoResultsButtonAction()
    {
        return $this->noResultsButtonAction;
    }

    public function getNoResultsQuickRegisterAction()
    {
        return $this->noResultsQuickRegisterAction;
    }
    
    /**
     * Reload combo box items dynamically
     *
     * @param string  $formname     The form name
     * @param string  $name         The field name
     * @param array   $items        The new items to populate the combo box
     * @param boolean $startEmpty   Whether to start with an empty option
     * @param boolean $fire_events  Whether to trigger the change event
     */
    public static function reload($formname, $name, $items, $startEmpty = FALSE, $fire_events = TRUE)
    {
        $fire_param = $fire_events ? 'true' : 'false';
        $code = "tcombo_clear('{$formname}', '{$name}', $fire_param); ";
        if ($startEmpty)
        {
            $code .= "tcombo_add_option('{$formname}', '{$name}', '', ''); ";
        }
        
        if ($items)
        {
            foreach ($items as $key => $value)
            {
                $value = htmlspecialchars( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                if (substr($key, 0, 3) == '>>>')
                {
                    $code .= "tcombo_create_opt_group('{$formname}', '{$name}', '{$value}'); ";
                }
                else
                {
                    $code .= "tcombo_add_option('{$formname}', '{$name}', '{$key}', '{$value}'); ";
                }
            }
        }
        TScript::create($code);
    }

    /**
     * Add a single option to a combobox
     * @param $formname form name (used in gtk version)
     * @param $name field name
     * @param $key option key/value
     * @param $value option label
     * @param $fire_events If change action will be fired
     */
    public static function addOption($formname, $name, $key, $value)
    {   
        // Escape special characters in the value
        $value = htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $code = '';
        if (substr((string) $key, 0, 3) == '>>>')
        {
            $code = "tcombo_create_opt_group('{$formname}', '{$name}', '{$value}'); ";
        }
        else
        {
            $code = "tcombo_add_option('{$formname}', '{$name}', '{$key}', '{$value}'); ";
        }
        
        TScript::create($code);
    }
    
    /**
     * Enable the combo box field
     *
     * @param string $form_name The form name
     * @param string $field     The field name
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tcombo_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the combo box field
     *
     * @param string $form_name The form name
     * @param string $field     The field name
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tcombo_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clear the combo box field
     *
     * @param string  $form_name   The form name
     * @param string  $field       The field name
     * @param boolean $fire_events Whether to trigger the change event
     */
    public static function clearField($form_name, $field, $fire_events = TRUE)
    {
        $fire_param = $fire_events ? 'true' : 'false';
        TScript::create( " tcombo_clear('{$form_name}', '{$field}', $fire_param); " );
    }
    
    /**
     * Set the default option label for the combo box
     *
     * @param string $option The label for the default option
     */
    public function setDefaultOption($option)
    {
        $this->defaultOption = $option;
    }
    
    /**
     * Render the combo box items
     *
     * Populates the `<select>` element with options and handles optgroups.
     */
    public function renderItems()
    {
        if ($this->defaultOption !== FALSE)
        {
            // creates an empty <option> tag
            $option = new TElement('option');
            
            $option->add( $this->defaultOption );
            $option->{'value'} = '';   // tag value

            // add the option tag to the combo
            $this->tag->add($option);
        }
                    
        if ($this->items)
        {
            // iterate the combobox items
            foreach ($this->items as $chave => $item)
            {
                if (substr($chave, 0, 3) == '>>>')
                {
                    $optgroup = new TElement('optgroup');
                    $optgroup->{'label'} = $item;
                    
                    // add the option to the combo
                    $this->tag->add($optgroup);
                }
                else
                {
                    // creates an <option> tag
                    $option = new TElement('option');
                    $option->{'value'} = $chave;  // define the index
                    $option->add(htmlspecialchars($item)); // add the item label
                    
                    if (substr($chave, 0, 3) == '###')
                    {
                        $option->{'disabled'} = '1';
                        $option->{'class'} = 'disabled';
                    }
                    
                    // verify if this option is selected
                    if (($chave == $this->value) AND !(is_null($this->value)) AND strlen((string) $this->value) > 0)
                    {
                        // mark as selected
                        $option->{'selected'} = 1;
                    }
                    
                    if (isset($optgroup))
                    {
                        $optgroup->add($option);
                    }
                    else
                    {
                        $this->tag->add($option);
                    }
                }
            }
        }
    }
    
    /**
     * Display the combo box widget
     *
     * Sets properties, applies styles, and renders items before displaying the widget.
     *
     * @throws Exception If the form associated with the combo box is not defined in TForm
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'name'}  = $this->name;    // tag name
        
        if ($this->id and empty($this->tag->{'id'}))
        {
            $this->tag->{'id'} = $this->id;
        }
        
        if (!empty($this->size))
        {
            if (strstr((string) $this->size, '%') !== FALSE)
            {
                $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
            }
            else
            {
                $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
            }
        }
        
        if (isset($this->changeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            
            $string_action = $this->changeAction->serialize(FALSE);
            $this->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
            $this->setProperty('onChange', $this->getProperty('changeaction'));
        }

        if (isset($this->noResultsButtonAction) && !$this->noResultsButtonAction->isHidden() && !$this->noResultsButtonAction->isDisabled())
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }

            $this->noResultsButtonAction->setParameter('_form_name', $this->formName);
            $this->noResultsButtonAction->setParameter('_field_name', $this->name);
            // get the action as URL
            $url = $this->noResultsButtonAction->serialize(FALSE);

            $url = htmlspecialchars($url);
            $wait_message = AdiantiCoreTranslator::translate('Loading');

            $obj = new \stdClass;
            $obj->model = $this->model;
            $obj->database = $this->database;
            $obj->key = $this->key;
            $obj->column = $this->column;
            $obj->orderColumn = $this->orderColumn;
            $obj->criteria = $this->criteria;
            $obj->field_name = $this->name;
            $obj->field_id = $this->id;
            $obj->field_form = $this->formName;
            $obj->component = explode('\\', get_called_class());
            $obj->component = end($obj->component);

            $action = "\$('.select2').prev().select2('close'); Adianti.waitMessage = '$wait_message';";
            $action.= "__adianti_post_page_lookup('{$this->formName}', '{$url}', this);";
            $action.= "return false;";
            
            $string_action = $this->noResultsButtonAction->serialize(FALSE);
            $this->setProperty('noresultsbtnaction', $action);
            
            $image = new TImage($this->noResultsButtonActionIcon);
            $image = $image->getContents();

            $btn = new TElement('span');
            $btn->add("{$image} {$this->noResultsButtonActionLabel}");
            $btn->onClick = $action;
            $btn->class = 'btn '. $this->noResultsButtonActionBtnClass;
            $btn->id = $this->id.'_btn';
            $btn->{"data-noresultsbtnprops"} = Crypt::encryptString( base64_encode(serialize($obj)));
            $btn->name = $this->name;

            $noResultsButtonActionProperties = [
                'icon' => $this->noResultsButtonActionIcon,
                'label' => $this->noResultsButtonActionLabel,
                'btnClass' => $this->noResultsButtonActionBtnClass,
                'btn' => $btn->getContents(),
                'noResultsMessage' => $this->noResultsMessage
            ];

            $this->setProperty('noresultsbtnprops', base64_encode(json_encode($noResultsButtonActionProperties)));
        }

        if (isset($this->noResultsQuickRegisterAction) && !$this->noResultsQuickRegisterAction->isHidden() && !$this->noResultsQuickRegisterAction->isDisabled())
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }

            $this->noResultsQuickRegisterAction->setParameter('b_from_form', $this->formName);
            $this->noResultsQuickRegisterAction->setParameter('b_from_field', $this->name);
            // get the action as URL
            $url = $this->noResultsQuickRegisterAction->serialize(FALSE);
            if ($this->noResultsQuickRegisterAction->isStatic())
            {
                $url .= '&static=1';
            }
            $url = htmlspecialchars($url);
            $wait_message = AdiantiCoreTranslator::translate('Loading');

            $obj = new \stdClass;
            $obj->model = $this->model;
            $obj->database = $this->database;
            $obj->key = $this->key;
            $obj->column = $this->column;
            $obj->orderColumn = $this->orderColumn;
            $obj->criteria = $this->criteria;
            $obj->field_name = $this->name;
            $obj->field_id = $this->id;
            $obj->field_form = $this->formName;
            $obj->component = explode('\\', get_called_class());
            $obj->component = end($obj->component);

            $action = "Adianti.waitMessage = '$wait_message';";
            $action.= "__adianti_post_lookup('{$this->formName}', '{$url}', this);";
            $action.= "\$('.select2').prev().select2('close');return false;";
            
            $string_action = $this->noResultsQuickRegisterAction->serialize(FALSE);
            $this->setProperty('createaction', $action);
            
            $image = new TImage($this->noResultsQuickRegisterActionIcon);
            $image = $image->getContents();

            $btn = new TElement('span');
            $btn->add("{$image} {$this->noResultsQuickRegisterActionLabel}");
            $btn->onClick = $action;
            $btn->class = 'btn '. $this->noResultsQuickRegisterActionBtnClass;
            $btn->{"data-noresultsbtnprops"} = Crypt::encryptString( base64_encode(serialize($obj)));
            $btn->{"data-quick_register_value"} = '';
            $btn->id = $this->id.'_btn';

            $input = new TEntry($this->name.'_quickregister');
            $input->id = $this->id.'_quickregister';
            $input->class = 'quickregister';
            $input->oninput = "tcombo_set_quick_register_value(this, '{$this->id}')";
            $input->setSize('100%');

            $noResultsQuickRegisterActionProperties = [
                'icon' => $this->noResultsQuickRegisterActionIcon,
                'label' => $this->noResultsQuickRegisterActionLabel,
                'btnClass' => $this->noResultsQuickRegisterActionBtnClass,
                'btn' => $btn->getContents(),
                'input' => $input->getContents(),
                'noResultsMessage' => $this->noResultsMessage
            ];

            $this->setProperty('noresultsquickregisterprops', base64_encode(json_encode($noResultsQuickRegisterActionProperties)));
        }
        
        if (isset($this->changeFunction))
        {
            $this->setProperty('changeaction', $this->changeFunction, FALSE);
            $this->setProperty('onChange', $this->changeFunction, FALSE);
        }
        
        // verify whether the widget is editable
        if (!parent::getEditable())
        {
            // make the widget read-only
            $this->tag->{'onclick'}  = "return false;";
            $this->tag->{'style'}   .= ';pointer-events:none';
            $this->tag->{'tabindex'} = '-1';
            $this->tag->{'class'}    = 'tcombo tcombo_disabled'; // CSS
        }
        
        if ($this->searchable)
        {
            $this->tag->{'role'} = 'tcombosearch';
        }
        
        // shows the combobox
        $this->renderItems();
        $this->tag->show();
        
        if ($this->searchable)
        {
            $select = AdiantiCoreTranslator::translate('Select');
            TScript::create("tcombo_enable_search('#{$this->id}', '{$select}')");
            
            if (!parent::getEditable())
            {
                TScript::create(" tmultisearch_disable_field( '{$this->formName}', '{$this->name}', '{$this->tag->{'title'}}'); ");
            }
        }
    }
}
