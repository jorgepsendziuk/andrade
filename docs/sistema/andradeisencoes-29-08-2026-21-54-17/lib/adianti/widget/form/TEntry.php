<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Util\TImage;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Entry Widget
 *
 * Represents a text input field widget for forms, supporting masks, numeric formatting,
 * auto-completion, and other advanced input handling.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TEntry extends TField implements AdiantiWidgetInterface
{
    private $toggleVisibility;
    private $mask;
    protected $completion;
    protected $numericMask;
    protected $decimals;
    protected $decimalsSeparator;
    protected $thousandSeparator;
    protected $reverse;
    protected $allowNegative;
    protected $replaceOnPost;
    protected $exitFunction;
    protected $exitAction;
    protected $enterAction;
    protected $id;
    protected $formName;
    protected $name;
    protected $value;
    protected $minLength;
    protected $delimiter;
    protected $exitOnEnterOn;
    protected $innerIcon;
    
    /**
     * Class Constructor
     *
     * Initializes a new instance of the TEntry class with a given name.
     *
     * @param string $name The name of the input field.
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'tentry_' . mt_rand(1000000000, 1999999999);
        $this->toggleVisibility = FALSE;
        $this->numericMask = FALSE;
        $this->replaceOnPost = FALSE;
        $this->minLength = 1;
        $this->exitOnEnterOn = FALSE;
        $this->tag->{'type'}   = 'text';
        $this->tag->{'widget'} = 'tentry';
    }
    
    /**
     * Enables or disables the toggle visibility functionality.
     *
     * When enabled, an eye icon is added to the field, allowing users to toggle the visibility
     * of the input content (useful for password fields).
     *
     * @param bool $toggleVisibility Whether to enable or disable toggle visibility (default: TRUE).
     */
    public function enableToggleVisibility($toggleVisibility = TRUE)
    {
        $this->toggleVisibility = $toggleVisibility;
        if($toggleVisibility)
        {
            $this->innerIcon = new TImage('fa:eye');
            $this->innerIcon->{'class'} .= ' tentry-toggle-visibility input-inner-icon right' ;
        }
    }

    /**
     * Sets the input type of the field (e.g., text, password, email).
     *
     * @param string $type The type of the input field.
     */
    public function setInputType($type)
    {
        $this->tag->{'type'}  = $type;
    }
    
    /**
     * Sets an inner icon to the input field.
     *
     * @param TImage $image The icon image.
     * @param string $side The position of the icon ('left' or 'right', default: 'right').
     */
    public function setInnerIcon(TImage $image, $side = 'right')
    {
        $this->innerIcon = $image;
        $this->innerIcon->{'class'} .= ' input-inner-icon ' . $side;
        
        if ($side == 'left')
        {
            $this->setProperty('style', "padding-left:23px", false); //aggregate style info
        }
    }
    
    /**
     * Enables form submission when the Enter key is pressed.
     */
    public function exitOnEnter()
    {
        $this->exitOnEnterOn = true;
    }
    
    /**
     * Sets an input mask for the field.
     *
     * @param string $mask The mask pattern.
     * @param bool $replaceOnPost Whether to replace the input value on postback (default: FALSE).
     */
    public function setMask($mask, $replaceOnPost = FALSE)
    {
        $this->mask = $mask;
        $this->replaceOnPost = $replaceOnPost;
    }
    
    /**
     * Sets a numeric input mask for the field.
     *
     * @param int $decimals Number of decimal places.
     * @param string $decimalsSeparator The character used as a decimal separator.
     * @param string $thousandSeparator The character used as a thousand separator.
     * @param bool $replaceOnPost Whether to replace the value on postback (default: FALSE).
     * @param bool $reverse Whether to enable reverse mode (default: FALSE).
     * @param bool $allowNegative Whether negative values are allowed (default: TRUE).
     */
    public function setNumericMask($decimals, $decimalsSeparator, $thousandSeparator, $replaceOnPost = FALSE, $reverse = FALSE, $allowNegative = TRUE)
    {
        if (empty($decimalsSeparator))
        {
            $decimals = 0;
        }
        else if (empty($decimals))
        {
            $decimalsSeparator = '';
        }
        
        $this->setProperty('style', "text-align:right;", false); //aggregate style info
        $this->numericMask = TRUE;
        $this->decimals = $decimals;
        $this->reverse = $reverse;
        $this->allowNegative = $allowNegative;
        $this->decimalsSeparator = $decimalsSeparator;
        $this->thousandSeparator = $thousandSeparator;
        $this->replaceOnPost = $replaceOnPost;
        
        $dec_pattern = $decimalsSeparator == '.' ? '\\.' : $decimalsSeparator;
        $tho_pattern = $thousandSeparator == '.' ? '\\.' : $thousandSeparator;
        
        //$this->tag->{'pattern'}   = '^\\$?(([1-9](\\d*|\\d{0,2}('.$tho_pattern.'\\d{3})*))|0)('.$dec_pattern.'\\d{1,2})?$';
        $this->tag->{'pattern'}   = '^\\$?(([1-9](\\d*|\\d{0,'.$decimals.'}('.$tho_pattern.'\\d{3})*))|0)('.$dec_pattern.'\\d{1,'.$decimals.'})?$';
        $this->tag->{'inputmode'} = 'numeric';
        $this->tag->{'data-nmask'}  = $decimals.$decimalsSeparator.$thousandSeparator;
    }
    
    /**
     * Sets the value of the input field.
     *
     * If a numeric mask or another transformation is applied, the value is formatted accordingly.
     *
     * @param string|float|int|null $value The value to set.
     */
    public function setValue($value)
    {
        if ($this->replaceOnPost)
        {
            if ($this->numericMask && is_numeric($value))
            {
                parent::setValue(number_format($value, $this->decimals, $this->decimalsSeparator, $this->thousandSeparator));
            }
            else if ($this->mask)
            {
                parent::setValue($this->formatMask($this->mask, $value));
            }
            else
            {
                parent::setValue($value);
            }
        }
        else
        {
            parent::setValue($value);
        }
    }
    
    /**
     * Retrieves the value of the field from a form submission (POST request).
     *
     * @return string|float The submitted value, formatted based on mask settings.
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            if ($this->replaceOnPost)
            {
                $value = $_POST[$name];
                
                if ($this->numericMask)
                {
                    $value = str_replace( $this->thousandSeparator, '', $value);
                    $value = str_replace( $this->decimalsSeparator, '.', $value);
                    return $value;
                }
                else if ($this->mask)
                {
                    return preg_replace('/[^a-z\d]+/i', '', $value);
                }
                else
                {
                    return $value;
                }
            }
            else
            {
                return $_POST[$name];
            }
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Sets the maximum length allowed for the input field.
     *
     * @param int $length The maximum number of characters.
     */
    public function setMaxLength($length)
    {
        if ($length > 0)
        {
            $this->tag->{'maxlength'} = $length;
        }
    }
    
    /**
     * Sets the auto-completion options for the input field.
     *
     * @param array $options An array of completion options.
     */
    function setCompletion($options)
    {
        $this->completion = $options;
    }
    
    /**
     * Sets an action to be executed when the user leaves the input field.
     *
     * @param TAction $action The action object.
     *
     * @throws Exception If the action is not static.
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
     * Sets an action to be executed when the user leaves the input field.
     *
     * @param TAction $action The action object.
     */
    function setEnterAction(TAction $action)
    {
        $this->enterAction = $action;
    }
    
    /**
     * Sets a JavaScript function to be executed when the user leaves the input field.
     *
     * @param string $function The JavaScript function code.
     */
    public function setExitFunction($function)
    {
        $this->exitFunction = $function;
    }
    
    /**
     * Disables the browser's autocomplete feature for the input field.
     */
    public function disableAutoComplete()
    {
        $this->tag->{'autocomplete'} = 'off';
        
    }
    
    /**
     * Forces the input text to be converted to lowercase.
     */
    public function forceLowerCase()
    {
        $this->tag->{'oninput'} = "tentry_lower(this)";
        //$this->tag->{'onBlur'} = "tentry_lower(this)";
        $this->tag->{'forcelower'} = "1";
        $this->setProperty('style', "text-transform: lowercase;", false); //aggregate style info
    }
    
    /**
     * Forces the input text to be converted to uppercase.
     */
    public function forceUpperCase()
    {
        $this->tag->{'oninput'} = "tentry_upper(this)";
        //$this->tag->{'onBlur'} = "tentry_upper(this)";
        $this->tag->{'forceupper'} = "1";
        $this->setProperty('style', "text-transform: uppercase;", false); //aggregate style info
    }
    
    /**
     * Sets the delimiter used for auto-completion.
     *
     * @param string $delimiter The delimiter character.
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }
    
    /**
     * Sets the minimum length required before triggering auto-completion.
     *
     * @param int $length The minimum number of characters.
     */
    public function setMinLength($length)
    {
        $this->minLength = $length;
    }
    
    /**
     * Reloads the auto-completion list dynamically.
     *
     * @param string $field The field name or ID.
     * @param array $list The array of options for auto-completion.
     * @param array|null $options Additional auto-completion options (optional).
     */
    public static function reloadCompletion($field, $list, $options = null, $timeout = null)
    {
        $list_json = json_encode($list);
        if (is_null($options))
        {
            $options = [];
        }
        
        $options_json = json_encode( $options );

        if($timeout)
        {
            TScript::create(" tentry_autocomplete_by_name( '{$field}', {$list_json}, '{$options_json}'); ", true, $timeout);
        }
        else
        {
            TScript::create(" tentry_autocomplete_by_name( '{$field}', {$list_json}, '{$options_json}'); ");
        }
        
    }
    
    /**
     * Applies a formatting mask to a given value.
     *
     * @param string $mask The mask pattern.
     * @param string|array $value The value to be formatted.
     *
     * @return string|array The formatted value.
     */
    protected function formatMask($mask, $value)
    {
        if(is_array($value))
        {
            foreach ($value as $key => $item)
            {
                $value[$key] = self::formatMask($mask, $item);
            }

            return $value;
        }
        else if ($value)
        {
            $value_index  = 0;
            $clear_result = '';
        
            $value = preg_replace('/[^a-z\d]+/i', '', $value);
            
            for ($mask_index=0; $mask_index < strlen($mask); $mask_index ++)
            {
                $mask_char = substr($mask, $mask_index,  1);
                $text_char = substr($value, $value_index, 1);
        
                if (in_array($mask_char, array('-', '_', '.', '/', '\\', ':', '|', '(', ')', '[', ']', '{', '}', ' ')))
                {
                    $clear_result .= $mask_char;
                }
                else
                {
                    $clear_result .= $text_char;
                    $value_index ++;
                }
            }
            return $clear_result;
        }
    }
    
    /**
     * Dynamically changes the mask of an input field.
     *
     * @param string $formName The name of the form.
     * @param string $name The name of the input field.
     * @param string $mask The new mask pattern.
     */
    public static function changeMask($formName, $name, $mask)
    {
        TScript::create("tentry_change_mask( '{$formName}', '{$name}', '{$mask}');");
    }
    
    /**
     * Renders the input field widget on the screen.
     *
     * Applies all defined properties, masks, completion, icons, and event bindings before displaying the field.
     *
     * @throws Exception If the form field is not properly associated with a TForm.
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'name'}  = $this->name;    // TAG name
        $this->tag->{'value'} = htmlspecialchars( (string) $this->value, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // TAG value
        
        if (!empty($this->size))
        {
            if (strstr((string) $this->size, '%') !== FALSE)
            {
                $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
            }
            else
            {
                $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
            }
        }
        
        if ($this->id and empty($this->tag->{'id'}))
        {
            $this->tag->{'id'} = $this->id;
        }
        
        if (isset($this->exitAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->exitAction->serialize(FALSE);
            $this->setProperty('exitaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
        }
        
        if (isset($this->exitAction))
        {
            // just aggregate onBlur, if the previous one does not have return clause
            if (strstr((string) $this->getProperty('onBlur'), 'return') == FALSE)
            {
                $this->setProperty('onBlur', $this->getProperty('exitaction'), FALSE);
            }
            else
            {
                $this->setProperty('onBlur', $this->getProperty('exitaction'), TRUE);
            }
        }

        if (isset($this->enterAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $url = $this->enterAction->serialize(FALSE);
            if ($this->enterAction->isStatic())
            {
                $url .= '&static=1';
            }
            $url = htmlspecialchars($url);

            $wait_message = AdiantiCoreTranslator::translate('Loading');

            $this->setProperty('data-enteraction', "Adianti.waitMessage = '$wait_message'; __adianti_post_data('{$this->formName}', '{$url}'); return false;");

            $this->exitOnEnterOn = true;
        }

        if (!parent::getEditable())
        {
            $this->tag->{'readonly'} = "1";
            $this->tag->{'class'} .= ' tfield_disabled'; // CSS
        }
        
        if (isset($this->exitFunction))
        {
            if (strstr((string) $this->getProperty('onBlur'), 'return') == FALSE)
            {
                $this->setProperty('onBlur', $this->exitFunction, FALSE);
            }
            else
            {
                $this->setProperty('onBlur', $this->exitFunction, TRUE);
            }
        }
        
        if ($this->mask)
        {
            TScript::create( "tentry_new_mask( '{$this->id}', '{$this->mask}'); ");
        }

        if($this->toggleVisibility)
        {
            $this->{'type'} = 'password';
            TScript::create(" tentry_toggle_visibility( '{$this->id}' ); ");
        }

        if (!empty($this->innerIcon))
        {
            $icon_wrapper = new TElement('div');
            $icon_wrapper->{'class'} = 'inner-icon-container';
            $icon_wrapper->{'id'} = "{$this->id}-container";
            $icon_wrapper->add($this->tag);
            $icon_wrapper->add($this->innerIcon);
            $icon_wrapper->show();
        }
        else
        {
            // shows the tag
            $this->tag->show();
        }
        
        if (isset($this->completion))
        {
            $options = [ 'minChars' => $this->minLength ];
            if (!empty($this->delimiter))
            {
                $options[ 'delimiter'] = $this->delimiter;
            }
            $options_json = json_encode( $options );
            $list = json_encode($this->completion);
            TScript::create(" tentry_autocomplete( '{$this->id}', $list, '{$options_json}'); ");
        }
        
        if ($this->numericMask)
        {
            $reverse = $this->reverse ? 'true' : 'false';
            $allowNegative = $this->allowNegative ? 'true' : 'false';

            TScript::create( "tentry_numeric_mask( '{$this->id}', {$this->decimals}, '{$this->decimalsSeparator}', '{$this->thousandSeparator}', {$reverse}, {$allowNegative}); ");
        }
        
        if ($this->exitOnEnterOn)
        {
            TScript::create( "tentry_exit_on_enter( '{$this->id}' ); ");
        }
    }
}
