<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;

/**
 * Represents a breadcrumb navigation component.
 *
 * This class allows the creation of a breadcrumb trail, which helps users 
 * navigate through hierarchical pages. It supports adding home links and 
 * multiple breadcrumb items.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @author     Nataniel Rabaioli
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TBreadCrumb extends TElement
{
    protected static $homeController;
    protected $container;
    protected $items;
    
    /**
     * Initializes a new instance of the TBreadCrumb class.
     *
     * The breadcrumb is displayed within a `<div>` container with an ordered list (`<ol>`)
     * styled as a breadcrumb navigation bar.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->{'id'} = 'div_breadcrumbs';
        
        $this->container = new TElement('ol');
        $this->container->{'class'} = 'tbreadcrumb';
        parent::add( $this->container );
    }
    
    /**
     * Creates and returns a new breadcrumb instance.
     *
     * @param array $options An array of breadcrumb labels.
     * @param bool  $home    Whether to include a home icon as the first item (default: true).
     *
     * @return TBreadCrumb The created breadcrumb instance.
     */
    public static function create( $options, $home = true)
    {
        $breadcrumb = new TBreadCrumb;
        if ($home)
        {
            $breadcrumb->addHome();
        }
        foreach ($options as $option)
        {
            $breadcrumb->addItem( $option );
        }
        return $breadcrumb;
    }
    
    /**
     * Adds a home link as the first breadcrumb item.
     *
     * The home link redirects to the main controller if defined, otherwise, it 
     * redirects to the default engine page.
     */
    public function addHome()
    {
        $li = new TElement('li');
        $li->{'class'} = 'home';
        $a = new TElement('a');
        $a->generator = 'adianti';
        
        if (self::$homeController)
        {
            $a->{'href'} = 'engine.php?class='.self::$homeController;
        }
        else
        {
            $a->{'href'} = 'engine.php';
        }
        
        $a->{'title'} = 'Home';
        
        $li->add( $a );
        $this->container->add( $li );
    }
    
    /**
     * Adds a new item to the breadcrumb.
     *
     * @param string  $path The text label for the breadcrumb item.
     * @param bool $last Whether this item is the last one in the breadcrumb trail.
     */
    public function addItem($path, $last = FALSE)
    {
        $li = new TElement('li');
        $this->container->add( $li );
        
        $span = new TElement('span');
        $span->add( $path );
        
        $this->items[$path] = $span;
        if( $last )
        {
            $li->add( $span );
        }
        else
        {
            $a = new TElement('a');
            
            $li->add( $a );
            $a->add( $span );
        }
            
    }
    
    /**
     * Marks a specific breadcrumb item as selected.
     *
     * @param string $path The breadcrumb label to mark as selected.
     */
    public function select($path)
    {
        foreach ($this->items as $key => $span)
        {
            if ($key == $path)
            {
                $span->{'class'} = 'selected';
            }
            else
            {
                $span->{'class'} = '';
            }
        }
    }
    
    /**
     * Defines the home controller for the breadcrumb.
     *
     * @param string $className The name of the controller to use for the home link.
     */
    public static function setHomeController($className)
    {
        self::$homeController = $className;
    }
}
