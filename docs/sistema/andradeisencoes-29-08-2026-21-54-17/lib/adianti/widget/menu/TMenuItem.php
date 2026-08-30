<?php
namespace Adianti\Widget\Menu;

use Adianti\Core\AdiantiCoreApplication;
use Adianti\Widget\Menu\TMenu;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Util\TImage;

/**
 * Represents an item in a menu.
 * 
 * This class defines a menu item with a label, action, optional image, and other properties 
 * like submenu and styling. It extends TElement to generate the appropriate HTML elements 
 * for rendering the menu item.
 *
 * @version    7.5
 * @package    widget
 * @subpackage menu
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMenuItem extends TElement
{
    private $label;
    private $action;
    private $image;
    private $menu;
    private $level;
    private $link;
    private $linkClass;
    private $classLink;
    private $menu_transformer;
    private $tagLabel;
    private $classIcon;
    
    /**
     * Initializes a menu item with label, action, and optional image.
     *
     * @param string      $label            The text displayed on the menu item.
     * @param string|null $action           The action associated with the menu item (e.g., URL or route).
     * @param string|null $image            The image path for the menu item (optional).
     * @param int         $level            The menu level (default is 0).
     * @param callable|null $menu_transformer A callback function to transform the menu item (optional).
     */
    public function __construct($label, $action, $image = NULL, $level = 0, $menu_transformer = null)
    {
        parent::__construct('li');
        $this->label     = $label;
        $this->action    = $action;
        $this->level     = $level;
        $this->link      = new TElement('a');
        $this->linkClass = 'dropdown-toggle';

        $this->menu_transformer = $menu_transformer;

        if ($image)
        {
            $this->image = $image;
        }
    }
    
    /**
     * Retrieves the action associated with the menu item.
     *
     * @return string|null The action (URL or route) of the menu item.
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Sets the action for the menu item.
     *
     * @param string|null $action The new action (URL or route).
     */
    public function setAction($action)
    {
        $this->action = $action;
    }
    
    /**
     * Retrieves the label of the menu item.
     *
     * @return string The label text.
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Sets the label for the menu item.
     *
     * @param string $label The new label text.
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    /**
     * Retrieves the image path of the menu item.
     *
     * @return string|null The image path, or null if not set.
     */
    public function getImage()
    {
        return $this->image;
    }
    
    /**
     * Sets the image for the menu item.
     *
     * @param string|null $image The image path.
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
    
    /**
     * Retrieves the submenu associated with the menu item.
     *
     * @return TMenu|null The submenu object, or null if no submenu is set.
     */
    public function getMenu()
    {
        return $this->menu;
    }
    
    /**
     * Retrieves the menu level of the item.
     *
     * @return int The level of the menu item.
     */
    public function getLevel()
    {
        return $this->level;
    }
    
    /**
     * Retrieves the link element of the menu item.
     *
     * @return TElement The link element.
     */
    public function getLink()
    {
        return $this->link;
    }
    
    /**
     * Sets the CSS class for the menu item's link.
     *
     * @param string $class The CSS class name.
     */
    public function setLinkClass($class)
    {
        $this->linkClass = $class;
    }
    
    /**
     * Assigns a submenu to the menu item.
     *
     * This method also sets the CSS class for a dropdown submenu.
     *
     * @param TMenu $menu The submenu object.
     */
    public function setMenu(TMenu $menu)
    {
        $this->{'class'} = 'dropdown-submenu';
        $this->menu = $menu;
    }

    /**
     * Sets a custom CSS class for the menu item link.
     *
     * @param string $class The CSS class name.
     */
    public function setClassLink($class)
    {
        $this->classLink = $class;
    }

    /**
     * Sets a custom CSS class for the menu item icon.
     *
     * @param string $class The CSS class name.
     */
    public function setClassIcon($class)
    {
        $this->classIcon = $class;
    }

    /**
     * Sets the HTML tag to be used for the menu item label.
     *
     * @param string $tag The HTML tag name (e.g., 'span', 'div').
     */
    public function setTagLabel($tag)
    {
        $this->tagLabel = $tag;
    }
    
    /**
     * Renders the menu item as an HTML element.
     *
     * This method generates the necessary HTML structure for the menu item,
     * including the action URL, image, submenu, and styling.
     */
    public function show()
    {
        if ($this->action)
        {
            $action = str_replace('#', '&', $this->action);

            // Controll if menu.xml contains a short url e.g. \home  -> back slash is the char controll
            if (substr($action,0,1) == '\\')
            {
                $this->link->{'href'} = substr($action, 1);
                $this->link->{'generator'} = 'adianti';
            }
            elseif ((substr($action,0,7) == 'http://') or (substr($action,0,8) == 'https://'))
            {
                $this->link->{'href'} = $action;
                $this->link->{'target'} = '_blank';
            }
            else
            {
                if ($router = AdiantiCoreApplication::getRouter())
                {
                    $this->link->{'href'} = $router("class={$action}", true);
                }
                else
                {
                    $this->link->{'href'} = "index.php?class={$action}";
                }
                $this->link->{'generator'} = 'adianti';
            }
        }
        else
        {
            $this->link->{'href'} = '#';
        }
        
        if (isset($this->image))
        {
            $image = new TImage($this->image);

            if ($this->classIcon)
            {
                $image->{'class'} .= " {$this->classIcon}";
            }

            $this->link->add($image);
        }
        
        $label = new TElement($this->tagLabel ?? 'span');
        if (substr($this->label, 0, 3) == '_t{')
        {
            $label->add(_t(substr($this->label,3,-1)));
        }
        else
        {
            $label->add($this->label);
        }
        
        if (!empty($this->label))
        {
            $this->link->add($label);
            $this->add($this->link);
        }
        
        if ($this->classLink)
        {
            $this->link->{'class'} = $this->classLink;
        }

        if ($this->menu instanceof TMenu)
        {
            $this->link->{'class'} = $this->linkClass;
            if (strstr($this->linkClass, 'dropdown'))
            {
                $this->link->{'data-toggle'} = "dropdown";
            }
            
            if ($this->level == 0)
            {
                $caret = new TElement('b');
                $caret->{'class'} = 'caret';
                $caret->add('');
                $this->link->add($caret);
            }

            if (!empty($this->menu_transformer))
            {
                $this->link = call_user_func($this->menu_transformer, $this->link);
            }

            parent::add($this->menu);
        }
        
        parent::show();
    }
}
