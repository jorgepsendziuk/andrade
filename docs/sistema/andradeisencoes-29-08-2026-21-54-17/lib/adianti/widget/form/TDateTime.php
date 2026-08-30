<?php
namespace Adianti\Widget\Form;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;

use DateTime;
use Exception;

/**
 * DateTimePicker Widget
 *
 * This class provides a date-time input field with a date picker.
 * It extends TEntry and implements AdiantiWidgetInterface.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDateTime extends TEntry implements AdiantiWidgetInterface
{
    private $mask;
    private $dbmask;
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
     * Initializes a new instance of the TDateTime widget.
     *
     * @param string $name The name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'tdatetime_' . mt_rand(1000000000, 1999999999);
        $this->mask = 'yyyy-mm-dd hh:ii';
        $this->dbmask = null;
        $this->options = [];
        $this->replaceOnPost = FALSE;

        $this->setOption('fontAwesome', true);

        $newmask = $this->mask;
        $newmask = str_replace('dd',   '99',   $newmask);
        $newmask = str_replace('hh',   '99',   $newmask);
        $newmask = str_replace('ii',   '99',   $newmask);
        $newmask = str_replace('mm',   '99',   $newmask);
        $newmask = str_replace('yyyy', '9999', $newmask);
        parent::setMask($newmask);
        $this->tag->{'widget'} = 'tdatetime';
    }

    /**
     * Stores the value inside the object
     *
     * @param string|array $value The value to set (date-time string or an array of date-time strings)
     *
     * @return void
     */
    public function setValue($value)
    {
        if(is_array($value))
        {
            foreach($value as $key => $v)
            {
                $value[$key] = str_replace('T', ' ', (string) $v);
            }
        }
        else
        {
            $value = str_replace('T', ' ', (string) $value);
        }
        
        if (!empty($this->dbmask) and ($this->mask !== $this->dbmask) )
        {
            return parent::setValue( self::convertToMask($value, $this->dbmask, $this->mask) );
        }
        else
        {
            return parent::setValue($value);
        }
    }

    /**
     * Retrieves the posted value
     *
     * Converts the value to the database mask format if necessary.
     *
     * @return string The processed date-time value
     */
    public function getPostData()
    {
        $value = parent::getPostData();

        if (!empty($this->dbmask) and ($this->mask !== $this->dbmask) )
        {
            return self::convertToMask($value, $this->mask, $this->dbmask);
        }
        else
        {
            return $value;
        }
    }

    /**
     * Converts a date-time string from one format to another
     *
     * @param string|array $value     The original date-time value
     * @param string       $fromMask  The source date-time format
     * @param string       $toMask    The target date-time format
     *
     * @return string|array           The formatted date-time value
     */
    public static function convertToMask($value, $fromMask, $toMask)
    {
        if (is_array($value)) // vector fields (field list)
        {
            foreach ($value as $key => $item)
            {
                $value[$key] = self::convertToMask($item, $fromMask, $toMask);
            }

            return $value;
        }
        else if ($value)
        {
            $value = substr($value,0,strlen($fromMask));
            if(preg_match('/A/i', $fromMask))
            {
                $phpToMask   = str_replace( ['dd','mm', 'yyyy', 'hh', 'ii', 'ss', 'AA'], ['d','m','Y', 'H', 'i', 's','A'], $toMask);
                $phpFromMask = str_replace( ['dd','mm', 'yyyy', 'hh', 'ii', 'ss', 'AA'], ['d','m','Y', 'h', 'i', 's', 'A'], $fromMask);
            }
            else
            {
                $phpToMask   = str_replace( ['dd','mm', 'yyyy', 'hh', 'ii', 'ss'], ['d','m','Y', 'H', 'i', 's'], $toMask);
                $phpFromMask = str_replace( ['dd','mm', 'yyyy', 'hh', 'ii', 'ss'], ['d','m','Y', 'H', 'i', 's'], $fromMask);
            }
            $date = DateTime::createFromFormat($phpFromMask, $value);
            if ($date)
            {
                return $date->format($phpToMask);
            }
        }

        return $value;
    }

    /**
     * Sets the JavaScript function to be executed when the field changes
     *
     * @param string $function JavaScript function name
     *
     * @return void
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Sets the action to be executed when the user leaves the field
     *
     * @param TAction $action The action object
     *
     * @return void
     */
    public function setExitAction(TAction $action)
    {
        $this->setChangeAction($action);
    }

    /**
     * Defines the action to be executed when the field value changes
     *
     * @param TAction $action The action object
     *
     * @throws Exception If the action is not static
     * @return void
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
     * Defines the input mask for the field
     *
     * @param string $mask          The mask format (e.g., 'dd-mm-yyyy hh:ii')
     * @param bool   $replaceOnPost Whether to replace the mask on post
     *
     * @return void
     */
    public function setMask($mask, $replaceOnPost = FALSE)
    {
        $this->mask = $mask;
        $this->replaceOnPost = $replaceOnPost;

        $newmask = $this->mask;
        $newmask = str_replace('dd',   '99',   $newmask);
        $newmask = str_replace('hh',   '99',   $newmask);
        $newmask = str_replace('ii',   '99',   $newmask);
        $newmask = str_replace('mm',   '99',   $newmask);
        $newmask = str_replace('ss',   '99',   $newmask);
        $newmask = str_replace('yyyy', '9999', $newmask);

        parent::setMask($newmask);
    }

    /**
     * Defines the database mask for storing the value
     *
     * @param string $mask The database mask format
     *
     * @return void
     */
    public function setDatabaseMask($mask)
    {
        $this->dbmask = $mask;
    }

    /**
     * Sets additional options for the date-time picker
     *
     * @link https://www.malot.fr/bootstrap-datetimepicker/
     *
     * @param string $option The option name
     * @param mixed  $value  The option value
     *
     * @return void
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Enables the field
     *
     * @param string $form_name The form name
     * @param string $field     The field name
     *
     * @return void
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tdate_enable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Disables the field
     *
     * @param string $form_name The form name
     * @param string $field     The field name
     *
     * @return void
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tdate_disable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Displays the widget on the screen
     *
     * Initializes the JavaScript date-time picker and applies configurations.
     *
     * @throws Exception If the change action requires a form context
     * @return void
     */
    public function show()
    {
        $js_mask = str_replace('yyyy', 'yy', $this->mask);
        $language = strtolower( AdiantiCoreTranslator::getLanguage() );
        $options = json_encode($this->options);

        $outer_size = 'undefined';
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $outer_size = $this->size;
            $this->size = '100%';
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

        TScript::create( "tdatetime_start( '#{$this->id}', '{$this->mask}', '{$language}', '{$outer_size}', '{$options}');");

        if (!parent::getEditable())
        {
            TScript::create( " tdate_disable_field( '{$this->formName}', '{$this->name}' ); " );
        }
    }
}
