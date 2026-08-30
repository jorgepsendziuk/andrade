<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TImage;

use Exception;

/**
 * Button Widget
 *
 * This class represents a button component with support for actions, images, labels, and additional properties.
 * It can be used within forms and supports JavaScript functions.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TButton extends TField implements AdiantiWidgetInterface
{
    private $action;
    private $image;
    private $functions;
    private $tagName;
    protected $properties;
    protected $label;
    protected $formName;
    
    /**
     * Creates a button with an icon and an action.
     *
     * @param string   $name     Button name
     * @param callable $callback Callback function for the button action
     * @param string   $label    Button label
     * @param string   $image    Path to the button icon
     *
     * @return TButton The created button instance
     */
    public static function create($name, $callback, $label, $image)
    {
        $button = new TButton( $name );
        $button->setAction(new TAction( $callback ), $label);
        $button->setImage( $image );
        return $button;
    }
    
    /**
     * Adds a CSS class to the button.
     *
     * @param string $class CSS class name(s) to be added
     */
    public function addStyleClass($class)
    {
        $classes = ['btn-primary', 'btn-secondary', 'btn-success', 'btn-danger', 'btn-warning', 'btn-info', 'btn-light', 'btn-dark', 'btn-link', 'btn-default'];
        $found   = false;
        
        foreach ($classes as $btnClass)
        {
            if (strpos($class, $btnClass) !== false)
            {
                $found = true;
            }
        }
        
        $this->{'class'} = 'btn '. ($found  ? '' : 'btn-default '). $class;
    }
    
    /**
     * Defines the action of the button.
     *
     * @param TAction     $action The TAction object representing the button action
     * @param string|null $label  The button label (optional)
     */
    public function setAction(TAction $action, $label = NULL)
    {
        $this->action = $action;
        $this->label  = $label;
    }
    
    /**
     * Returns the button's action.
     *
     * @return TAction|null The TAction object assigned to the button, or null if no action is set
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Sets the tag name for the button.
     *
     * @param string $name The tag name to be used (e.g., 'button', 'a', 'div')
     */
    public function setTagName($name)
    {
        $this->tagName = $name;
    }
    
    /**
     * Sets the icon/image of the button.
     *
     * @param string $image Path to the image file
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
    
    /**
     * Sets the label of the button.
     *
     * @param string $label The button label text
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    /**
     * Returns the button label.
     *
     * @return string|null The label text or null if not set
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Adds a JavaScript function to be executed when the button is clicked.
     *
     * @param string $function JavaScript function code snippet
     */
    public function addFunction($function)
    {
        if ($function)
        {
            $this->functions = $function.';';
        }
    }
    
    /**
     * Sets a custom property for the button.
     *
     * @param string  $name    Property name
     * @param mixed   $value   Property value
     * @param boolean $replace Whether to replace an existing property (default: true)
     */
    public function setProperty($name, $value, $replace = TRUE)
    {
        $this->properties[$name] = $value;
    }
    
    /**
     * Retrieves a property value of the button.
     *
     * @param string $name Property name
     *
     * @return mixed|null The property value or null if not set
     */
    public function getProperty($name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }
    
    /**
     * Enables the button in a specific form.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the button field
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tbutton_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables the button in a specific form.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the button field
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tbutton_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Renders the button on the screen.
     *
     * This method generates the button element, applies the defined properties, and sets the action or JavaScript function.
     *
     * @throws Exception If the form name is not set when an action is assigned to the button
     */
    public function show()
    {
        if ($this->action)
        {
            if (empty($this->formName))
            {
                $label = ($this->label instanceof TLabel) ? $this->label->getValue() : $this->label;
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $label, 'TForm::setFields()') );
            }
            
            if($this->action->isHidden())
            {
                return '';
            }

            // get the action as URL
            $url = $this->action->serialize(FALSE);
            if ($this->action->isStatic())
            {
                $url .= '&static=1';
            }
            $url = htmlspecialchars($url);
            $wait_message = AdiantiCoreTranslator::translate('Loading');
            // define the button's action (ajax post)
            $action = "Adianti.waitMessage = '$wait_message';";
            $action.= "{$this->functions}";
            $action.= "__adianti_post_data('{$this->formName}', '{$url}');";
            $action.= "return false;";
                        
            $button = new TElement( !empty($this->tagName)? $this->tagName : 'button' );
            $button->{'id'}      = 'tbutton_'.$this->name;
            $button->{'name'}    = $this->name;
            $button->{'class'}   = 'btn btn-default btn-sm';
            $button->{'onclick'} = $action;
            $action = '';

            if($this->action->isDisabled())
            {
                $button->onclick = '';
                $button->disabled = 'disabled';
            }
        }
        else
        {
            $action = $this->functions;
            // creates the button using a div
            $button = new TElement( !empty($this->tagName)? $this->tagName : 'div' );
            $button->{'id'}      = 'tbutton_'.$this->name;
            $button->{'name'}    = $this->name;
            $button->{'class'}   = 'btn btn-default btn-sm';
            $button->{'onclick'} = $action;
        }
        
        if ($this->properties)
        {
            foreach ($this->properties as $property => $value)
            {
                $button->$property = $value;
            }
        }

        $span = new TElement('span');
        if ($this->image)
        {
            $image = new TImage($this->image);
            if (!empty($this->label))
            {
                $image->{'style'} .= ';padding-right:4px';
            }
            $span->add($image);
        }
        
        if ($this->label)
        {
            $span->add($this->label);
            $button->{'aria-label'} = $this->label;
        }
        
        $button->add($span);
        $button->show();
    }
}
