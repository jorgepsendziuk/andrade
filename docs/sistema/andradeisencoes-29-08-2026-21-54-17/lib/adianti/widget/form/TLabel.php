<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TStyle;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Base\TScript;

/**
 * Label Widget
 *
 * This class represents a label element that can be styled with font color, size, and decoration.
 * It extends TField and implements AdiantiWidgetInterface, providing additional formatting capabilities.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TLabel extends TField implements AdiantiWidgetInterface
{
    private $toggleVisibility;
    private $fontStyle;
    private $embedStyle;
    protected $value;
    protected $size;
    protected $id;
    
    /**
     * Class Constructor
     *
     * Initializes a label with optional styles such as color, font size, and decoration.
     *
     * @param string      $value      The label text content.
     * @param string|null $color      The font color (optional).
     * @param string|null $fontsize   The font size (optional, e.g., '12px' or '12pt').
     * @param string|null $decoration The font decoration (optional, accepts 'b' for bold, 'i' for italic, 'u' for underline).
     * @param int|null    $size       The width of the label (optional).
     */
    public function __construct($value, $color = null, $fontsize = null, $decoration = null, $size = null)
    {
        $this->id   = 'tlabel_' . mt_rand(1000000000, 1999999999);
        $stylename = 'tlabel_style_'.$this->id;
        $this->toggleVisibility = FALSE;
        
        // set the label's content
        $this->setValue($value);
        
        $this->embedStyle = new TStyle($stylename);
        
        if (!empty($color))
        {
            $this->setFontColor($color);
        }
        
        if (!empty($fontsize))
        {
            $this->setFontSize($fontsize);
        }
        
        if (!empty($decoration))
        {
            $this->setFontStyle($decoration);
        }
        
        if (!empty($size))
        {
            $this->setSize($size);
        }
        
        // create a new element
        $this->tag = new TElement('label');
    }

    /**
     * Enable or disable the visibility toggle feature
     *
     * When enabled, the label content will be blurred, and an eye icon will allow toggling visibility.
     *
     * @param bool $toggleVisibility Whether to enable or disable visibility toggling (default: TRUE).
     */
    public function enableToggleVisibility($toggleVisibility = TRUE)
    {
        $this->toggleVisibility = $toggleVisibility;
    }
    
    /**
     * Clone the object
     *
     * Ensures the embedded style is also cloned when duplicating the object.
     */
    public function __clone()
    {
        parent::__clone();
        $this->embedStyle = clone $this->embedStyle;
    }
    
    /**
     * Set the font size of the label
     *
     * @param string|int $size The font size (e.g., '12px', '14pt', or an integer which defaults to 'pt' if no unit is provided).
     */
    public function setFontSize($size)
    {
        $this->embedStyle->{'font_size'}    = (strpos($size, 'px') or strpos($size, 'pt')) ? $size : $size.'pt';
    }
    
    /**
     * Set the font style of the label
     *
     * Defines text decoration styles such as bold, italic, and underline.
     *
     * @param string $decoration A combination of 'b' (bold), 'i' (italic), and 'u' (underline).
     */
    public function setFontStyle($decoration)
    {
        if (strpos(strtolower($decoration), 'b') !== FALSE)
        {
            $this->embedStyle->{'font-weight'} = 'bold';
        }
        
        if (strpos(strtolower($decoration), 'i') !== FALSE)
        {
            $this->embedStyle->{'font-style'} = 'italic';
        }
        
        if (strpos(strtolower($decoration), 'u') !== FALSE)
        {
            $this->embedStyle->{'text-decoration'} = 'underline';
        }
    }
    
    /**
     * Set the font family of the label
     *
     * @param string $font The font family name (e.g., 'Arial', 'Times New Roman').
     */
    public function setFontFace($font)
    {
        $this->embedStyle->{'font_family'} = $font;
    }
    
    /**
     * Set the font color of the label
     *
     * @param string $color The font color in hexadecimal (e.g., '#FF0000') or named colors (e.g., 'red').
     */
    public function setFontColor($color)
    {
        $this->embedStyle->{'color'} = $color;
    }
    
    /**
     * Add content inside the label
     *
     * Appends new content to the label element.
     *
     * @param string|TElement $content The content to be added (string or TElement object).
     */
    function add($content)
    {
        $this->tag->add($content);
        
        if (is_string($content))
        {
            $this->value .= $content;
        }
    }
    
    /**
     * Get the label content
     *
     * @return string The current value of the label.
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Displays the label on the screen
     *
     * Applies the configured styles and renders the label, including optional visibility toggle effects.
     */
    public function show()
    {
        if ($this->size)
        {
            if (strstr((string) $this->size, '%') !== FALSE)
            {
                $this->embedStyle->{'width'} = $this->size;
            }
            else
            {
                $this->embedStyle->{'width'} = $this->size . 'px';
            }
        }
        
        // if the embed style has any content
        if ($this->embedStyle->hasContent())
        {
            $this->setProperty('style', $this->embedStyle->getInline() . $this->getProperty('style'), TRUE);
        }
        
        $this->tag->{'id'} = $this->id;
        
        if ($this->toggleVisibility)
        {
            $icon = new TElement('i');
            $icon->{'class'} = 'fa fa-eye-slash';

            $span = new TElement('span');
            $span->add($this->value);
            $span->{'style'} = 'filter: blur(5px);';

            $this->tag->add($span);
            $this->tag->{'class'} .= ' label-toggle-visibilty ';
            $this->tag->add($icon);

            TScript::create(" tlabel_toggle_visibility( '{$this->id}' ); ");
        }
        else
        {
            // add content to the tag
            $this->tag->add($this->value);
        }

        // show the tag
        $this->tag->show();
    }
}
