<?php
namespace Adianti\Widget\Container;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TStyle;

/**
  * Panel Container: Allows organizing widgets using fixed (absolute) positions.
 *
 * This class provides a container with a relative positioning style, allowing elements to be placed 
 * at absolute positions inside it.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TPanel extends TElement
{
    private $style;
    private $width;
    private $height;
    
    /**
     * Class Constructor.
     *
     * Initializes a panel with a given width and height, setting up its styles and unique ID.
     *
     * @param int $width  The width of the panel in pixels.
     * @param int $height The height of the panel in pixels.
     */
    public function __construct($width, $height)
    {
        parent::__construct('div');
		
        $this->{'id'} = 'tpanel_' . mt_rand(1000000000, 1999999999);
        
        // creates the panel style
        $this->style = new TStyle('style_'.$this->{'id'});
        $this->style-> position = 'relative';
        $this->width = $width;
        $this->height = $height;
        
        $this->{'class'} = 'style_'.$this->{'id'};
    }
    
    /**
     * Set the panel's dimensions.
     *
     * @param int $width  The new width of the panel in pixels.
     * @param int $height The new height of the panel in pixels.
     */
    public function setSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }
    
    /**
     * Get the panel's dimensions.
     *
     * @return array An array containing the panel's width and height in pixels.
     */
    public function getSize()
    {
        return array($this->width, $this->height);
    }
    
    /**
     * Insert a widget into the panel at a specific position.
     *
     * @param TElement $widget The widget to be placed inside the panel.
     * @param int      $col    The horizontal position (left offset) in pixels.
     * @param int      $row    The vertical position (top offset) in pixels.
     */
    public function put($widget, $col, $row)
    {
        // creates a layer to put the widget inside
        $layer = new TElement('div');
        // define the layer position
        $layer-> style = "position:absolute; left:{$col}px; top:{$row}px;";
        // add the widget to the layer
        $layer->add($widget);
        
        // add the widget to the container
        parent::add($layer);
    }
    
    /**
     * Render the panel and display its contents.
     *
     * This method sets the panel's final dimensions and applies its style before rendering.
     */
    public function show()
    {
        $this->style-> width  = $this->width.'px';
        $this->style-> height = $this->height.'px';
        $this->style->show();
        
        parent::show();
    }
}
