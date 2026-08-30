<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TRadioButton;

use Exception;

/**
 * A group of radio buttons
 *
 * This class manages a group of radio buttons, allowing different layouts,
 * actions, and interaction modes.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TRadioGroup extends TField implements AdiantiWidgetInterface
{
    private $layout = 'vertical';
    private $changeAction;
    private $items;
    private $breakItems;
    private $buttons;
    private $labels;
    private $appearance;
    protected $changeFunction;
    protected $formName;
    protected $labelClass;
    protected $useButton;
    protected $is_boolean;
    
    /**
     * Class Constructor
     *
     * Initializes a new instance of the TRadioGroup class.
     *
     * @param string $name The name of the field.
     */
    public function __construct($name)
    {
        parent::__construct($name);
        parent::setSize(NULL);
        $this->labelClass = 'tcheckgroup_label ';
        $this->useButton  = FALSE;
        $this->is_boolean = FALSE;
    }
    
    /**
     * Clone object
     *
     * Creates a deep copy of the object, ensuring that cloned instances
     * have unique radio button and label objects.
     */
    public function __clone()
    {
        if (is_array($this->items))
        {
            $oldbuttons = $this->buttons;
            $this->buttons = array();
            $this->labels  = array();

            foreach ($this->items as $key => $value)
            {
                $button = new TRadioButton($this->name);
                $button->setValue($key);
                $button->setProperty('onchange', $oldbuttons[$key]->getProperty('onchange'));
                
                $obj = new TLabel($value);
                $this->buttons[$key] = $button;
                $this->labels[$key] = $obj;
            }
        }
    }
    
    /**
     * Enable boolean mode
     *
     * Configures the radio group to work in boolean mode with "Yes" and "No" options.
     */
    public function setBooleanMode()
    {
        $this->is_boolean = true;
        $this->addItems( [ '1' => AdiantiCoreTranslator::translate('Yes'),
                           '2' => AdiantiCoreTranslator::translate('No') ] );
        $this->setLayout('horizontal');
        $this->setUseButton();
        
        // if setValue() was called previously
        if ($this->value === true)
        {
            $this->value = '1';
        }
        else if ($this->value === false)
        {
            $this->value = '2';
        }
    }
    
    /**
     * Set the field's value
     *
     * @param mixed $value The value to set (string or boolean in boolean mode).
     */
    public function setValue($value)
    {
        if ($this->is_boolean)
        {
            $this->value = $value ? '1' : '2';
        }
        else
        {
            parent::setValue($value);
        }
    }
    
    /**
     * Get the field's value
     *
     * @return mixed The current value (boolean if in boolean mode, otherwise string).
     */
    public function getValue()
    {
        if ($this->is_boolean)
        {
            return $this->value == '1' ? true : false;
        }
        else
        {
            return parent::getValue();
        }
    }
    
    /**
     * Get the posted value
     *
     * Retrieves the posted value from the form submission.
     *
     * @return mixed The posted value (boolean if in boolean mode, otherwise string).
     */
    public function getPostData()
    {
        if ($this->is_boolean)
        {
            $data = parent::getPostData();
            return $data == '1' ? true : false;
        }
        else
        {
            return parent::getPostData();
        }
    }
    
    /**
     * Set the layout of the radio buttons
     *
     * @param string $dir The layout direction (vertical or horizontal).
     */
    public function setLayout($dir)
    {
        $this->layout = $dir;
    }
    
    /**
     * Get the layout of the radio buttons
     *
     * @return string The layout direction (vertical or horizontal).
     */
    public function getLayout()
    {
        return $this->layout;
    }
    
    /**
     * Set the number of items before a line break
     *
     * @param int $breakItems The number of items before inserting a line break.
     */
    public function setBreakItems($breakItems)
    {
        $this->breakItems = $breakItems;
    }
    
    /**
     * Display radio buttons as styled buttons
     */
    public function setUseButton()
    {
       $this->labelClass = 'btn btn-default ';
       $this->useButton  = TRUE;
    }
    
    /**
     * Add items to the radio group
     *
     * @param array $items An associative array where keys are values and values are labels.
     */
    public function addItems($items)
    {
        if (is_array($items))
        {
            $this->items = $items;
            $this->buttons = array();
            $this->labels  = array();

            foreach ($items as $key => $value)
            {
                $button = new TRadioButton($this->name);
                $button->setValue($key);

                $obj = new TLabel($value);
                $this->buttons[$key] = $button;
                $this->labels[$key] = $obj;
            }
        }
    }
    
    /**
     * Get the radio button items
     *
     * @return array The array of available options.
     */
    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * Get the radio button objects
     *
     * @return array The array of TRadioButton objects.
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Get the labels associated with radio buttons
     *
     * @return array The array of TLabel objects.
     */
    public function getLabels()
    {
        return $this->labels;
    }
    
    /**
     * Set an action to be executed when the user changes the selection
     *
     * @param TAction $action The action to execute on change.
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
     * Set a JavaScript function to be executed when the selection changes
     *
     * @param string $function The JavaScript function to execute.
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }
    
    /**
     * Reload radio items dynamically
     *
     * Allows reloading the radio options after the group has been rendered.
     *
     * @param string $formname The form name.
     * @param string $name The field name.
     * @param array $items The array of new options.
     * @param array $options Additional options including layout, breakItems, size, useButton, value, changeAction, changeFunction, and checkAll.
     */
    public static function reload($formname, $name, $items, $options = [])
    {
        $field = new self($name);
        $field->addItems($items);

        $form = new TForm($formname);
        $form->addField($field);

        if (! empty($options['layout']))
        {
            $field->setLayout($options['layout']);
        }

        if (! empty($options['breakItems']))
        {
            $field->setBreakItems($options['breakItems']);
        }

        if (! empty($options['size']))
        {
            $field->setSize($options['size']);
        }

        if (! empty($options['useButton']))
        {
            $field->setUseButton($options['useButton']);
        }

        if (! empty($options['value']))
        {
            $field->setValue($options['value']);
        }

        if (! empty($options['changeAction']))
        {
            $field->setChangeAction($options['changeAction']);
        }

        if (! empty($options['changeFunction']))
        {
            $field->setChangeFunction($options['changeFunction']);
        }

        if (! empty($options['checkAll']))
        {
            $field->checkAll($options['checkAll']);
        }

        $content = $field->getContents();

        TScript::create( " tradiogroup_reload('{$formname}', '{$name}', `{$content}`); " );
    }

    /**
     * Enable the radio group field
     *
     * @param string $form_name The form name.
     * @param string $field The field name.
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tradiogroup_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the radio group field
     *
     * @param string $form_name The form name.
     * @param string $field The field name.
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tradiogroup_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clear the radio group selection
     *
     * @param string $form_name The form name.
     * @param string $field The field name.
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tradiogroup_clear_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Render the radio group
     *
     * Displays the radio buttons according to the configured layout and appearance.
     */
    public function show()
    {
        $editable_class = (!parent::getEditable()) ? 'tfield_block_events' : '';
        
        if ($this->useButton)
        {
            echo "<div tradiogroup=\"{$this->name}\" class=\"toggle-wrapper {$editable_class}\" ".$this->getPropertiesAsString('aria').' data-toggle="buttons">';
            
            if (strpos( (string) $this->getSize(), '%') !== FALSE)
            {
                echo '<div class="btn-group" style="clear:both;float:left;width:100%;display:table" role="group">';
            }
            else
            {
                echo '<div class="btn-group" style="clear:both;float:left;display:table" role="group">';
            }
        }
        else
        {
            echo "<div tradiogroup=\"{$this->name}\" class=\"toggle-wrapper {$editable_class}\" ".$this->getPropertiesAsString('aria').' role="group">';
        }
        
        if ($this->items)
        {
            // iterate the RadioButton options
            $i = 0;
            foreach ($this->items as $index => $label)
            {
                $button = $this->buttons[$index];
                $button->setName($this->name);
                $active = FALSE;
                $id = $button->getId();
                
                // check if contains any value
                if ( $this->value == $index AND !(is_null($this->value)) AND strlen((string) $this->value) > 0)
                {
                    // mark as checked
                    $button->setProperty('checked', '1');
                    $active = TRUE;
                }
                
                // create the label for the button
                $obj = $this->labels[$index];
                $obj->{'class'} = $this->labelClass. ($active?'active':'');
                
                if ($this->getSize() AND !$obj->getSize())
                {
                    $obj->setSize($this->getSize());
                }
                
                if ($this->getSize() AND $this->useButton)
                {
                    if (strpos($this->getSize(), '%') !== FALSE)
                    {
                        $size = str_replace('%', '', $this->getSize());
                        $obj->setSize( ($size / count($this->items)) . '%');
                    }
                    else
                    {
                        $obj->setSize($this->getSize());
                    }
                }
                
                // check whether the widget is non-editable
                if (parent::getEditable())
                {
                    if (isset($this->changeAction))
                    {
                        if (!TForm::getFormByName($this->formName) instanceof TForm)
                        {
                            throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
                        }
                        $string_action = $this->changeAction->serialize(FALSE);
                        
                        $button->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$id}', 'callback')");
                        $button->setProperty('onChange', $button->getProperty('changeaction'), FALSE);
                    }
                    
                    if (isset($this->changeFunction))
                    {
                        $button->setProperty('changeaction', $this->changeFunction, FALSE);
                        $button->setProperty('onChange', $this->changeFunction, FALSE);
                    }
                }
                else
                {
                    $button->setEditable(FALSE);
                    //$obj->setFontColor('gray');
                }
                
                if ($this->useButton)
                {
                    $obj->add($button);
                    $obj->show();
                }
                else
                {
                    $button->setProperty('class', 'filled-in');
                    $obj->{'for'} = $button->getId();
                    
                    $wrapper = new TElement('div');
                    $wrapper->{'style'} = 'display:inline-flex;align-items:center;';
                    $wrapper->add($button);
                    $wrapper->add($obj);
                    $wrapper->show();
                }
                
                $i ++;
                
                if ($this->layout == 'vertical' OR ($this->breakItems == $i))
                {
                    $i = 0;
                    if ($this->useButton)
                    {
                       echo '</div>';
                       echo '<div class="btn-group" style="clear:both;float:left;display:table">';
                    }
                    else
                    {
                        // shows a line break
                        $br = new TElement('br');
                        $br->show();
                    }
                }
                echo "\n";
            }
        }
        
        if ($this->useButton)
        {
            echo '</div>';
            echo '</div>';
        }
        else
        {
            echo '</div>';
        }
        
        if (!empty($this->getAfterElement()))
        {
            $this->getAfterElement()->show();
        }
    }
}
