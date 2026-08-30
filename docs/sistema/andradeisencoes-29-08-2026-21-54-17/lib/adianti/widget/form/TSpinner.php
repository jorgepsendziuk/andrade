<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TField;

use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Spinner Widget (also known as spin button)
 *
 * This widget provides a numeric input field with increment and decrement buttons.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSpinner extends TField implements AdiantiWidgetInterface
{
    private $min;
    private $max;
    private $step;
    private $exitAction;
    private $exitFunction;
    protected $id;
    protected $formName;
    protected $value;
    protected $stepper;
    
    /**
     * Class Constructor
     *
     * Initializes the spinner widget with a unique identifier and default properties.
     *
     * @param string $name Name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'tspinner_'.mt_rand(1000000000, 1999999999);
        $this->tag->{'widget'} = 'tspinner';
        $this->stepper = false;
    }
    
    /**
     * Define the field's range
     *
     * Sets the minimum, maximum, and step values for the spinner. 
     * Throws an exception if the step value is zero.
     *
     * @param float|int $min Minimal value allowed
     * @param float|int $max Maximal value allowed
     * @param float|int $step Step increment value
     *
     * @throws Exception If the step value is zero
     */
    public function setRange($min, $max, $step)
    {
        $this->min = $min;
        $this->max = $max;
        $this->step = $step;
        
        if ($step == 0)
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $step, 'setRange'));
        }
        
        if (is_int($step) AND $this->getValue() % $step !== 0)
        {
            parent::setValue($min);
        }
    }
    
    /**
     * Define the action to be executed when the user leaves the form field
     *
     * The action must be static; otherwise, an exception will be thrown.
     *
     * @param TAction $action Action object to be executed on exit
     *
     * @throws Exception If the action is not static
     */
    function setExitAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->exitAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Enable the field
     *
     * Allows user interaction with the specified field.
     *
     * @param string $form_name Name of the form
     * @param string $field Name of the field
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tspinner_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the field
     *
     * Prevents user interaction with the specified field.
     *
     * @param string $form_name Name of the form
     * @param string $field Name of the field
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tspinner_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Set exit function
     *
     * Defines a JavaScript function to be executed when the user exits the field.
     *
     * @param string $function JavaScript function to be executed
     */
    public function setExitFunction($function)
    {
        $this->exitFunction = $function;
    }
    
    /**
     * Enable stepper buttons
     *
     * Activates the increment and decrement buttons for the spinner widget.
     */
    public function enableStepper()
    {
        $this->stepper = TRUE;
    }

    /**
     * Disable stepper buttons
     *
     * Deactivates the increment and decrement buttons for the spinner widget.
     */
    public function disableStepper()
    {
        $this->stepper = FALSE;
    }
    
    /**
     * Render the widget on the screen
     *
     * Generates the HTML and JavaScript necessary for displaying the spinner widget.
     * Configures the properties such as min, max, step values, and exit action.
     *
     * @throws Exception If the form name is not set when an exit action is defined
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'name'}  = $this->name;    // TAG name
        $this->tag->{'value'} = $this->value;   // TAG value
        $this->tag->{'type'}  = 'text';         // input type
        $this->tag->{'data-min'} = $this->min;
        $this->tag->{'data-max'} = $this->max;
        $this->tag->{'data-step'} = $this->step;
        
        if ($this->step > 0 and $this->step < 1)
        {
            $this->tag->{'data-rule'} = 'currency';
        }
        
        $this->setProperty('style', "text-align:right", false); //aggregate style info
        
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
            $this->setProperty('relwidth', "{$this->size}", false); //aggregate style info
        }
        else
        {
            $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
        }
        
        if ($this->id)
        {
            $this->tag->{'id'}  = $this->id;
        }
        
        if (isset($this->exitAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->exitAction->serialize(FALSE);
            $this->setProperty('exitaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
        }
        
        $exit_action = "function() {}";
        if (isset($this->exitFunction))
        {
            $exit_action = "function() { {$this->exitFunction} }";
        }
        
        if (!parent::getEditable())
        {
            $this->tag->{'tabindex'} = '-1';
        }

        $stepper = $this->stepper ? 'true' : 'false';

        $this->tag->show();
        TScript::create(" tspinner_start( '#{$this->id}', $exit_action, {$stepper}); ");
        
        // verify if the widget is non-editable
        if (!parent::getEditable())
        {
            self::disableField($this->formName, $this->name);
        }
    }
    
    /**
     * Set the value
     *
     * Assigns a numeric value to the spinner widget.
     *
     * @param float|int $value The value to be set
     */
    public function setValue($value)
    {
        parent::setValue( (float) $value);
    }

    /**
     * Retrieves the value of the field from a form submission (POST request).
     *
     * @return string|float The submitted value, formatted based on mask settings.
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            return $_POST[$name];
        }
        else
        {
            return '';
        }
    }
}
