<?php

namespace Mad\Widget\Form;

use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Util\TImage;

/**
 * @version    4.0
 * @package    widget
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

class BTreeView extends TField implements AdiantiWidgetInterface
{
    private $items;
    private $item_action;
    private $group_action;
    private $group_transformer;
    private $item_transformer;
    private $check;
    private $expand;
    private $height;
    private $width;
    private $startOpened;
    private $container;
    private $iconOpened;
    private $iconClosed;
    
    /**
     * Class BTreeView
     *
     * This class represents a hierarchical tree view component for forms.
     * It extends TField and implements AdiantiWidgetInterface.
     *
     * @package Mad\Widget\Form
     */
    public function __construct($name)
    {
        parent::__construct($name);
        
        $this->id = 'btreeview_' . mt_rand(1000000000, 1999999999);
        
        $this->tag->setName('div');
        $this->tag->id = $this->id;
        $this->tag->class = 'btreeview';
        $this->tag->btreeview = $name;
        $this->tag->widget = 'btreeview';
     
        $this->width = '100%';
        $this->height = '100%';
        $this->startOpened = true;
        $this->expand = true;
        $this->check = false;
        $this->container = false;
        
        $this->items = [];
        $this->group_action = [];
        $this->item_action = [];
        
        $this->iconOpened = new TImage('fa:minus');
        $this->iconClosed = new TImage('fa:plus');
    }
    
    /**
     * Sets the icons for opened and closed states.
     *
     * @param TImage $opened The icon for an opened group.
     * @param TImage $closed The icon for a closed group.
     */
    public function setIcons(TImage $opened, TImage $closed)
    {
        $this->iconOpened = $opened;
        $this->iconClosed = $closed;
    }
    
    /**
     * Sets the name of the tree view.
     *
     * @param string $name The name of the tree view.
     */
    public function setName($name)
    {
        $this->name = $name;    
    }
    
    /**
     * Gets the name of the tree view.
     *
     * @return string The name of the tree view.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the value of the tree view.
     *
     * @param mixed $value The value to set.
     */
    public function setValue($value)
    {
        $this->value = $value;    
    }
    
    /**
     * Gets the value of the tree view.
     *
     * @return mixed The value of the tree view.
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Enables the checkbox functionality for items.
     */
    public function enableCheck()
    {
        $this->check = true;
    }
    
    /**
     * Disables the checkbox functionality for items.
     */
    public function disableCheck()
    {
        $this->check = false;
    }
    
    /**
     * Checks if the checkbox functionality is enabled.
     *
     * @return bool True if enabled, false otherwise.
     */
    public function isCheck()
    {
        return $this->check;
    }
    
    /**
     * Enables the expander functionality for groups.
     */
    public function enableExpander()
    {
        $this->expand = true;
    }
    
    /**
     * Disables the expander functionality for groups.
     */
    public function disableExpander()
    {
        $this->expand = false;
    }
    
    /**
     * Checks if the expander functionality is enabled.
     *
     * @return bool True if enabled, false otherwise.
     */
    public function isExpand()
    {
        return $this->expand;
    }
    
    /**
     * Enables the container mode.
     */
    public function enableContainer()
    {
        $this->container = true;
    }
    
    /**
     * Disables the container mode.
     */
    public function disableContainer()
    {
        $this->container = false;
    }
    
    /**
     * Checks if the container mode is enabled.
     *
     * @return bool True if enabled, false otherwise.
     */
    public function isContainer()
    {
        return $this->container;
    }
    
    /**
     * Defines whether the tree should start opened.
     *
     * @param bool $open True to start opened, false otherwise.
     */
    public function setStartOpened($open = true)
    {
        $this->startOpened = $open;
    }
    
    /**
     * Sets the size of the tree view.
     *
     * @param string|int $width The width of the tree view.
     * @param string|int $height The height of the tree view (default is '100%').
     */
    public function setSize($width, $height = '100%')
    {
        $width = (strstr($width, '%') !== FALSE) ? $width : "{$width}px";
        $height = (strstr($height, '%') !== FALSE) ? $height : "{$height}px";

        $this->width = $width;
        $this->height = $height;        
    }
    
    /**
     * Sets a transformation function for group labels.
     *
     * @param callable $callable A function to transform the group label.
     */
    public function setGroupTransformer($callable)
    {
        $this->group_transformer = $callable;
    }
    
    /**
     * Sets a transformation function for item labels.
     *
     * @param callable $callable A function to transform the item label.
     */
    public function setItemTransformer($callable)
    {
        $this->item_transformer = $callable;
    }
    
    /**
     * Gets the size of the tree view.
     *
     * @return array An array containing width and height.
     */
    public function getSize()
    {
        return [$this->width, $this->height];
    }
    
    /**
     * Adds an action to a group.
     *
     * @param TAction $action The action to add.
     * @param string $label The label of the action.
     * @param string $icon The icon associated with the action.
     *
     * @return TAction The added action.
     */
    public function addGroupAction(TAction $action, $label, $icon)
    {
        $this->group_action[] = [$action, $label, $icon];
        return $action;
    }
    
    /**
     * Adds an action to an item.
     *
     * @param TAction $action The action to add.
     * @param string $label The label of the action.
     * @param string $icon The icon associated with the action.
     *
     * @return TAction The added action.
     */
    public function addItemAction(TAction $action, $label, $icon)
    {
        $this->item_action[] = [$action, $label, $icon];
        return $action;
    }
    
    /**
     * Gets the actions assigned to items.
     *
     * @return array An array of item actions.
     */
    public function getItemAction()
    {
        return $this->item_action;
    }
    
    /**
     * Gets the actions assigned to groups.
     *
     * @return array An array of group actions.
     */
    public function getGroupAction()
    {
        return $this->group_action;
    }
    
    /**
     * Clears all item actions.
     */
    public function clearItemAction()
    {
        $this->item_action = [];
    }
    
    /**
     * Clears all group actions.
     */
    public function clearGroupAction()
    {
        $this->group_action = [];
    }
    
    /**
     * Sets the items of the tree view.
     *
     * @param array $items The array of items.
     */
    public function setItems($items)
    {
        $this->items = $items;
    }
    
    /**
     * Gets the items of the tree view.
     *
     * @return array The array of items.
     */
    public function getItems()
    {
        return $this->items;
    }
    
    /**
     * Creates action buttons for a given item or group.
     *
     * @param array $actions The actions to create buttons for.
     * @param string $name The name of the item/group.
     * @param array $items The related items.
     * @param mixed $object An optional object for parameter preparation.
     *
     * @return array An array of TButton instances.
     */
    private function makeActions($actions, $name, $items, $object)
    {
        if (empty($actions))
        {
            return [];
        }
        
        $buttons = [];
        
        foreach($actions as $actionParameter)
        {
            list($actionDefault, $label, $image) = $actionParameter;
            
            $action = $object ? $actionDefault->prepare($object) : clone $actionDefault;
            $action->setParameter('key', $name);
            $action->setParameter('items', base64_encode(json_encode($items)));
            
            $btn = new TButton('button_btreeview_' . mt_rand(1000000000, 1999999999));
            $btn->setAction($action);
            $btn->setImage($image);
            $btn->setLabel('');
            $btn->title = $label;
            $btn->class = '';
            
            $btn->setFormName($this->getFormName());
            
            $buttons[] = $btn;
        }
        
        return $buttons;
    }
    
    /**
     * Creates the title element for a group.
     *
     * @param string $key The unique key of the group.
     * @param string $name The name of the group.
     * @param array $items The related items.
     * @param mixed|null $object An optional object related to the group.
     *
     * @return TElement The generated title element.
     */
    private function makeTitle($key, $name, $items, $object = null)
    {
        $this->iconOpened->class .= ' btreeview-icon-opened';
        $this->iconClosed->class .= ' btreeview-icon-closed';
        
        $actionsGroup = $this->makeActions($this->group_action, $key, $items, $object);
        
        $check = '';

        if ($this->check)
        {
            $check = new TElement('input');
            $check->type = 'checkbox';
            $check->value = $key;
            $check->class = 'btreeview-checkbox-group';
            $check->onclick = 'btreeview_toggle_all(event, this)';
        }
        
        $name = is_array($name) ? $name[0] : $name;
        
        $div = new TElement('div');
        $div->class = 'btreeview-group-title';
        
        if ($this->group_transformer)
        {
            $name = call_user_func($this->group_transformer, $name, $key, $items, $div);
        }
        
        if ($this->expand)
        {
            $div->add(TElement::tag('div', [$this->iconOpened, $this->iconClosed], ['class' => 'btreeview-group-icon']));
        }
        
        $div->add(TElement::tag('div', [$check, $name], ['class' => 'btreeview-group-name']));
        $div->add(TElement::tag('div', $actionsGroup, ['class' => 'btreeview-group-actions']));
        
        return $div;
    }
    
    /**
     * Creates a group element.
     *
     * @param string $key The unique key of the group.
     * @param string $name The name of the group.
     * @param array $items The related items.
     * @param int $index The hierarchical index level.
     * @param mixed|null $object An optional object related to the group.
     *
     * @return TElement The generated group element.
     */
    private function makeGroup($key, $name, $items, $index = 1, $object = null)
    {
        $group = new TElement('div');
        $group->class = 'btreeview-group';
        $group->id = $key;

        if ($this->startOpened)
        {
            $group->class .= ' btreeview-open';
        }
        
        $group_name = $this->makeTitle($key, $name, $items, $object);
        $group_items = TElement::tag('div', '', ['data-index' => $index, 'class' => 'btreeview-group-items']);
        
        $group->add($group_name);
        $group->add($group_items);
        
        foreach($items as $key => $item)
        {
            $key = str_replace('btreekey_', '', (string) $key);
            
            if (!empty($item['items']) && is_array($item['items']))
            {
                $group_items->add($this->makeGroup($key, $item['label'], $item['items'], ++$index, $item['object'] ?? null));
            }
            else
            {
                $group_items->last = 'true';
                $div = new TElement('div');
                $div->class = 'btreeview-item';
                
                if ($this->item_transformer)
                {
                    $item = call_user_func($this->item_transformer, $item, $key, $div);
                }
                
                $div->class = 'btreeview-item';
                
                $labelItem = is_array($item) ? $item[0] : $item;
                $actionsItems = is_array($item) ? $this->makeActions($this->item_action, $key, $item[0], $item[1]) : $this->makeActions($this->item_action, $key, $item, null);
                
                if ($this->check)
                {
                    $check = new TElement('input');
                    $check->type = 'checkbox';
                    $check->class = 'btreeview-checkbox-item';
                    $check->name = $this->name . '[]';
                    $check->value = $key;

                    if (is_array($this->value) && in_array($key, $this->value))
                    {
                        $check->checked = true;    
                    }
                    
                    $label = TElement::tag('label', [$check, TElement::tag('span', $labelItem)], ['class' => 'btreeview-item-title']);
                    $div->add($label);
                }
                else
                {
                    $div->add(TElement::tag('div', $labelItem, ['class' => 'btreeview-item-title']));
                }
                
                $div->add(TElement::tag('div', $actionsItems, ['class' => 'btreeview-item-actions']));
        
                $group_items->add($div);
            }
        }
        
        return $group; 
    }
    
    /**
     * Displays the tree view component.
     */
    public function show()
    {
        if ($this->items)
        {
            foreach ($this->items as $key => $items)
            {
                $key = str_replace('btreekey_', '', (string) $key);

                if(is_array($items) && !empty($items['label']))
                {
                    $this->tag->add($this->makeGroup($key, $items['label'], $items['items'] ?? [], 1, $items['object'] ?? NULL));
                }
                else
                {
                    $this->tag->add($this->makeGroup($key, $items, [], 1, $items['object'] ?? NULL));
                }
            } 
        }
        
        if ($this->container)
        {
            $this->tag->class .= ' btreeview-container';
        }
        
        $this->tag->show();
        
        $expand = $this->expand ? 'true' : 'false';
        
        TScript::create("btreeview_start('{$this->id}', {$expand})");
    }
    
    /**
     * Reloads the tree view with new data.
     *
     * @param string $formname The name of the form.
     * @param string $name The name of the tree view.
     * @param array $items The items to load.
     * @param array $options Additional options such as expand, check, and size.
     */
    public static function reload($formname, $name, $items, $options = [])
    {
        $field = new self($name);
        $field->setFormName($formname);
        
        if (isset($options['expand']))
        {
            $options['expand'] ? $field->enableExpander() : $field->disableExpander();
        }

        if (isset($options['check']))
        {
            $options['check'] ? $field->enableCheck() : $field->disableCheck();
        }

        if (isset($options['container']))
        {
            $options['container'] ? $field->enableContainer() : $field->disableContainer();
        }

        if (! empty($options['value']))
        {
            $field->setValue($options['value']);
        }
        
        if (! empty($options['item_transformer']))
        {
            $field->setItemTransformer($options['item_transformer']);
        }
        
        if (! empty($options['group_transformer']))
        {
            $field->setGroupTransformer($options['group_transformer']);
        }
        
        if (! empty($options['startOpened']))
        {
            $field->setStartOpened();
        }
        
        if (! empty($options['size']))
        {
            $field->setSize($options['size'][0], $options['size'][1]??null);
        }
        
        if (! empty($options['group_actions']))
        {
            foreach($options['group_actions'] as $action)
            {
                $field->addGroupAction($action[0], $action[1]??'', $action[2]??'');
            }
        }
        
        if (! empty($options['item_actions']))
        {
            foreach($options['item_actions'] as $action)
            {
                $field->addItemAction($action[0], $action[1]??'', $action[2]??'');
            }
        }
        
        $field->setItems($items);
        
        $content = base64_encode($field->getContents());
        
        TScript::create( " btreeview_reload('{$formname}', '{$name}', `{$content}`); " );
    }
    
    /**
     * Sorts the tree view items recursively.
     *
     * @param array &$array The array of items to be sorted.
     */
    protected static function sort(&$array)
    {
        foreach ($array as &$value)
        {
            if (!empty($value['items']))
            {
                self::sort($value['items']);
            }
        }
        
        uasort($array, function($a,$b){
            if (empty($a['label'])) {
                return $a[0] <=> $b[0];
            }
            return $a['label'] <=> $b['label'];
        });
    }
}
