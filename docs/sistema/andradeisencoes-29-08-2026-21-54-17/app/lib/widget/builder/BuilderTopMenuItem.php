<?php

/**
 * Class BuilderTopMenuItem
 *
 * Represents a menu item for the BuilderTopMenu. This class extends TElement
 * and provides properties for the menu label, action, image, link, and submenu.
 */
class BuilderTopMenuItem extends TElement
{
    private $label;
    private $action;
    private $image;
    private $menu;
    private $level;
    private $link;
    private $linkClass;
    
    /**
     * BuilderTopMenuItem constructor.
     *
     * Initializes a menu item with a label, action, optional image, and menu level.
     *
     * @param string      $label  The menu label.
     * @param string|null $action The action associated with the menu item (URL or application class).
     * @param string|null $image  The optional image for the menu item.
     * @param int         $level  The nesting level of the menu item (default is 0).
     */
    public function __construct($label, $action, $image = NULL, $level = 0)
    {
        parent::__construct('li');
        $this->label     = $label;
        $this->action    = $action;
        $this->level     = $level;
        $this->link      = new TElement('a');
        $this->linkClass = 'dropdown-toggle';
        
        if ($image)
        {
            $this->image = $image;
        }
    }
    
    /**
     * Retrieves the action associated with the menu item.
     *
     * @return string|null The action URL or class name.
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Sets the action for the menu item.
     *
     * @param string $action The action URL or class name.
     */
    public function setAction($action)
    {
        $this->action = $action;
    }
    
    /**
     * Retrieves the label of the menu item.
     *
     * @return string The menu label.
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Sets the label of the menu item.
     *
     * @param string $label The menu label.
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    /**
     * Retrieves the image associated with the menu item.
     *
     * @return string|null The image path or URL.
     */
    public function getImage()
    {
        return $this->image;
    }
    
    /**
     * Sets the image for the menu item.
     *
     * @param string $image The image path or URL.
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
    
    /**
     * Sets the CSS class for the menu item link.
     *
     * @param string $class The CSS class name.
     */
    public function setLinkClass($class)
    {
        $this->linkClass = $class;
    }
    
    /**
     * Defines a submenu for the menu item.
     *
     * @param BuilderTopMenu $menu A BuilderTopMenu instance representing the submenu.
     */
    public function setMenu(BuilderTopMenu $menu)
    {
        $this->{'class'} = 'dropdown-submenu';
        $this->menu = $menu;
    }
    
    /**
     * Displays the menu item on the screen.
     *
     * Generates the HTML structure for the menu item, including links, images,
     * submenus, and action handling.
     */
    public function show()
    {
        if ($this->action)
        {
            //$url['class'] = $this->action;
            //$url_str = http_build_query($url);
            $action = str_replace('#', '&', $this->action);
            if ((substr($action,0,7) == 'http://') or (substr($action,0,8) == 'https://'))
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
                    $this->link->{'onclick'} = "__adianti_load_page('index.php?class={$action}');";
                }
            }
        }
        else
        {
            $this->link->{'href'} = '#';
        }
        
        if (isset($this->image))
        {
            $image = new TImage($this->image);
            $this->link->add($image);
        }
        
        $label = '';
        if (substr($this->label, 0, 3) == '_t{')
        {
            $label = _t(substr($this->label,3,-1));
        }
        else
        {
            $label = $this->label;
        }
        
        if (!empty($this->label))
        {
            $this->link->add($label);
            $this->add($this->link);
        }
        
        if ($this->menu instanceof BuilderTopMenu)
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
            parent::add($this->menu);
        }
        
        parent::show();
    }
}