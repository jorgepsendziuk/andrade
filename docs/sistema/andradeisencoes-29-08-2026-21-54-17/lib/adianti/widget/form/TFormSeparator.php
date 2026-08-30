<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Base\TElement;

/**
 * Represents a visual separator with a title for forms.
 *
 * This class creates a separator with a customizable title, font color, 
 * font size, and separator color. The separator consists of a title 
 * (`h4` element) and a horizontal rule (`hr` element).
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TFormSeparator extends TElement
{
    private $fontColor;
    private $separatorColor;
    private $fontSize;
    private $header;
    private $divisor;
    
    /**
     * Class Constructor
     *
     * Initializes the form separator with a title and optional style settings.
     *
     * @param string $text          Separator title text
     * @param string $fontColor     Font color of the title (default: "#333333")
     * @param string $fontSize      Font size of the title in pixels (default: "16")
     * @param string $separatorColor Separator line color (default: "#eeeeee")
     */
    public function __construct($text, $fontColor = '#333333', $fontSize = '16', $separatorColor = '#eeeeee')
    {
        parent::__construct('div');
        
        $this->fontColor = $fontColor;
        $this->separatorColor = $separatorColor;
        $this->fontSize = $fontSize;
        
        $this->header = new TElement('h4');
        $this->header->{'class'} = 'tseparator';
        $this->header->{'style'} = "font-size: {$this->fontSize}px; color: {$this->fontColor};";
        
        $this->divisor = new TElement('hr');
        $this->divisor->{'style'} = "border-bottom-color: {$this->separatorColor}";
        $this->divisor->{'class'} = 'tseparator-divisor';
        $this->header->add($text);

        $this->add($this->header);
        $this->add($this->divisor);
    }

    /**
     * Set the font size of the separator title.
     *
     * @param string|int $size Font size in pixels
     */
    public function setFontSize($size)
    {
        $this->fontSize = $size;
        $this->header->{'style'} = "font-size: {$this->fontSize}px; color: {$this->fontColor};";
    }
    
    /**
     * Set the font color of the separator title.
     *
     * @param string $color Font color in any valid CSS color format (e.g., "#000000", "red")
     */
    public function setFontColor($color)
    {
        $this->fontColor = $color;
        $this->header->{'style'} = "font-size: {$this->fontSize}px; color: {$this->fontColor};";
    }

    /**
     * Set the color of the separator line.
     *
     * @param string $color Separator line color in any valid CSS color format
     */
    public function setSeparatorColor($color)
    {
        $this->separatorColor = $color;
        $this->divisor->{'style'} = "border-top-color: {$this->separatorColor}";
    }
}
