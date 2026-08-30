<?php
namespace Adianti\Widget\Form;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;

use DateTime;
use Exception;

/**
 * DatePicker Widget
 *
 * This class represents a date input field with a date picker widget.
 * It extends the TEntry class and implements the AdiantiWidgetInterface.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDate extends TEntry implements AdiantiWidgetInterface
{
    protected $mask;
    protected $dbmask;
    protected $id;
    protected $size;
    protected $options;
    protected $value;
    protected $replaceOnPost;
    protected $changeFunction;
    protected $changeAction;

    /**
     * Class Constructor
     *
     * Initializes the TDate widget with a unique identifier, default mask, and default options.
     *
     * @param string $name Name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'tdate_' . mt_rand(1000000000, 1999999999);
        $this->mask = 'yyyy-mm-dd';
        $this->dbmask = null;
        $this->options = [];
        $this->replaceOnPost = FALSE;

        $newmask = $this->mask;
        $newmask = str_replace('dd',   '99',   $newmask);
        $newmask = str_replace('mm',   '99',   $newmask);
        $newmask = str_replace('yyyy', '9999', $newmask);
        parent::setMask($newmask);
        $this->tag->{'widget'} = 'tdate';
        $this->tag->{'autocomplete'} = 'off';
    }

    /**
     * Store the value inside the object
     *
     * If a database mask is set and differs from the display mask, 
     * it converts the value to the correct format before storing.
     *
     * @param string|null $value The date value to be set
     */
    public function setValue($value)
    {
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
     * Return the post data
     *
     * If a database mask is set and differs from the display mask, 
     * it converts the value to the correct format before returning.
     *
     * @return string|null The formatted post data
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
     * Convert a date from one format mask to another
     *
     * @param string|array|null $value The date value to convert (can be an array for multiple values)
     * @param string $fromMask The source format mask (e.g., 'dd/mm/yyyy')
     * @param string $toMask The target format mask (e.g., 'yyyy-mm-dd')
     *
     * @return string|array|null The formatted date value
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

            $phpFromMask = str_replace( ['dd','mm', 'yyyy'], ['d','m','Y'], $fromMask);
            $phpToMask   = str_replace( ['dd','mm', 'yyyy'], ['d','m','Y'], $toMask);

            $date = DateTime::createFromFormat($phpFromMask, $value);
            if ($date)
            {
                return $date->format($phpToMask);
            }
        }

        return $value;
    }

    /**
     * Define the display mask for the field
     *
     * @param string $mask The date format mask (e.g., 'dd-mm-yyyy')
     * @param bool $replaceOnPost Whether to replace the value on form submission
     */
    public function setMask($mask, $replaceOnPost = FALSE)
    {
        $this->mask = $mask;
        $this->replaceOnPost = $replaceOnPost;

        $newmask = $this->mask;
        $newmask = str_replace('dd',   '99',   $newmask);
        $newmask = str_replace('mm',   '99',   $newmask);
        $newmask = str_replace('yyyy', '9999', $newmask);

        parent::setMask($newmask);
    }

    /**
     * Get the current display mask
     *
     * @return string The current date format mask
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * Set the mask used to store the data in the database
     *
     * @param string $mask The database format mask (e.g., 'yyyy-mm-dd')
     */
    public function setDatabaseMask($mask)
    {
        $this->dbmask = $mask;
    }

    /**
     * Get the database mask
     *
     * @return string|null The database format mask
     */
    public function getDatabaseMask()
    {
        return $this->dbmask;
    }

    /**
     * Set extra options for the date picker
     *
     * @link https://bootstrap-datepicker.readthedocs.io/en/latest/options.html
     *
     * @param string $option The option name
     * @param mixed $value The option value
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Define the action to be executed when the user exits the field
     *
     * This is an alias for setChangeAction().
     *
     * @param TAction $action The action to be executed
     */
    public function setExitAction(TAction $action)
    {
        $this->setChangeAction($action);
    }

    /**
     * Define the action to be executed when the user changes the field
     *
     * @param TAction $action The action object (must be static)
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
     * Set a JavaScript function to be executed when the field changes
     *
     * @param string $function The JavaScript function to execute
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Convert a date from Brazilian format (dd/mm/yyyy) to US format (yyyy-mm-dd)
     *
     * @param string|null $date The date in dd/mm/yyyy format
     *
     * @return string|null The date in yyyy-mm-dd format
     */
    public static function date2us($date)
    {
        if ($date)
        {
            // get the date parts
            $day  = substr($date,0,2);
            $mon  = substr($date,3,2);
            $year = substr($date,6,4);
            return "{$year}-{$mon}-{$day}";
        }
    }

    /**
     * Convert a date from US format (yyyy-mm-dd) to Brazilian format (dd/mm/yyyy)
     *
     * @param string|null $date The date in yyyy-mm-dd format
     *
     * @return string|null The date in dd/mm/yyyy format
     */
    public static function date2br($date)
    {
        if ($date)
        {
            // get the date parts
            $year = substr($date,0,4);
            $mon  = substr($date,5,2);
            $day  = substr($date,8,2);
            return "{$day}/{$mon}/{$year}";
        }
    }

    /**
     * Enable the date field in a form
     *
     * @param string $form_name The name of the form
     * @param string $field The name of the field to enable
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tdate_enable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Disable the date field in a form
     *
     * @param string $form_name The name of the form
     * @param string $field The name of the field to disable
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tdate_disable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Render the widget on the screen
     *
     * This method initializes the date picker JavaScript settings and applies 
     * the defined mask, language, and options.
     *
     * @throws Exception If the change action is defined but the form is not set
     */
    public function show()
    {
        $js_mask = str_replace('yyyy', 'yy', $this->mask);
        $language = strtolower( AdiantiCoreTranslator::getLanguage() );
        $options = json_encode($this->options);

        $outer_size = 'undefined';
        if (strstr( (string) $this->size, '%') !== FALSE)
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
            $this->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback');");
            $this->setProperty('onChange', $this->getProperty('changeaction'));
        }

        if (isset($this->changeFunction))
        {
            $this->setProperty('changeaction', $this->changeFunction, FALSE);
            $this->setProperty('onChange', $this->changeFunction, FALSE);
        }

        parent::show();

        TScript::create( "tdate_start( '#{$this->id}', '{$this->mask}', '{$language}', '{$outer_size}', '{$options}');");

        if (!parent::getEditable())
        {
            TScript::create( " tdate_disable_field( '{$this->formName}', '{$this->name}' ); " );
        }
    }
}