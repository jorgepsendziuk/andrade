<?php
namespace Adianti\Widget\Menu;

use Adianti\Widget\Menu\TMenu;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Util\AdiantiStringConversion;

use SimpleXMLElement;

/**
 * Represents a navigation menu bar.
 * The menu bar can be built dynamically from an XML file and allows for hierarchical menus.
 *
 * @version    7.5
 * @package    widget
 * @subpackage menu
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMenuBar extends TElement
{
    /**
     * Initializes a new instance of the TMenuBar class.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->{'style'} = 'margin: 0;';
        $this->{'class'} = 'navbar';
    }
    
    /**
     * Creates a new TMenuBar instance from an XML file.
     *
     * @param string $xml_file Path to the XML file defining the menu structure.
     * @param callable|null $permission_callback Optional callback function to check permissions for menu items.
     * @param string $bar_class The CSS class for the navigation bar container.
     * @param string $menu_class The CSS class for submenus.
     * @param string $item_class The CSS class for individual menu items.
     *
     * @return TMenuBar|null Returns a TMenuBar instance if the file is valid, otherwise null.
     */
    public static function newFromXML($xml_file, $permission_callback = NULL, $bar_class = 'nav navbar-nav', $menu_class = 'dropdown-menu', $item_class = '')
    {
        if (file_exists($xml_file))
        {
            $menu_string = AdiantiStringConversion::assureUnicode(file_get_contents($xml_file));
            $xml = new SimpleXMLElement($menu_string);
            
            $menubar = new TMenuBar;
            $ul = new TElement('ul');
            $ul->{'class'} = $bar_class;
            $menubar->add($ul);
            foreach ($xml as $xmlElement)
            {
                $atts   = $xmlElement->attributes();
                $label  = (string) $atts['label'];
                $action = (string) $xmlElement-> action;
                $icon   = (string) $xmlElement-> icon;
                
                $item = new TMenuItem($label, $action, $icon);
                $menu = new TMenu($xmlElement-> menu-> menuitem, $permission_callback, 1, $menu_class, $item_class);

                // check children count (permissions)
                if (count($menu->getMenuItems()) >0)
                {
                    $item->setMenu($menu);
                    $item->{'class'} = 'active';
                    $ul->add($item);
                }
                else if ($action)
                {
                    $ul->add($item);
                }
            }
            
            return $menubar;
        }
    }
    
    /**
     * Renders the menu bar.
     */
    public function show()
    {
        TScript::create( 'tmenubar_start();' );
        parent::show();
    }
}
