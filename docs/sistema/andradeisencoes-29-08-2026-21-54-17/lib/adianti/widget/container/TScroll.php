<?php
namespace Adianti\Widget\Container;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TStyle;
use Adianti\Widget\Util\TSourceCode;

/**
 * Scrolled Window: allows embedding other containers inside, creating scrollbars when the content exceeds the visual area.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TScroll extends TElement
{
    private $width;
    private $height;
    private $margin;
    private $transparency;
    
    /**
     * Class constructor.
     * Initializes the TScroll component with a unique ID, default margin, and transparency settings.
     */
    public function __construct()
    {
        $this->{'id'} = 'tscroll_' . mt_rand(1000000000, 1999999999);
        $this->margin = 2;
        $this->transparency = FALSE;
        parent::__construct('div');
    }
    
    /**
     * Set the scrollable panel size.
     *
     * @param int|string $width  The width of the panel (numeric value in pixels or CSS dimension string).
     * @param int|string $height The height of the panel (numeric value in pixels or CSS dimension string).
     */
    public function setSize($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;
    }
    
    /**
     * Set the margin for the scrollable panel.
     *
     * @param int $margin The margin size in pixels.
     */
    public function setMargin($margin)
    {
        $this->margin = $margin;
    }
    
    /**
     * Set the transparency of the scrollable panel.
     *
     * @param bool $bool Whether the panel should be transparent.
     */
    public function setTransparency($bool)
    {
        $this->transparency = $bool;
    }
    
    /**
     * Render the scrollable panel with applied styles and configurations.
     */
    public function show()
    {
        if (!$this->transparency)
        {
            $this->{'style'} .= ';border: 1px solid #c2c2c2';
            $this->{'style'} .= ';background: #ffffff';
        }
        $this->{'style'} .= ";padding: {$this->margin}px";
        
        if (!empty($this->width))
        {
            $this->{'style'} .= is_numeric($this->width) ? ";width:{$this->width}px" : ";width:{$this->width}";
        }
        
        if (!empty($this->height))
        {
            $this->{'style'} .= is_numeric($this->height) ? ";height:{$this->height}px" : ";height:{$this->height}";
        }
        
        $this->{'class'} .= " tscroll";
        parent::show();
    }
}
