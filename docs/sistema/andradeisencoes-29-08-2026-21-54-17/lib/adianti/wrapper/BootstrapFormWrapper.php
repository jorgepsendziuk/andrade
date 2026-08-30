<?php
namespace Adianti\Wrapper;

use Adianti\Widget\Wrapper\TQuickForm;
use Adianti\Widget\Wrapper\AdiantiFormBuilder;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\AdiantiFormInterface;
use Adianti\Widget\Form\AdiantiWidgetInterface;

/**
 * Bootstrap form decorator for Adianti Framework
 *
 * This class acts as a wrapper for `TQuickForm`, applying Bootstrap styling to the form.
 * It ensures that the form elements are properly structured using Bootstrap classes.
 *
 * @version    7.5
 * @package    wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 * @wrapper    TQuickForm
 */
class BootstrapFormWrapper implements AdiantiFormInterface
{
    private $decorated;
    private $currentGroup;
    private $element;
    
    /**
     * Constructor method
     *
     * Initializes the BootstrapFormWrapper with a given form and applies Bootstrap styles.
     *
     * @param TQuickForm $form  The form instance to be decorated.
     * @param string     $class CSS class to be applied to the form (default: 'form-horizontal').
     */
    public function __construct(TQuickForm $form, $class = 'form-horizontal')
    {
        $this->decorated = $form;
        
        $this->element   = new TElement('form');
        $this->element->{'class'}   = $class;
        $this->element->{'type'}    = 'bootstrap';
        $this->element->{'enctype'} = "multipart/form-data";
        $this->element->{'method'}  = 'post';
        $this->element->{'name'}    = $this->decorated->getName();
        $this->element->{'id'}      = $this->decorated->getName();
        $this->element->{'novalidate'}  = '';
    }
    
    /**
     * Enable or disable client-side validation
     *
     * @param bool $bool If true, enables client validation; if false, disables it.
     */
    public function setClientValidation($bool)
    {
        if ($bool)
        {
            unset($this->element->{'novalidate'});
        }
        else
        {
            $this->element->{'novalidate'}  = '';
        }
    }
    
    /**
     * Get the rendered form element
     *
     * @return TElement The form element wrapped in a Bootstrap-styled container.
     */
    public function getElement()
    {
        return $this->element;
    }
    
    /**
     * Magic method to redirect calls to the decorated form
     *
     * This method allows calling methods of `TQuickForm` directly on this wrapper.
     *
     * @param string $method     The method name being called.
     * @param array  $parameters The parameters passed to the method.
     *
     * @return mixed The result of the called method.
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->decorated, $method),$parameters);
    }
    
    /**
     * Magic method to assign properties to the decorated form
     *
     * @param string $property The property name.
     * @param mixed  $value    The value to be assigned.
     */
    public function __set($property, $value)
    {
        return $this->element->$property = $value;
    }
    
    /**
     * Set the form name
     *
     * @param string $name The name to be set for the form.
     */
    public function setName($name)
    {
        return $this->decorated->setName($name);
    }
    
    /**
     * Get the form name
     *
     * @return string The name of the form.
     */
    public function getName()
    {
        return $this->decorated->getName();
    }
    
    /**
     * Add a field to the form
     *
     * @param AdiantiWidgetInterface $field The field to be added.
     */
    public function addField(AdiantiWidgetInterface $field)
    {
        return $this->decorated->addField($field);
    }
    
    /**
     * Remove a field from the form
     *
     * @param AdiantiWidgetInterface $field The field to be removed.
     */
    public function delField(AdiantiWidgetInterface $field)
    {
        return $this->decorated->delField($field);
    }
    
    /**
     * Set multiple fields in the form
     *
     * @param array $fields An array of form fields.
     */
    public function setFields($fields)
    {
        return $this->decorated->setFields($fields);
    }
    
    /**
     * Retrieve a field from the form by name
     *
     * @param string $name The name of the field.
     *
     * @return AdiantiWidgetInterface|null The requested field, or null if not found.
     */
    public function getField($name)
    {
        return $this->decorated->getField($name);
    }
    
    /**
     * Get all fields in the form
     *
     * @return array An array of `AdiantiWidgetInterface` fields.
     */
    public function getFields()
    {
        return $this->decorated->getFields();
    }
    
    /**
     * Clear all data from the form
     */
    public function clear()
    {
        return $this->decorated->clear();
    }
    
    /**
     * Set form data
     *
     * @param object $object An object containing data to populate the form fields.
     */
    public function setData($object)
    {
        return $this->decorated->setData($object);
    }
    
