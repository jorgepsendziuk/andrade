<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Color Picker Widget
 *
 * This widget provides a color picker input field with various customization options.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TColor extends TEntry implements AdiantiWidgetInterface
{
    const THEME_CLASSIC  = 'classic';
    const THEME_NANO     = 'nano';
    const THEME_MONOLITH = 'monolith';

    protected $formName;
    protected $name;
    protected $id;
    protected $size;
    protected $changeFunction;
    protected $changeAction;
    protected $theme;
    protected $options;
    
    /**
     * Class Constructor
     *
     * Initializes the color picker widget, setting its default properties and options.
     *
     * @param string $name The name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'tcolor_'.mt_rand(1000000000, 1999999999);
        $this->tag->{'widget'} = 'tcolor';
        $this->tag->{'autocomplete'} = 'off';
        $this->setSize('100%');

        $this->theme = self::THEME_CLASSIC;
        $this->options = [
            'swatches' => [
                '#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3', '#03A9F4',
                '#00BCD4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#ffe821', '#FFC107',
                '#FF9800', '#FF5722', '#795548', '#9E9E9E', '#607D8B', '#000000', '#ffffff',
            ],
            'components' => [
                'preview' => true,
                'opacity' => true,
                'hue' => true,
                'interaction' => [
                    'hex' => false,
                    'rgba' => false,
                    'hsla' => false,
                    'hsva' => false,
                    'cmyk' => false,
                    'input' => false,
                    'clear' => true,
                    'save' => true
                ]
            ],
        ];
    }

    /**
     * Set an extra option for the color picker
     *
     * Allows setting additional configuration options for the color picker.
     * Refer to the component documentation for available options.
     *
     * @see https://github.com/Simonwep/pickr#options
     *
     * @param string $option The name of the option to set
     * @param mixed  $value  The value of the option (can be an array for merging)
     */
    public function setOption($option, $value)
    {
        if (is_array($value))
        {
            $oldOptions = $this->options[$option]??[];

            $value = array_merge($oldOptions, $value);
        }

        $this->options[$option] = $value;
    }

    /**
     * Get all options of the color picker
     *
     * @return array The current configuration options of the widget
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get a specific option from the color picker
     *
     * @param string $option The name of the option to retrieve
     *
     * @return mixed|null The value of the option if set, otherwise null
     */
    public function getOption($option)
    {
        if (empty($this->options[$option]))
        {
            return null;
        }

        return $this->options[$option];
    }

    /**
     * Set the theme for the color picker
     *
     * Changes the visual appearance of the color picker based on predefined themes.
     *
     * @param string $theme The theme to be applied (classic, nano, or monolith)
     */
    public function setTheme($theme)
    {
        if (! in_array($theme, [self::THEME_CLASSIC, self::THEME_NANO, self::THEME_MONOLITH]) )
        {
            $theme = self::THEME_CLASSIC;
        }

        $this->theme = $theme;
    }
    
    /**
     * Enable a form field
     *
     * Allows interaction with the color picker field in a given form.
     *
     * @param string $form_name The name of the form
     * @param string $field The name of the field to enable
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tcolor_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable a form field
     *
     * Prevents user interaction with the color picker field in a given form.
     *
     * @param string $form_name The name of the form
     * @param string $field The name of the field to disable
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tcolor_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Set a JavaScript function to execute when the color changes
     *
     * This function will be triggered when the user selects a different color.
     *
     * @param string $function The JavaScript function code
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }
    
    /**
     * Set an action to be executed when the color value changes
     *
     * Defines a server-side action that is triggered when the user changes the color.
     *
     * @param TAction $action The action to execute on change
     */
    public function setChangeAction(TAction $action)
    {
        $this->changeAction = $action;
    }
    
    /**
     * Render the color picker widget on the screen
     *
     * Generates the HTML and JavaScript required for the color picker to function,
     * applying configurations, event handlers, and displaying the widget.
     *
     * @throws Exception If the form is not properly set when using change actions
     */
    public function show()
    {
        $wrapper = new TElement('div');
        $wrapper->{'class'} = 'input-group color-div colorpicker-component';
        $wrapper->{'style'} = 'float:inherit';
        
        $span = new TElement('span');
        $span->{'class'} = 'input-group-addon tcolor';
        
        $outer_size = 'undefined';
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $outer_size = $this->size;
            $this->size = '100%';
        }
        
        if ($this->changeAction)
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            
            $string_action = $this->changeAction->serialize(FALSE);
            $this->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
            $this->changeFunction = $this->getProperty('changeaction');
        }
        
        $i = new TElement('i');
        $i->{'class'} = 'tcolor-icon';
        $span->add($i);
        ob_start();
        parent::show();
        $child = ob_get_contents();
        ob_end_clean();
        $wrapper->add($child);
        $wrapper->add($span);
        $wrapper->show();
        
        $options = json_encode($this->options);

        TScript::create("tcolor_start('{$this->id}', '{$outer_size}', '{$this->theme}', function(color) { {$this->changeFunction} }, {$options}); ");
        
        if (!parent::getEditable())
        {
            self::disableField($this->formName, $this->name);
        }
    }
}