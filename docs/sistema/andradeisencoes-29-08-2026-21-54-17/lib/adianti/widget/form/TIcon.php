<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;
use Adianti\Control\TAction;

/**
 * Icon Widget
 *
 * A form widget that allows users to select icons.
 * It extends TEntry and implements AdiantiWidgetInterface.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TIcon extends TEntry implements AdiantiWidgetInterface
{
    protected $id;
    protected $changeFunction;
    protected $formName;
    protected $name;
    protected $changeAction;
    
    /**
     * Class Constructor
     *
     * Initializes the icon widget, sets a unique ID, and disables autocomplete.
     *
     * @param string $name Name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'ticon_'.mt_rand(1000000000, 1999999999);
        $this->tag->{'autocomplete'} = 'off';
    }
    
    /**
     * Enable the field
     *
     * Enables a specific form field identified by its name.
     *
     * @param string $form_name Name of the form
     * @param string $field Name of the field to be enabled
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " ticon_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the field
     *
     * Disables a specific form field identified by its name.
     *
     * @param string $form_name Name of the form
     * @param string $field Name of the field to be disabled
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " ticon_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Set change function
     *
     * Defines a JavaScript function to be executed when the icon selection changes.
     *
     * @param string $function JavaScript function to be executed
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Define the action to be executed when the user changes the icon
     *
     * Sets a TAction that will be triggered when the icon selection is changed.
     * The action must be static.
     *
     * @param TAction $action Action to be executed
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
     * Shows the widget on the screen
     *
     * Renders the icon selection widget, applies any defined actions,
     * and initializes JavaScript behavior.
     *
     * @throws Exception If the form containing the field is not set in TForm::setFields()
     */
    public function show()
    {
        $wrapper = new TElement('div');
        $wrapper->{'class'} = 'input-group';
        $span = new TElement('span');
        $span->{'class'} = 'input-group-addon';
        
        if (isset($this->exitAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->exitAction->serialize(FALSE);
            $this->setProperty('exitaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
        }

        if (isset($this->changeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->changeAction->serialize(FALSE);
            $this->setProperty('changeAction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
        }

        if (!empty($this->exitAction) || !empty($this->changeAction))
        {
            $this->setChangeFunction( $this->changeFunction . "; tform_fire_field_actions('{$this->formName}', '{$this->name}'); " );
        }
        
        $i = new TElement('i');
        $span->add($i);
        
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $outer_size = $this->size;
            $this->size = '100%';
            $wrapper->{'style'} = "width: $outer_size";
        }
        
        ob_start();
        parent::show();
        $child = ob_get_contents();
        ob_end_clean();
        
        $wrapper->add($child);
        $wrapper->add($span);
        $wrapper->show();
        
        if (parent::getEditable())
        {
            if($this->changeFunction)
            {
                TScript::create(" ticon_start('{$this->id}',function(icon){ {$this->changeFunction} }); ");   
            }
            else
            {
                TScript::create(" ticon_start('{$this->id}',false); ");
            }
        }
    }
}
