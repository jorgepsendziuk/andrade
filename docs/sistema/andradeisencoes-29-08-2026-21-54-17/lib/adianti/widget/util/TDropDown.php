<?php
namespace Adianti\Widget\Util;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Util\TImage;

/**
 * TDropDown Widget
 *
 * This class represents a dropdown menu component.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDropDown extends TElement
{
    protected $elements;
    private $button;
    
    /**
     * Class Constructor
     *
     * Initializes a dropdown component with a button.
     *
     * @param string      $label     The dropdown button label.
     * @param string|null $icon      The dropdown button icon (optional).
     * @param bool        $use_caret Whether to display a caret icon (optional).
     * @param string      $title     The tooltip title for the button (optional).
     * @param int|null    $height    The height of the dropdown menu (optional).
     */
    public function __construct($label, $icon = NULL, $use_caret = FALSE, $title = '', $height = null)
    {
        parent::__construct('div');
        $this->{'class'} = 'btn-group';
        $this->{'style'} = 'display:inline-block; -moz-user-select: none; -webkit-user-select:none; user-select:none;position:static;';
        
        $button = new TElement('button');
        $button->{'data-toggle'} = 'dropdown';
        $button->{'class'}       = 'btn btn-default btn-sm dropdown-toggle';
        $button->{'boundary'} = 'viewport';
        $this->button = $button;
        
        if ($icon)
        {
            $button->add(new TImage($icon));
        }
        
        if ($title)
        {
            $button->{'title'} = $title;
        }
        $button->add($label);
        
        if ($use_caret)
        {
            $span = new TElement('span');
            $span->{'class'} = 'fa fa-chevron-down';
            $span->{'style'} = 'margin-left: 3px';
            $button->add($span);
        }
        
        parent::add($button);
        
        //$this->id = 'tdropdown_' . mt_rand(1000000000, 1999999999);
        $this->elements = new TElement('ul');
        $this->elements->{'class'} = 'dropdown-menu pull-left';
        $this->elements->{'aria-labelledby'} = 'drop2';
        $this->elements->{'widget'} = 'tdropdown';
        
        if (!empty($height))
        {
            $this->elements->{'style'} = "height:{$height}px;overflow:auto";
        }
        parent::add($this->elements);
    }
    
    /**
     * Define the dropdown pull side.
     *
     * @param string $side The pull direction (left or right).
     */
    public function setPullSide($side)
    {
        $this->elements->{'class'} = "dropdown-menu pull-{$side} dropdown-menu-{$side}";
    }

    /**
     * Set the button size.
     *
     * @param string $size The button size (e.g., 'sm' for small, 'lg' for large).
     */
    public function setButtonSize($size)
    {
        $this->button->{'class'} = "btn btn-default btn-{$size} dropdown-toggle";
    }
    
    /**
     * Set the button CSS class.
     *
     * @param string $class The CSS class to be applied to the button.
     */
    public function setButtonClass($class)
    {
        $this->button->{'class'} = $class;
    }
    
    /**
     * Get the dropdown button element.
     *
     * @return TElement The button element.
     */
    public function getButton()
    {
        return $this->button;
    }
    
    /**
     * Add an action item to the dropdown.
     *
     * @param string       $title   The action title.
     * @param TAction|string $action The action (TAction instance or JavaScript string).
     * @param string|null  $icon    The action icon (optional).
     * @param string       $popover Tooltip or popover text (optional).
     * @param bool         $add     Whether to add the item to the dropdown (optional, default: true).
     *
     * @return TElement|null The created list item element or null if the action is hidden.
     */
    public function addAction($title, $action, $icon = NULL, $popover = '', $add = true)
    {
        $li = new TElement('li');
        // $li->{'class'} = "dropdown-item";
        $link = new TElement('a');
        
        if ($action instanceof TAction)
        { 
            if($action->isHidden())
            {
                return;
            }

            $link->{'href'} = $action->serialize();
            $link->{'generator'} = "adianti";

            if($action->isDisabled())
            {
                unset($link->generator);
                $link->disabled = 'disabled';
            }
        }
        else if (is_string($action))
        {
            $link->{'onclick'} = $action;
        }
        $link->{'style'} = 'cursor: pointer';
        
        if ($popover)
        {
            $link->{'title'} = $popover;
        }
        
        if ($icon)
        {
            $image = is_object($icon) ? clone $icon : new TImage($icon);
            $image->{'style'} .= ';padding: 4px';
            $link->add($image);
        }
        
        $span = new TElement('span');
        $span->add($title);
        $link->add($span);
        $li->add($link);
        
        if ($add)
        {
            $this->elements->add($li);
        }
        return $li;
    }
    
    /**
     * Add a POST action to the dropdown.
     *
     * This method creates an action that submits a form via AJAX.
     *
     * @param string       $title   The action title.
     * @param TAction|string $action The action (TAction instance or JavaScript string).
     * @param string       $form    The form name to be submitted.
     * @param string|null  $icon    The action icon (optional).
     * @param string       $popover Tooltip or popover text (optional).
     * @param bool         $add     Whether to add the item to the dropdown (optional, default: true).
     *
     * @return TElement|null The created list item element or null if the action is hidden.
     */
    public function addPostAction($title, $action, $form, $icon = NULL, $popover = '', $add = true)
    {
        $li = new TElement('li');
        
        $link = new TElement('a');
        
        if ($action instanceof TAction)
        { 
            if($action->isHidden())
            {
                return;
            }

            $url = $action->serialize(FALSE);
            
            if ($action->isStatic())
            {
                $url .= '&static=1';
            }
            $url = htmlspecialchars($url);
            $wait_message = AdiantiCoreTranslator::translate('Loading');
            
            // define the button's action (ajax post)
            $clickAction = "Adianti.waitMessage = '$wait_message';";
            $clickAction.= "{$this->functions}";
            $clickAction.= "__adianti_post_data('{$form}', '{$url}');";
            $clickAction.= "return false;";

            if($action->isDisabled())
            {
                $link->disabled = 'disabled';
                $clickAction = '';
            }
            
            $link->{'onclick'} = $clickAction;
        }
        else if (is_string($action))
        {
            $link->{'onclick'} = $action;
        }
        $link->{'style'} = 'cursor: pointer';
        
        if ($popover)
        {
            $link->{'title'} = $popover;
        }
        
        if ($icon)
        {
            $image = is_object($icon) ? clone $icon : new TImage($icon);
            $image->{'style'} .= ';padding: 4px';
            $link->add($image);
        }
        
        $span = new TElement('span');
        $span->add($title);
        $link->add($span);
        $li->add($link);
        
        if ($add)
        {
            $this->elements->add($li);
        }
        return $li;
    }
    
    /**
     * Add an action group to the dropdown.
     *
     * @param string $title   The title of the action group.
     * @param array  $actions The list of actions (each action should be an array with [title, action, icon]).
     * @param string $icon    The icon for the action group.
     */
    public function addActionGroup($title, $actions, $icon)
    {
        $li = new TElement('li');
        $li->{'class'} = "dropdown-submenu";
        $link = new TElement('a');
        $span = new TElement('span');
        
        if ($icon)
        {
            $image = is_object($icon) ? clone $icon : new TImage($icon);
            $image->{'style'} .= ';padding: 4px';
            $link->add($image);
        }
        
        $span->add($title);
        $link->add($span);
        $li->add($link);
        
        $ul = new TElement('ul');
        $ul->{'class'} = "dropdown-menu";
        $li->add($ul);
        if ($actions)
        {
            foreach ($actions as $action)
            {
                $ul->add( $this->addAction( $action[0], $action[1], $action[2], '', false ) );
            }
        }
        
        $this->elements->add($li);
    }
    
    /**
     * Add a header item to the dropdown.
     *
     * @param string $header The header text.
     */
    public function addHeader($header)
    {
        $li = new TElement('li');
        $li->{'class'} = 'dropdown-header';
        $li->add($header);
        $this->elements->add($li);
    }
    
    /**
     * Add a separator to the dropdown.
     */
    public function addSeparator()
    {
        $li = new TElement('li');
        $li->{'class'} = 'dropdown-divider';
        $this->elements->add($li);
    }
    
    /**
     * Clear all items from the dropdown.
     */
    public function clearItems()
    {
        $this->elements->clearChildren();
    }
}
