<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCheckGroup;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Container\THBox;
use Adianti\Validator\TFieldValidator;
use Adianti\Validator\TRequiredValidator;

use Exception;

/**
 * Provides a quick form wrapper for input data with a standard container for elements.
 * This class extends TForm and simplifies the creation of forms using tables.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TQuickForm extends TForm
{
    protected $fields; // array containing the form fields
    protected $name;   // form name
    protected $actionButtons;
    protected $inputRows;
    protected $currentRow;
    protected $table;
    protected $actionsContainer;
    protected $hasAction;
    protected $fieldsByRow;
    protected $titleCell;
    protected $actionCell;
    protected $fieldPositions;
    protected $client_validation;
    
    /**
     * Class Constructor
     * Initializes the form and creates a table as the main container.
     *
     * @param string $name Form name (default: 'my_form')
     */
    public function __construct($name = 'my_form')
    {
        parent::__construct($name);
        
        // creates a table
        $this->table = new TTable;
        $this->hasAction = FALSE;
        $this->client_validation = FALSE;
        
        $this->fieldsByRow = 1;
        
        $this->setProperty('novalidate','');
        
        // add the table to the form
        parent::add($this->table);
    }
    
    /**
     * Enables or disables client-side validation.
     *
     * @param bool $bool TRUE to enable validation, FALSE to disable it.
     */
    public function setClientValidation($bool)
    {
        if ($bool)
        {
            $this->unsetProperty('novalidate');
        }
        else
        {
            $this->setProperty('novalidate','');
        }
    }
    
    /**
     * Returns the container that holds the form actions.
     *
     * @return THBox|null The actions container or null if not defined.
     */
    public function getActionsContainer()
    {
        return $this->actionsContainer;
    }
    
    /**
     * Returns the internal table used in the form layout.
     *
     * @return TTable The internal table instance.
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * Defines the number of fields per row in the form.
     *
     * @param int $count The number of fields per row (must be between 1 and 3).
     *
     * @throws Exception If the count is outside the allowed range.
     */
    public function setFieldsByRow($count)
    {
        if (is_int($count) AND $count >=1 AND $count <=3)
        {
            $this->fieldsByRow = $count;
            if (!empty($this->titleCell))
            {
                $this->titleCell->{'colspan'}  = 2 * $this->fieldsByRow;
            }
            if (!empty($this->actionCell))
            {
                $this->actionCell->{'colspan'} = 2 * $this->fieldsByRow;
            }
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('The method (^1) just accept values of type ^2 between ^3 and ^4', __METHOD__, 'integer', 1, 3));
        }
    }
    
    /**
     * Returns the current number of fields per row.
     *
     * @return int The number of fields per row.
     */
    public function getFieldsByRow()
    {
        return $this->fieldsByRow;
    }
    
    /**
     * Intercepts property assignments and applies special handling.
     *
     * @param string $name  Property name.
     * @param mixed  $value Property value.
     */
    public function __set($name, $value)
    {
        if ($name == 'class')
        {
            $this->table->{'width'} = '100%';
        }
        
        if (method_exists('TForm', '__set'))
        {
            parent::__set($name, $value);
        }
    }
    
    /**
     * Returns the main container of the form.
     *
     * @return TTable The container instance.
     */
    public function getContainer()
    {
        return $this->table;
    }
    
    /**
     * Sets the title of the form.
     *
     * @param string $title The title to be displayed.
     */
    public function setFormTitle($title)
    {
        // add the field to the container
        $row = $this->table->addRow();
        $row->{'class'} = 'tformtitle';
        $this->table->{'width'} = '100%';
        $this->titleCell = $row->addCell( new TLabel($title) );
        $this->titleCell->{'colspan'} = 2 * $this->fieldsByRow;
    }
    
    /**
     * Returns the rows containing form input fields.
     *
     * @return array The array of input rows.
     */
    public function getInputRows()
    {
        return $this->inputRows;
    }
    
    /**
     * Adds a form field with a label and optional validation.
     *
     * @param string                $label      The field label.
     * @param AdiantiWidgetInterface $object     The form field object.
     * @param int|null              $size       The field size (default: 200).
     * @param TFieldValidator|null  $validator  A validation object (optional).
     * @param int|null              $label_size The label size (optional).
     *
     * @return mixed The row object where the field was added.
     */
    public function addQuickField($label, AdiantiWidgetInterface $object, $size = 200, TFieldValidator $validator = NULL, $label_size = NULL)
    {
        if ($size && !$object instanceof TRadioGroup && !$object instanceof TCheckGroup)
        {
            $object->setSize($size);
        }
        parent::addField($object);
        
        if ($label instanceof TLabel)
        {
            $label_field = $label;
            $label_value = $label->getValue();
        }
        else
        {
            $label_field = new TLabel($label);
            $label_value = $label;
        }
        
        $object->setLabel($label_value);
        
        if ( empty($this->currentRow) OR ( $this->fieldPositions % $this->fieldsByRow ) == 0 )
        {
            // add the field to the container
            $this->currentRow = $this->table->addRow();
            $this->currentRow->{'class'} = 'tformrow';
        }
        $row = $this->currentRow;
        
        if ($validator instanceof TRequiredValidator)
        {
            $label_field->setFontColor('#FF0000');
        }
        
        if ($label_size)
        {
            $label_field->setSize($label_size);
        }
        if ($object instanceof THidden)
        {
            $row->addCell( '' );
            $row->{'style'} = 'display:none';
        }
        else
        {
            $cell = $row->addCell( $label_field );
            $cell->{'width'} = '30%';
        }
        $row->addCell( $object );
        
        if ($validator)
        {
            $object->addValidation($label_value, $validator);
        }
        
        $this->inputRows[] = array($label_field, array($object), $validator instanceof TRequiredValidator, $row);
        $this->fieldPositions ++;
        return $row;
    }
    
    /**
     * Adds multiple form fields under a single label.
     *
     * @param string $label    The label for the field group.
     * @param array  $objects  An array of form field objects.
     * @param bool   $required TRUE if the fields are required.
     *
     * @return mixed The row object where the fields were added.
     */
    public function addQuickFields($label, $objects, $required = FALSE)
    {
        if ( empty($this->currentRow) OR ( $this->fieldPositions % $this->fieldsByRow ) == 0 )
        {
            // add the field to the container
            $this->currentRow = $this->table->addRow();
            $this->currentRow->{'class'} = 'tformrow';
        }
        $row = $this->currentRow;
        
        if ($label instanceof TLabel)
        {
            $label_field = $label;
            $label_value = $label->getValue();
        }
        else
        {
            $label_field = new TLabel($label);
            $label_value = $label;
        }
        
        if ($required)
        {
            $label_field->setFontColor('#FF0000');
        }
        
        $row->addCell( $label_field );
        
        $hbox = new THBox;
        foreach ($objects as $object)
        {
            parent::addField($object);
            
            if (!$object instanceof TButton)
            {
                $object->setLabel($label_value);
            }
            $hbox->add($object);
        }
        $row->addCell( $hbox );
        
        $this->fieldPositions ++;
        
        $this->inputRows[] = array($label_field, $objects, $required, $row);
        return $row;
    }
    
    /**
     * Adds an action button to the form.
     *
     * @param string  $label  The label for the action.
     * @param TAction $action The action object.
     * @param string  $icon   The icon for the button (default: 'fa:save').
     *
     * @return TButton The created button instance.
     */
    public function addQuickAction($label, TAction $action, $icon = 'fa:save')
    {
        $name   = 'btn_'.strtolower(str_replace(' ', '_', $label));
        $button = new TButton($name);
        parent::addField($button);
        
        // define the button action
        $button->setAction($action, $label);
        $button->setImage($icon);
        
        if (!$this->hasAction)
        {
            $this->actionsContainer = new THBox;
            
            $row  = $this->table->addRow();
            $row->{'class'} = 'tformaction';
            $this->actionCell = $row->addCell( $this->actionsContainer );
            $this->actionCell->{'colspan'} = 2 * $this->fieldsByRow;
        }
        
        // add cell for button
        $this->actionsContainer->add($button);
        
        $this->hasAction = TRUE;
        $this->actionButtons[] = $button;
        
        return $button;
    }
    
    /**
     * Adds a button with a JavaScript action to the form.
     *
     * @param string $label  The button label.
     * @param string $action The JavaScript action.
     * @param string $icon   The button icon (default: 'fa:save').
     *
     * @return TButton The created button instance.
     */
    public function addQuickButton($label, $action, $icon = 'fa:save')
    {
        $name   = strtolower(str_replace(' ', '_', $label));
        $button = new TButton($name);
        parent::addField($button);
        
        // define the button action
        $button->addFunction($action);
        $button->setLabel($label);
        $button->setImage($icon);
        
        if (!$this->hasAction)
        {
            $this->actionsContainer = new THBox;
            
            $row  = $this->table->addRow();
            $row->{'class'} = 'tformaction';
            $this->actionCell = $row->addCell( $this->actionsContainer );
            $this->actionCell->{'colspan'} = 2 * $this->fieldsByRow;
        }
        
        // add cell for button
        $this->actionsContainer->add($button);
        $this->hasAction = TRUE;
        
        return $button;
    }
    
    /**
     * Clears all action buttons from the form.
     */
    public function delActions()
    {
        if ($this->actionsContainer)
        {
            foreach ($this->actionButtons as $key => $button)
            {
                parent::delField($button);
                unset($this->actionButtons[$key]);
            }
            $this->actionsContainer->clearChildren();
        }
    }
    
    /**
     * Returns an array of action buttons added to the form.
     *
     * @return array The array of action buttons.
     */
    public function getActionButtons()
    {
        return $this->actionButtons;
    }
    
    /**
     * Detaches all action buttons and clears them from the form.
     *
     * @return array The detached action buttons.
     */
    public function detachActionButtons()
    {
        $buttons = $this->getActionButtons();
        $this->delActions();
        return $buttons;
    }
    
    /**
     * Adds a new row to the form table.
     *
     * @return mixed The created row object.
     */
    public function addRow()
    {
        return $this->table->addRow();
    }
    
    /**
     * Displays a form field using JavaScript.
     *
     * @param string $form  The form name.
     * @param string $field The field name.
     * @param int    $speed The speed of the animation effect (default: 0).
     */
    public static function showField($form, $field, $speed = 0)
    {
        TScript::create("tform_show_field('{$form}', '{$field}', {$speed})");
    }
    
    /**
     * Hides a form field using JavaScript.
     *
     * @param string $form  The form name.
     * @param string $field The field name.
     * @param int    $speed The speed of the animation effect (default: 0).
     */
    public static function hideField($form, $field, $speed = 0)
    {
        TScript::create("tform_hide_field('{$form}', '{$field}', {$speed})");
    }
}
