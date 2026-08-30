<?php
namespace Adianti\Widget\Container;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TNotebook;
use Adianti\Widget\Form\TLabel;

/**
 * Frame Widget
 *
 * Creates a bordered area with a title positioned at its top-left corner.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TFrame extends TElement
{
    private $legend;
    private $width;
    private $height;
    
    /**
     * Class Constructor
     *
     * Initializes the frame with optional width and height.
     *
     * @param string|null $width  The frame width (e.g., '100%', '500px').
     * @param string|null $height The frame height (e.g., '300px', '50%').
     */
    public function __construct($width = NULL, $height = NULL)
    {
        parent::__construct('fieldset');
        $this->{'id'}    = 'tfieldset_' . mt_rand(1000000000, 1999999999);
        $this->{'class'} = 'tframe';
        
        $this->width  = $width;
        $this->height = $height;
        
        if ($width)
        {
            $this->{'style'} .= (strstr($width, '%') !== FALSE) ? ";width:{$width}" : ";width:{$width}px";
        }
        
        if ($height)
        {
            $this->{'style'} .= (strstr($height, '%') !== FALSE) ? ";height:{$height}" : ";height:{$height}px";
        }
    }
    
    /**
     * Returns the frame size
     *
     * @return array An array containing the width and height of the frame.
     */
    public function getSize()
    {
        return array($this->width, $this->height);
    }
    
    /**
     * Set the legend
     *
     * Defines the title (legend) for the frame.
     *
     * @param string $legend The frame legend text.
     */
    public function setLegend($legend)
    {
        $obj = new TElement('legend');
        $obj->add(new TLabel($legend));
        parent::add($obj);
        $this->legend = $legend;
    }
    
    /**
     * Get the legend
     *
     * Retrieves the text of the frame legend.
     *
     * @return string|null The legend text or null if not set.
     */
    public function getLegend()
    {
        return $this->legend;
    }
    
    /**
     * Get the Frame ID
     *
     * Retrieves the unique identifier of the frame.
     *
     * @return string The frame ID.
     */
    public function getId()
    {
        return $this->{'id'};
    }
}
