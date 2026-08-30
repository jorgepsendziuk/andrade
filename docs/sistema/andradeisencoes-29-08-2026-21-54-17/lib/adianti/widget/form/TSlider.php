<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Control\TAction;

/**
 * Slider Widget
 *
 * This class represents a slider input field, allowing users to select a numeric value within a defined range.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSlider extends TField implements AdiantiWidgetInterface
{
    protected $id;
    private $min;
    private $max;
    private $step;
    private $changeAction;
    
    /**
     * Class Constructor
     *
     * Initializes the slider widget, setting a unique ID and configuring it as a slider.
     *
     * @param string $name The name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'tslider_'.mt_rand(1000000000, 1999999999);
        $this->tag->{'widget'} = 'tslider';
    }
    
    /**
     * Define the field's range
     *
     * Sets the minimum, maximum, and step values for the slider.
     *
     * @param int|float $min  The minimal value of the slider
     * @param int|float $max  The maximal value of the slider
     * @param int|float $step The step increment of the slider
     */
    public function setRange($min, $max, $step)
    {
        $this->min = $min;
        $this->max = $max;
        $this->step = $step;
        $this->value = $min;
    }
    
    /**
     * Enable the field
     *
     * Enables the slider input field dynamically via JavaScript.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field     The name of the field to enable
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tslider_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the field
     *
     * Disables the slider input field dynamically via JavaScript.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field     The name of the field to disable
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tslider_disable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Define the action to be executed when the user changes the form field
     *
     * The action must be static; otherwise, an exception will be thrown.
     *
     * @param TAction $action Action object to be executed on exit
     *
     * @throws Exception If the action is not static
     */
    function setChangeAction(TAction $action)
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
     * Shows the widget on the screen
     *
     * Renders the slider input field with the specified properties and initializes it using JavaScript.
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'name'}  = $this->name;    // TAG name
        $this->tag->{'value'} = $this->value;   // TAG value
        $this->tag->{'type'}  = 'range';         // input type
        $this->tag->{'min'}   = $this->min;
        $this->tag->{'max'}   = $this->max;
        $this->tag->{'step'}  = $this->step;
        
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
        }
        else
        {
            $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
        }
        
        if ($this->id)
        {
            $this->tag->{'id'} = $this->id;
        }

        if (isset($this->changeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->changeAction->serialize(FALSE);
            $this->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
        }
        
        $this->tag->{'readonly'} = "1";
        $this->tag->show();
        
        TScript::create(" tslider_start( '#{$this->id}'); ");
        
        if (!parent::getEditable())
        {
            self::disableField($this->formName, $this->name);
        }
    }
}