    /**
     * Get form data
     *
     * @param string $class The class name to instantiate for returning the data (default: 'StdClass').
     *
     * @return object An instance of the specified class populated with form data.
     */
    public function getData($class = 'StdClass')
    {
        return $this->decorated->getData($class);
    }
    
    /**
     * Validate the form data
     *
     * @return void Throws an exception if validation fails.
     * @throws Exception If validation fails.
     */
    public function validate()
    {
        return $this->decorated->validate();
    }
    
    /**
     * Render and display the decorated form
     *
     * This method applies Bootstrap styling to the form and renders it.
     */
    public function show()
    {
        $fieldsByRow = $this->decorated->getFieldsByRow();
        if ($this->element->{'class'} == 'form-horizontal')
        {
            $classWidth  = array(1=>array(3,9), 2=>array(2,4), 3=>array(2,2));
            $labelClass  = $classWidth[$fieldsByRow][0];
            $fieldClass  = $classWidth[$fieldsByRow][1];
        }
        
        $fieldCount  = 0;
        
        $input_rows = $this->decorated->getInputRows();
        
        if ($input_rows)
        {
            foreach ($input_rows as $input_row)
            {
                $field_label  = $input_row[0];
                $fields       = $input_row[1];
                $required     = $input_row[2];
                $original_row = $input_row[3];
                
                // form vertical doesn't group elements, just change form group grid class
                if ( empty($this->currentGroup) OR ( $fieldCount % $fieldsByRow ) == 0 OR (strpos($this->element->{'class'}, 'form-vertical') !== FALSE) )
                {
                    // add the field to the container
                    $this->currentGroup = new TElement('div');
                    
                    foreach ($original_row->getProperties() as $property => $value)
                    {
                        $this->currentGroup->$property = $value;
                    }
                    
                    $this->currentGroup->{'class'}  = 'row tformrow form-group';
                    $this->currentGroup->{'class'} .= ( ( strpos($this->element->{'class'}, 'form-vertical') !== FALSE ) ? ' col-sm-'.(12/$fieldsByRow) : '');
                    $this->element->add($this->currentGroup);
                }
                
                $group = $this->currentGroup;
                
                if ($field_label instanceof TLabel)
                {
                    $label = $field_label;
                }
                else
                {
                    $label = new TElement('label');
                    $label->add( $field_label );
                }
                
                if ($this->element->{'class'} == 'form-inline')
                {
                    $label->{'style'} = 'padding-left: 3px; font-weight: bold';
                }
                else
                {
                    $label->{'style'} = 'font-weight: bold; margin-bottom: 3px';
                    if ($this->element->{'class'} == 'form-horizontal')
                    {
                        $label->{'class'} = 'col-sm-'.$labelClass.' control-label';
                    }
                    else
                    {
                        $label->{'class'} = ' control-label';
                    }
                }
                
                if (count($fields)==1 AND $fields[0] instanceof THidden)
                {
                    $group->add('');
                    $group->{'style'} = 'display:none';
                }
                else
                {
                    $group->add($label);
                }
                
                if ($this->element->{'class'} !== 'form-inline')
                {
                    $col = new TElement('div');
                    if ($this->element->{'class'} == 'form-horizontal')
                    {
                        $col->{'class'} = 'col-sm-'.$fieldClass . ' fb-field-container';
                    }
                    
                    $group->add($col);
                }
                
                foreach ($fields as $field)
                {
                    if ($this->element->{'class'} == 'form-inline')
                    {
                        $label->{'style'} .= ';float:left';
                        $group->add(BootstrapFormBuilder::wrapField($field, 'inline-block'));
                    }
                    else
                    {
                        $col->add(BootstrapFormBuilder::wrapField($field, 'inline-block'));
                    }
                }
                $fieldCount ++;
            }
        }
        
        if ($this->decorated->getActionButtons())
        {
            $group = new TElement('div');
            $group->{'class'} = 'form-group';
            $col = new TElement('div');
            
            if ($this->element->{'class'} == 'form-horizontal')
            {
                $col->{'class'} = 'col-sm-offset-'.$labelClass.' col-sm-'.$fieldClass;
            }
            
            $i = 0;
            foreach ($this->decorated->getActionButtons() as $action)
            {
                $col->add($action);
                $i ++;
            }
            $group->add($col);
            $this->element->add($group);
        }
        
        $this->element->{'width'} = '100%';
        $this->element->show();
    }
}
