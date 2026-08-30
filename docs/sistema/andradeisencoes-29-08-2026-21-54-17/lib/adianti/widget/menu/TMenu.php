<?php
namespace Adianti\Widget\Menu;

use Adianti\Widget\Menu\TMenuItem;
use Adianti\Widget\Base\TElement;

use SimpleXMLElement;

/**
 * Represents a hierarchical menu widget that can be generated from an XML structure.
 * This class allows adding menu items and supports permission-based filtering.
 *
 * @version    7.5
 * @package    widget
 * @subpackage menu
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMenu extends TElement
{
    private $items;
    private $menu_class;
    private $item_class;
    private $menu_level;
    private $link_class;
    private $item_transformer;
    private $menu_transformer;
    
    /**
     * Initializes a new instance of the TMenu class.
     *
     * @param SimpleXMLElement $xml The XML structure defining the menu.
     * @param callable|null $permission_callback Optional callback function to check permissions for menu items.
     * @param int $menu_level The hierarchical level of the menu (default: 1).
     * @param string $menu_class The CSS class for the menu container.
     * @param string $item_class The CSS class for individual menu items.
     * @param string $link_class The CSS class for menu item links.
     * @param callable|null $item_transformer Optional transformation function for menu items.
     * @param callable|null $menu_transformer Optional transformation function for menus.
     */
    public function __construct($xml, $permission_callback = NULL, $menu_level = 1, $menu_class = 'dropdown-menu', $item_class = '', $link_class = 'dropdown-toggle', $item_transformer = null, $menu_transformer = null)
    {
        parent::__construct('ul');
        $this->items = array();
        
        $this->{'class'}  = $menu_class . " level-{$menu_level}";
        $this->menu_class = $menu_class;
        $this->menu_level = $menu_level;
        $this->item_class = $item_class;
        $this->link_class = $link_class;
        $this->item_transformer = $item_transformer;
        $this->menu_transformer = $menu_transformer;
        
        if ($xml instanceof SimpleXMLElement)
        {
            $this->parse($xml, $permission_callback);
        }
    }
    
    /**
     * Adds a menu item to the menu.
     *
     * @param TMenuItem $menuitem The menu item to be added.
     */
    public function addMenuItem(TMenuItem $menuitem)
    {
        if (!empty($this->item_transformer))
        {
            call_user_func( $this->item_transformer, $menuitem );
        }
        $this->items[] = $menuitem;
    }
    
    /**
     * Retrieves the menu items stored in this menu.
     *
     * @return TMenuItem[] An array of TMenuItem objects.
     */
    public function getMenuItems()
    {
        return $this->items;
    }
    
    /**
     * Parses an XML structure to populate the menu with items.
     *
     * @param SimpleXMLElement $xml The XML structure defining the menu.
     * @param callable|null $permission_callback Optional callback function to check permissions for menu items.
     */
    public function parse($xml, $permission_callback = NULL)
    {
        $i = 0;
        foreach ($xml as $xmlElement)
        {
            $atts     = $xmlElement-> attributes ();
            $label    = (string) $atts['label'];
            $action   = (string) $xmlElement-> action;
            $icon     = (string) $xmlElement-> icon;
            $menu     = NULL;
            $menuItem = new TMenuItem($label, $action, $icon, $this->menu_level, $this->menu_transformer);
            $menuItem->setLinkClass($this->link_class);
            
            if ($xmlElement-> menu)
            {
                $menu_atts = $xmlElement-> menu-> attributes ();
                $menu_class = !empty( $menu_atts['class'] ) ? $menu_atts['class']: $this->menu_class;
                $menu = new TMenu($xmlElement-> menu-> menuitem, $permission_callback, $this->menu_level +1, $menu_class, $this->item_class, $this->link_class, $this->item_transformer, $this->menu_transformer);

                foreach (parent::getProperties() as $property => $value)
                {
                    $menu->setProperty($property, $value);
                }

                $menuItem->setMenu($menu);
                if ($this->item_class)
                {
                    $menuItem->{'class'} = $this->item_class;
                }
            }
            
            // just child nodes have actions
            if ( $action )
            {
                if ( !empty($action) AND $permission_callback AND (substr($action,0,7) !== 'http://') AND (substr($action,0,8) !== 'https://'))
                {
                    // check permission
                    $parts = explode('#', $action);
                    $className = $parts[0];
                    if (call_user_func($permission_callback, $className))
                    {
                        $this->addMenuItem($menuItem);
                    }
                }
                else
                {
                    // menus without permission check
                    $this->addMenuItem($menuItem);
                }
            }
            // parent nodes are shown just when they have valid children (with permission)
            else if ( isset($menu) AND count($menu->getMenuItems()) > 0)
            {
                $this->addMenuItem($menuItem);
            }
            
            $i ++;
        }
    }
    
    /**
     * Renders the menu and its items.
     */
    public function show()
    {
        if ($this->items)
        {
            foreach ($this->items as $item)
            {
                parent::add($item);
            }
        }
        parent::show();
    }
}
