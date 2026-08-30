<?php
namespace Adianti\Widget\Base;

use Adianti\Control\TPage;
use Adianti\Widget\Base\TElement;

/**
 * Manages CSS styles for the application.
 *
 * This class allows the definition, retrieval, and display of CSS styles dynamically.
 * Styles can be created, modified, and shown either inline or as external stylesheets.
 *
 * @version    7.5
 * @package    widget
 * @subpackage base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TStyle
{
    private $name;           // stylesheet name
    private $properties;     // properties
    static  private $loaded; // array of loaded styles
    static  private $styles;
    
    /**
     * Class Constructor
     *
     * Initializes a new style with a given name.
     *
     * @param string $name The name of the style.
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->properties = array();
    }
    
    /**
     * Imports and applies a CSS style from a file.
     *
     * @param string $filename The path to the CSS file.
     */
    public static function importFromFile($filename)
    {
        $style = new TElement('style');
        $style->add( file_get_contents( $filename ) );
        $style->show();
    }
    
    /**
     * Retrieves the name of the style.
     *
     * @return string The name of the style.
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Finds a style by its properties.
     *
     * @param object $object An object containing style properties.
     *
     * @return string|null The name of the matching style, or null if no match is found.
     */
    public static function findStyle($object)
    {
        if (self::$styles)
        {
            foreach (self::$styles as $stylename => $style)
            {
                if ((array)$style->properties === (array)$object->properties)
                {
                    return $stylename;
                }
            }
        }
    }
    
    /**
     * Sets a CSS property value.
     *
     * Automatically converts underscores to hyphens in property names.
     *
     * @param string $name  The name of the CSS property.
     * @param string $value The value to be assigned to the CSS property.
     */
    public function __set($name, $value)
    {
        // replaces "_" by "-" in the property's name
        $name = str_replace('_', '-', $name);
        
        // store the assigned tag property
        $this->properties[$name] = $value;
    }
    
    /**
     * Retrieves a CSS property value.
     *
     * Automatically converts underscores to hyphens in property names.
     *
     * @param string $name The name of the CSS property.
     *
     * @return string|null The value of the CSS property, or null if it is not set.
     */
    public function __get($name)
    {
        // replaces "_" by "-" in the property's name
        $name = str_replace('_', '-', $name);
        
        return $this->properties[$name];
    }
    
    /**
     * Checks if the style has any defined properties.
     *
     * @return bool True if the style contains properties, false otherwise.
     */
    public function hasContent()
    {
        return count($this->properties) > 0;
    }
    
    /**
     * Generates and returns the full CSS definition of the style.
     *
     * @return string The CSS content formatted as a class definition.
     */
    public function getContent()
    {
        // open the style
        $style = '';
        $style.= "    .{$this->name}\n";
        $style.= "    {\n";
        if ($this->properties)
        {
            // iterate the style properties
            foreach ($this->properties as $name=>$value)
            {
                $style.= "        {$name}: {$value};\n";
            }
        }
        $style.= "    }\n";
        return $style;
    }
    
    /**
     * Retrieves the style properties formatted for inline use.
     *
     * @return string The inline CSS string containing all style properties.
     */
    public function getInline()
    {
        $style = '';
        if ($this->properties)
        {
            // iterate the style properties
            foreach ($this->properties as $name=>$value)
            {
                $name = str_replace('_', '-', $name);
                $style.= "{$name}: {$value};";
            }
        }
        
        return $style;
    }
    
    /**
     * Displays the style.
     *
     * If the style has not been loaded yet, it is either printed inline or registered
     * globally for the page.
     *
     * @param bool $inline Whether to output the style inline (true) or register it globally (false).
     */
    public function show( $inline = FALSE)
    {
        // check if the style is already loaded
        if (!isset(self::$loaded[$this->name]))
        {
            if ($inline)
            {
                echo "    <style type='text/css' media='screen'>\n";
                echo $this->getContent();
                echo "    </style>\n";
            }
            else
            {
                $style = $this->getContent();
                TPage::register_css($this->name, $style);
                // mark the style as loaded
                self::$loaded[$this->name] = TRUE;
                self::$styles[$this->name] = $this;
            }
        }
    }
}
