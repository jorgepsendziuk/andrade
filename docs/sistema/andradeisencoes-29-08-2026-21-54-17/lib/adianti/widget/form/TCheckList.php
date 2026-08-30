<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Base\TScript;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Widget\Wrapper\AdiantiDatabaseWidgetTrait;
use Adianti\Validator\TFieldValidator;
use Adianti\Control\TAction;

/**
 * TCheckList
 *
 * A checklist component that allows multiple selections in a datagrid.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCheckList implements AdiantiWidgetInterface
{
    protected $datagrid;
    protected $idColumn;
    protected $fields;
    protected $formName;
    protected $name;
    protected $value;
    protected $validations;
    protected $checkColumn;
    protected $checkAllButton;
    protected $width;
    protected $separator;
    protected $selectAction;
    
    use AdiantiDatabaseWidgetTrait;
    
    /**
     * Constructor method
     *
     * Initializes the checklist component, creates a datagrid, and adds the check-all button.
     *
     * @param string $name The name of the checklist field
     */
    public function __construct($name)
    {
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->{'style'} = 'width: 100%';
        $this->datagrid->{'widget'} = 'tchecklist';
        $this->datagrid->{'class'}  .= ' tchecklist';
        $this->datagrid->disableDefaultClick(); // important!
        
        $id = $this->datagrid->{'id'};
        
        $check = new TCheckButton('check_all_'.$id);
        $check->setIndexValue('on');
        $check->{'onclick'} = "tchecklist_select_all(this, '{$id}')";
        $check->{'style'} = 'cursor:pointer';
        $check->setProperty('class', 'filled-in');
        $this->checkAllButton = $check;
        
        $label = new TLabel('');
        $label->{'style'} = 'margin:0';
        $label->{'class'} = 'checklist-label';
        $check->after($label);
        $label->{'for'} = $check->getId();
        
        $this->checkColumn = $this->datagrid->addColumn( new TDataGridColumn('check',   $check->getContents(),   'center',  '1%') );
        
        $this->setName($name);
        $this->value = [];
        $this->fields = [];
        $this->width = '100%';
    }
    
    /**
     * Define the action to be executed when a row is selected
     *
     * @param TAction $action A static action object
     *
     * @throws Exception If the action is not static
     */
    public function setSelectAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->selectAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Disables HTML conversion on output
     * 
     * Prevents the automatic conversion of special characters into HTML entities.
     */
    public function disableHtmlConversion()
    {
        $this->datagrid->disableHtmlConversion();
    }

    /**
     * Sets the checklist size
     *
     * @param string $size The width of the checklist (e.g., '100%', '300px')
     */
    public function setSize($size)
    {
        $this->width = $size;
        
        if (strstr($size, '%') !== FALSE)
        {
            $this->datagrid->{'style'} .= ";width: {$size}";
        }
        else
        {
            $this->datagrid->{'style'} .= ";width: {$size}px";
        }
    }
    
    /**
     * Returns the checklist size
     *
     * @return array An array containing the width and height of the checklist
     */
    function getSize()
    {
        return [$this->width, $this->datagrid->getHeight()];
    }
    
    /**
     * Changes the checklist ID
     *
     * @param string $id The new ID for the checklist
     */
    public function setId($id)
    {
        $this->checkAllButton->{'onclick'} = "tchecklist_select_all(this, '{$id}')";
        $this->checkColumn->setLabel( $this->checkAllButton->getContents() );
        $this->datagrid->setId($id);
    }
    
    /**
     * Disables the check-all button
     *
     * Removes the label from the check-all column.
     */
    public function disableCheckAll()
    {
        $this->checkColumn->setLabel('');
    }
    
    /**
     * Defines the field name
     *
     * @param string $name The name of the checklist field
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the field name
     *
     * @return string The name of the checklist field
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the selected values for the checklist
     *
     * @param mixed $value An array of selected IDs or a string separated by the defined separator
     */
    public function setValue($value)
    {
        if ($this->separator && is_scalar($value))
        {
            $value = explode($this->separator, $value);
        }

        $this->value = $value;
        $id_column = $this->idColumn;
        $items = $this->datagrid->getItems();
        
        if ($items)
        {
            foreach ($items as $item)
            {
                $item->{'check'}->setValue(null);
                $position = $this->datagrid->getRowIndex( $id_column, $item->$id_column );
                if (is_int($position))
                {
                    $row = $this->datagrid->getRow($position);
                    $row->{'className'} = '';
                }
                
                if ($this->value)
                {
                    if (in_array($item->$id_column, $this->value))
                    {
                        $item->{'check'}->setValue('on');
                        
                        if (is_int($position))
                        {
                            $row = $this->datagrid->getRow($position);
                            $row->{'className'} = 'selected';
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Returns the selected values of the checklist
     *
     * @return mixed The selected values, either as an array or a string
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Defines the column that identifies each item
     *
     * @param string $name The name of the identification column
     */
    public function setIdColumn($name)
    {
        $this->idColumn = $name;
    }
    
    /**
     * Adds a column to the checklist
     *
     * @param string $name  The database field name
     * @param string $label The column header label
     * @param string $align The alignment of the column ('left', 'center', 'right')
     * @param string $width The width of the column (e.g., '100px')
     *
     * @return TDataGridColumn The created column
     */
    public function addColumn($name, $label, $align, $width)
    {
        if (empty($this->idColumn))
        {
            $this->idColumn = $name;
        }
        
        return $this->datagrid->addColumn( new TDataGridColumn($name, $label, $align, $width) );
    }
    
    /**
     * Adds an item to the checklist
     *
     * @param object $object The object representing a row in the checklist
     */
    public function addItem($object)
    {
        $id_column = $this->idColumn;
        $object->{'check'} = new TCheckButton('check_' . $this->name . '_' . base64_encode($object->$id_column));
        $object->{'check'}->setIndexValue('on');
        $object->{'check'}->setProperty('class', 'filled-in');
        $object->{'check'}->{'style'} = 'cursor:pointer';
        
        $label = new TLabel('');
        $label->{'style'} = 'margin:0';
        $label->{'class'} = 'checklist-label';
        $object->{'check'}->after($label);
        $label->{'for'} = $object->{'check'}->getId();
        
        if (count($this->datagrid->getItems()) == 0)
        {
            $this->datagrid->createModel();
        }
        
        $row = $this->datagrid->addItem($object);
        
        if (in_array($object->$id_column, $this->value))
        {
            $object->{'check'}->setValue('on');
            $row->{'className'} = 'selected';
        }
        
        $this->fields[] = $object->{'check'};
        
        $form = TForm::getFormByName($this->formName);
        if ($form)
        {
            $form->addField($object->{'check'});
        }
    }
    
    /**
     * Adds multiple items to the checklist
     *
     * @param array $objects An array of objects to be added as rows
     */
    public function addItems($objects)
    {
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $this->addItem($object);
            }
        }
    }
    
    /**
     * Populates the checklist with model objects from the database
     *
     * @param string    $database    The database connection name
     * @param string    $model       The model class name
     * @param string    $key         The key column name
     * @param string    $ordercolumn The column used for ordering (optional)
     * @param TCriteria $criteria    Additional selection criteria (optional)
     */
    public function fillWith($database, $model, $key, $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        TTransaction::open($database);
        $this->addItems( $this->getObjectsFromModel($database, $model, $key, $ordercolumn, $criteria) );
        TTransaction::close();
    }
    
    /**
     * Clears all items from the checklist
     */
    public function clear()
    {
        $this->datagrid->clear();
    }
    
    /**
     * Returns the checklist fields
     *
     * @return array An array of field objects
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    /**
     * Defines the name of the form to which the checklist belongs
     *
     * @param string $name The form name
     */
    public function setFormName($name)
    {
        $this->formName = $name;
    }
    
    /**
     * Returns the name of the form to which the checklist belongs
     *
     * @return string The form name
     */
    public function getFormName()
    {
        return $this->formName;
    }
    
    /**
     * Redirects method calls to the decorated datagrid object
     *
     * @param string $method     The method being called
     * @param array  $parameters The method parameters
     *
     * @return mixed The return value of the called method
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->datagrid, $method),$parameters);
    }
    
    /**
     * Defines the separator used for storing multiple values as a string
     *
     * @param string $sep The separator string
     */
    public function setValueSeparator($sep)
    {
        $this->separator = $sep;
    }

    /**
     * Retrieves the selected values from the checklist after form submission
     *
     * @return mixed The selected values, either as an array or a string
     */
    public function getPostData()
    {
        $value = [];
        $items = $this->datagrid->getItems();
        
        $id_column = $this->idColumn;
        if ($items)
        {
            foreach ($items as $item)
            {
                $field_name = 'check_'.$this->name . '_' . base64_encode($item->$id_column);
                
                if (!empty($_POST[$field_name]) && $_POST[$field_name] == 'on')
                {
                    $value[] = $item->$id_column;
                }
            }
        }

        if ($this->separator)
        {
            $value = implode($this->separator, $value);
        }
        
        return $value;
    }
    
    /**
     * Adds a validation rule to the checklist
     *
     * @param string          $label      The field label
     * @param TFieldValidator $validator  The validator object
     * @param mixed           $parameters Additional parameters for validation (optional)
     */
    public function addValidation($label, TFieldValidator $validator, $parameters = NULL)
    {
        $this->validations[] = array($label, $validator, $parameters);
    }
    
    /**
     * Returns all validation rules applied to the checklist
     *
     * @return array An array of validation rules
     */
    public function getValidations()
    {
        return $this->validations;
    }
    
    /**
     * Validates the checklist field
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
     * Enables the checklist field
     *
     * @param string $field The field name
     */
    public static function enableField($field)
    {
        TScript::create( " tchecklist_enable_field('{$field}'); " );
    }

    /**
     * Disables the checklist field
     *
     * @param string $field The field name
     */
    public static function disableField($field)
    {
        TScript::create( " tchecklist_disable_field('{$field}'); " );
    }
    
    /**
     * Displays the checklist component
     *
     * Creates the datagrid model if it does not exist, sets the selection action if defined,
     * and renders the datagrid with JavaScript integration for selection handling.
     */
    public function show()
    {
        if (count($this->datagrid->getItems()) == 0)
        {
            $this->datagrid->createModel();
        }
        
        $this->datagrid->{'name'} = $this->name;
        
        if (!empty($this->selectAction))
        { 
            $this->datagrid->{'onselect'} = $this->selectAction->serialize(FALSE);
        }
        
        $this->datagrid->show();
        
        $id = $this->datagrid->{'id'};
        TScript::create("tchecklist_row_enable_check('{$id}')");
    }
}
