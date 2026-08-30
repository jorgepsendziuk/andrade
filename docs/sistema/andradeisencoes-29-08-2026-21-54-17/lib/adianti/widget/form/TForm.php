<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TButton;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Util\AdiantiStringConversion;

use Exception;
use ReflectionClass;

/**
 * Represents a form and provides methods to manage its fields, properties, and data.
 *
 * This class allows adding, removing, and manipulating form fields, setting properties,
 * and handling form data operations such as validation and retrieval.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TForm implements AdiantiFormInterface
{
    protected $fields; // array containing the form fields
    protected $name;   // form name
    protected $children;
    protected $js_function;
    protected $element;
    protected $silent_fields;
    private static $forms;
    
    /**
     * Class constructor.
     *
     * Initializes a new form with the given name and sets up its basic properties.
     *
     * @param string|null $name The form name. Default is 'my_form'.
     */
    public function __construct($name = 'my_form')
    {
        if ($name)
        {
            $this->setName($name);
        }
        $this->children = [];
        $this->silent_fields = [];
        $this->element  = new TElement('form');
    }
    
    /**
     * Sets the tag name for the form element.
     *
     * @param string $name The new tag name.
     */
    public function setTagName($name)
    {
        $this->element->setName($name);
    }
    
    /**
     * Magic method to intercept property assignments.
     *
     * If the class is TForm, TQuickForm, or TQuickNotebookForm, scalar values are assigned
     * to the element properties. Otherwise, they are assigned as normal properties.
     *
     * @param string $name  The property name.
     * @param mixed  $value The property value.
     */
    public function __set($name, $value)
    {
        $rc = new ReflectionClass( $this );
        $classname = $rc-> getShortName ();
        
        if (in_array($classname, array('TForm', 'TQuickForm', 'TQuickNotebookForm')))
        {
            // objects and arrays are not set as properties
            if (is_scalar($value))
            {              
                // store the property's value
                $this->element->$name = $value;
            }
        }
        else
        {
            $this->$name = $value;
        }
    }
    
    /**
     * Marks a field as silent, meaning it will not be included in form data processing.
     *
     * @param string $name The field name to be marked as silent.
     */
    public function silentField($name)
    {
        $this->silent_fields[] = $name;
    }
    
    /**
     * Defines a form property.
     *
     * @param string  $name    The property name.
     * @param mixed   $value   The property value.
     * @param bool    $replace Whether to replace the existing value or append to it (default: true).
     */
    public function setProperty($name, $value, $replace = TRUE)
    {
        if ($replace)
        {
            // delegates the property assign to the composed object
            $this->element->$name = $value;
        }
        else
        {
            if ($this->element->$name)
            {
            
                // delegates the property assign to the composed object
                $this->element->$name = $this->element->$name . ';' . $value;
            }
            else
            {
                // delegates the property assign to the composed object
                $this->element->$name = $value;
            }
        }
    }
    
    /**
     * Removes a property from the form.
     *
     * @param string $name The property name to be removed.
     */
    public function unsetProperty($name)
    {
        unset($this->element->$name);
    }
    
    /**
     * Retrieves a form instance by its name.
     *
     * @param string $name The name of the form.
     *
     * @return TForm|null The form instance if found, otherwise null.
     */
    public static function getFormByName($name)
    {
        if (isset(self::$forms[$name]))
        {
            return self::$forms[$name];
        }
    }

    /**
     * Retrieves a form instance that contains a specific field.
     *
     * @param string $fieldName The field name to search for.
     *
     * @return TForm|false The form instance if found, otherwise false.
     */
    public static function getFormByField($fieldName)
    {
        if(self::$forms)
        {
            foreach (self::$forms as $form)
            {
                if (!empty($form->fields[$fieldName]))
                {
                    return $form;
                }
            }
        }

        return false;
    }

    /**
     * Sets the name of the form.
     *
     * @param string $name The form name.
     */
    public function setName($name)
    {
        $this->name = $name;
        // register this form
        self::$forms[$this->name] = $this;
    }
    
    /**
     * Retrieves the form name.
     *
     * @return string The form name.
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sends data to a form located in the parent window.
     *
     * @param string  $form_name  The form name.
     * @param object  $object     The object containing form data.
     * @param bool    $aggregate  Whether to aggregate values instead of replacing them (default: false).
     * @param bool    $fireEvents Whether to trigger JavaScript events (default: true).
     * @param int     $timeout    Timeout for sending data (default: 0).
     */
    public static function sendData($form_name, $object, $aggregate = FALSE, $fireEvents = TRUE, $timeout = 0)
    {
        $fire_param = $fireEvents ? 'true' : 'false';
        // iterate the object properties
        if ($object)
        {
            foreach ($object as $field => $value)
            {
                if (is_array($value))
                {
                    $value = json_encode($value);
                }
                
                $value = addslashes((string) $value);
                $value = AdiantiStringConversion::assureUnicode($value);
                
                $value = str_replace(array("\n", "\r"), array( '\n', '\r'), $value );
                
                // send the property value to the form
                if ($aggregate)
                {
                    TScript::create( " tform_send_data_aggregate('{$form_name}', '{$field}', '$value', $fire_param); " );
                }
                else
                {
                    TScript::create( " tform_send_data('{$form_name}', '{$field}', '$value', $fire_param, '$timeout'); " );
                    TScript::create( " tform_send_data_by_id('{$form_name}', '{$field}', '$value', $fire_param, '$timeout'); " );
                }
            }
        }
    }
    
    /**
     * Sets the form fields as editable or read-only.
     *
     * @param bool $bool Whether the fields should be editable.
     */
    public function setEditable($bool)
    {
        if ($this->fields)
        {
            foreach ($this->fields as $object)
            {
                $object->setEditable($bool);
            }
        }
    }
    
    /**
     * Adds a field to the form.
     *
     * @param AdiantiWidgetInterface $field The field object to be added.
     *
     * @throws Exception If a field with the same name already exists.
     */
    public function addField(AdiantiWidgetInterface $field)
    {
        $name = $field->getName();
        if (isset($this->fields[$name]) AND substr($name,-2) !== '[]')
        {
            throw new Exception(AdiantiCoreTranslator::translate('You have already added a field called "^1" inside the form', $name));
        }
        
        if ($name)
        {
            $this->fields[$name] = $field;
            $field->setFormName($this->name);
            
            if ($field instanceof TButton)
            {
                $field->addFunction($this->js_function);
            }
        }
    }
    
    /**
     * Removes a field from the form.
     *
     * @param AdiantiWidgetInterface $field The field object to be removed.
     */
    public function delField(AdiantiWidgetInterface $field)
    {
        if ($this->fields)
        {
            foreach($this->fields as $name => $object)
            {
                if ($field === $object)
                {
                    unset($this->fields[$name]);
                }
            }
        }
    }
    
    /**
     * Removes all fields from the form.
     */
    public function delFields()
    {
        $this->fields = array();
    }
    
    /**
     * Sets the form fields.
     *
     * @param array $fields An array of AdiantiWidgetInterface objects representing form fields.
     *
     * @throws Exception If the parameter is not an array.
     */
    public function setFields($fields)
    {
        if (is_array($fields))
        {
            $this->fields = array();
            $this->js_function = '';
            // iterate the form fields
            foreach ($fields as $field)
            {
                $this->addField($field);
            }
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('Method ^1 must receive a parameter of type ^2', __METHOD__, 'Array'));
        }
    }
    
    /**
     * Retrieves a field from the form by its name.
     *
     * @param string $name The field name.
     *
     * @return AdiantiWidgetInterface|null The field object if found, otherwise null.
     */
    public function getField($name)
    {
        if (isset($this->fields[$name]))
        {
            return $this->fields[$name];
        }
    }
    
    /**
     * Retrieves all form fields.
     *
     * @return array An array containing all form fields.
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    /**
     * Clears the form data.
     *
     * @param bool $keepDefaults Whether to keep default values (default: false).
     */
    public function clear($keepDefaults = FALSE)
    {
        if ($this->fields)
        {
            // iterate the form fields
            foreach ($this->fields as $name => $field)
            {
                // labels don't have name
                if ($name AND !$keepDefaults)
                {
                    $field->setValue(NULL);
                }
            }
        }
    }
    
    /**
     * Sets the form data.
     *
     * @param object $object The object containing form data.
     */
    public function setData($object)
    {
        if ($this->fields)
        {
            // iterate the form fields
            foreach ($this->fields as $name => $field)
            {
                $name = str_replace(['[',']'], ['',''], $name);
            
                if ($name) // labels don't have name
                {
                    if (isset($object->$name))
                    {
                        $field->setValue($object->$name);
                    }
                }
            }
        }
    }
    
    /**
     * Retrieves the form POST data as an object.
     *
     * @param string $class The class name for the returned object (default: 'StdClass').
     *
     * @return object An instance of the specified class containing form data.
     * @throws Exception If the class does not exist.
     */
    public function getData($class = 'StdClass')
    {
        if (!class_exists($class))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Class ^1 not found in ^2', $class, __METHOD__));
        }
        
        $object = new $class;
        if ($this->fields)
        {
            foreach ($this->fields as $key => $fieldObject)
            {
                $key = str_replace(['[',']'], ['',''], $key);
            
                if (!$fieldObject instanceof TButton && !in_array($key, $this->silent_fields))
                {
                    $object->$key = $fieldObject->getPostData();
                }
            }
        }

        return $object;
    }
    
    /**
     * Retrieves the initial form values as an object.
     *
     * @param string  $class      The class name for the returned object (default: 'StdClass').
     * @param bool    $withOptions Whether to include options for selectable fields (default: false).
     *
     * @return object An instance of the specified class containing form values.
     * @throws Exception If the class does not exist.
     */
    public function getValues($class = 'StdClass', $withOptions = false)
    {
        if (!class_exists($class))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Class ^1 not found in ^2', $class, __METHOD__));
        }
        
        $object = new $class;
        if ($this->fields)
        {
            foreach ($this->fields as $key => $field)
            {
                $key = str_replace(['[',']'], ['',''], $key);
                
                if (!$field instanceof TButton)
                {
                    if ($withOptions AND method_exists($field, 'getItems'))
                    {
                        $items = $field->getItems();
                        
                        if (is_array($field->getValue()))
                        {
                            $value = [];
                            foreach ($field->getValue() as $field_value)
                            {
                                if ($field_value)
                                {
                                    $value[] = $items[$field_value];
                                }
                            }
                            $object->$key = $value;
                        }
                    }
                    else
                    {
                        $object->$key = $field->getValue();
                    }
                }
            }
        }
        
        return $object;
    }
    
    /**
     * Validates the form fields.
     *
     * @throws Exception If validation fails, an exception with the error messages is thrown.
     */
    public function validate()
    {
        // assign post data before validation
        // validation exception would prevent
        // the user code to execute setData()
        $this->setData($this->getData());
        
        $errors = array();
        if ($this->fields)
        {
            foreach ($this->fields as $fieldObject)
            {
                try
                {
                    $fieldObject->validate();
                }
                catch (Exception $e)
                {
                    $errors[] = $e->getMessage() . '.';
                }
            }
        }
        
        if (count($errors) > 0)
        {
            throw new Exception(implode("<br>", $errors));
        }
    }
    
    /**
     * Adds a child object to the form (typically a table or panel).
     *
     * @param object $object The object to be added. It must implement the show() method.
     */
    public function add($object)
    {
        if (!in_array($object, $this->children))
        {
            $this->children[] = $object;
        }
    }
    
    /**
     * Packs multiple child objects into the form.
     *
     * @param mixed ...$objects The objects to be packed. Each must implement the show() method.
     */
    public function pack()
    {
        $this->children = func_get_args();
    }
    
    /**
     * Retrieves the first child object of the form.
     *
     * @return object|null The first child object if exists, otherwise null.
     */
    public function getChild()
    {
        return $this->children[0];
    }
    
    /**
     * Displays the form.
     *
     * Configures the form properties and renders it, including any child elements.
     */
    public function show()
    {
        // define form properties
        $this->element->{'enctype'} = "multipart/form-data";
        $this->element->{'name'}    = $this->name; // form name
        $this->element->{'id'}      = $this->name; // form id
        $this->element->{'method'}  = 'post';      // transfer method
        
        // add the container to the form
        if (isset($this->children))
        {
            foreach ($this->children as $child)
            {
                $this->element->add($child);
            }
        }
        // show the form
        $this->element->show();
    }
}
