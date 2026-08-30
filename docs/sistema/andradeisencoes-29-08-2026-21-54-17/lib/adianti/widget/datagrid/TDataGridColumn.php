<?php
namespace Adianti\Widget\Datagrid;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Form\TEntry;

/**
 * Represents a column in the DataGrid.
 *
 * This class allows defining properties for a DataGrid column, such as label, alignment,
 * width, actions, transformation functions, searchability, and total calculations.
 *
 * @version    7.5
 * @package    widget
 * @subpackage datagrid
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDataGridColumn
{
    private $name;
    private $label;
    private $align;
    private $width;
    private $action;
    private $editaction;
    private $transformer;
    private $properties;
    private $dataProperties;
    private $totalFunction;
    private $totalMask;
    private $totalCallback;
    private $totalTransformed;
    private $searchable;
    private $inputSearch;
    private $htmlConversion;
    protected $totalFormField;
    private $hide;
    
    /**
     * Class constructor.
     *
     * Initializes a new DataGrid column with the specified properties.
     *
     * @param string      $name  Name of the column in the database.
     * @param string      $label Text label that will be shown in the header.
     * @param string      $align Column alignment (left, center, right).
     * @param int|null    $width Column width in pixels (optional).
     */
    public function __construct($name, $label, $align, $width = NULL)
    {
        $this->name  = $name;
        $this->label = $label;
        $this->align = $align;
        $this->width = $width;
        $this->searchable = false;
        $this->properties = array();
        $this->dataProperties = array();
        $this->htmlConversion = true;
        $this->hide = false;
    }
    
    /**
     * Sets the visibility of the column.
     *
     * @param bool $bool Whether the column should be visible (true) or hidden (false).
     */
    public function setVisibility($bool)
    {
        if ($bool)
        {
            $this->setProperty('style', '');
            $this->setDataProperty('style', '');
        }
        else
        {
            $this->setProperty('style', 'display:none');
            $this->setDataProperty('style', 'display:none');
        }
    }
    
    /**
     * Enables automatic hiding of the column based on width.
     *
     * @param int $width The width threshold at which the column will be hidden.
     */
    public function enableAutoHide($width)
    {
        $this->setProperty('hiddable', $width);
        $this->setDataProperty('hiddable', $width);
    }

    public function hide()
    {
        $this->hide = true;
    }

    public function unhide()
    {
        $this->hide = false;
    }

    public function isHidden()
    {
        return $this->hide;
    }
    
    /**
     * Enables the search functionality for the column.
     *
     * This creates an input field that allows searching within the column.
     */
    public function enableSearch()
    {
        $this->searchable = true;
        
        $name = 'search_' . str_replace(['-', '>'],['_', ''],$this->name) . '_' . uniqid();
        
        $this->inputSearch = new TEntry($name);
        $this->inputSearch->setId($name);
        $this->inputSearch->{'placeholder'} = AdiantiCoreTranslator::translate('Search');
        $this->inputSearch->setSize('50%');
    }
    
    /**
     * Enables HTML conversion on the column output.
     *
     * This ensures that HTML special characters are converted properly.
     */
    public function enableHtmlConversion()
    {
        $this->htmlConversion = true;
    }
    
    /**
     * Disables HTML conversion on the column output.
     *
     * This prevents the automatic conversion of HTML special characters.
     */
    public function disableHtmlConversion()
    {
        $this->htmlConversion = false;
    }
    
    /**
     * Checks whether HTML conversion is enabled for the column.
     *
     * @return bool True if HTML conversion is enabled, false otherwise.
     */
    public function hasHtmlConversionEnabled()
    {
        return $this->htmlConversion;
    }
    
    /**
     * Retrieves the search input field for the column.
     *
     * @return TEntry|null The input field object, or null if search is not enabled.
     */
    public function getInputSearch()
    {
        return $this->inputSearch;
    }
    
    /**
     * Checks whether the column is searchable.
     *
     * @return bool True if the column is searchable, false otherwise.
     */
    public function isSearchable()
    {
        return $this->searchable;
    }
    
    /**
     * Sets a property for the column header.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The value to set for the property.
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }
    
    /**
     * Sets a property for the column data.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The value to set for the property.
     */
    public function setDataProperty($name, $value)
    {
        $this->dataProperties[$name] = $value;
    }
    
    /**
     * Retrieves a property from the column header.
     *
     * @param string $name The name of the property.
     *
     * @return mixed|null The value of the property, or null if not set.
     */
    public function getProperty($name)
    {
        if (isset($this->properties[$name]))
        {
            return $this->properties[$name];
        }
    }
    
    /**
     * Retrieves a data property from the column.
     *
     * @param string $name The name of the property.
     *
     * @return mixed|null The value of the property, or null if not set.
     */
    public function getDataProperty($name)
    {
        if (isset($this->dataProperties[$name]))
        {
            return $this->dataProperties[$name];
        }
    }
    
    /**
     * Retrieves all properties of the column header.
     *
     * @return array The array of properties.
     */
    public function getProperties()
    {
        return $this->properties;
    }
    
    /**
     * Retrieves all data properties of the column.
     *
     * @return array The array of data properties.
     */
    public function getDataProperties()
    {
        return $this->dataProperties;
    }
    
    /**
     * Magic method for setting a property dynamically.
     *
     * @param string $name  The property name.
     * @param mixed  $value The property value.
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
     * Retrieves the name of the database column.
     *
     * @return string The column name.
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Retrieves the label of the column.
     *
     * @return string The column label.
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Sets the label of the column.
     *
     * @param string $label The column label.
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    /**
     * Retrieves the alignment of the column.
     *
     * @return string The alignment (left, center, right).
     */
    public function getAlign()
    {
        return $this->align;
    }
    
    /**
     * Retrieves the width of the column.
     *
     * @return int|null The column width in pixels, or null if not set.
     */
    public function getWidth()
    {
        return $this->width;
    }
    
    /**
     * Sets an action to be executed when the column header is clicked.
     *
     * @param TAction     $action     The action to execute.
     * @param array|null  $parameters Optional action parameters.
     */
    public function setAction(TAction $action, $parameters = null)
    {
        $this->action = $action;
        
        if ($parameters)
        {
            $this->action->setParameters($parameters);
        }
    }
    
    /**
     * Retrieves the action assigned to the column header.
     *
     * @return TAction|null The action object, or null if not set.
     */
    public function getAction()
    {
        // verify if the column has an actions
        if ($this->action)
        {
            return $this->action;
        }
    }
    
    /**
     * Removes the action associated with the column header.
     */
    public function removeAction()
    {
        $this->action = null;
    }
    
    /**
     * Sets an edit action for the column.
     *
     * @param TDataGridAction $editaction The edit action to execute.
     */
    public function setEditAction(TDataGridAction $editaction)
    {
        $this->editaction = $editaction;
    }
    
    /**
     * Retrieves the edit action assigned to the column.
     *
     * @return TDataGridAction|null The edit action object, or null if not set.
     */
    public function getEditAction()
    {
        // verify if the column has an actions
        if ($this->editaction)
        {
            return $this->editaction;
        }
    }
    
    /**
     * Sets a callback function to transform the column's data.
     *
     * @param callable $callback The transformation function.
     */
    public function setTransformer(Callable $callback)
    {
        $this->transformer = $callback;
    }

    /**
     * Retrieves the transformation callback function.
     *
     * @return callable|null The transformation function, or null if not set.
     */
    public function getTransformer()
    {
        return $this->transformer;
    }
    
    /**
     * Enables total calculation for the column.
     *
     * @param string      $function           The aggregation function (sum, count, etc.).
     * @param string|null $prefix             Optional prefix for the total.
     * @param int         $decimals           Number of decimal places.
     * @param string      $decimal_separator  Decimal separator character.
     * @param string      $thousand_separator Thousand separator character.
     */
    public function enableTotal($function, $prefix = null, $decimals = 2, $decimal_separator = ',', $thousand_separator = '.')
    {
        $this->totalFunction = $function;
        $this->totalMask     = "{$prefix}:{$decimals}{$decimal_separator}{$thousand_separator}";
        
        if ($function == 'sum')
        {
            $totalCallback = function($values) {
                return array_sum($values);
            };
            
            $this->setTotalFunction( $totalCallback );
        }

        if ($function == 'count')
        {
            $totalCallback = function($values) {
                return count($values);
            };
            
            $this->setTotalFunction( $totalCallback );
        }
    }

    public function setTotalFormField($totalFormField)
    {
        $this->totalFormField = $totalFormField;
    }

    public function getTotalFormField()
    {
        return $this->totalFormField;
    }
    
    /**
     * Sets a callback function for calculating the total of the column.
     *
     * @param callable $callback           The total calculation function.
     * @param bool     $apply_transformer  Whether to apply the transformation function to the total.
     */
    public function setTotalFunction(Callable $callback, $apply_transformer = true)
    {
        $this->totalCallback = $callback;
        $this->totalTransformed = $apply_transformer;
    }
    
    /**
     * Retrieves the total calculation callback function.
     *
     * @return callable|null The total function, or null if not set.
     */
    public function getTotalCallback()
    {
        return $this->totalCallback;
    }
    
    /**
     * Retrieves the total function type.
     *
     * @return string|null The function type (sum, count, etc.), or null if not set.
     */
    public function getTotalFunction()
    {
        return $this->totalFunction;
    }
    
    /**
     * Retrieves the format mask for the total value.
     *
     * @return string|null The total format mask, or null if not set.
     */
    public function getTotalMask()
    {
        return $this->totalMask;
    }
    
    /**
     * Checks whether the total function applies a transformation.
     *
     * @return bool True if transformation is applied, false otherwise.
     */
    public function totalTransformed()
    {
        return $this->totalTransformed;
    }
}
