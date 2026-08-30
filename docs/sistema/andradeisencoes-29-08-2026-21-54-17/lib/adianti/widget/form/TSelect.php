<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Util\TImage;
use Mad\Util\Crypt;

use Exception;

/**
 * TSelect is a multi-selection dropdown widget.
 *
 * This class represents a select input field with support for multiple selections,
 * search functionality, and dynamic item reloading. It extends TField and implements
 * the AdiantiWidgetInterface.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSelect extends TField implements AdiantiWidgetInterface
{
    private   $searchable;

    protected $id;
    protected $height;
    protected $items; // array containing the combobox options
    protected $formName;
    protected $changeFunction;
    protected $changeAction;
    protected $defaultOption;
    protected $separator;
    protected $value;
    protected $withTitles;
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
     * Class Constructor.
     *
     * Initializes the select widget, sets a unique ID, and configures default properties.
     *
     * @param string $name The name of the widget.
     */
    public function __construct($name)
    {
        // executes the parent class constructor
        parent::__construct($name);
        $this->id   = 'tselect_' . mt_rand(1000000000, 1999999999);
        $this->defaultOption = '';
        $this->withTitles = true;
        
        // creates a <select> tag
        $this->tag = new TElement('select');
        $this->tag->{'class'} = 'tselect'; // CSS
        $this->tag->{'multiple'} = '1';
        $this->tag->{'widget'} = 'tselect';
    }
    
    /**
     * Enables search functionality for the select widget.
     *
     * Removes the default CSS class and marks the widget as searchable.
     */
    public function enableSearch()
    {
        unset($this->tag->{'class'});
        $this->searchable = true;
    }
    
    /**
     * Disables multiple selection.
     *
     * Removes the 'multiple' attribute from the select field and sets its size to 3.
     */
    public function disableMultiple()
    {
        unset($this->tag->{'multiple'});
        $this->tag->{'size'} = 3;
    }

    /**
     * Disables option titles.
     *
     * Prevents titles from being displayed in the option elements.
     */
    public function disableTitles()
    {
        $this->withTitles = false;
    }
    
    /**
     * Sets the default option for the select widget.
     *
     * @param string $option The default option text.
     */
    public function setDefaultOption($option)
    {
        $this->defaultOption = $option;
    }
    
    /**
     * Adds items to the select widget.
     *
     * @param array $items An associative array where keys are option values and values are option labels.
     */
    public function addItems($items)
    {
        if (is_array($items))
        {
            $this->items = $items;
        }
    }
    
    /**
     * Retrieves the list of items in the select widget.
     *
     * @return array|null The array of items or null if no items are set.
     */
    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * Sets the dimensions of the select widget.
     *
     * @param int|string $width  The width in pixels or percentage.
     * @param int|string|null $height The height in pixels or percentage (optional).
     */
    public function setSize($width, $height = NULL)
    {
        $this->size = $width;
        $this->height = $height;
    }
    
    /**
     * Retrieves the size of the select widget.
     *
     * @return array An array containing the width and height.
     */
    public function getSize()
    {
        return array( $this->size, $this->height );
    }
    
    /**
     * Sets the value separator for multi-selection.
     *
     * @param string $sep The separator string.
     */
    public function setValueSeparator($sep)
    {
        $this->separator = $sep;
    }
    
    /**
     * Sets the selected value(s) for the select widget.
     *
     * If a separator is set, the value is converted into an array.
     *
     * @param string|array|null $value The selected value(s).
     */
    public function setValue($value)
    {
        if (empty($this->separator))
        {
            $this->value = $value;
        }
        else
        {
            if ($value)
            {
                $this->value = explode($this->separator, $value);
            }
            else
            {
                $this->value = null;
            }
        }
    }
    
    /**
     * Retrieves the posted data for the select widget.
     *
     * @return array|string The selected value(s) from the POST request.
     */
    public function getPostData()
    {
        if (isset($_POST[$this->name]))
        {
            if ($this->tag->{'multiple'})
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
                return $_POST[$this->name][0];
            }
        }
        else
        {
            return array();
        }
    }
    
    /**
     * Sets an action to be executed when the select value changes.
     *
     * @param TAction $action The action object.
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
     * Sets a JavaScript function to be executed when the select value changes.
     *
     * @param string $function The JavaScript function name.
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

    public function prepareNoResultsActions()
    {
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
            $obj->column = $this->mask ?? $this->column;
            $obj->orderColumn = $this->dorderColumn;
            $obj->criteria = $this->criteria;
            $obj->field_name = $this->name;
            $obj->field_id = $this->id;
            $obj->field_form = $this->formName;

            $obj->component = explode('\\', get_called_class());
            $obj->component = end($obj->component);

            $action = "\$('.select2').prev().select2('close'); Adianti.waitMessage = '$wait_message';";
            $action.= "__adianti_post_page_lookup('{$this->formName}', '{$url}', this);";
            $action.= "return false;";
            
            $this->setProperty('noresultsbtnaction', $action);
            
            $image = new TImage($this->noResultsButtonActionIcon);
            $image = $image->getContents();

            $btn = new TElement('span');
            $btn->add("{$image} {$this->noResultsButtonActionLabel}");
            $btn->onClick = $action;
            $btn->class = 'btn '. $this->noResultsButtonActionBtnClass;
            $btn->id = $this->id.'_btn';
            $btn->{"data-noresultsbtnprops"} = Crypt::encryptString( base64_encode(serialize($obj)));

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
            $obj->column = $this->mask ?? $this->column;
            $obj->orderColumn = $this->dorderColumn;
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
            $btn->name = $this->name;

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
    }
    
    /**
     * Dynamically reloads the select widget options.
     *
     * @param string  $formname    The name of the form containing the widget.
     * @param string  $name        The name of the widget.
     * @param array   $items       The new list of items.
     * @param boolean $startEmpty  Whether to start with an empty option.
     */
    public static function reload($formname, $name, $items, $startEmpty = FALSE)
    {
        $code = "tselect_clear('{$formname}', '{$name}'); ";
        if ($startEmpty)
        {
            $code .= "tselect_add_option('{$formname}', '{$name}', '', ''); ";
        }
        
        if ($items)
        {
            foreach ($items as $key => $value)
            {
                $value = htmlspecialchars( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                $code .= "tselect_add_option('{$formname}', '{$name}', '{$key}', '{$value}'); ";
            }
        }
        TScript::create($code);
    }
    
    /**
     * Enables the select widget field.
     *
     * @param string $form_name The name of the form.
     * @param string $field     The name of the field to enable.
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tselect_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables the select widget field.
     *
     * @param string $form_name The name of the form.
     * @param string $field     The name of the field to disable.
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tselect_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clears the select widget field.
     *
     * @param string $form_name The name of the form.
     * @param string $field     The name of the field to clear.
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tselect_clear_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Renders the select widget options.
     *
     * @param boolean $with_titles Whether to include option titles.
     */
    protected function renderItems( $with_titles = true )
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
                    if ($with_titles)
                    {
                        $option->{'title'} = $item;  // define the title
                    }
                    $option->{'titside'} = 'left';  // define the title side
                    $option->add(htmlspecialchars($item));      // add the item label
                    
                    // verify if this option is selected
                    if ( (is_array($this->value)  AND @in_array($chave, $this->value)) OR
                         (is_scalar($this->value) AND strlen( (string) $this->value ) > 0 AND @in_array($chave, (array) $this->value)))
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
     * Displays the select widget.
     *
     * Configures HTML attributes, applies properties, renders options, and
     * applies JavaScript enhancements if necessary.
     *
     * @throws Exception If the widget is not assigned to a valid form.
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'name'}  = $this->name.'[]';    // tag name
        $this->tag->{'id'}    = $this->id;
        
        $this->setProperty('style', (strstr((string) $this->size, '%') !== FALSE)   ? "width:{$this->size}"    : "width:{$this->size}px",   false); //aggregate style info
        
        if (!empty($this->height))
        {
            $this->setProperty('style', (strstr($this->height, '%') !== FALSE) ? "height:{$this->height}" : "height:{$this->height}px", false); //aggregate style info
        }
        
        // verify whether the widget is editable
        if (parent::getEditable())
        {
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
            
            if (isset($this->changeFunction))
            {
                $this->setProperty('changeaction', $this->changeFunction, FALSE);
                $this->setProperty('onChange', $this->changeFunction, FALSE);
            }
        }
        else
        {
            // make the widget read-only
            $this->tag->{'onclick'} = "return false;";
            $this->tag->{'style'}  .= ';pointer-events:none';
            $this->tag->{'class'}   = 'tselect_disabled'; // CSS
        }

        if ($this->searchable)
        {
            $this->tag->{'role'} = 'tselectsearch';
        }

        // shows the widget
        $this->renderItems( $this->withTitles );
        $this->tag->show();

        if ($this->searchable)
        {
            $select = AdiantiCoreTranslator::translate('Select');
            TScript::create("tselect_enable_search('#{$this->id}', '{$select}')");
            
            if (!parent::getEditable())
            {
                TScript::create(" tmultisearch_disable_field( '{$this->formName}', '{$this->name}'); ");
            }
        }
    }
}
