<?php
namespace Adianti\Widget\Form;

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Util\TImage;

/**
 * Html Editor Widget
 *

 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class THtmlEditor extends TField implements AdiantiWidgetInterface
{
    protected $id;
    protected $size;
    protected $formName;
    protected $toolbar;
    protected $customButtons;
    protected $completion;
    protected $options;
    private   $height;
    
    /**
     * Class Constructor
     *
     * Initializes the HTML Editor widget, setting default properties and creating the underlying `textarea` element.
     *
     * @param string $name The name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'THtmlEditor_'.mt_rand(1000000000, 1999999999);
        $this->toolbar = true;
        $this->options = [];
        $this->customButtons = [];
        // creates a tag
        $this->tag = new TElement('textarea');
        $this->tag->{'widget'} = 'thtmleditor';
    }
    
    /**
     * Sets the maximum length of the editor's content.
     *
     * @param int $length The maximum number of characters allowed
     */
    public function setMaxLength($length)
    {
        if ($length > 0)
        {
            $this->options['maxlength'] = $length;
        }
    }

    /**
     * Sets an option for the HTML editor.
     *
     * Available options are listed in the Summernote documentation.
     *
     * @link https://summernote.org/deep-dive/
     *
     * @param string $option The name of the option
     * @param mixed  $value  The value to set for the option
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Adds a custom button to the editor toolbar.
     *
     * Custom buttons allow adding new functionality to the editor.
     *
     * @link https://summernote.org/deep-dive/#custom-button
     *
     * @param string  $name      The name of the custom button
     * @param string  $function  JavaScript function to be executed when the button is clicked
     * @param string  $title     Tooltip text for the button
     * @param TImage  $icon      Icon for the button
     * @param bool    $showLabel Whether to display the button label (default: false)
     */
    public function addCustomButton($name, $function, $title, TImage $icon, $showLabel = false)
    {
        $this->customButtons[] = [
            'name' => $name,
            'function' => base64_encode($function),
            'title' => base64_encode($title),
            'showLabel' => $showLabel,
            'icon' => base64_encode($icon->getContents()),
        ];
    }
    
    /**
     * Sets the size of the editor.
     *
     * @param int|string $width  The width of the editor (e.g., "100%", "500px")
     * @param int|null   $height The height of the editor (optional)
     */
    public function setSize($width, $height = NULL)
    {
        $this->size   = $width;
        if ($height)
        {
            $this->height = $height;
        }
    }
    
    /**
     * Gets the current size of the editor.
     *
     * @return array An array containing the width and height of the editor
     */
    public function getSize()
    {
        return array( $this->size, $this->height );
    }
    
    /**
     * Disables the toolbar, enabling air mode.
     *
     * Air mode removes the toolbar and provides an inline editing experience.
     */
    public function disableToolbar()
    {
        $this->toolbar = false;
    }
    
    /**
     * Sets autocomplete options for the editor.
     *
     * @param array $options An array of options for autocomplete suggestions
     */
    function setCompletion($options)
    {
        $this->completion = $options;
    }
    
    /**
     * Enables the HTML editor field.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field     The name of the field to enable
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " thtmleditor_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables the HTML editor field.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field     The name of the field to disable
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " thtmleditor_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clears the content of the HTML editor field.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field     The name of the field to clear
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " thtmleditor_clear_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Reloads the autocomplete suggestions for the editor.
     *
     * @param string $field   The name or ID of the editor field
     * @param array  $options The new set of autocomplete options
     */
    public static function reloadCompletion($field, $options)
    {
        $options = json_encode($options);
        TScript::create(" thtml_editor_reload_completion( '{$field}', $options); ");
    }
    
    /**
     * Inserts text into the HTML editor at the current cursor position.
     *
     * @param string $form_name The name of the form containing the field
     * @param string $field     The name of the field where text will be inserted
     * @param string $content   The text content to insert
     */
    public static function insertText($form_name, $field, $content)
    {
        TScript::create( " thtmleditor_insert_text('{$form_name}', '{$field}', '{$content}'); " );
    }
    
    /**
     * Displays the HTML editor widget.
     *
     * This method initializes the editor, applies its settings, and inserts the appropriate JavaScript for rendering.
     */
    public function show()
    {
        $this->tag->{'id'} = $this->id;
        $this->tag->{'class'}  = 'thtmleditor';       // CSS
        $this->tag->{'name'}   = $this->name;   // tag name
        
        $ini = AdiantiApplicationConfig::get();
        $locale = !empty($ini['general']['locale']) ? $ini['general']['locale'] : 'pt-BR';
        
        // add the content to the textarea
        $this->tag->add(htmlspecialchars( (string) $this->value));
        
        // show the tag
        $div = new TElement('div');
        $div->style = 'display: none';
        $div->add($this->tag);
        $div->show();
        
        $options = $this->options;
        if (!$this->toolbar)
        {
            $options[ 'airMode'] = true;
        }
        if (!empty($this->completion))
        {
            $options[ 'completion'] = $this->completion;
        }
        
        $options_json = json_encode( $options );
        $buttons_json = json_encode( $this->customButtons );
        TScript::create(" thtmleditor_start( '{$this->tag->{'id'}}', '{$this->size}', '{$this->height}', '{$locale}', '{$options_json}', '{$buttons_json}' ); ");
        TScript::create(" $('#{$this->tag->id}').parent().show();");
        
        // check if the field is not editable
        if (!parent::getEditable())
        {
            TScript::create( " thtmleditor_disable_field('{$this->formName}', '{$this->name}'); " );
        }
    }
}
