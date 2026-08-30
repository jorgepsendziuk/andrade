<?php
namespace Adianti\Widget\Datagrid;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Represents an action inside a DataGrid.
 * This class extends TAction and is responsible for managing actions associated with data grid elements.
 *
 * @version    7.5
 * @package    widget
 * @subpackage datagrid
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDataGridAction extends TAction
{
    private $field;
    private $fields;
    private $image;
    private $label;
    private $buttonClass;
    private $useButton;
    private $displayCondition;
    private $usePostAction = false;
    
    /**
     * Class constructor.
     *
     * Initializes the DataGrid action with a given callback and optional parameters.
     * If parameters are provided, it sets the fields from the parent's parameters.
     *
     * @param callable $action     The callback function to be executed.
     * @param array|null $parameters Optional array of parameters for the action.
     */
    public function __construct($action, $parameters = null)
    {
        parent::__construct($action, $parameters);
        
        if ($parameters)
        {
            $this->setFields( parent::getFieldParameters() );
        }
    }
    
    /**
     * Defines which Active Record property will be passed along with the action.
     *
     * @param string $field The name of the Active Record property.
     */
    public function setField($field)
    {
        $this->field = $field;
        
        $this->setParameter('key',  '{'.$field.'}');
        $this->setParameter($field, '{'.$field.'}');
    }
    
    /**
     * Defines which Active Record properties will be passed along with the action.
     *
     * @param array $fields An array of Active Record properties.
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
        
        if ($fields)
        {
            if (empty($this->field) && empty(parent::getParameter('key')))
            {
                $this->setParameter('key', '{'.$fields[0].'}');
            }
            
            foreach ($fields as $field)
            {
                $this->setParameter($field, '{'.$field.'}');
            }
        }
    }
    
    /**
     * Retrieves the Active Record property that will be passed along with the action.
     *
     * @return string|null The field name or null if not set.
     */
    public function getField()
    {
        return $this->field;
    }
    
    /**
     * Retrieves the Active Record properties that will be passed along with the action.
     *
     * @return array|null An array of field names or null if not set.
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    /**
     * Checks if at least one field is defined for the action.
     *
     * @return bool True if at least one field is defined, false otherwise.
     */
    public function fieldDefined()
    {
        return (!empty($this->field) or !empty($this->fields));
    }
    
    /**
     * Defines an icon for the action.
     *
     * @param string $image The path to the image icon.
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
    
    /**
     * Retrieves the icon associated with the action.
     *
     * @return string|null The image path or null if not set.
     */
    public function getImage()
    {
        return $this->image;
    }
    
    /**
     * Defines the label for the action.
     *
     * @param string $label A string containing the text label.
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    /**
     * Retrieves the text label for the action.
     *
     * @return string|null The label or null if not set.
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Defines the CSS class for the button associated with the action.
     *
     * @param string $buttonClass The CSS class name.
     */
    public function setButtonClass($buttonClass)
    {
        $this->buttonClass = $buttonClass;
    }
    
    /**
     * Retrieves the CSS class of the button associated with the action.
     *
     * @return string|null The button class or null if not set.
     */
    public function getButtonClass()
    {
        return $this->buttonClass;
    }
    
    /**
     * Defines whether the action should use a regular button.
     *
     * @param bool $useButton True to use a button, false otherwise.
     */
    public function setUseButton($useButton)
    {
        $this->useButton = $useButton;
    }
    
    /**
     * Checks whether the action uses a regular button.
     *
     * @return bool|null True if a button is used, false otherwise.
     */
    public function getUseButton()
    {
        return $this->useButton;
    }
    
    /**
     * Defines a condition to determine whether the action should be displayed.
     *
     * @param callable $displayCondition A callback function that evaluates the display condition.
     */
    public function setDisplayCondition( /*Callable*/ $displayCondition )
    {
        $this->displayCondition = $displayCondition;
    }
    
    /**
     * Retrieves the display condition for the action.
     *
     * @return callable|null The callback function or null if not set.
     */
    public function getDisplayCondition()
    {
        return $this->displayCondition;
    }
    
    /**
     * Prepares the action for use with a given object.
     *
     * Ensures that the required field(s) exist in the provided object before executing the action.
     *
     * @param object $object The data object.
     *
     * @throws Exception If the required field is not defined or does not exist in the object.
     * @return array The prepared action parameters.
     */
    public function prepare($object)
    {
        if ( !$this->fieldDefined() )
        {
            throw new Exception(AdiantiCoreTranslator::translate('Field for action ^1 not defined', parent::toString()) . '.<br>' . 
                                AdiantiCoreTranslator::translate('Use the ^1 method', 'setField'.'()').'.');
        }
        
        if ($field = $this->getField())
        {
            if ( !isset( $object->$field ) )
            {
                throw new Exception(AdiantiCoreTranslator::translate('Field ^1 not exists or contains NULL value', $field));
            }
        }
        
        if ($fields = $this->getFields())
        {
            $field = $fields[0];
            
            if ( !isset( $object->$field ) )
            {
                throw new Exception(AdiantiCoreTranslator::translate('Field ^1 not exists or contains NULL value', $field));
            }
        }
        
        return parent::prepare($object);
    }
    
    /**
     * Converts the action into a URL.
     *
     * This method serializes the action parameters and appends necessary request parameters.
     *
     * @param bool $format_action Whether to format the action as a document or JavaScript action.
     *
     * @return string The serialized action URL.
     */
    public function serialize($format_action = TRUE)
    {
        if (is_array($this->action) AND is_object($this->action[0]))
        {
            if (isset( $_REQUEST['offset'] ))
            {
                $this->setParameter('offset',     $_REQUEST['offset'] );
            }
            if (isset( $_REQUEST['limit'] ))
            {
                $this->setParameter('limit',      $_REQUEST['limit'] );
            }
            if (isset( $_REQUEST['page'] ))
            {
                $this->setParameter('page',       $_REQUEST['page'] );
            }
            if (isset( $_REQUEST['first_page'] ))
            {
                $this->setParameter('first_page', $_REQUEST['first_page'] );
            }
            if (isset( $_REQUEST['order'] ))
            {
                $this->setParameter('order',      $_REQUEST['order'] );
            }
        }
        if (parent::isStatic())
        {
            $this->setParameter('static',     '1' );
        }
        return parent::serialize($format_action);
    }

    /**
     * Enables the post action for the DataGrid action.
     */
    public function enablePostAction()
    {
        $this->usePostAction = true;
    }

    /**
     * Disables the post action for the DataGrid action.
     */
    public function disablePostAction()
    {
        $this->usePostAction = false;
    }

    /**
     * Checks whether the post action is enabled.
     *
     * @return bool True if post action is enabled, false otherwise.
     */
    public function getUsePostAction()
    {
        return $this->usePostAction;
    }
}
