<?php
namespace Adianti\Widget\Form;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Validator\TFieldValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Validator\TEmailValidator;
use Adianti\Validator\TMinLengthValidator;
use Adianti\Validator\TMaxLengthValidator;

use Exception;
use ReflectionClass;
use Closure;

/**
 * Base abstract class to construct all the widgets
 *
 * Provides base functionality for form widgets, including validation, properties, and rendering.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
abstract class TField
{
    protected $id;
    protected $name;
    protected $size;
    protected $value;
    protected $editable;
    protected $tag;
    protected $formName;
    protected $label;
    protected $properties;
    protected $valueCallback;
    protected $hidden;
    private   $validations;
    
    /**
     * Class Constructor
     *
     * Initializes the field with a name, sets default properties, and creates the input element.
     *
     * @param string $name The name of the field (required)
     *
     * @throws Exception If the field name is empty
     */
    public function __construct($name)
    {
        $rc = new ReflectionClass( $this );
        $classname = $rc-> getShortName ();
        
        if (empty($name))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 constructor is required', 'name', $classname));
        }
        
        // define some default properties
        self::setEditable(TRUE);
        self::setName(trim($name));
        
        // initialize validations array
        $this->validations = [];
        $this->properties  = [];
        
        $this->hidden = false;

        // creates a <input> tag
        $this->tag = new TElement('input');
        $this->tag->{'class'} = 'tfield';   // classe CSS
        $this->tag->{'widget'} = strtolower($classname);
    }
    
    /**
     * Changes the input tag name.
     *
     * @param string $name The new tag name
     */
    public function setTagName($name)
    {
        $this->tag->setName($name);
    }
    
    /**
     * Magic method to set a property dynamically.
     *
     * @param string $name  Property name
     * @param mixed  $value Property value (only scalar values are allowed)
     */
    public function __set($name, $value)
    {
        // objects and arrays are not set as properties
        if (is_scalar($value))
        {              
            // store the property's value
            $this->setProperty($name, $value);
        }
    }
    
    /**
     * Magic method to retrieve a property value.
     *
     * @param string $name Property name
     *
     * @return mixed The property value
     */
    public function __get($name)
    {
        return $this->getProperty($name);
    }
    
    /**
     * Magic method to check if a property is set.
     *
     * @param string $name Property name
     *
     * @return bool True if the property is set, false otherwise
     */
    public function __isset($name)
    {
        return isset($this->tag->$name);
    }
    
    /**
     * Magic method to clone the object.
     * Ensures the cloned object has a separate instance of the tag element.
     */
    function __clone()
    {
        $this->tag = clone $this->tag;
    }
    
    /**
     * Magic method to redirect function calls to the internal tag element.
     *
     * @param string $method Method name
     * @param array  $param  Array of parameters
     *
     * @return mixed The result of the called method
     * @throws Exception If the method does not exist
     */
    public function __call($method, $param)
    {
        if (method_exists($this->tag, $method))
        {
            return call_user_func_array( array($this->tag, $method), $param );
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate("Method ^1 not found", $method.'()'));
        }
    }
    
    /**
     * hide object
     */
    public function hide()
    {
        $this->hidden = true;
        $this->tag->hide();
    }

    /**
     * Sets a callback function to be executed when setting the field value.
     *
     * @param Closure $callback The callback function
     */
    public function setValueCallback($callback)
    {
        $this->valueCallback = $callback;
    }
    
    /**
     * Defines the field's label.
     *
     * @param string $label The label text
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Retrieves the field's label.
     *
     * @return string|null The label text or null if not set
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Sets the field's name.
     *
     * @param string $name The field name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Retrieves the field's name.
     *
     * @return string The field name
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the field's ID.
     *
     * @param string $id The field ID
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Retrieves the field's ID.
     *
     * @return string The field ID
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets the field's value and executes the callback if defined.
     *
     * @param mixed $value The field value
     */
    public function setValue($value)
    {
        $this->value = $value;
        
        if (!empty($this->valueCallback) && ($this->valueCallback instanceof Closure))
        {
            $callback = $this->valueCallback;
            $callback($this, $value);
        }
    }
    
    /**
     * Retrieves the field's value.
     *
     * @return mixed The field value
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Defines the name of the form to which the field belongs.
     *
     * @param string $name The form name
     */
    public function setFormName($name)
    {
        $this->formName = $name;
    }
    
    /**
     * Retrieves the name of the form to which the field belongs.
     *
     * @return string The form name
     */
    public function getFormName()
    {
        return $this->formName;
    }
    
    /**
     * Defines the field's tooltip.
     *
     * @param string $tip The tooltip text
     */
    public function setTip($tip)
    {
        $this->tag->{'title'} = $tip;
    }
    
    /**
     * Retrieves the posted data for the field.
     *
     * @return mixed The posted value or an empty string if not set
     */
    public function getPostData()
    {
        if (isset($_POST[$this->name]))
        {
            return $_POST[$this->name];
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Sets whether the field is editable.
     *
     * @param bool $editable True if editable, false otherwise
     */
    public function setEditable($editable)
    {
        $this->editable= $editable;
    }

    /**
     * Checks if the field is editable.
     *
     * @return bool True if editable, false otherwise
     */
    public function getEditable()
    {
        return $this->editable;
    }
    
    /**
     * Sets a field property.
     *
     * @param string  $name    Property name
     * @param mixed   $value   Property value
     * @param bool    $replace Whether to replace the existing value (default: true)
     */
    public function setProperty($name, $value, $replace = TRUE)
    {
        if ($replace)
        {
            // delegates the property assign to the composed object
            $this->tag->$name = $value;
        }
        else
        {
            if ($this->tag->$name)
            {
            
                // delegates the property assign to the composed object
                $this->tag->$name = $this->tag->$name . ';' . $value;
            }
            else
            {
                // delegates the property assign to the composed object
                $this->tag->$name = $value;
            }
        }
        
        $this->properties[ $name ] = $this->tag->$name;
    }
    
    /**
     * Retrieves properties as a string.
     *
     * @param string|null $filter Optional filter to include only properties matching a substring
     *
     * @return string The formatted property string
     */
    public function getPropertiesAsString($filter = null)
    {
        $content = '';
        
        if ($this->properties)
        {
            foreach ($this->properties as $name => $value)
            {
                if ( empty($filter) || ($filter && strpos($name, $filter) !== false))
                {
                    $value = str_replace('"', '&quot;', $value);
                    $content .= " {$name}=\"{$value}\"";
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Retrieves a specific property value.
     *
     * @param string $name Property name
     *
     * @return mixed The property value
     */
    public function getProperty($name)
    {
        return $this->tag->$name;
    }
    
    /**
     * Defines the field's width.
     *
     * @param int      $width  The field width in pixels
     * @param int|null $height (Unused) Optional height parameter
     */
    public function setSize($width, $height = NULL)
    {
        $this->size = $width;
    }
    
    /**
     * Retrieves the field's size.
     *
     * @return int The field width in pixels
     */
    public function getSize()
    {
        return $this->size;
    }
    
    /**
     * Adds a validation rule to the field.
     *
     * @param string            $label      Field label for validation messages
     * @param TFieldValidator   $validator  The validation rule object
     * @param mixed             $parameters Additional parameters for the validator
     */
    public function addValidation($label, TFieldValidator $validator, $parameters = NULL)
    {
        $this->validations[] = array($label, $validator, $parameters);
        
        if ($validator instanceof TRequiredValidator)
        {
            $this->tag->{'required'} = '';
        }
        
        if ($validator instanceof TEmailValidator)
        {
            $this->tag->{'type'} = 'email';
        }
        
        if ($validator instanceof TMinLengthValidator)
        {
            $this->tag->{'minlength'} = $parameters[0];
        }
        
        if ($validator instanceof TMaxLengthValidator)
        {
            $this->tag->{'maxlength'} = $parameters[0];
        }
    }
    
    /**
     * Retrieves the field's validation rules.
     *
     * @return array The list of validation rules
     */
    public function getValidations()
    {
        return $this->validations;
    }
    
    /**
     * Checks if the field is required.
     *
     * @return bool True if the field has a required validation rule, false otherwise
     */
    public function isRequired()
    {
        if ($this->validations)
        {
            foreach ($this->validations as $validation)
            {
                $validator = $validation[1];
                if ($validator instanceof TRequiredValidator)
                {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
    
    /**
     * Validates the field using the assigned validation rules.
     *
     * @throws Exception If validation fails
     */
    public function validate()
    {
        if ($this->validations)
        {
            foreach ($this->validations as $validation)
            {
                $label      = $validation[0];
                $validator  = $validation[1];
                $parameters = $validation[2];
                
                $validator->validate($label, $this->getValue(), $parameters);
            }
        }
    }
    
    /**
     * Converts the object to a string representation.
     *
     * @return string The rendered HTML of the field
     */
    public function __toString()
    {
        return $this->getContents();
    }
    
    /**
     * Retrieves the element content as a string.
     *
     * @return string The rendered HTML content
     */
    public function getContents()
    {
        ob_start();
        $this->show();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    /**
     * Enables the specified field in the given form.
     *
     * @param string $form_name The form name
     * @param string $field     The field name
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tfield_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables the specified field in the given form.
     *
     * @param string $form_name The form name
     * @param string $field     The field name
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tfield_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clears the value of the specified field in the given form.
     *
     * @param string $form_name The form name
     * @param string $field     The field name
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tfield_clear_field('{$form_name}', '{$field}'); " );
    }
}
