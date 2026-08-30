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
use Adianti\Widget\Form\TCheckButton;

use Exception;

/**
 * Represents a group of checkboxes.
 *
 * This class allows the creation and management of multiple checkboxes as a group.
 * It supports various layouts, different display styles (such as buttons or switches),
 * and dynamic reloading of checkbox items.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCheckGroup extends TField implements AdiantiWidgetInterface
{
    private $layout = 'vertical';
    private $changeAction;
    private $items;
    private $breakItems;
    private $buttons;
    private $labels;
    private $allItemsChecked;
    protected $separator;
    protected $changeFunction;
    protected $formName;
    protected $labelClass;
    protected $useButton;
    protected $useSwitch;
    protected $value;
    
    /**
     * Initializes a new instance of the TCheckGroup class.
     *
     * @param string $name The name of the field.
     */
    public function __construct($name)
    {
        parent::__construct($name);
        parent::setSize(NULL);
        $this->labelClass = 'tcheckgroup_label ';
        $this->useButton  = FALSE;
        $this->useSwitch  = FALSE;
    }
    
    /**
     * Clones the object, ensuring that checkboxes and labels are duplicated correctly.
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
                $button = new TCheckButton("{$this->name}[]");
                $button->setProperty('checkgroup', $this->name);
                $button->setIndexValue($key);
                $button->setProperty('onchange', $oldbuttons[$key]->getProperty('onchange'));
                
                $obj = new TLabel($value);
                $this->buttons[$key] = $button;
                $this->labels[$key] = $obj;
            }
        }
    }
    
    /**
     * Marks all checkboxes as checked.
     */
    public function checkAll()
    {
        $this->allItemsChecked = TRUE;
    }
    
    /**
     * Defines the layout of the checkboxes.
     *
     * @param string $dir The layout direction ('vertical' or 'horizontal').
     */
    public function setLayout($dir)
    {
        $this->layout = $dir;
    }
    
    /**
     * Retrieves the current layout direction of the checkboxes.
     *
     * @return string The layout direction ('vertical' or 'horizontal').
     */
    public function getLayout()
    {
        return $this->layout;
    }
    
    /**
     * Defines after how many items a line break should be added.
     *
     * @param int $breakItems The number of items after which a break occurs.
     */
    public function setBreakItems($breakItems)
    {
        $this->breakItems = $breakItems;
    }
    
    /**
     * Displays checkboxes as buttons instead of standard checkboxes.
     */
    public function setUseButton()
    {
       $this->labelClass = 'btn btn-default ';
       $this->useButton  = TRUE;
    }

    /**
     * Displays checkboxes as switches.
     *
     * @param bool   $useSwitch  Whether to use switch style.
     * @param string $labelClass The CSS class for switch labels.
     */
    public function setUseSwitch($useSwitch = TRUE, $labelClass = 'blue')
    {
       $this->labelClass = 'tswitch ' . $labelClass . ' ';
       $this->useSwitch  = $useSwitch;
    }
    
    /**
     * Adds items to the checkbox group.
     *
     * @param array $items An associative array of key-value pairs where the key is the item value, and the value is the item label.
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
                $button = new TCheckButton("{$this->name}[]");
                $button->setProperty('checkgroup', $this->name);
                $button->setIndexValue($key);

                $obj = new TLabel($value);
                $this->buttons[$key] = $button;
                $this->labels[$key] = $obj;
            }
        }
    }
    
    /**
     * Retrieves the list of items in the checkbox group.
     *
     * @return array|null The list of items or null if none are set.
     */
    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * Retrieves the checkbox button elements.
     *
     * @return array|null The list of checkbox button elements.
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Retrieves the checkbox label elements.
     *
     * @return array|null The list of label elements.
     */
    public function getLabels()
    {
        return $this->labels;
    }
    
    /**
     * Sets the separator used when handling multiple selected values.
     *
     * @param string $sep The separator string.
     */
    public function setValueSeparator($sep)
    {
        $this->separator = $sep;
    }
    
    /**
     * Sets the selected value(s) of the checkbox group.
     *
     * If a separator is defined, the value will be split into an array.
     *
     * @param string|array|null $value The selected value(s).
     */
    public function setValue($value)
    {
        if (empty($this->separator))
        {
            $this->value = $value;
        }
        else
        {
            if ($value)
            {
                $this->value = explode($this->separator, $value);
            }
            else
            {
                $this->value = null;
            }
        }
    }
    
    /**
     * Retrieves the submitted values from the form post request.
     *
     * @return array|string The submitted values as an array, or a string if a separator is defined.
     */
    public function getPostData()
    {
        if (isset($_POST[$this->name]))
        {
            if (empty($this->separator))
            {
                return $_POST[$this->name];
            }
            else
            {
                return implode($this->separator, $_POST[$this->name]);
            }
        }
        else
        {
            return array();
        }
    }
    
    /**
     * Defines an action to be executed when the checkbox group value changes.
     *
     * @param TAction $action The action to execute.
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
     * Sets a JavaScript function to be executed when the checkbox group value changes.
     *
     * @param string $function The JavaScript function.
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }
    
    /**
     * Dynamically reloads the checkbox items after the component has been rendered.
     *
     * @param string $formname The name of the form.
     * @param string $name     The name of the field.
     * @param array  $items    The new items to populate the field.
     * @param array  $options  Additional options for reloading:
     *                         - layout: The layout direction.
     *                         - size: The size of the component.
     *                         - breakItems: Number of items before a break.
     *                         - useButton: Whether to use button style.
     *                         - valueSeparator: Separator for values.
     *                         - value: Default selected value(s).
     *                         - changeAction: Action to trigger on change.
     *                         - changeFunction: JavaScript function for change event.
     *                         - checkAll: Whether to check all items by default.
     */
    public static function reload($formname, $name, $items, $options)
    {
        $field = new self($name);
        $field->addItems($items);

        $form = new TForm($formname);
        $form->addField($field);

        if (! empty($options['layout']))
        {
            $field->setLayout($options['layout']);
        }

        if (! empty($options['size']))
        {
            $field->setSize($options['size']);
        }

        if (! empty($options['breakItems']))
        {
            $field->setBreakItems($options['breakItems']);
        }

        if (! empty($options['useButton']))
        {
            $field->setUseButton($options['useButton']);
        }

        if (! empty($options['valueSeparator']))
        {
            $field->setValueSeparator($options['valueSeparator']);
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

        TScript::create( " tcheckgroup_reload('{$formname}', '{$name}', `{$content}`); " );
    }

    /**
     * Enables the checkbox group field in the form.
     *
     * @param string $form_name The name of the form.
     * @param string $field     The name of the field.
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tcheckgroup_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables the checkbox group field in the form.
     *
     * @param string $form_name The name of the form.
     * @param string $field     The name of the field.
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tcheckgroup_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clears the values of the checkbox group field.
     *
     * @param string $form_name The name of the form.
     * @param string $field     The name of the field.
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tcheckgroup_clear_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Renders the checkbox group component on the screen.
     *
     * This method generates the necessary HTML structure for the checkbox group,
     * including button-based or switch-based display if enabled.
     */
    public function show()
    {
        $editable_class = (!parent::getEditable()) ? 'tfield_block_events' : '';
        
        if ($this->useButton)
        {
            echo "<div tcheckgroup=\"{$this->name}\" class=\"toggle-wrapper {$editable_class}\" ".$this->getPropertiesAsString('aria').' data-toggle="buttons">';
            echo '<div class="btn-group" style="clear:both;float:left;display:table">';
        }
        else
        {
            echo "<div tcheckgroup=\"{$this->name}\" class=\"toggle-wrapper {$editable_class}\" ".$this->getPropertiesAsString('aria').' role="group">';
        }
        
        if ($this->items)
        {
            // iterate the checkgroup options
            $i = 0;
            foreach ($this->items as $index => $label)
            {
                $button = $this->buttons[$index];
                $button->setName($this->name.'[]');
                $active = FALSE;
                $id = $button->getId();
                
                // verify if the checkbutton is checked
                if (!(is_null($this->value)) && (@in_array($index, $this->value)) OR $this->allItemsChecked)
                {
                    $button->setValue($index); // value=indexvalue (checked)
                    $active = TRUE;
                }
                
                // create the label for the button
                $obj = $this->labels[$index];
                $obj->{'class'} = $this->labelClass . ($active?'active':'');
                $obj->setTip($this->tag->title);
                
                if ($this->getSize() AND !$obj->getSize())
                {
                    $obj->setSize($this->getSize());
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
                    $classButton = 'filled-in';

                    if ($this->useSwitch)
                    {
                        $classButton .= ' btn-tswitch';
                    }

                    $button->setProperty('class', $classButton);
                    
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
