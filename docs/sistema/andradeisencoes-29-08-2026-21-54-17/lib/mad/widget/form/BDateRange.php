<?php
namespace Mad\Widget\Form;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Util\TImage;

use DateTime;
use Exception;

/**
 * @version    4.0
 * @package    widget
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

class BDateRange extends TField implements AdiantiWidgetInterface
{
    protected $mask;
    protected $dbmask;
    protected $id;
    protected $size;
    protected $options;
    protected $value;
    protected $changeAction;
    protected $name_end;
    protected $title;
    protected $grid;
    protected $calendars;
    protected $autoApply;
    protected $stepSeconds;
    protected $stepHours;
    protected $stepMinutes;
    protected $separator;
    protected $startValue;
    protected $endValue;
    protected $disableDates;
    protected $enableDates;
    private $parent;

    /**
     * Constructor
     *
     * Initializes the BDateRange widget with default values and options.
     *
     * @param string $name Name of the widget.
     * @param string|null $name_end Name of the end date field (optional).
     */
    public function __construct($name, $name_end = null)
    {
        parent::__construct($name);
        $this->id   = 'bdaterange_' . mt_rand(1000000000, 1999999999);
        $this->mask = 'DD/MM/YYYY';
        $this->dbmask = 'YYYY-MM-DD';
        $this->name = $name;
        $this->title = false;
        $this->autoApply = true;
        $this->name_end = $name_end;
        $this->grid = 2;
        $this->calendars = 2;
        $this->stepSeconds = 10;
        $this->stepHours = 1;
        $this->stepMinutes = 5;
        $this->separator = ' - ';
        
        if($this->name_end)
        {
            $this->separator = null;
        }
        
        $this->disableDates = false;
        $this->enableDates = false;
        $this->options = ['zIndex' => 10];
        
        $this->tag->{'widget'} = 'bdaterange';
        $this->tag->{'autocomplete'} = 'off';
    }
    
    /**
     * Set the form name
     *
     * Associates the widget with a form and handles the configuration of the end date field if applicable.
     *
     * @param string $name Form name.
     */
    public function setFormName($name)
    {
        parent::setFormName($name);
        
        if ($this->name_end) {
            $form = TForm::getFormByName($name);
            $end = clone $this;
            $end->setName($this->name_end);
            $end->name_end = null;
            $end->parent = $this;
            $form->addField($end);
        }
    }
    
    /**
     * Set enable dates
     *
     * Specifies which dates should be enabled in the date picker.
     *
     * @param array $dates List of dates to be enabled.
     */
    public function setEnableDates(array $dates)
    {
        $this->enableDates = $dates;
    }
    
    /**
     * Get disable dates
     *
     * Returns the list of dates that are disabled.
     *
     * @return array|false List of disabled dates or false if none.
     */
    public function getDisableDates()
    {
        return $this->disableDates;
    }
    
    /**
     * Set disable dates
     *
     * Specifies which dates should be disabled in the date picker.
     *
     * @param array $dates List of dates to be disabled.
     */
    public function setDisableDates(array $dates)
    {
        $this->disableDates = $dates;
    }
    
    /**
     * Get enable dates
     *
     * Returns the list of enabled dates.
     *
     * @return array|false List of enabled dates or false if none.
     */
    public function getEnableDates()
    {
        return $this->enableDates;
    }
    
    /**
     * Set separator
     *
     * Defines the separator character used between start and end dates.
     *
     * @param string $separator Separator character.
     */
    public function setSeparator(string $separator)
    {
        $this->separator = $separator;
    }
    
    /**
     * Get separator
     *
     * Returns the separator character used between start and end dates.
     *
     * @return string Separator character.
     */
    public function getSeparator()
    {
        return $this->separator;
    }
    
    /**
     * Set calendars popover
     *
     * Defines the number of calendars displayed in the date picker popover.
     *
     * @param int $calendars Number of calendars.
     */
    public function setCalendars(int $calendars)
    {
        $this->calendars = $calendars;
    }
    
    /**
     * Get calendars popover
     *
     * Returns the number of calendars displayed in the date picker popover.
     *
     * @return int Number of calendars.
     */
    public function getCalendars()
    {
        return $this->calendars;
    }
    
    /**
     * Set steps
     *
     * Defines the step intervals for hours, minutes, and seconds.
     *
     * @param int $stepHours Step interval for hours.
     * @param int $stepMinutes Step interval for minutes.
     * @param int $stepSeconds Step interval for seconds.
     */
    public function setSteps($stepHours = 1, $stepMinutes = 5, $stepSeconds = 10)
    {
        $this->stepHours = $stepHours;
        $this->stepMinutes = $stepMinutes;
        $this->stepSeconds = $stepSeconds;
    }
    
    /**
     * Set grid popover
     *
     * Defines the grid size for the calendar popover.
     *
     * @param int $grid Grid size.
     */
    public function setGrid(int $grid)
    {
        $this->grid = $grid;
    }
    
    /**
     * Show confirm buttons
     *
     * Disables the auto-apply feature, requiring the user to confirm selections.
     */
    public function showConfirmButtons()
    {
        $this->autoApply = false;
    }
    
    /**
     * Hide confirm buttons
     *
     * Enables the auto-apply feature, applying selections automatically.
     */
    public function hideConfirmButtons()
    {
        $this->autoApply = true;
    }
    
    /**
     * Get grid popover
     *
     * Returns the grid size for the calendar popover.
     *
     * @return int Grid size.
     */
    public function getGrid()
    {
        return $this->grid;
    }
    
    /**
     * Set title popover
     *
     * Defines the title displayed on the calendar popover.
     *
     * @param string $title Title text.
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }
    
    /**
     * Get title popover
     *
     * Returns the title displayed on the calendar popover.
     *
     * @return string|null Title text or null if not set.
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * Store the value inside the object
     *
     * Converts and stores the date range value based on the configured mask.
     *
     * @param string $value Date range value.
     */
    public function setValue($value)
    {
        $v = $value;
        if (!empty($this->dbmask) and ($this->mask !== $this->dbmask) )
        {
            if (! empty($this->separator))
            {
                $values = explode($this->separator, $value);
                $v1 = self::replaceToMask($values[0], $this->dbmask, $this->mask);
                $v2 = self::replaceToMask($values[1], $this->dbmask, $this->mask);
                $v = $v1 . $this->separator . $v2;
            }
            else
            {
                $v = self::replaceToMask($value, $this->dbmask, $this->mask);
            }
        }
        
        if ($this->parent) {
            $this->parent->endValue = $v;
        } else {
            $this->startValue = $v;
        }
    }

    /**
     * Get the start date value
     *
     * Returns the stored start date value.
     *
     * @return string|null Start date value.
     */
    public function getValue()
    {
        return $this->startValue;
    }

    /**
     * Set the end date value
     *
     * Stores the end date value after applying the necessary transformations.
     *
     * @param string $value End date value.
     */
    public function setEndValue($value)
    {
        $v = $value;
        if (!empty($this->dbmask) and ($this->mask !== $this->dbmask) )
        {
            $v = self::replaceToMask($value, $this->dbmask, $this->mask);
        }
    
        $this->endValue = $v;
    }

    /**
     * Get the name of the end date field
     *
     * Returns the name of the end date field if it is set.
     *
     * @return string|null End date field name or null if not set.
     */
    public function getNameEnd()
    {
        return $this->name_end;
    }

    /**
     * Get the end date value
     *
     * Returns the stored end date value.
     *
     * @return string|null End date value.
     */
    public function getEndValue()
    {
        return $this->endValue;
    }

    /**
     * Return the post data
     *
     * Retrieves the value from the request and applies necessary transformations.
     *
     * @return string|null Formatted date range value.
     */
    public function getPostData()
    {
        $value = $_POST[$this->name] ?? null;
        
        if (!empty($this->dbmask) and ($this->mask !== $this->dbmask) )
        {
            if (! empty($this->separator))
            {
                $values = explode($this->separator, $value);
                $v1 = self::replaceToMask($values[0], $this->mask, $this->dbmask);
                $v2 = self::replaceToMask($values[1], $this->mask, $this->dbmask);
                return $v1 . $this->separator . $v2;
            }
            
            return self::replaceToMask($value, $this->mask, $this->dbmask);
        }
        else
        {
            return $value;
        }
    }

    /**
     * Validate field value
     *
     * Applies validations to the widget's value if defined.
     *
     * @throws Exception If validation fails.
     */
    public function validate()
    {
        $validations = parent::getValidations();
        if ($validations)
        {
            foreach ($validations as $validation)
            {
                $label      = $validation[0];
                $validator  = $validation[1];
                $parameters = $validation[2];
                
                if ($this->parent) 
                {
                    $validator->validate($label, $this->parent->getEndValue(), $parameters);
                }
                else 
                {
                    $validator->validate($label, $this->getValue(), $parameters);
                }
            }
        }
    }

    /**
     * Define the field's mask
     *
     * Sets the date format mask for the input field.
     *
     * @param string $mask Date format mask (e.g., dd-mm-yyyy).
     */
    public function setMask($mask)
    {
        $this->mask = $mask;
    }

    /**
     * Return mask
     *
     * Retrieves the current mask format for the input field.
     *
     * @return string Mask format.
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * Set the mask to be used for database storage
     *
     * Defines the mask used to store values in the database.
     *
     * @param string $mask Database mask format.
     */
    public function setDatabaseMask($mask)
    {
        $this->dbmask = $mask;
    }

    /**
     * Return database mask
     *
     * Retrieves the mask format used for database storage.
     *
     * @return string Database mask format.
     */
    public function getDatabaseMask()
    {
        return $this->dbmask;
    }

    /**
     * Set extra easepick options
     *
     * Configures additional options for the easepick date range picker.
     *
     * @param string $option Option name.
     * @param mixed $value Option value.
     *
     * @link https://easepick.com/
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Define the action to be executed when the field loses focus
     *
     * @param TAction $action Action to execute.
     */
    public function setExitAction(TAction $action)
    {
        $this->setChangeAction($action);
    }

    /**
     * Define the action to be executed when the field value changes
     *
     * @param TAction $action Action to execute.
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
     * Enable the field
     *
     * Enables the specified field in the given form.
     *
     * @param string $form_name Form name.
     * @param string $field Field name.
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " bdaterange_enable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Disable the field
     *
     * Disables the specified field in the given form.
     *
     * @param string $form_name Form name.
     * @param string $field Field name.
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " bdaterange_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Convert date format mask
     *
     * Converts a date format mask from lowercase format (e.g., 'dd-mm-yyyy') to uppercase format
     * suitable for the easepick date range picker.
     *
     * @param string $mask The date format mask to be converted.
     *
     * @return string The converted mask with uppercase format.
     */
    public static function convertMask($mask)
    {
        return str_replace(['d', 'm', 'y', 'i', 's', 'h'], ['D', 'M', 'Y', 'm', 's', 'H'], strtolower($mask));
    }
    
    /**
     * Convert date format from one mask to another
     *
     * Converts a date value from one date format to another.
     * This method is useful for transforming between user-friendly formats and database storage formats.
     *
     * @param string $value The date value to be transformed.
     * @param string $fromMask The current mask format of the value.
     * @param string $toMask The desired mask format for conversion.
     *
     * @return string The date value formatted according to the target mask. Returns the original value if conversion fails.
     */
    public static function replaceToMask($value, $fromMask, $toMask)
    {
        if (empty($value))
        {
            return $value;    
        }
        
        $value = substr($value,0,strlen($toMask));

        $phpFromMask = str_replace( ['dd','mm', 'yyyy', 'hh', 'ii', 'ss'], ['d','m','Y', 'H', 'i', 's'], strtolower($fromMask));
        $phpToMask   = str_replace( ['dd','mm', 'yyyy', 'hh', 'ii', 'ss'], ['d','m','Y', 'H', 'i', 's'], strtolower($toMask));
        
        $date = DateTime::createFromFormat($phpFromMask, $value);
        
        if ($date)
        {
            return $date->format($phpToMask);
        }
        
        return $value;
    }

    /**
     * Show the widget
     *
     * Renders the date range widget on the screen with all configured properties.
     *
     * @throws Exception If invalid configurations are detected.
     */
    public function show()
    {
        $entryStart = new TEntry($this->name);
        $entryStart->setId('start_'.$this->id);
        $entryStart->setValue($this->startValue);
        $entryStart->widget = 'bdaterange';

        if($this->mask)
        {
            $newmask = $this->mask;
            $newmask = str_ireplace('dd',   '99',   $newmask);
            $newmask = str_ireplace('mm',   '99',   $newmask);
            $newmask = str_ireplace('yyyy', '9999', $newmask);
            $entryStart->setMask($newmask);
        }
        
        $container = new TElement('div');
        $container->id = $this->id;
        $container->setProperties($this->properties);
        $container->class = 'bdaterange-container tdate-group date';
        
        $container->add($entryStart);
        
        $entryEnd = null;
        if ($this->name_end) 
        {
            $entryEnd = new TEntry($this->name_end);
            $entryEnd->setId('end_'.$this->id);
            $entryEnd->widget = 'bdaterange';
            $entryEnd->setValue($this->endValue);
            $entryEnd->class .= ' bdaterange_end_field ';
            if($this->mask)
            {
                $newmask = $this->mask;
                $newmask = str_ireplace('dd',   '99',   $newmask);
                $newmask = str_ireplace('mm',   '99',   $newmask);
                $newmask = str_ireplace('yyyy', '9999', $newmask);
                $entryEnd->setMask($newmask);
            }

            $container->add($entryEnd);
            $container->class .= ' start_end';
        }
        elseif($this->separator && $this->mask)
        {
            $newmask = $this->mask;
            $newmask = str_ireplace('dd',   '99',   $newmask);
            $newmask = str_ireplace('mm',   '99',   $newmask);
            $newmask = str_ireplace('yyyy', '9999', $newmask);
            $entryStart->setMask($newmask.$this->separator.$newmask);
        }

        if (!empty($this->size))
        {
            if (strstr((string) $this->size, '%') !== FALSE)
            {
                $container->setProperty('style', "width:{$this->size};", false); //aggregate style info
                $entryStart->setProperty('style', "width:{$this->size};", false); //aggregate style info
                if($entryEnd)
                {
                    $entryEnd->setProperty('style', "width:{$this->size};", false); //aggregate style info
                }
            }
            else
            {
                $container->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
                $entryStart->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
                if($entryStart)
                {
                    $entryStart->setProperty('style', "width:{$this->size};", false); //aggregate style info
                }
            }
        }

        $container->add(TElement::tag('span', new TImage('far:calendar'), ['class' => 'bdaterange-icon btn btn-default tdate-group-addon']));

        if (isset($this->changeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }

            $string_action = $this->changeAction->serialize(FALSE);
            $this->options['changeaction'] = "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback');";
        }
        
        if (! empty($this->enableDates && ! empty($this->disableDates)))
        {
            throw new Exception('Only inform enable or disable dates');
        }

        $this->options['seconds'] = strpos($this->mask, 'ss') !== false;
        $this->options['time'] = strpos(strtolower($this->mask), 'h') !== false || strpos(strtolower($this->mask), 'i') !== false;
        $this->options['stepSeconds'] = $this->stepSeconds;
        $this->options['stepHours'] = $this->stepHours;
        $this->options['stepMinutes'] = $this->stepMinutes;
        $this->options['separator'] = $this->separator;
        $this->options['format'] = self::convertMask($this->mask);
        $this->options['autoApply'] = $this->autoApply;
        $this->options['grid'] = $this->grid;
        $this->options['calendars'] = $this->calendars;
        $this->options['title'] = $this->title;
        $this->options['name_start'] = $this->name;
        $this->options['name_end'] = $this->name_end;
        $this->options['disableDates'] = $this->disableDates;
        $this->options['enableDates'] = $this->enableDates;
        $this->options['id_start'] = 'start_'.$this->id;
        $this->options['id_end'] = 'end_'.$this->id;
        $this->options['language'] = strtolower( AdiantiCoreTranslator::getLanguage() );
        
        $options = json_encode($this->options);
        
        $container->show();
        
        TScript::create( "bdaterange_start('{$this->id}', {$options});");

        if (!parent::getEditable())
        {
            TScript::create( " bdaterange_disable_field( '{$this->formName}', '{$this->id}' ); " );
        }
    }
}
