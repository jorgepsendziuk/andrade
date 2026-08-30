<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;

/**
  * Text Display Widget
 *
 * This class represents a text display component with customizable
 * color, font size, and text decorations. It extends TElement and 
 * provides optional toggle visibility functionality.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TTextDisplay extends TElement
{
    private $toggleVisibility;
    private $size = null;

    /**
     * Class Constructor
     *
     * Initializes a text display element with optional styles.
     *
     * @param string      $value      The text content to display.
     * @param string|null $color      (Optional) The text color in CSS format (e.g., "#ff0000" or "red").
     * @param string|null $fontSize   (Optional) The font size (e.g., "12pt", "14px").
     * @param string|null $decoration (Optional) Text decoration options: 'b' for bold, 'i' for italic, 'u' for underline.
     */
    public function __construct($value, $color = null, $fontSize = null, $decoration = null)
    {
        parent::__construct('span');
        $this->{'class'} = 'ttd';

        $this->toggleVisibility = FALSE;
        
        $style = array();
        
        if (!empty($color))
        {
            $style['color'] = $color;
        }
        
        if (!empty($fontSize))
        {
            $style['font-size'] = (strpos($fontSize, 'px') or strpos($fontSize, 'pt')) ? $fontSize : $fontSize.'pt';
        }
        
        if (!empty($decoration))
        {
            if (strpos(strtolower($decoration), 'b') !== FALSE)
            {
                $style['font-weight'] = 'bold';
            }
            
            if (strpos(strtolower($decoration), 'i') !== FALSE)
            {
                $style['font-style'] = 'italic';
            }
            
            if (strpos(strtolower($decoration), 'u') !== FALSE)
            {
                $style['text-decoration'] = 'underline';
            }
        }
        
        parent::add($value);

        $this->{'style'} = substr( str_replace(['"',','], ['',';'], json_encode($style) ), 1, -1);
    }

    /**
     * Set the text display width
     *
     * Defines the width of the text display element.
     *
     * @param int|string $width The width of the text element in pixels (e.g., "200px").
     */
    public function setSize($width)
    {
        $this->size = $width;
    }
    
    /**
     * Get the text display width
     *
     * Returns the currently defined width of the text display.
     *
     * @return int|string|null The width of the text display or null if not set.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Enable or disable toggle visibility
     *
     * Allows the text content to be toggled between visible and blurred states.
     *
     * @param bool $toggleVisibility (Optional) TRUE to enable toggle visibility, FALSE to disable it. Default is TRUE.
     */
    public function enableToggleVisibility($toggleVisibility = TRUE)
    {
        $this->toggleVisibility = $toggleVisibility;
    }

    /**
     * Render the text display element
     *
     * Outputs the text display element. If toggle visibility is enabled, 
     * the text is initially blurred, and an eye icon is added for visibility toggling.
     */
    public function show()
    {
        if ($this->toggleVisibility)
        {
            $icon = new TElement('i');
            $icon->{'class'} = 'fa fa-eye-slash';

            $span = new TElement('span');
            $span->{'class'} .= ' label-toggle-visibilty ';

            $spanValue = new TElement('span');
            $spanValue->add(parent::getChildren()[0]);
            $spanValue->{'class'} = $this->class;
            $spanValue->{'style'} = $this->style . ';filter: blur(5px);';

            $span->add($spanValue);
            $span->add($icon);
            $span->id   = 'ttextdisplay_' . mt_rand(1000000000, 1999999999);

            $span->show();

            TScript::create(" tlabel_toggle_visibility( '{$span->id}' ); ");
        }
        else
        {
            parent::show();
        }
    }
}
