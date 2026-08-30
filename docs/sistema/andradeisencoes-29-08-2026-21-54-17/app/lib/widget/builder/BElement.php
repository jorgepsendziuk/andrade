<?php

/**
 * Class BElement
 *
 * This class extends TElement and provides additional methods for defining
 * size, height, and properties of an HTML element.
 *
 * @version    1.0
 * @package    widget
 * @subpackage base
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class BElement extends TElement
{
    protected $size;
    protected $height;
    
    /**
     * BElement constructor.
     *
     * Initializes an HTML element with a given tag name.
     *
     * @param string $tagname The name of the HTML tag.
     */
    public function __construct($tagname)
    {
        parent::__construct($tagname);
    }
    
    /**
     * Sets the width and optional height of the element.
     *
     * @param string      $width  The width of the element (e.g., "100px", "50%").
     * @param string|null $height The height of the element (optional).
     */
    public function setSize($width, $height = NULL)
    {
        $this->size   = $width;
        if ($height)
        {
            $this->height = $height;
        }
        
        if ($this->size)
        {
            $this->size = str_replace('px', '', $this->size);
            $size = (strstr($this->size, '%') !== FALSE) ? $this->size : "{$this->size}px";
            $this->setProperty('style', "width:{$size};", FALSE); //aggregate style info
        }
        
        if ($this->height)
        {
            $this->height = str_replace('px', '', $this->height);
            $height = (strstr($this->height, '%') !== FALSE) ? $this->height : "{$this->height}px";
            $this->setProperty('style', "height:{$height}", FALSE); //aggregate style info
        }
        
    }
    
    /**
     * Retrieves the size of the element.
     *
     * @return array An array containing the width and height of the element.
     */
    public function getSize()
    {
        return array( $this->size, $this->height );
    }

    /**
     * Sets an attribute (property) of the HTML element.
     *
     * @param string  $name    The name of the property.
     * @param mixed   $value   The value of the property.
     * @param bool    $replace Whether to replace the existing property value (default: true).
     */
    public function setProperty($name, $value, $replace = TRUE)
    {
        if ($replace)
        {
            // delegates the property assign to the composed object
            $this->$name = $value;
        }
        else
        {
            if ($this->$name)
            {
            
                // delegates the property assign to the composed object
                $this->$name = $this->$name . ';' . $value;
            }
            else
            {
                // delegates the property assign to the composed object
                $this->$name = $value;
            }
        }
        
        parent::setProperty($name, $this->name);
    }
}
