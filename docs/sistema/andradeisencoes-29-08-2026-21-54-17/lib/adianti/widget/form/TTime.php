<?php
namespace Adianti\Widget\Form;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;

use Exception;

/**
 * TimePicker Widget
 *
 * This class represents a time picker field, extending the standard input field with additional
 * functionalities for handling time format, validation, and event-driven actions.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TTime extends TEntry implements AdiantiWidgetInterface
{
    private $mask;
    protected $id;
    protected $size;
    protected $value;
    protected $options;
    protected $replaceOnPost;
    protected $changeFunction;
    protected $changeAction;

    /**
     * Class Constructor
     *
     * Initializes the time picker widget, setting default mask, ID, and options.
     *
     * @param string $name The name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'ttime_' . mt_rand(1000000000, 1999999999);
        $this->mask = 'hh:ii';
        $this->options = [];

        $this->setOption('startView', 1);
        $this->setOption('pickDate', false);
        $this->setOption('formatViewType', 'time');
        $this->setOption('fontAwesome', true);

        $newmask = $this->mask;
        $newmask = str_replace('hh',   '99',   $newmask);
        $newmask = str_replace('ii',   '99',   $newmask);
        $newmask = str_replace('ss',   '99',   $newmask);
        parent::setMask($newmask);
        $this->tag->{'widget'} = 'ttime';
    }

    /**
     * Defines the mask for the field
     *
     * Sets the time format mask for the input field and updates the mask in the parent class.
     *
     * @param string $mask The format mask (e.g., 'hh:ii')
     * @param bool $replaceOnPost Whether to replace the mask when submitting the form (default: false)
     */
    public function setMask($mask, $replaceOnPost = FALSE)
    {
        $this->mask = $mask;
        $this->replaceOnPost = $replaceOnPost;

        $newmask = $this->mask;
        $newmask = str_replace('hh',   '99',   $newmask);
        $newmask = str_replace('ii',   '99',   $newmask);
        $newmask = str_replace('ss',   '99',   $newmask);

        parent::setMask($newmask, $replaceOnPost);
    }

    /**
     * Sets an extra option for the date/time picker
     *
     * Configures additional settings for the date/time picker component.
     *
     * @link https://www.malot.fr/bootstrap-datetimepicker/
     *
     * @param string $option The name of the option
     * @param mixed $value The value to be set for the option
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Defines an action to be executed when the field loses focus
     *
     * This is a convenience method that internally calls `setChangeAction()`.
     *
     * @param TAction $action The action object to be executed
     */
    public function setExitAction(TAction $action)
    {
        $this->setChangeAction($action);
    }

    /**
     * Defines an action to be executed when the user changes the field value
     *
     * The action must be static to be used in this context.
     *
     * @param TAction $action The action object to be executed
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
     * Sets a custom JavaScript function to be executed when the field value changes
     *
     * This allows defining a client-side callback function.
     *
     * @param string $function The JavaScript function name or inline function code
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Enables the field in a form
     *
     * Uses JavaScript to enable the time picker field dynamically.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field The name of the field to be enabled
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tdate_enable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Disables the field in a form
     *
     * Uses JavaScript to disable the time picker field dynamically.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field The name of the field to be disabled
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tdate_disable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Displays the widget
     *
     * Renders the time picker on the screen, applying the defined options and event handlers.
     *
     * @throws Exception If the associated form is not properly set
     */
    public function show()
    {
        $language = strtolower( AdiantiCoreTranslator::getLanguage() );
        $options = json_encode($this->options);

        if (parent::getEditable())
        {
            $outer_size = 'undefined';
            if (strstr((string) $this->size, '%') !== FALSE)
            {
                $outer_size = $this->size;
                $this->size = '100%';
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

        if (isset($this->changeFunction))
        {
            $this->setProperty('changeaction', $this->changeFunction, FALSE);
            $this->setProperty('onChange', $this->changeFunction, FALSE);
        }

        parent::show();

        if (parent::getEditable())
        {
            TScript::create( "tdatetime_start( '#{$this->id}', '{$this->mask}', '{$language}', '{$outer_size}', '{$options}');");
        }
    }
}