<?php
namespace Adianti\Widget\Form;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Base\TStyle;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TForm;
use Exception;

/**
 * Class TArrowStep
 *
 * This class represents a step-based navigation component using arrows.
 * It allows users to navigate through different steps visually and interactively.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Lucas Tomasi
 * @author     Matheus Agnes Dias
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TArrowStep extends TField implements AdiantiWidgetInterface
{
    protected $container;
    protected $items;
    protected $colorItems;
    protected $action;
    protected $selected;
    protected $width;
    protected $height;
    protected $name;
    protected $id;
    protected $color;
    protected $fontColor;
    protected $disableColor;
    protected $disableFontColor;
    protected $hideText;
    protected $fontSize;
    protected $formName;
    protected $className;
    protected $editable;

    /**
     * Constructor
     *
     * Initializes the TArrowStep component with default values.
     *
     * @param string $name The name of the component.
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->tag = new TElement('div');

        $this->id = 'tarrowstep_' . mt_rand(1000000000, 1999999999);

        $this->className = "arrow_steps_{$name}";
        $this->class = $this->className;
        
        $this->container = new TElement('div');
        $this->container->{'class'} = 'arrow_steps';
        
        $this->colorItems = [];

        $this->name = $name;
        $this->editable = true;
        $this->hideText = false;
        $this->height = 50;
        $this->width = '100%';
        $this->fontSize = '14px';
        $this->color = "#6c757d";
        $this->fontColor = "#ffffff";
        $this->disableColor = "#e8e8e8";
        $this->disableFontColor = "#333";

        parent::add( $this->container );
    }

    /**
     * Disables the arrow step field.
     *
     * @param string $formName The name of the form containing the field.
     * @param string $name     The name of the arrow step field to disable.
     */
    public static function disableField($formName, $name)
    {
        TScript::create("tarrowstep_disable_field('{$name}');");
    }

    /**
     * Enables the arrow step field.
     *
     * @param string $formName The name of the form containing the field.
     * @param string $name     The name of the arrow step field to enable.
     */
    public static function enableField($formName, $name)
    {
        TScript::create("tarrowstep_enable_field('{$name}');");
    }


    /**
     * Clears the currently selected item in the arrow step field.
     *
     * @param string $formName The name of the form containing the field.
     * @param string $name     The name of the arrow step field to clear.
     */
    public static function clearField($formName, $name)
    {
        TScript::create("tarrowstep_clear('{$name}');");
    }

    /**
     * Sets the current step in the arrow step field.
     *
     * @param string $name  The name of the arrow step field.
     * @param string $value The value of the step to set as current.
     */
    public static function defineCurrent($name, $value)
    {
        TScript::create("tarrowstep_set_current('{$name}', '{$value}');");
    }
    
    /**
     * Sets whether the field is editable.
     *
     * @param bool $editable True to make the field editable, false otherwise.
     */
    public function setEditable($editable)
    {
        $this->editable= $editable;
    }

    /**
     * Checks if the field is editable.
     *
     * @return bool True if the field is editable, false otherwise.
     */
    public function getEditable()
    {
        return $this->editable;
    }

    /**
     * Retrieves the posted data for the arrow step field.
     *
     * @return mixed|null The posted value or null if not set.
     */
    public function getPostData()
    {
        if (isset($_POST[$this->name]))
        {
            return $_POST[$this->name] ? $_POST[$this->name] : null;
        }

        return null;
    }

    /**
     * Sets the form name associated with the field.
     *
     * @param string $name The name of the form.
     */
    public function setFormName($name)
    {
        $this->formName = $name;
    }

    /**
     * Sets the name of the field.
    *
    * @param string $name The field name.
    */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Retrieves the name of the field.
     *
     * @return string The field name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the value of the currently selected step.
     *
     * @param mixed $value The value of the selected step.
     */
    public function setValue($value)
    {
        $this->setCurrentKey($value);
    }

    /**
     * Gets the value of the currently selected step.
     *
     * @return mixed|null The selected step value or null if not set.
     */
    public function getValue()
    {
        return $this->getCurrent();
    }

    /**
     * Defines whether the step text should be hidden.
     *
     * @param bool $hide True to hide text, false to display it.
     */
    public function setHideText(bool $hide = true)
    {
        $this->hideText = $hide;
    }

    /**
     * Sets the font size for the step text.
     *
     * @param string|int $fontSize The font size (e.g., '14px' or '100%').
     */
    public function setFontSize($fontSize)
    {
        $fontSize = (strstr($fontSize, '%') !== FALSE) ? $fontSize : "{$fontSize}px";

        $this->fontSize = $fontSize;
    }

    /**
     * Sets the color for filled (active) steps.
     *
     * @param string      $color     The background color.
     * @param string|null $fontColor (Optional) The font color.
     */
    public function setFilledColor(string $color, $fontColor = null)
    {
        $this->color = $color;

        if ($fontColor)
        {
            $this->fontColor = $fontColor;
        }
    }

    /**
     * Sets the font color for filled (active) steps.
     *
     * @param string $fontColor The font color.
     */
    public function setFilledFontColor(string $fontColor)
    {
        $this->fontColor = $fontColor;
    }

    /**
     * Sets the color for unfilled (inactive) steps.
     *
     * @param string      $color     The background color.
     * @param string|null $fontColor (Optional) The font color.
     */
    public function setUnfilledColor(string $color, $fontColor = null)
    {
        $this->disableColor = $color;

        if ($fontColor)
        {
            $this->disableFontColor = $fontColor;
        }
    }

    /**
     * Sets the font color for unfilled (inactive) steps.
     *
     * @param string $color The font color.
     */
    public function setUnfilledFontColor(string $color)
    {
        $this->disableFontColor = $color;
    }
    
    /**
     * Sets the width of the component.
     *
     * @param int|string $width The width in pixels or as a percentage.
     */
    public function setWidth($width)
    {
        if (is_numeric($width))
        {
            $this->width = $width . 'px';
        }
        else
        {
            $this->width = $width;
        }
    }

    /**
     * Retrieves the width of the component.
     *
     * @return string The width value.
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets both the width and height of the component.
     *
     * @param int|string      $width  The width in pixels or as a percentage.
     * @param int|string|null $height (Optional) The height in pixels.
     */
    public function setSize($width, $height = null)
    {
        if ($height)
        {
            $this->setHeight($height);
        }

        $this->setWidth($width);
    }

    /**
     * Sets the height of the component.
     *
     * @param int $height The height in pixels.
     *
     * @throws Exception If the height is not numeric.
     */
    public function setHeight($height)
    {
        if (! is_numeric($height))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $height, __METHOD__));
        }

        $this->height = $height;
    }

    /**
     * Retrieves the height of the component.
     *
     * @return int The height in pixels.
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Retrieves the size of the component.
     *
     * @return null Always returns null.
     */
    public function getSize()
    {
        return null;
    }

    /**
     * Adds an item to the step navigation.
     *
     * @param string      $title The step title.
     * @param string|null $id    (Optional) The step ID.
     * @param string|null $color (Optional) The step color.
     */
    public function addItem($title, $id = null, $color = null)
    {
        if ($id)
        {
            $this->items[$id] = $title;
            $this->colorItems[$id] = $color;
        }
        else
        {
            $this->items[] = $title;
            $this->colorItems[] = $color;
        }
    }

    /**
     * Sets the colors for specific items.
     *
     * @param array $colorItems Associative array of item colors.
     */
    public function setColorItems($colorItems)
    {
        $this->colorItems = $colorItems;
    }
    
    /**
     * Sets the items for the step navigation.
     *
     * @param array $items Associative array of items with keys as IDs and values as titles.
     */
    public function setItems($items)
    {
        if ($items)
        {
            $this->items = [];

            foreach($items as $key => $title)
            {
                $this->items[$key] = $title;
            }
        }
    }

    /**
     * Adds multiple items to the step navigation.
     *
     * @param array $items Associative array of items with keys as IDs and values as titles.
     */
    public function addItems($items)
    {
        if ($items)
        {
            foreach($items as $key => $title)
            {
                $this->items[$key] = $title;
            }
        }
    }
    
    /**
     * Retrieves all items in the step navigation.
     *
     * @return array The list of items.
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Retrieves a specific item from the step navigation.
     *
     * @param string $key The key of the item.
     *
     * @return string|null The item title or null if not found.
     */
    public function getItem($key)
    {
        return ! empty($this->items[$key]) ? $this->items[$key] : NULL;
    }

    /**
     * Sets an action to be executed when a step is clicked.
     *
     * @param TAction $action The action object.
     */
    public function setAction(TAction $action)
    {
        $this->action = $action;
    }

    /**
     * Retrieves the action associated with the steps.
     *
     * @return TAction|null The action object or null if not set.
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the currently selected step by its key.
     *
     * @param string $key The key of the step.
     */
    public function setCurrentKey($key)
    {
        $this->selected = $key;
    }

    /**
     * Retrieves the currently selected step key.
     *
     * @return string|null The selected step key or null if none selected.
     */
    public function getCurrent()
    {
        return $this->selected;
    }

    /**
     * Sets the currently selected step by its title.
     *
     * @param string $title The title of the step.
     */
    public function setCurrent($title)
    {
        if (in_array($title, $this->items))
        {
            $this->selected = array_search($title, $this->items);
        }
    }
    
    /**
     * Generates a serialized action for a given step.
     *
     * @param string  $key      The key of the step.
     * @param string  $value    The value of the step.
     * @param bool    $selected Whether the step is selected.
     *
     * @return string The serialized JavaScript action.
     */
    private function getSerializedAction($key, $value, $selected = false)
    {
        $this->action->setParameter('value', $value);
        $this->action->setParameter('__selected', $selected);

        if (!TForm::getFormByName($this->formName) instanceof TForm)
        {
            return "__adianti_load_page('{$this->action->serialize(true)}');";
        }
        else
        {
            $string_action = $this->action->serialize(FALSE);

            $key = $this->id ."_" . str_replace([' ', '-'], ['', ''], $key);

            return "setTimeout(function(){__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$key}', 'callback')},0)";
        }
    }

    /**
     * Creates an HTML representation of a step item.
     *
     * @param string  $key      The key of the step.
     * @param string  $value    The displayed text of the step.
     * @param bool    $selected Whether the step is selected.
     */
    private function makeItem($key, $value, $selected = false)
    {
        $div = new TElement('div');
        $div->{'class'}  = 'step';
        $div->{'data-key'} = $key;
        $div->{'class'} .= $selected && ! is_null($this->selected) ? ' current ' : '';

        $input = new TElement('input');
        $input->{'type'} = 'hidden';
        $input->{'id'} = $this->id ."_" . str_replace([' ', '-'], ['', ''], $key);
        $input->{'value'} = $key;

        $div->add( $input );
        $this->container->add( $div );
        
        $span = new TElement('span');
        $span->add($value);
        
        if ($this->action)
        {
            $div->{'onclick'} = $this->getSerializedAction($key, $value, $selected);
        }
        
        if (! $this->hideText)
        {
            $div->add($span);
            $this->style = 'overflow-x: auto;';
        }
        else
        {
            $div->title = $value;
        }
    }

    /**
     * Generates and applies CSS styles for the component.
     *
     * It defines styles for steps, colors, and different states (selected, unselected).
     */
    private function makeStyle()
    {
        $size1 = $this->height/2 . 'px';
        $size2 = $this->height/3 . 'px';

        $styles = new TElement('style');
        $styles->type = 'text/css';
        $styles->media = 'screen';

        $styleClassHeight = new TStyle($this->className.' .arrow_steps');
        $styleClassHeight->height = $this->height .'px';
        $styles->add($styleClassHeight);

        $styleClassBackground = new TStyle($this->className.'::-webkit-scrollbar-thumb,.'.$this->className.' .step.current,.'.$this->className.' .step.preview-current');
        $styleClassBackground->{"background-color"} = $this->color;
        $styleClassBackground->{"color"} = $this->fontColor;
        $styles->add($styleClassBackground);

        $styleClassBackgroundDisable = new TStyle($this->className.' .step');
        $styleClassBackgroundDisable->{"background-color"} = $this->disableColor;
        $styleClassBackgroundDisable->{"color"} = $this->disableFontColor;
        $styleClassBackgroundDisable->{"font-size"} = $this->fontSize;
        $styleClassBackgroundDisable->{"padding-left"} = "{$size2}";
        $styles->add($styleClassBackgroundDisable);
        
        $styleClassBorder = new TStyle($this->className.' .step.current:after,.'.$this->className.' .step.preview-current:after');
        $styleClassBorder->{"border-left-color"} = $this->color;
        $styleClassBorder->{"border-left-width"} = $size2;
        $styles->add($styleClassBorder);

        $styleClassBorderHeight = new TStyle($this->className.' .step:after,.'.$this->className.' .step:before');
        $styleClassBorderHeight->{"border-top-width"} =  $size1;
        $styleClassBorderHeight->{"border-bottom-width"} = $size1;
        $styleClassBorderHeight->{"right"} = "-{$size2}";
        $styleClassBorderHeight->{"border-left-width"} = $size2;
        $styleClassBorderHeight->{"border-left-color"} = $this->disableColor;
        $styles->add($styleClassBorderHeight);
        
        $styleClassBorderStepBefore = new TStyle($this->className.' .step:before');
        $styleClassBorderStepBefore->{'border-left-width'} = $size2;
        $styleClassBorderStepBefore->{"border-left-color"} = 'white';
        $styles->add($styleClassBorderStepBefore);

        $styleClassBorderSpanBefore = new TStyle($this->className.' span:before');
        $styleClassBorderSpanBefore->{'left'} = "-{$size2}";
        $styles->add($styleClassBorderSpanBefore);

        if (! empty($this->colorItems))
        {
            foreach($this->colorItems as $key => $color)
            {
                $styleClassBackgroundStep = new TStyle("{$this->className} .step.current[data-key=\"{$key}\"],.{$this->className} .step.preview-current[data-key=\"{$key}\"]");
                $styleClassBackgroundStep->{"background-color"} = $color;
                $styles->add($styleClassBackgroundStep);
                
                $styleClassBackgroundStepArrow = new TStyle("{$this->className} .step.current[data-key=\"{$key}\"]:after,.{$this->className} .step.preview-current[data-key=\"{$key}\"]:after");
                $styleClassBackgroundStepArrow->{"border-left-color"} = $color;
                $styles->add($styleClassBackgroundStepArrow);
            }
        }

        parent::add($styles);
    }

    /**
     * Renders the component and outputs it as HTML.
     */
    public function show()
    {
        $this->makeStyle();

        if ($this->items)
        {
            $selected = true;

            foreach($this->items as $key => $value)
            {
                $this->makeItem($key, $value, $selected);

                if ($this->selected == $key)
                {
                    $selected = false;
                }
            }
        }

        $input = new TElement('input');
        $input->{'type'} = 'hidden';
        $input->{'widget'} = 'tarrowstep';
        $input->{'id'} = $this->id;
        $input->{'name'} = $this->name;
        $input->{'value'} = $this->selected;

        parent::add($input);

        if (! $this->editable)
        {
            $this->className .= ' disabled ';
        }

        if ($this->width)
        {
            $this->style = 'width: ' . $this->width;
        }

        parent::setProperty('class', $this->className . " div_arrow_steps");
        
        TScript::create("tarrowstep_start('{$this->name}');");

        parent::show();
    }
}