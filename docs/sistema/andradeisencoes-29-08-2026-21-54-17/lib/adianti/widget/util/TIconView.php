<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Util\AdiantiTemplateHandler;

use stdClass;

/**
 * TIconView Widget
 *
 * This class represents an icon view widget, which displays a collection of items
 * in an icon-based format. It supports features such as popovers, context menus, 
 * item templates, and drag-and-drop functionality.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TIconView extends TElement
{
    protected $items;
    protected $options;
    protected $itemProperties;
    protected $labelField;
    protected $iconField;
    protected $infoFields;
    protected $popover;
    protected $poptitle;
    protected $popcontent;
    protected $popside;
    protected $popcondition;
    protected $enableMoving;
    protected $moveAction;
    protected $dragSelector;
    protected $dropSelector;
    protected $itemTemplate;
    protected $templateAttribute;
    protected $doubleClickEnabled;
    
    /**
     * Constructor method
     *
     * Initializes the icon view with default settings and assigns a unique ID.
     */
    public function __construct()
    {
        parent::__construct('ul');
        
        $this->{'id'}    = 'ticonview_' . mt_rand(1000000000, 1999999999);
        $this->{'class'} = 'ticonview';
        $this->items   = [];
        $this->options = [];
        $this->popover = FALSE;
        $this->popside = null;
        $this->enableMoving = FALSE;
        $this->doubleClickEnabled = FALSE;
    }
    
    /**
     * Enables double-click functionality
     *
     * When enabled, double-clicking an item will trigger its associated action.
     */
    public function enableDoubleClick()
    {
        $this->doubleClickEnabled = TRUE;
    }
    
    /**
     * Sets the item template for rendering
     *
     * Defines a template that will be used to render each item in the icon view.
     *
     * @param string $template The template content to be used for rendering items.
     */
    public function setItemTemplate($template)
    {
        $this->itemTemplate = $template;
    }
    
    /**
     * Sets the template attribute for rendering
     *
     * Specifies the attribute that will be used as a template for rendering each item.
     *
     * @param string $attribute The name of the attribute containing the template.
     */
    public function setTemplateAttribute($attribute)
    {
        $this->templateAttribute = $attribute;
    }
    
    /**
     * Enables popover functionality
     *
     * Configures a popover that appears when an item is hovered over.
     *
     * @param string      $title       The title of the popover.
     * @param string      $content     The content of the popover.
     * @param string|null $popside     (Optional) The side where the popover should appear.
     * @param callable|null $popcondition (Optional) A condition to determine when the popover should be displayed.
     */
    public function enablePopover($title, $content, $popside = null, $popcondition = null)
    {
        $this->popover = TRUE;
        $this->poptitle = $title;
        $this->popcontent = $content;
        $this->popside = $popside;
        $this->popcondition = $popcondition;
    }
    
    /**
     * Enables the move action functionality
     *
     * Allows items to be dragged and dropped between specified selectors.
     *
     * @param object $moveAction       The action to be triggered when an item is moved.
     * @param string $source_selector  The selector for the source items to be dragged.
     * @param string $target_selector  The selector for the target drop area.
     */
    public function enableMoveAction( $moveAction, $source_selector, $target_selector )
    {
        $this->enableMoving = TRUE;
        $this->moveAction   = $moveAction;
        $this->dragSelector = $source_selector;
        $this->dropSelector = $target_selector;
    }
    
    /**
     * Sets the information fields for items
     *
     * Defines additional attributes that will be stored in the item element for search or filtering purposes.
     *
     * @param array $fields An array of field names that should be included in the item's data attributes.
     */
    public function setInfoAttributes($fields)
    {
        $this->infoFields = $fields;
    }
    
    /**
     * Adds an item to the icon view
     *
     * Inserts an object into the list of displayed items.
     *
     * @param object $object The object representing the item.
     *
     * @return stdClass Returns an object where additional properties can be set for the item.
     */
    public function addItem($object)
    {
        $this->items[] = $object;
        
        $itemProperties = new StdClass;
        $this->itemProperties[] = $itemProperties;
        return $itemProperties;
    }
    
    /**
     * Sets the icon attribute
     *
     * Defines the attribute that will be used to retrieve the icon for each item.
     *
     * @param string $iconField The name of the attribute containing the icon path or identifier.
     */
    public function setIconAttribute( $iconField )
    {
        $this->iconField = $iconField;
    }
    
    /**
     * Sets the label attribute
     *
     * Defines the attribute that will be used as the label for each item.
     *
     * @param string $labelField The name of the attribute containing the label text.
     */
    public function setLabelAttribute( $labelField )
    {
        $this->labelField = $labelField;
    }
    
    /**
     * Adds an option to the context menu
     *
     * Defines an item in the right-click context menu of each icon.
     *
     * @param string        $label            The label of the menu option.
     * @param object|null   $action           (Optional) The action to execute when the option is selected.
     * @param string|null   $icon             (Optional) The icon to display in the menu option.
     * @param callable|null $displayCondition (Optional) A function that determines whether the option should be displayed.
     */
    public function addContextMenuOption($label, $action = null, $icon = null, /*Callable*/ $displayCondition = null)
    {
        $this->options[] = [$label, $action, $icon, $displayCondition];
    }
    
    /**
     * Displays the icon view
     *
     * Renders the items, applying templates, icons, labels, popovers, and context menus.
     */
    public function show()
    {
        if ($this->items)
        {
            foreach ($this->items as $key => $object)
            {
                $iconField      = $this->iconField;
                $labelField     = $this->labelField;
                $itemProperties = $this->itemProperties[$key];
                $first_action = null;
                
                $li = new TElement('li');
                
                // set info data in the root element for things like fuse search
                if ($this->infoFields)
                {
                    foreach ($this->infoFields as $infoField)
                    {
                        $li->$infoField = isset($object->{$infoField}) ? $object->{$infoField} : NULL;
                        $li->{"data-{$infoField}"} = isset($object->{$infoField}) ? $object->{$infoField} : NULL;
                    }
                }
                
                if ($itemProperties)
                {
                    foreach ($itemProperties as $item_prop_name => $item_prop_value)
                    {
                        $li->$item_prop_name = $item_prop_value;
                    }
                }
                
                if (empty($li->{'id'}))
                {
                    $li->{'id'} = 'ticonitem_' . mt_rand(1000000000, 1999999999);
                }
                
                $id = $li->{'id'};
                
                if ($this->popover && (empty($this->popcondition) OR call_user_func($this->popcondition, $object)))
                {
                    $poptitle   = $this->poptitle;
                    $popcontent = $this->popcontent;
                    $poptitle   = AdiantiTemplateHandler::replace($poptitle, $object);
                    $popcontent = AdiantiTemplateHandler::replace($popcontent, $object, null, true);
                    
                    $li->{'popover'} = 'true';
                    $li->{'poptitle'} = $poptitle;
                    $li->{'popcontent'} = htmlspecialchars(str_replace("\n", '', nl2br($popcontent)));
                    
                    if ($this->popside)
                    {
                        $li->{'popside'} = $this->popside;
                    }
                }
                
                if (!empty($object->{$this->templateAttribute}))
                {
                    $item_content = new TElement('div');
                    $item_content->add(AdiantiTemplateHandler::replace($object->{$this->templateAttribute}, $object));
                    $li->add($item_content);
                }
                else if (!empty($this->itemTemplate))
                {
                    $item_content = new TElement('div');
                    $item_content->add(AdiantiTemplateHandler::replace($this->itemTemplate, $object));
                    $li->add($item_content);
                }
                else
                {
                    $item_wrapper = new TElement('div');
                    $item_wrapper->add(new TImage($object->$iconField));
                    $item_wrapper->add(TElement::tag('span', $object->$labelField));
                    
                    $li->add($item_wrapper);
                }
                
                parent::add($li);
                
                if ($this->options)
                {
                    $dropdown = new TElement('ul');
                    $dropdown->{'class'} = 'dropdown-menu pull-left dropdown-iconview';
                    $dropdown->{'style'} = 'position:absolute; display:none;';
                    
                    foreach ($this->options as $index => $option)
                    {
                        $action_label     = $option[0];
                        $action_template  = $option[1];
                        $action_icon      = $option[2];
                        $action_condition = $option[3];
                        
                        if ($action_template)
                        {
                            $item_action = clone $action_template;
                            
                            if ($this->infoFields)
                            {
                                foreach ($this->infoFields as $infoField)
                                {
                                    $info_data = isset($object->{$infoField}) ? $object->{$infoField} : NULL;
                                    $item_action->setParameter($infoField, "{$object->$infoField}");
                                }
                            }
                            $action = $item_action->prepare($object);
                            
                            if (empty($action_condition) OR call_user_func($action_condition, $object))
                            {
                                $option_li = new TElement('li');
                                $option_link = new TElement('a');
                                $option_link->{'onclick'} = "__adianti_load_page('{$action->serialize()}');";
                                $option_link->{'style'} = 'cursor: pointer';
                                
                                if ($action_icon)
                                {
                                    $image = is_object($action_icon) ? clone $action_icon : new TImage($action_icon);
                                    $image->{'style'} .= ';padding: 4px';
                                    $option_link->add($image);
                                }
                                
                                $span = TElement::tag('span', $action_label);
                                $option_link->add($span);
                                $option_li->add($option_link);
                                
                                $dropdown->add($option_li);
                                
                                if (empty($first_action))
                                {
                                    $first_action = $action;
                                }
                            }
                        }
                        else
                        {
                            if ($action_label)
                            {
                                $dropdown->add(TElement::tag('li', $action_label, ['role'=>'presentation', 'class'=>'dropdown-header']));
                            }
                            else
                            {
                                $dropdown->add(TElement::tag('li', '', ['class'=>'divider']));
                            }
                        }
                    }
                    
                    parent::add($dropdown);
                    $li->add(TScript::create("ticonview_contextmenu_start('{$id}')", false));
                    
                    if ($first_action)
                    {
                        if ($this->doubleClickEnabled)
                        {
                            $li->{'ondblclick'} = "__adianti_load_page('{$first_action->serialize()}');";
                        }
                        else
                        {
                            $li->{'href'}      = $first_action->serialize();
                        }
                        $li->{'generator'} = 'adianti';
                    }
                }
            }
        }
        
        if ($this->enableMoving)
        {
            $wrapper_id = $this->{'id'};
            $source_selectors = [];
            $source_not_selectors = [];
            $target_selectors = [];
            
            if ($this->dragSelector)
            {
                foreach ($this->dragSelector as $key => $value)
                {
                    if (is_array($value))
                    {
                        foreach ($value as $val)
                        {
                            if (substr($val,0,1) == '!')
                            {
                                $val = substr($val,1);
                                $source_not_selectors[] = "[{$key}!=\"{$val}\"]";
                            }
                            else
                            {
                                $source_selectors[] = "[{$key}=\"{$val}\"]";
                            }
                        }
                    }
                    else
                    {
                        if (substr($value,0,1) == '!')
                        {
                            $value = substr($value,1);
                            $source_not_selectors[] = "[{$key}!=\"{$value}\"]";
                        }
                        else
                        {
                            $source_selectors[] = "[{$key}=\"{$value}\"]";
                        }
                    }
                }
            }
            
            if ($this->dropSelector)
            {
                foreach ($this->dropSelector as $key => $value)
                {
                    if (is_array($value))
                    {
                        foreach ($value as $val)
                        {
                            $target_selectors[] = "[{$key}=\"{$val}\"]";
                        }
                    }
                    else
                    {
                        $target_selectors[] = "[{$key}=\"{$value}\"]";
                    }
                }
            }
            
            $source_not_selector_string = implode('', $source_not_selectors);
            $source_selector_string = implode( $source_not_selector_string . ',', $source_selectors) . $source_not_selector_string;
            
            $target_selector_string = implode(',', $target_selectors);
            $move_action_string = $this->moveAction->serialize();
            
            parent::add(TScript::create("ticonview_move_start( '{$wrapper_id}', '{$move_action_string}', '{$source_selector_string}', '{$target_selector_string}' )", false));
        }
        
        parent::add(TScript::create('ticonview_bind_click()', false));
        parent::show();
    }
}
