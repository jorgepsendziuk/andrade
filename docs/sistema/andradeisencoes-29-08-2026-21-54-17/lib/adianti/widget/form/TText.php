<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TField;

use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Represents a multi-line text input field (textarea, also known as Memo) in a form.
 *
 * This widget allows defining size, maximum length, text transformation (uppercase/lowercase),
 * exit actions, and other properties.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TText extends TField implements AdiantiWidgetInterface
{
    private   $exitAction;
    private   $exitFunction;
    protected $id;
    protected $formName;
    protected $size;
    protected $height;
    
    /**
     * Class Constructor
     *
     * Initializes the TText widget with a unique identifier and default properties.
     *
     * @param string $name Widget's name
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'ttext_' . mt_rand(1000000000, 1999999999);
        
        // creates a <textarea> tag
        $this->tag = new TElement('textarea');
        $this->tag->{'class'} = 'tfield';       // CSS
        $this->tag->{'widget'} = 'ttext';
        // defines the text default height
        $this->height= 100;
    }
    
    /**
     * Sets the widget's dimensions.
     *
     * @param string|int $width  Widget's width (can be a percentage or pixel value)
     * @param string|int|null $height Widget's height (can be a percentage, pixel value, or null to keep default height)
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
     * Gets the current size of the widget.
     *
     * @return array<int|string> An array containing width and height values.
     */
    public function getSize()
    {
        return array( $this->size, $this->height );
    }
    
    /**
     * Defines the maximum length of the text input.
     *
     * @param int $length Maximum number of characters allowed
     */
    public function setMaxLength($length)
    {
        if ($length > 0)
        {
            $this->tag->{'maxlength'} = $length;
        }
    }
    
    /**
     * Defines an action to be executed when the user exits the text field.
     *
     * @param TAction $action The action to be executed
     *
     * @throws Exception If the action is not static
     */
    function setExitAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->exitAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Defines a JavaScript function to be executed when the user exits the text field.
     *
     * @param string $function JavaScript function name
     */
    public function setExitFunction($function)
    {
        $this->exitFunction = $function;
    }
    
    /**
     * Forces the text input to always be in lowercase.
     *
     * Applies JavaScript transformation and sets CSS style accordingly.
     */
    public function forceLowerCase()
    {
        $this->tag->{'oninput'} = "tentry_lower(this)";
        //$this->tag->{'onBlur'} = "return tentry_lower(this)";
        $this->tag->{'forcelower'} = "1";
        $this->setProperty('style', 'text-transform: lowercase');
        
    }
    
    /**
     * Forces the text input to always be in uppercase.
     *
     * Applies JavaScript transformation and sets CSS style accordingly.
     */
    public function forceUpperCase()
    {
        $this->tag->{'oninput'} = "tentry_upper(this)";
        //$this->tag->{'onBlur'} = "return tentry_upper(this)";
        $this->tag->{'forceupper'} = "1";
        $this->setProperty('style', 'text-transform: uppercase');
    }
    
    /**
     * Retrieves the value of the text input from the $_POST request.
     *
     * @return string The posted data value or an empty string if not set
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            return $_POST[$name];
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Renders the widget and applies its properties.
     *
     * Displays the textarea element with defined size, styles, and actions.
     * Throws an exception if the exit action is set but the form is not properly registered.
     *
     * @throws Exception If the form containing this field is not properly set
     */
    public function show()
    {
        $this->tag->{'name'}  = $this->name;   // tag name
        
        if ($this->size)
        {
            $size = (strstr((string) $this->size, '%') !== FALSE) ? $this->size : "{$this->size}px";
            $this->setProperty('style', "width:{$size};", FALSE); //aggregate style info
        }
        
        if ($this->height)
        {
            $height = (strstr($this->height, '%') !== FALSE) ? $this->height : "{$this->height}px";
            $this->setProperty('style', "height:{$height}", FALSE); //aggregate style info
        }
        
        if ($this->id and empty($this->tag->{'id'}))
        {
            $this->tag->{'id'} = $this->id;
        }
        
        // check if the field is not editable
        if (!parent::getEditable())
        {
            // make the widget read-only
            $this->tag->{'readonly'} = "1";
            $this->tag->{'class'} = $this->tag->{'class'} == 'tfield' ? 'tfield_disabled' : $this->tag->{'class'} . ' tfield_disabled'; // CSS
            $this->tag->{'tabindex'} = '-1';
        }
        
        if (isset($this->exitAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->exitAction->serialize(FALSE);
            $this->setProperty('exitaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
            $this->setProperty('onBlur', $this->getProperty('exitaction'), FALSE);
        }
        
        if (isset($this->exitFunction))
        {
            $this->setProperty('onBlur', $this->exitFunction, FALSE );
        }
        
        // add the content to the textarea
        $this->tag->add(htmlspecialchars( (string) $this->value));
        // show the tag
        $this->tag->show();
    }
}
