<?php
namespace Adianti\Widget\Form;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Util\TImage;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Exception;
use stdClass;

/**
 * Represents a dynamic field list component that extends a table structure.
 * It allows adding, removing, and managing dynamic fields with sorting, cloning,
 * and custom actions.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TFieldList extends TTable
{
    private $fields;
    private $labels;
    private $body_created;
    private $detail_row;
    private $remove_function;
    private $remove_action;
    private $clone_function;
    private $sort_action;
    private $sorting;
    private $fields_properties;
    private $row_functions;
    private $row_actions;
    private $automatic_aria;
    private $summarize;
    private $totals;
    private $total_functions;
    private $remove_enabled;
    private $clone_enabled;
    private $remove_icon;
    private $remove_title;
    private $field_prefix;
    private $thead;
    private $tfoot;
    private $tbody;
    private $allow_post_empty;
    protected $totalUpdateAction;
    
    /**
     * Class Constructor
     * Initializes the TFieldList component, setting default properties and behaviors.
     */
    public function __construct()
    {
        parent::__construct();
        $this->{'id'}     = 'tfieldlist_' . mt_rand(1000000000, 1999999999);
        $this->{'name'}   = $this->{'id'};
        $this->{'class'}  = 'tfieldlist';
        
        $this->fields = [];
        $this->fields_properties = [];
        $this->row_functions = [];
        $this->row_actions = [];
        $this->body_created = false;
        $this->detail_row = 0;
        $this->sorting = false;
        $this->automatic_aria = false;
        $this->remove_function = 'ttable_remove_row(this)';
        $this->clone_function  = 'ttable_clone_previous_row(this)';
        $this->summarize = false;
        $this->total_functions = null;
        $this->remove_enabled = true;
        $this->clone_enabled = true;
        $this->allow_post_empty = true;
    }
    
    /**
     * Disables the ability to post an empty row in the field list.
     */
    public function disablePostEmptyRow()
    {
        $this->allow_post_empty = false;
    }
    
    /**
     * Retrieves the posted data as a list of objects.
     * The method processes all fields and structures them into objects.
     *
     * @return array An array of stdClass objects containing the submitted data.
     */
    public function getPostData()
    {
        $data = [];
        
        if($this->fields)
        {
            foreach($this->fields as $field)
            {
                $field_name = $field->getName();
                $name  = str_replace( ['[', ']'], ['', ''], $field->getName());
                
                $data[$name] = $field->getPostData();
            }
        }
        
        
        $results = [];
        
        if($data)
        {
            foreach ($data as $name => $values)
            {
                $field_name = $name;
                
                if (!empty($this->field_prefix))
                {
                    $field_name = str_replace($this->field_prefix . '_', '', $field_name);
                }
                
                if($values)
                {
                    foreach ($values as $row => $value)
                    {
                        $results[$row] = $results[$row] ?? new stdClass;
                        $results[$row]->$field_name = $value;
                    }
                }
            }
        }
        
        if (!$this->allow_post_empty)
        {
            if ($results)
            {
                foreach ($results as $row => $object)
                {
                    $array_object = (array) $object;
                    unset($array_object['uniq']);
                    if (count(array_filter($array_object)) == 0)
                    {
                        unset($results[$row]);
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Gets the number of rows submitted.
     *
     * @param string|null $field_name The field name to count values for.
     *
     * @return int The count of non-empty rows in the submitted data.
     */
    public function getRowCount($field_name = null)
    {
        if (count($this->fields) > 0)
        {
            if (isset($this->fields[$field_name]))
            {
                $field = $this->fields[$field_name];
            }
            else if (isset($this->fields[$field_name.'[]']))
            {
                $field = $this->fields[$field_name.'[]'];
            }
            else
            {
                $field = array_values($this->fields)[0];
            }
            
            return count(array_filter($field->getPostData(), function($value){
                return $value !== '';
            }));
        }
        
        return 0;
    }
    
    /**
     * Disables the remove button, preventing users from deleting rows.
     */
    public function disableRemoveButton()
    {
        $this->remove_enabled = false;
    }

    /**
     * Checks whether the remove button is enabled.
     *
     * @return bool True if removal is enabled, false otherwise.
     */
    public function getRemoveEnabled()
    {
        return $this->remove_enabled;
    }

    /**
     * Disables the clone button, preventing users from duplicating rows.
     */
    public function disableCloneButton()
    {
        $this->clone_enabled = false;
    }
    
    /**
     * Checks whether the clone button is enabled.
     *
     * @return bool True if cloning is enabled, false otherwise.
     */
    public function getCloneEnabled()
    {
        $this->clone_enabled = false;
    }

    /**
     * Enables row sorting functionality.
     */
    public function enableSorting()
    {
        $this->sorting = true;
    }
    
    /**
     * Enables automatic generation of ARIA labels for accessibility.
     */
    public function generateAria()
    {
        $this->automatic_aria = true;
    }
    
    /**
     * Defines an action to be executed when the user sorts the rows.
     *
     * @param TAction $action The sorting action.
     *
     * @throws Exception If the provided action is not static.
     */
    public function setSortAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->sort_action = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Sets the JavaScript function to be executed when removing a row.
     *
     * @param string $action The JavaScript function.
     * @param string|null $icon The icon for the remove button.
     * @param string|null $title The tooltip title for the remove button.
     */
    public function setRemoveFunction($action, $icon = null, $title = null)
    {
        $this->remove_function = $action;
        $this->remove_icon     = $icon;
        $this->remove_title    = $title;
    }
    
    /**
     * Sets a PHP action to be executed when removing a row.
     *
     * @param TAction|null $action The removal action.
     * @param string|null $icon The icon for the remove button.
     * @param string|null $title The tooltip title for the remove button.
     *
     * @throws Exception If the provided action is not static.
     */
    public function setRemoveAction(?TAction $action = null, $icon = null, $title = null)
    {
        if ($action)
        {
            if ($action->isStatic())
            {
                $this->remove_action = $action;
            }
            else
            {
                $string_action = $action->toString();
                throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
            }
        }
        
        $this->remove_icon  = $icon;
        $this->remove_title = $title;
    }
    
    /**
     * Sets the JavaScript function to be executed when cloning a row.
     *
     * @param string $action The JavaScript function.
     */
    public function setCloneFunction($action)
    {
        $this->clone_function = $action;
    }
    
    /**
     * Adds a button with a custom JavaScript function.
     *
     * @param string $function The JavaScript function.
     * @param string $icon The button icon.
     * @param string $title The button tooltip title.
     */
    public function addButtonFunction($function, $icon, $title)
    {
        $this->row_functions[] = [$function, $icon, $title];
    }
    
    /**
     * Adds a button with a PHP action.
     *
     * @param TAction $action The action to be executed.
     * @param string $icon The button icon.
     * @param string $title The button tooltip title.
     */
    public function addButtonAction(TAction $action, $icon, $title)
    {
        $this->row_actions[] = [$action, $icon, $title];
    }
    
    /**
     * Defines an action to update total values dynamically.
     *
     * @param TAction $action The total update action.
     */
    public function setTotalUpdateAction(TAction $action)
    {
        $this->totalUpdateAction = $action;
        parent::setProperty('total-update-action', $action->serialize(false));
    }
    
    /**
     * Sets a prefix for field names in the list.
     *
     * @param string $prefix The prefix to be used.
     */
    public function setFieldPrefix($prefix)
    {
        $this->field_prefix = $prefix;
    }
    
    /**
     * Retrieves the current field name prefix.
     *
     * @return string|null The field prefix if set, null otherwise.
     */
    public function getFieldPrefix()
    {
        return $this->field_prefix;
    }
    
    /**
     * Adds a field to the field list.
     *
     * @param string|TLabel $label The field label.
     * @param AdiantiWidgetInterface $field The field object.
     * @param array|null $properties Additional field properties.
     *
     * @throws Exception If a duplicate field name is detected.
     */
    public function addField($label, AdiantiWidgetInterface $field, $properties = null)
    {
        if ($field instanceof TField)
        {
            $name = $field->getName();
            
            if (!empty($this->field_prefix) && strpos($name, $this->field_prefix) === false)
            {
                $name = $this->field_prefix . '_' . $name;
                $field->setName($name);
            }
            
            if (isset($this->fields[$name]) AND substr($name,-2) !== '[]')
            {
                throw new Exception(AdiantiCoreTranslator::translate('You have already added a field called "^1" inside the form', $name));
            }
            
            if ($name)
            {
                $this->fields[$name] = $field;
                $this->fields_properties[$name] = $properties;
            }
            
            if (isset($properties['sum']) && $properties['sum'] == true)
            {
                $this->summarize = true;
            }
            
            if (isset($properties['uniqid']) && $properties['uniqid'] == true)
            {
                $field->{'uniqid'} = 'true';
            }
            
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
            
            $field->setLabel($label_value);
            $this->labels[$name] = $label_field;
        }
    }
    
    /**
     * Adds a table header row based on the defined fields.
     *
     * @return TElement The generated header element.
     */
    public function addHeader()
    {
        $this->thead = $section = parent::addSection('thead');
        
        if ($this->fields)
        {
            $row = parent::addRow();
            
            if ($this->sorting)
            {
                $row->addCell( '' );
            }
            
            foreach ($this->fields as $name => $field)
            {
                if ($field instanceof THidden)
                {
                    $cell = $row->addCell( '' );
                    $cell->{'style'} = 'display:none';
                }
                else
                {
                    $cell = $row->addCell( $this->labels[ $field->getName()] );
                    
                    if (!empty($this->fields_properties[$name]))
                    {
                        foreach ($this->fields_properties[$name] as $property => $value)
                        {
                            $cell->setProperty($property, $value);
                        }
                    }
                }
            }
            
            $all_actions = array_merge( (array) $this->row_functions, (array) $this->row_actions );
            
            if ($all_actions)
            {
                foreach ($all_actions as $row_action)
                {
                    $cell = $row->addCell( '' );
                    $cell->{'style'} = 'display:none';
                }
            }
            
            if ($this->remove_enabled)
            {
                // aligned with remove button
                $cell = $row->addCell( '' );
                $cell->{'style'} = 'display:none';
            }
        }
        
        return $section;
    }
    
    /**
     * Adds a row with field inputs based on an item.
     *
     * @param stdClass $item The data object for the row.
     *
     * @return TElement The created row.
     */
    public function addDetail( $item )
    {
        $uniqid = mt_rand(1000000, 9999999);
        $field_list_name = $this->{'name'};
        
        if (!$this->body_created)
        {
            $this->tbody = parent::addSection('tbody');
            $this->body_created = true;
        }
        
        if ($this->fields)
        {
            $row = parent::addRow();
            $row->{'id'} = $uniqid;
            
            if ($this->sorting)
            {
                $move = new TImage('fas:arrows-alt gray');
                $move->{'class'} .= ' handle';
                $move->{'style'} .= ';font-size:100%;cursor:move';
                $row->addCell( $move );
            }
            
            foreach ($this->fields as $field)
            {
                $field_name = $field->getName();
                $name  = str_replace( ['[', ']'], ['', ''], $field->getName());
                
                $clone = clone $field;
                
                if (isset($this->fields_properties[$field_name]['sum']) && $this->fields_properties[$field_name]['sum'] == true)
                {
                    $clone->{'exitaction'} = "tfieldlist_update_sum('{$field_list_name}', '{$name}', 'callback')";
                    $clone->{'onBlur'}     = "tfieldlist_update_sum('{$field_list_name}', '{$name}', 'callback')";
                    
                    $this->total_functions .= $clone->{'exitaction'} . ';';
                    
                    $value = isset($item->$name) ? $item->$name : 0;
                    
                    if (isset($field->{'data-nmask'}))
                    {
                        $dec_sep = substr($field->{'data-nmask'},1,1);
                        $tho_sep = substr($field->{'data-nmask'},2,1);
                        
                        if ( (strpos($value, $tho_sep) !== false) && (strpos($value, $dec_sep) !== false) )
                        {
                            $value   = str_replace($tho_sep, '', $value);
                            $value   = str_replace($dec_sep, '.', $value);
                        }
                    }
                    
                    if (isset($this->totals[$name]))
                    {
                        $this->totals[$name] += $value;
                    }
                    else
                    {
                        $this->totals[$name] = $value;
                    }
                }

                if (isset($this->fields_properties[$field_name]['count']) && $this->fields_properties[$field_name]['count'] == true)
                {
                    $fieldClass = get_class($field);
                    
                    if(preg_match('/TEntry|TNumeric/', $fieldClass))
                    {
                        $field->{'exitaction'} = "tfieldlist_update_count('{$field_list_name}', '{$name}', 'callback')";
                        $field->{'onBlur'}     = "tfieldlist_update_count('{$field_list_name}', '{$name}', 'callback')";
                        $this->total_functions .= $field->{'exitaction'} . ';';
                    }
                    else
                    {
                        $field->{'changeaction'} = "tfieldlist_update_count('{$field_list_name}', '{$name}', 'callback')";
                        $field->{'onchange'}     = "tfieldlist_update_count('{$field_list_name}', '{$name}', 'callback')";
                        $this->total_functions .= $field->{'changeaction'} . ';';
                    }

                    $value = isset($item->$name) ? $item->$name : 0;
                    
                    if (isset($field->{'data-nmask'}))
                    {
                        $dec_sep = substr($field->{'data-nmask'},1,1);
                        $tho_sep = substr($field->{'data-nmask'},2,1);
                        
                        if ( (strpos($value, $tho_sep) !== false) && (strpos($value, $dec_sep) !== false) )
                        {
                            $value   = str_replace($tho_sep, '', $value);
                            $value   = str_replace($dec_sep, '.', $value);
                        }
                    }
                    
                    if (isset($this->totals[$name]))
                    {
                        $this->totals[$name] += $value;
                    }
                    else
                    {
                        $this->totals[$name] = $value;
                    }
                }
                
                if ($this->automatic_aria)
                {
                    $label = $this->labels[ $field->getName() ];
                    $aria_label = $label->getValue();
                    $field->{'aria-label'} = $aria_label;
                }
                
                $clone->setId($name.'_'.$uniqid);
                $clone->{'data-row'} = $this->detail_row;
                
                $cell = $row->addCell( $clone );
                $cell->{'class'} = 'field';
                
                if (!empty($this->fields_properties[$field_name]))
                {
                    foreach ($this->fields_properties[$field_name] as $property => $value)
                    {
                        $cell->setProperty($property, $value);
                    }
                }
                
                if ($clone instanceof THidden)
                {
                    $cell->{'style'} = 'display:none';
                }
                
                if (!empty($item->$name) OR (isset($item->$name) AND $item->$name == '0'))
                {
                    $clone->setValue( $item->$name );
                }
                else
                {
                    if ($field->{'uniqid'} == true)
                    {
                        $clone->setValue( mt_rand(1000000000, 1999999999) );
                    }
                    else
                    {
                        $clone->setValue( null );
                    }
                }
            }
            
            if ($this->row_actions)
            {
                foreach ($this->row_actions as $row_action)
                {
                    $string_action = $row_action[0]->serialize(FALSE);
                    
                    $btn = new TElement('div');
                    $btn->{'class'} = 'btn btn-default btn-sm';
                    $btn->{'onclick'} = "__adianti_post_exec('{$string_action}', tfieldlist_get_row_data(this), null, undefined, '1')";
                    $btn->{'title'} = $row_action[2];
                    $btn->add(new TImage($row_action[1]));
                    $row->addCell( $btn );
                }
            }
            
            if ($this->row_functions)
            {
                foreach ($this->row_functions as $row_function)
                {
                    $btn = new TElement('div');
                    $btn->{'class'} = 'btn btn-default btn-sm';
                    $btn->{'onclick'} = $row_function[0];
                    $btn->{'title'} = $row_function[2];
                    $btn->add(new TImage($row_function[1]));
                    $row->addCell( $btn );
                }
            }
            
            if ($this->remove_enabled)
            {
                $del = new TElement('div');
                $del->{'class'} = 'btn btn-default btn-sm';
                $del->{'onclick'} = $this->total_functions;
                
                if (isset($this->remove_action))
                {
                    $string_action = $this->remove_action->serialize(FALSE);
                    $del->{'onclick'} .= ";__adianti_post_exec('{$string_action}', tfieldlist_get_row_data(this), null, undefined, '1');";
                }

                $del->{'onclick'} .= $this->remove_function;

                $this->total_functions = '';
                
                $del->{'title'} = $this->remove_title ? $this->remove_title : AdiantiCoreTranslator::translate('Delete');
                $del->add($this->remove_icon ? new TImage($this->remove_icon) : '<i class="fa fa-times red"></i>');
                $row->addCell( $del );
            }
        }
        
        $this->detail_row ++;
        
        return $row;
    }
    
    /**
     * Adds a cloning action to duplicate rows.
     *
     * @param TAction|null $clone_action The cloning action.
     * @param string|null $icon The clone button icon.
     * @param string|null $title The clone button tooltip title.
     *
     * @throws Exception If no detail rows have been added before calling this method.
     */
    public function addCloneAction(?TAction $clone_action = null, $icon = null, $title = null)
    {
        if (!$this->body_created)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', 'addDetail', 'addCloneAction'));
        }
        
        $this->tfoot = parent::addSection('tfoot');
        
        $row = parent::addRow();
        
        if ($this->sorting)
        {
            $row->addCell( '' );
        }
        
        if ($this->fields)
        {
            foreach ($this->fields as $field)
            {
                $field_name = $field->getName();
                
                $cell = $row->addCell('');
                if ($field instanceof THidden)
                {
                    $cell->{'style'} = 'display:none';
                }
                else if (isset($this->fields_properties[$field_name]['sum']) && $this->fields_properties[$field_name]['sum'] == true)
                {
                    $totalFormField = $this->fields_properties[$field_name]['totalFormField'] ?? false;
                    $field_name = str_replace('[]', '', $field_name);
                    $grand_total = clone $field;
                    $grand_total->setId($field_name.'_'.mt_rand(1000000, 9999999));
                    $grand_total->setName('grandtotal_'.$field_name);
                    $grand_total->{'field_name'} = $field_name;
                    $grand_total->setEditable(FALSE);
                    $grand_total->{'style'}  .= ';font-weight:bold;border:0 !important;background:none';
                    
                    if (!empty($this->totals[$field_name]))
                    {
                        $grand_total->setValue($this->totals[$field_name]);
                    }
                    
                    if($totalFormField)
                    {
                        $grand_total->{'data-total-form-field'} = $totalFormField;
                    }

                    $cell->add($grand_total);
                }
                else if (isset($this->fields_properties[$field_name]['count']) && $this->fields_properties[$field_name]['count'] == true)
                {
                    $field_name = str_replace('[]', '', $field_name);
                    $grand_total = new TEntry('grandtotal_'.$field_name);
                    $grand_total->setId($field_name.'_'.mt_rand(1000000, 9999999));
                    $grand_total->{'field_name'} = $field_name;
                    $grand_total->setEditable(FALSE);
                    $grand_total->{'style'}  .= ';font-weight:bold;border:0 !important;background:none; width: 100%';
                    
                    
                    if (!empty($this->totals[$field_name]))
                    {
                        $grand_total->setValue($this->totals[$field_name]);
                    }

                    if(!empty($this->fields_properties[$field_name]['totalFormField']))
                    {
                        $grand_total->{'data-total-form-field'} = $this->fields_properties[$field_name]['totalFormField'];
                    }
                    
                    $cell->add($grand_total);
                }
            }
        }
        
        $all_actions = array_merge( (array) $this->row_functions, (array) $this->row_actions );
        
        if ($all_actions)
        {
            foreach ($all_actions as $row_action)
            {
                $cell = $row->addCell('');
            }
        }
        
        $add = new TElement('div');
        $add->{'class'} = 'btn btn-default btn-sm';
        $add->{'onclick'} = $this->clone_function;
        $add->{'title'} = $title ? $title : AdiantiCoreTranslator::translate('Add');
        
        if ($clone_action)
        {
            $string_action = $clone_action->serialize(FALSE);
            $add->{'onclick'} = $add->{'onclick'}.";__adianti_post_exec('{$string_action}', tfieldlist_get_last_row_data(this), null, undefined, '1');";
        }
        
        $add->add($icon ? new TImage($icon) : '<i class="fa fa-plus green"></i>');
        
        // add buttons in table
        if($this->clone_enabled)
        {
            $row->addCell($add);
        }
    }
    
    /**
     * Sets the clone action by first removing the previous one and then adding a new one.
     *
     * @param TAction|null $clone_action The cloning action.
     * @param string|null $icon The clone button icon.
     * @param string|null $title The clone button tooltip title.
     */
    public function setCloneAction(?TAction $clone_action = null, $icon = null, $title = null)
    {        
        if($this->tfoot && !empty($this->tfoot->getChildren()[0]))
        {
            $this->tfoot->del($this->tfoot->getChildren()[0]);
        }

        $this->addCloneAction($clone_action, $icon, $title);    
    }

    /**
     * Clears all rows from a specified field list.
     *
     * @param string $name The field list identifier.
     */
    public static function clear($name)
    {
        TScript::create( "tfieldlist_clear('{$name}');" );
    }
    
    /**
     * Clears a specified number of rows from a field list.
     *
     * @param string $name The field list identifier.
     * @param int $start The starting row index.
     * @param int $length The number of rows to clear.
     */
    public static function clearRows($name, $start = 0, $length = 0)
    {
        TScript::create( "tfieldlist_clear_rows('{$name}', {$start}, {$length});" );
    }
    
    /**
     * Adds a specified number of rows to a field list.
     *
     * @param string $name The field list identifier.
     * @param int $rows The number of rows to add.
     * @param int $timeout The delay in milliseconds before adding the rows.
     */
    public static function addRows($name, $rows, $timeout = 50)
    {
        TScript::create( "tfieldlist_add_rows('{$name}', {$rows}, {$timeout});" );
    }
    
    /**
     * Enables scrolling for the field list.
     *
     * @param int $height The height of the scrollable area in pixels.
     *
     * @throws Exception If called before adding a clone action.
     */
    public function makeScrollable($height)
    {
        if (empty($this->tfoot))
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', 'addCloneAction()', 'makeScrollable()'));
        }
        else
        {
            $this->thead->{'style'} .= ';display:block';
            $this->tbody->{'style'} .= ';display:block;overflow-y: scroll;height:'.$height.'px';
            $this->tbody->{'class'} .= ' thin-scroll';
            $this->tfoot->{'style'} .= ';display:block;float:right;margin-right:10px';
        }
    }
    
    /**
     * Retrieves the table header section.
     *
     * @return TElement The header element.
     */
    public function getHead()
    {
        return $this->thead;
    }

    /**
     * Retrieves the table footer section.
     *
     * @return TElement The footer element.
     */
    public function getFoot()
    {
        return $this->tfoot;
    }

    /**
     * Retrieves the table body section.
     *
     * @return TElement The body element.
     */
    public function getBody()
    {
        return $this->tbody;
    }
    
    /**
     * Enables a specific field in the field list.
     *
     * @param string $field The field name.
     */
    public static function enableField($field)
    {
        TScript::create( " tfieldlist_enable_field('{$field}'); " );
    }
    
    /**
     * Disables a specific field in the field list.
     *
     * @param string $field The field name.
     */
    public static function disableField($field)
    {
        TScript::create( " tfieldlist_disable_field('{$field}'); " );
    }
    
    /**
     * Renders the component and applies sorting behavior if enabled.
     */
    public function show()
    {
        parent::show();
        $id = $this->{'id'};
        
        if ($this->sorting)
        {
            if (empty($this->sort_action))
            {
                TScript::create("ttable_sortable_rows('{$id}', '.handle', function() { ttable_remove_row($('#{$id}'));} )");
            }
            else
            {
                if (!empty($this->fields))
                {
                    $first_field = array_values($this->fields)[0];
                    $this->sort_action->setParameter('static', '1');
                    $form_name   = $first_field->getFormName();
                    $string_action = $this->sort_action->serialize(FALSE);
                    $sort_action = "function() { ttable_remove_row($('#{$id}')); __adianti_post_data('{$form_name}', '{$string_action}'); }";
                    TScript::create("ttable_sortable_rows('{$id}', '.handle', $sort_action)");
                }
            }
        }
    }
}
