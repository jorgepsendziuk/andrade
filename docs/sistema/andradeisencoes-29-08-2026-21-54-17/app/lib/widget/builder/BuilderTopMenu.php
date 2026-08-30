<?php

/**
 * Class BuilderTopMenu
 *
 * This class represents a hierarchical top menu that can be built from an XML structure.
 * It allows defining menu items, submenus, and applying permissions and transformations.
 */
class BuilderTopMenu extends TElement
{
    private $items;
    private $ini;
    private $menu_class;
    private $item_class;
    private $menu_level;
    private $link_class;
    private $item_transformer;
    
    /**
     * BuilderTopMenu constructor.
     *
     * Initializes the top menu from an XML structure and applies optional permission checks and transformations.
     *
     * @param SimpleXMLElement $xml The parsed XML object containing menu definitions.
     * @param callable|null $permission_callback A callback function to check permissions for menu items.
     * @param int $menu_level The hierarchical level of the menu (default: 1).
     * @param string $menu_class The CSS class applied to the menu container (default: 'dropdown-menu').
     * @param string $item_class The CSS class applied to menu items (default: '').
     * @param string $link_class The CSS class applied to menu links (default: 'dropdown-toggle').
     * @param callable|null $item_transformer A callback function to transform menu items before adding them.
     */
    public function __construct($xml, $permission_callback = NULL, $menu_level = 1, $menu_class = 'dropdown-menu', $item_class = '', $link_class = 'dropdown-toggle', $item_transformer = null)
    {
        parent::__construct('ul');
        $this->items = array();
        
        $this->ini = parse_ini_file('app/config/application.ini', true);
        
        $this->{'class'}  = $menu_class . " level-{$menu_level}";
        $this->menu_class = $menu_class;
        $this->menu_level = $menu_level;
        $this->item_class = $item_class;
        $this->link_class = $link_class;
        $this->item_transformer = $item_transformer;
        
        if ($xml instanceof SimpleXMLElement)
        {
            $this->parse($xml, $permission_callback);
        }
    }
    
    /**
     * Adds a menu item to the menu.
     *
     * If an item transformer is set, it is applied to the menu item before adding it.
     *
     * @param BuilderTopMenuItem $menuitem The menu item to be added.
     */
    public function addMenuItem(BuilderTopMenuItem $menuitem)
    {
        if (!empty($this->item_transformer))
        {
            call_user_func( $this->item_transformer, $menuitem );
        }
        $this->items[] = $menuitem;
    }
    
    /**
     * Retrieves the menu items.
     *
     * @return array The list of menu items.
     */
    public function getMenuItems()
    {
        return $this->items;
    }
    
    /**
     * Parses an XML structure to create the menu hierarchy.
     *
     * Reads menu entries from an XML object and applies permission checks if provided.
     *
     * @param SimpleXMLElement $xml The XML object representing the menu structure.
     * @param callable|null $permission_callback A callback function to check permissions for menu items.
     */
    public function parse($xml, $permission_callback = NULL)
    {
        $i = 0;
        foreach ($xml as $xmlElement)
        {
            $atts     = $xmlElement->attributes();
            $label    = (string) $atts['label'];
            $action   = (string) $xmlElement-> action;
            $icon     = (string) $xmlElement-> icon;
            $menu     = NULL;

            if ($action && (! empty($this->ini['general']['use_tabs']) || ! empty($this->ini['general']['use_mdi_windows'])))
            {
                $action .= "#adianti_open_tab=1#adianti_tab_name={$label}";
            }

            $menuItem = new BuilderTopMenuItem($label, $action, $icon, $this->menu_level);
            $menuItem->setLinkClass($this->link_class);
            
            if ($xmlElement->menu)
            {
                $menu_atts = $xmlElement->menu->attributes();
                $menu_class = !empty( $menu_atts['class'] ) ? $menu_atts['class']: $this->menu_class;
                $menu = new BuilderTopMenu($xmlElement-> menu-> menuitem, $permission_callback, $this->menu_level +1, $menu_class, $this->item_class, $this->link_class, $this->item_transformer);

                foreach (parent::getProperties() as $property => $value)
                {
                    $menu->setProperty($property, $value);
                }

                $menuItem->setMenu($menu);
                if ($this->item_class && $this->menu_level <= 1)
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
     * Displays the menu on the screen.
     *
     * Renders all menu items and their submenus before displaying the menu.
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
