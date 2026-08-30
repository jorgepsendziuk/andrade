<?php
namespace Adianti\Wrapper;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TCheckButton;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TSlider;
use Adianti\Widget\Form\THtmlEditor;
use Adianti\Widget\Form\AdiantiFormInterface;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TSeekButton;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Form\TCheckGroup;
use Adianti\Widget\Form\TMultiEntry;
use Adianti\Widget\Util\TActionLink;
use Adianti\Widget\Wrapper\TDBRadioGroup;
use Adianti\Widget\Wrapper\TDBCheckGroup;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TArrowStep;
use Adianti\Widget\Form\TCheckList;
use BComboNoResultsService;
use Mad\Widget\Form\BDateRange;

use stdClass;
use Exception;

/**
 * Bootstrap form builder for Adianti Framework
 *
 * A Bootstrap form builder for the Adianti Framework.
 * Provides a structured way to create and manage forms with Bootstrap styling.
 *
 * @version    7.5
 * @package    wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class BootstrapFormBuilder implements AdiantiFormInterface
{
    private $id;
    private $decorated;
    private $tabcontent;
    private $tabcurrent;
    private $current_page;
    private $properties;
    private $actions;
    private $header_actions;
    private $title;
    private $column_classes;
    private $header_properties;
    private $padding;
    private $name;
    private $tabFunction;
    private $tabAction;
    private $field_sizes;
    private $automatic_aria;
    private $hidden;
    private $panel;
    private $client_validation;
    private $csrf_validation;
    
    /**
     * Constructor method
     *
     * Initializes the form builder with a given name.
     *
     * @param string $name The name of the form (default: 'my_form')
     */
    public function __construct($name = 'my_form')
    {
        $this->decorated         = new TForm($name);
        $this->tabcurrent        = NULL;
        $this->current_page      = 0;
        $this->header_actions    = array();
        $this->actions           = array();
        $this->padding           = 10;
        $this->name              = $name;
        $this->id                = 'bform_' . mt_rand(1000000000, 1999999999);
        $this->field_sizes       = null;
        $this->automatic_aria    = false;
        $this->client_validation = false;
        $this->csrf_validation   = false;
        $this->hidden            = false;
        
        $this->column_classes = array();
        $this->column_classes[1]  = ['col-sm-12'];
        $this->column_classes[2]  = ['col-sm-4 col-lg-2', 'col-sm-8 col-lg-10'];
        $this->column_classes[3]  = ['col-sm-4 col-lg-2', 'col-sm-4','col-sm-2'];
        $this->column_classes[4]  = ['col-sm-4 col-lg-2', 'col-sm-8 col-lg-4','col-sm-4 col-lg-2', 'col-sm-8 col-lg-4'];
        $this->column_classes[5]  = ['col-sm-2', 'col-sm-2','col-sm-2', 'col-sm-2', 'col-sm-2'];
        $this->column_classes[6]  = ['col-sm-2', 'col-sm-2','col-sm-2', 'col-sm-2', 'col-sm-2', 'col-sm-2'];
        $this->column_classes[7]  = ['col-sm-1', 'col-sm-1','col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1'];
        $this->column_classes[8]  = ['col-sm-1', 'col-sm-1','col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1'];
        $this->column_classes[9]  = ['col-sm-1', 'col-sm-1','col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1'];
        $this->column_classes[10] = ['col-sm-1', 'col-sm-1','col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1'];
        $this->column_classes[11] = ['col-sm-1', 'col-sm-1','col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1'];
        $this->column_classes[12] = ['col-sm-1', 'col-sm-1','col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1', 'col-sm-1'];
    }
    
    /**
     * Enable or disable client-side validation
     *
     * @param bool $bool Whether to enable client-side validation
     */
    public function setClientValidation($bool)
    {
        $this->client_validation = $bool;
    }
    
    /**
     * Enable CSRF protection for the form
     *
     * Generates a CSRF token and stores it in the session for validation.
     */
    public function enableCSRFProtection()
    {
        $this->csrf_validation = true;
        
        TSession::setValue('csrf_token_'.$this->name.'_before', TSession::getValue('csrf_token_'.$this->name));
        TSession::setValue('csrf_token_'.$this->name, bin2hex(random_bytes(32)));
    }
    
    /**
     * Add an expand button to toggle form visibility
     *
     * @param string|null $label        The button label (default: 'Expand')
     * @param string|null $icon         The button icon (default: 'fa:search')
     * @param bool        $start_hidden Whether the form starts hidden (default: true)
     *
     * @return TButton The created button
     */
    public function addExpandButton( $label = null, $icon = null, $start_hidden = true)
    {
        $form_name = $this->getName();
        
        $button = new TButton($form_name.'_show_hide');
        $button->{'class'} = 'btn btn-info btn-sm active';
        $button->setLabel($label ?? AdiantiCoreTranslator::translate('Expand'));
        $button->setImage($icon ?? 'fa:search');
        $button->addFunction("\$('[name={$form_name}]').slideToggle('fast'); $(this).toggleClass( 'active' )");
        $this->addHeaderWidget($button);
        
        if ($start_hidden)
        {
            $this->decorated->setProperty('style', 'display:none');
        }
        
        return $button;
    }
    
    /**
     * Hide the form
     */
    public function hide()
    {
        $this->hidden = true;
    }
    
    /**
     * Enable automatic generation of ARIA labels
     */
    public function generateAria()
    {
        $this->automatic_aria = true;
    }
    
    /**
     * Get the form ID
     *
     * @return string The unique form ID
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set field size configuration
     *
     * @param mixed $size The size configuration for fields
     */
    public function setFieldSizes($size)
    {
        $this->field_sizes = $size;
    }
    
    /**
     * Set the form title
     *
     * @param string $title The form title
     */
    public function setFormTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * Set the padding for form elements
     *
     * @param int $padding The padding size in pixels
     */
    public function setPadding($padding)
    {
        $this->padding = $padding;
    }
    
    /**
     * Set the current page of a multi-page form
     *
     * @param int $i The page index (starting from 0)
     */
    public function setCurrentPage($i)
    {
        $this->current_page = $i;
    }
    
    /**
     * Redirect method calls to the decorated object
     *
     * @param string $method     The method name
     * @param array  $parameters The method parameters
     *
     * @return mixed The result of the called method
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->decorated, $method),$parameters);
    }
    
    /**
     * Redirect property assignments to the decorated object
     *
     * @param string $property The property name
     * @param mixed  $value    The value to assign
     */
    public function __set($property, $value)
    {
        return $this->decorated->$property = $value;
    }
    
    /**
     * Set a property for the form
     *
     * @param string $name  The property name
     * @param mixed  $value The property value
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }
    
    /**
     * Set a property for the form header
     *
     * @param string $name  The property name
     * @param mixed  $value The property value
     */
    public function setHeaderProperty($name, $value)
    {
        $this->header_properties[$name] = $value;
    }
    
    /**
     * Set the form name
     *
     * @param string $name The form name
     */
    public function setName($name)
    {
        return $this->decorated->setName($name);
    }
    
    /**
     * Get the form name
     *
     * @return string The form name
     */
    public function getName()
    {
        return $this->decorated->getName();
    }
    
    /**
     * Add a field to the form
     *
     * @param AdiantiWidgetInterface $field The field to add
     */
    public function addField(AdiantiWidgetInterface $field)
    {
        return $this->decorated->addField($field);
    }
    
    /**
     * Remove a field from the form
     *
     * @param AdiantiWidgetInterface $field The field to remove
     */
    public function delField(AdiantiWidgetInterface $field)
    {
        return $this->decorated->delField($field);
    }
    
    /**
     * Set multiple fields in the form
     *
     * @param array $fields The array of fields to set
     */
    public function setFields($fields)
    {
        return $this->decorated->setFields($fields);
    }
    
    /**
     * Get a field by name
     *
     * @param string $name The field name
     *
     * @return TField|null The field instance or null if not found
     */
    public function getField($name)
    {
        return $this->decorated->getField($name);
    }
    
    /**
     * Get all fields in the form
     *
     * @return array The array of form fields
     */
    public function getFields()
    {
        return $this->decorated->getFields();
    }
    
    /**
     * Clear the form fields
     *
     * @param bool $keepDefaults Whether to keep default values (default: false)
     */
    public function clear( $keepDefaults = FALSE )
    {
        return $this->decorated->clear( $keepDefaults );
    }
    
    /**
     * Set data in the form
     *
     * @param object $object The data object
     */
    public function setData($object)
    {
        return $this->decorated->setData($object);
    }
    
    /**
     * Get form data as an object
     *
     * @param string $class The class name of the return object (default: 'StdClass')
     *
     * @return object The form data
     */
    public function getData($class = 'StdClass')
    {
        return $this->decorated->getData($class);
    }
    
    /**
     * Get the form actions
     *
     * @return array The array of action buttons
     */
    public function getActions()
    {
        return $this->actions;
    }
    
    /**
     * Validate the form data
     *
     * Performs CSRF validation if enabled.
     *
     * @throws Exception If CSRF validation fails
     */
    public function validate()
    {
        if ($this->csrf_validation)
        {
    		if (!hash_equals($_POST['csrf_token'], TSession::getValue('csrf_token_'.$this->name.'_before')))
    		{
    			throw new Exception(AdiantiCoreTranslator::translate('CSRF Error'));
    		}
        }
        
        return $this->decorated->validate();
    }
    
    /**
     * Append a new tab/page to the form
     *
     * @param string $title The title of the tab
     */
    public function appendPage($title)
    {
        $this->tabcurrent = $title;
        $this->tabcontent[$title] = array();
    }
    
    /**
     * Set a JavaScript function for handling tab clicks
     *
     * @param string $function The JavaScript function
     */
    public function setTabFunction($function)
    {
        $this->tabFunction = $function;
    }
    
    /**
     * Define the action for a Notebook tab
     *
     * @param TAction $action The action object
     */
    public function setTabAction(TAction $action)
    {
        $this->tabAction = $action;
    }
    
    /**
     * Add multiple fields to the form
     *
     * @param mixed ...$fields The fields to add
     */
    public function addFields()
    {
        $args = func_get_args();
        
        $this->validateInlineArguments($args, 'addFields');
        
        // object that represents a row
        $row = new stdClass;
        $row->{'content'} = $args;
        $row->{'type'}    = 'fields';
        
        if ($args)
        {
            $this->tabcontent[$this->tabcurrent][] = $row;
            
            foreach ($args as $slot)
            {
                foreach ($slot as $content)
                {
                    if ($content instanceof AdiantiWidgetInterface)
                    {
                        $this->decorated->addField($content);
                    }
                    
                    if ($content instanceof BootstrapFormBuilder)
                    {
                        if ($content->getFields())
                        {
                            foreach ($content->getFields() as $field)
                            {
                                if($field instanceof BDateRange && !empty($this->decorated->getFields()[$field->getName()]) && empty($field->getNameEnd()) )
                                {
                                    continue;
                                }
                                else
                                {
                                    $this->decorated->addField($field);
                                }
                            }
                        }
                        
                        $content->setTagName('div');
                    }
                }
            }
        }
        
        // return, because the user may fill aditional attributes
        return $row;
    }
    
    /**
     * Add content to the form
     *
     * @param mixed ...$content The content to add
     */
    public function addContent()
    {
        $args = func_get_args();
        
        $this->validateInlineArguments($args, 'addContent');
        
        // object that represents a row
        $row = new stdClass;
        $row->{'content'} = $args;
        $row->{'type'}    = 'content';
        
        if ($args)
        {
            $this->tabcontent[$this->tabcurrent][] = $row;
            
            foreach ($args as $arg)
            {
                foreach ($arg as $slot)
                {
                    if (!empty($slot) && $slot instanceof BootstrapFormBuilder)
                    {
                        if ($slot->getFields())
                        {
                            foreach ($slot->getFields() as $field)
                            {
                                $this->addField($field);
                            }
                        }
                        $slot->setTagName('div');
                    }
                }
            }
        }
        
        // return, because the user may fill aditional attributes
        return $row;
    }
    
    /**
     * Validate the argument type for field/content addition methods
     *
     * @param array  $args   The arguments array
     * @param string $method The method name
     *
     * @throws Exception If an argument is not an array
     */
    public function validateInlineArguments($args, $method)
    {
        if ($args)
        {
            foreach ($args as $arg)
            {
                if (!is_array($arg))
                {
                    throw new Exception(AdiantiCoreTranslator::translate('Method ^1 must receive a parameter of type ^2', $method, 'Array'));
                }
            }
        }
    }
    
    /**
     * Add an action button to the form
     *
     * @param string  $label  The button label
     * @param TAction $action The action to execute
     * @param string  $icon   The button icon (default: 'fa:save')
     * @param string|null $name Optional button name
     *
     * @return TButton The created button
     */
    public function addAction($label, TAction $action, $icon = 'fa:save', $name = null)
    {
        $label_info = ($label instanceof TLabel) ? $label->getValue() : $label;
        $name   = $name ?? 'btn_'.strtolower(str_replace(' ', '_', $label_info));
        $button = new TButton($name);
        $this->decorated->addField($button);
        
        // define the button action
        $button->setAction($action, $label);
        $button->setImage($icon);
        
        $this->actions[] = $button;
        return $button;
    }
    
    /**
     * Add an action link button
     *
     * @param string  $label  The button label
     * @param TAction $action The action to execute
     * @param string  $icon   The button icon (default: 'fa:save')
     *
     * @return TActionLink The created action link
     */
    public function addActionLink($label, TAction $action, $icon = 'fa:save')
    {
        $label_info = ($label instanceof TLabel) ? $label->getValue() : $label;
        $button = new TActionLink($label_info, $action, null, null, null, $icon);
        $button->{'class'} = 'btn btn-sm btn-default';
        $this->actions[] = $button;
        return $button;
    }
    
    /**
     * Add an action button to the form header
     *
     * @param string  $label  The button label
     * @param TAction $action The action to execute
     * @param string  $icon   The button icon (default: 'fa:save')
     *
     * @return TButton The created button
     */
    public function addHeaderAction($label, TAction $action, $icon = 'fa:save')
    {
        $label_info = ($label instanceof TLabel) ? $label->getValue() : $label;
        $name   = strtolower(str_replace(' ', '_', $label_info));
        $button = new TButton($name);
        $this->decorated->addField($button);
        
        // define the button action
        $button->setAction($action, $label);
        $button->setImage($icon);
        
        $this->header_actions[] = $button;
        return $button;
    }
    
    /**
     * Add a widget to the form header
     *
     * @param mixed $widget The widget to add
     *
     * @return mixed The added widget
     */
    public function addHeaderWidget($widget)
    {
        $this->header_actions[] = $widget;
        return $widget;
    }
    
    /**
     * Add a widget to the form footer
     *
     * @param mixed $widget The widget to add
     *
     * @return mixed The added widget
     */
    public function addFooterWidget($widget)
    {
        $this->actions[] = $widget;
        return $widget;
    }
    
    /**
     * Add an action link button to the form header
     *
     * @param string  $label  The button label
     * @param TAction $action The action to execute
     * @param string  $icon   The button icon (default: 'fa:save')
     *
     * @return TActionLink The created header action link
     */
    public function addHeaderActionLink($label, TAction $action, $icon = 'fa:save')
    {
        $label_info = ($label instanceof TLabel) ? $label->getValue() : $label;
        $button = new TActionLink($label_info, $action, null, null, null, $icon);
        $button->{'class'} = 'btn btn-sm btn-default';
        $this->header_actions[] = $button;
        return $button;
    }
    
    /**
     * Add a custom button to the form
     *
     * @param string $label  The button label
     * @param string $action The JavaScript action to execute
     * @param string $icon   The button icon (default: 'fa:save')
     *
     * @return TButton The created button
     */
    public function addButton($label, $action, $icon = 'fa:save')
    {
        $label_info = ($label instanceof TLabel) ? $label->getValue() : $label;
        $name   = strtolower(str_replace(' ', '_', $label_info));
        $button = new TButton($name);
        if (strstr($icon, '#') !== FALSE)
        {
            $pieces = explode('#', $icon);
            $color = $pieces[1];
            $button->{'style'} = "color: #{$color}";
        }
        
        // define the button action
        $button->addFunction($action);
        $button->setLabel($label);
        $button->setImage($icon);
        
        $this->actions[] = $button;
        return $button;
    }
    
    /**
     * Remove all action buttons from the form
     */
    public function delActions()
    {
        if ($this->actions)
        {
            foreach ($this->actions as $key => $button)
            {
                unset($this->actions[$key]);
            }
        }
    }
    
    /**
     * Get an array of action buttons in the form
     *
     * @return array The array of action buttons
     */
    public function getActionButtons()
    {
        return $this->actions;
    }
    
    /**
     * Set column classes for form layout
     *
     * @param int   $key     The column index
     * @param array $classes The array of CSS classes
     */
    public function setColumnClasses($key, $classes)
    {
        $this->column_classes[$key] = $classes;
    }
    
    /**
     * Render the form
     */
    public function render()
    {
        if ($this->hidden)
        {
            return;
        }
        
        if ($this->csrf_validation)
        {
            $csrf_token = new THidden('csrf_token');
            $this->addFields([$csrf_token]);
            $csrf_token->setValue(TSession::getValue('csrf_token_'.$this->name));
            $this->decorated->silentField('csrf_token');
        }

        if (BComboNoResultsService::getPropertiesJson($_REQUEST))
        {
            $noresultsbtnprops = new THidden('form_noresultsbtnprops');
            $this->addFields([$noresultsbtnprops]);
            $noresultsbtnprops->setValue(BComboNoResultsService::getPropertiesJson($_REQUEST));
            $this->decorated->silentField('form_noresultsbtnprops');
        }
        
        $this->decorated->{'class'} = 'form-horizontal';
        $this->decorated->{'type'}  = 'bootstrap';
        
        $panel = new TElement('div');
        $panel->{'class'}  = 'card panel';
        $panel->{'style'}  = 'width: 100%';
        $panel->{'widget'} = 'bootstrapformbuilder';
        $panel->{'form'}   = $this->name;
        $panel->{'id'}     = $this->id;
        
        if ($this->properties)
        {
            foreach ($this->properties as $property => $value)
            {
                $panel->$property = $value;
            }
        }
        
        if (!empty($this->title) || count($this->header_actions) > 0)
        {
            $heading = new TElement('div');
            $heading->{'class'} = 'card-header panel-heading';
            $heading->add(TElement::tag('div', $this->title, ['class'=>'panel-title card-title']));
            
            if ($this->header_properties)
            {
                foreach ($this->header_properties as $property => $value)
                {
                    if (isset($heading->$property))
                    {
                        $heading->$property .= ' ' . $value;
                    }
                    else
                    {
                        $heading->$property = $value;
                    }
                }
            }
            
            if ($this->header_actions)
            {
                $title_actions = new TElement('div');
                $title_actions->{'class'} = 'header-actions';
                $title_actions->{'style'} = 'float:right';
                $heading->add($title_actions);
                foreach ($this->header_actions as $action_button)
                {
                    $title_actions->add($action_button);
                }
            }
            $panel->add($heading);
        }
        
        $body = new TElement('div');
        $body->{'class'} = 'card-body panel-body';
        $body->{'style'} = 'width: 100%';
        
        $panel->add($this->decorated);
        $this->decorated->add($body);
        
        if ($this->tabcurrent !== null)
        {
            $tabs = new TElement('ul');
            $tabs->{'class'} = 'nav nav-tabs';
            $tabs->{'role'}  = 'tablist';
            
            $tab_counter = 0;
            foreach ($this->tabcontent as $tab => $rows)
            {
                $class = ($tab_counter == $this->current_page) ? 'active' : '';
                
                $tab_li = new TElement('li');
                $tab_li->{'role'}  = 'presentation';
                $tab_li->{'class'} = $class . " nav-item";

                $tab_link = new TElement('a');
                $tab_link->{'href'} = "#tab_{$this->id}_{$tab_counter}";
                $tab_link->{'role'} = 'tab';
                $tab_link->{'data-toggle'} = 'tab';
                $tab_link->{'aria-expanded'} = 'true';
                $tab_link->{'class'} = "nav-link " . $class;
                if ($this->tabFunction)
                {
                    $tab_link->{'onclick'} = $this->tabFunction;
                    $tab_link->{'data-current_page'} = $tab_counter;
                }
                
                if ($this->tabAction)
                {
                    $this->tabAction->setParameter('current_page', $tab_counter);
                    $string_action = $this->tabAction->serialize(FALSE);
                    $tab_link->{'onclick'} = "__adianti_ajax_exec('$string_action')";
                }
                
                $tab_li->add($tab_link);
                $tab_link->add( TElement::tag('span', $tab, ['class'=>'tab-name'])); 
                
                $tabs->add($tab_li);
                $tab_counter ++;
            }
            
            $body->add($tabs);
        }
        
        $content = new TElement('div');
        $content->{'class'} = 'tab-content';
        $body->add($content);
        
        $tab_counter = 0;
        if($this->tabcontent !== null)
        {
            foreach ($this->tabcontent as $tab => $rows)
            {
                $tabpanel = new TElement('div');
                $tabpanel->{'role'}  = 'tabpanel';
                $tabpanel->{'class'} = 'tab-pane tabpanel_'.$this->name .' '. ( ($tab_counter == $this->current_page) ? 'active' : '' );
                $tabpanel->{'style'} = 'padding:10px; margin-top: -1px;';
                if ($tab)
                {
                    $tabpanel->{'style'} .= 'border: 1px solid #DDDDDD';
                }
                $tabpanel->{'id'}    = "tab_{$this->id}_{$tab_counter}";
                
                $content->add($tabpanel);
                
                if ($rows)
                {
                    foreach ($rows as $row)
                    {
                        $aria_label  = null;
                        $aria_id     = null;
                        
                        $slots = $row->{'content'};
                        $type  = $row->{'type'};
                        
                        $form_group = new TElement('div');
                        $form_group->{'class'} = 'form-group tformrow row' . ' ' . ( isset($row->{'class'}) ? $row->{'class'} : '' );
                        $tabpanel->add($form_group);
                        $row_visual_widgets = 0;
                        
                        if (isset($row->{'style'}))
                        {
                            $form_group->{'style'} = $row->{'style'};
                        }
                        
                        $slot_counter = count($slots);
                        $row_counter  = 0;
                        
                        foreach ($slots as $slot)
                        {
                            $label_css    = ((count($slots)>1) AND (count($slot)==1) AND $slot[0] instanceof TLabel AND empty($row->layout)) ? ' col-form-label control-label' : '';
                            $column_class = (isset($row->layout) && !empty($row->layout[$row_counter])) ? $row->layout[$row_counter] : (isset($this->column_classes[$slot_counter][$row_counter]) ? $this->column_classes[$slot_counter][$row_counter] : null);
                            $slot_wrapper = new TElement('div');
                            $slot_wrapper->{'class'} = $column_class . ' fb-field-container '.$label_css;
                            $slot_wrapper->{'style'} = 'min-height:26px';
                            $form_group->add($slot_wrapper);
                            
                            // one field per slot do not need to be wrapped
                            if (count($slot)==1)
                            {
                                foreach ($slot as $field)
                                {
                                    $field_wrapper = self::wrapField($field, 'inherit', $this->field_sizes);
                                    
                                    $slot_wrapper->add($field_wrapper);
                                    
                                    if (!$field instanceof THidden)
                                    {
                                        $row_visual_widgets ++;
                                    }
                                    
                                    if ($field instanceof TLabel)
                                    {
                                        $aria_label = $field->getValue();
                                        $aria_id    = $field->getId();
                                    }
                                    
                                    if ($this->automatic_aria && !empty($aria_label) && !$field instanceof TLabel && $field instanceof TField)
                                    {
                                        $field->{'aria-label'} = $aria_label;
                                        $field->{'aria-labelledby'} = $aria_id;
                                    }
                                    
                                    if ($field instanceof TField && $field->isRequired())
                                    {
                                        $field->{'aria-required'} = 'true';
                                    }
                                }
                            }
                            else // more fields must be wrapped
                            {
                                $field_counter = 0;
                                foreach ($slot as $field)
                                {
                                    $field_wrapper = self::wrapField($field, 'inline-block', $this->field_sizes);
                                    
                                    if ( ($field_counter+1 < count($slot)) and (!$field instanceof TDBSeekButton) ) // padding less last element
                                    {
                                        $field_wrapper->{'style'} .= ';padding-right: '.$this->padding.'px;';
                                    }
                                    
                                    $slot_wrapper->add($field_wrapper);
                                    
                                    if (!$field instanceof THidden)
                                    {
                                        $row_visual_widgets ++;
                                    }
                                    
                                    if ($field instanceof TLabel)
                                    {
                                        $aria_label = $field->getValue();
                                        $aria_id    = $field->getId();
                                    }
                                    
                                    if ($this->automatic_aria && !empty($aria_label) && !$field instanceof TLabel && $field instanceof TField)
                                    {
                                        $field->{'aria-label'} = $aria_label;
                                        $field->{'aria-labelledby'} = $aria_id;
                                    }
                                    
                                    if ($field instanceof TField && $field->isRequired())
                                    {
                                        $field->{'aria-required'} = 'true';
                                    }
                                    
                                    $field_counter ++;
                                }
                            }
                            
                            $row_counter ++;
                        }
                        
                        if ($row_visual_widgets == 0)
                        {
                            $form_group->{'style'} = 'display:none';
                        }
                    }
                }
                $tab_counter ++;
            }
        }
        if ($this->actions)
        {
            $footer = new TElement('div');
            $footer->{'class'} = 'panel-footer card-footer';
            $footer->{'style'} = 'width: 100%';
            $this->decorated->add($footer);
            
            foreach ($this->actions as $action_button)
            {
                $footer->add($action_button);
            }
        }
        
        if (!$this->client_validation)
        {
            $this->decorated->setProperty('novalidate','');
        }
        
        $this->panel = $panel;
        return $this->panel;
    }
    
    /**
     * Display the form
     */
    public function show()
    {
        if (empty($this->panel))
        {
            $this->render();
        }
        if (!$this->hidden)
        {
            $this->panel->show();
        }
    }
    
    /**
     * Create a wrapper for a form field
     *
     * @param mixed  $field              The field object
     * @param string $display            The display style (e.g., 'inline-block')
     * @param mixed  $default_field_size The default field size
     *
     * @return TElement The wrapper element
     */
    public static function wrapField($field, $display, $default_field_size = null)
    {
        $object = $field; // BC Compability
        $field_size = (is_object($object) && method_exists($object, 'getSize')) ? $field->getSize() : null;
        $has_underline = (!$field instanceof TLabel && !$field instanceof TRadioGroup && !$field instanceof TCheckGroup && !$field instanceof TButton && !$field instanceof THidden && !$field instanceof TSlider && !$field instanceof TCheckButton);
        $field_wrapper = new TElement('div');
        $field_wrapper->{'class'} = 'fb-inline-field-container ' . ((($field instanceof TField) and ($has_underline)) ? 'form-line' : '');
        $field_wrapper->{'style'} = "display: {$display};vertical-align:top;" . ($display=='inline-block'?'float:left':'');

        if ($field instanceof TField)
        {
            $field_wrapper->{'wrapped-widget'} = $field->{'widget'};
        }
        
        if (!empty($default_field_size))
        {
            if (is_array($field_size))
            {
                $field_size[0] = $default_field_size;
            }
            else
            {
                $field_size = $default_field_size;
            }
        }
        
        if ($field instanceof TField || $field instanceof TCheckList || $field instanceof TTextDisplay)
        {
            if (is_array($field_size))
            {
                $width  = $field_size[0];
                $height = $field_size[1];
                
                if ($width)
                {
                    $field_wrapper->{'style'} .= ( (strpos($width,  '%') !== FALSE) ? ';width: '  . $width  : ';width: '  . $width.'px');
                }
                
                if (!$object instanceof THtmlEditor)
                {
                    if ($height)
                    {
                        $field_wrapper->{'style'} .= ( (strpos($height, '%') !== FALSE) ? ';height: ' . $height : ';height: ' . $height.'px');
                    }
                }
            }
            else if ($field_size && !$object instanceof TRadioGroup AND !$object instanceof TCheckGroup)
            {
                $field_wrapper->{'style'} .= ( (strpos($field_size, '%') !== FALSE) ? ';width: '.$field_size : ';width: '.$field_size.'px');
            }
            else if ($field instanceof TArrowStep)
            {
                $field_wrapper->{'style'} .= ';width:100%';
            }
            
            if (is_callable([$object, 'getAfterElement']) && $object->getAfterElement())
            {
                $field_wrapper->{'style'} .= ';display:inline-table';
            }
        }
        else if ($field instanceof TFormSeparator || $field instanceof BootstrapFormBuilder || $field instanceof TFieldList)
        {
            $field_wrapper->{'style'} .= ';width:100%';
        }
        
        $field_wrapper->add($field);
        
        if ($field instanceof AdiantiWidgetInterface)
        {
            $input_class = 'form-control';
            
            if ($field instanceof TLabel || $field instanceof TCheckButton)
            {
                $input_class = '';
            }
            else if ($field instanceof TButton)
            {
                $input_class = empty($field->{'class'}) ? 'btn btn-default btn-sm' : '';
            }
            
            $field_class = $input_class . ' ' . ( isset($field->{'class'}) ? $field->{'class'} : '' );
            
            if ($field instanceof TField && trim($field_class))
            {
                $field->{'class'} = $field_class;
            }
        }
        
        if (is_object($object) && (method_exists($object, 'setSize')))
        {
            if (in_array($object->getProperty('widget'), ['tmultisearch', 'tdbmultisearch', 'thtmleditor', 'tmultientry']))
            {
                $object->setSize('100%', $field_size[1] - 3);
            }
            else if ( ($field_size) AND !($object instanceof TRadioGroup || $object instanceof TCheckGroup))
            {
                $object->setSize('100%', '100%');
            }
        }
        
        return $field_wrapper;
    }
    
    /**
     * Show a form field using JavaScript
     *
     * @param string  $form  The form name
     * @param string  $field The field name
     * @param int     $speed The animation speed (default: 0)
     */
    public static function showField($form, $field, $speed = 0)
    {
        TScript::create("tform_show_field('{$form}', '{$field}', {$speed})");
    }
    
    /**
     * Hide a form field using JavaScript
     *
     * @param string  $form  The form name
     * @param string  $field The field name
     * @param int     $speed The animation speed (default: 0)
     */
    public static function hideField($form, $field, $speed = 0)
    {
        TScript::create("tform_hide_field('{$form}', '{$field}', {$speed})");
    }
    
    /**
     * Convert the object into a string
     *
     * @return string The form content as a string
     */
    public function __toString()
    {
        return $this->getContents();
    }
    
    /**
     * Get the form content as a string
     *
     * @return string The HTML representation of the form
     */
    public function getContents()
    {
        ob_start();
        $this->show();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
