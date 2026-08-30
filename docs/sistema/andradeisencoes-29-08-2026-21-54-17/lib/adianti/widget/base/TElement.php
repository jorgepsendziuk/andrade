<?php
namespace Adianti\Widget\Base;

/**
 * Represents an HTML element, allowing attributes, child elements, and rendering.
 *
 * This class provides methods to create, modify, and render HTML elements dynamically.
 * It supports setting properties, adding children, managing attributes, and defining 
 * behavior such as wrapping and visibility.
 *
 * @version    7.5
 * @package    widget
 * @subpackage base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TElement
{
    private $tagname;     // tag name
    private $properties;  // tag properties
    private $wrapped;
    private $useLineBreaks;
    private $useSingleQuotes;
    private $afterElement;
    protected $children;
    private static $voidelements;
    private $hidden;
    
    /**
     * Initializes an HTML element with a given tag name.
     *
     * @param string $tagname The name of the HTML tag.
     */
    public function __construct($tagname)
    {
        // define the element name
        $this->tagname = $tagname;
        $this->useLineBreaks = TRUE;
        $this->useSingleQuotes = FALSE;
        $this->wrapped = FALSE;
        $this->hidden = FALSE;
        $this->properties = [];
        
        if (empty(self::$voidelements))
        {
            self::$voidelements = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr',
                                        'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');
        }
    }
    
    /**
     * Creates a new HTML element with specified attributes and content.
     *
     * @param string       $tagname    The name of the HTML tag.
     * @param string|array $value      The content of the element, can be a string or an array of elements.
     * @param array|null   $attributes An associative array of attributes (optional).
     *
     * @return TElement The created HTML element.
     */
    public static function tag($tagname, $value, $attributes = NULL)
    {
        $object = new TElement($tagname);
        
        if (is_array($value))
        {
            foreach ($value as $element)
            {
                $object->add($element);
            }
        }
        else
        {
            $object->add($value);
        }
        
        if ($attributes)
        {
            foreach ($attributes as $att_name => $att_value)
            {
                $object->$att_name = $att_value;
            }
        }
        
        return $object;
    }
    
    /**
     * Hides the element, preventing it from being rendered.
     */
    public function hide()
    {
        $this->hidden = true;
    }
    
    /**
     * Inserts an element after the current element.
     *
     * @param TElement|string $element The element or content to be added after the current element.
     */
    public function after($element)
    {
        $this->afterElement = $element;
    }
    
    /**
     * Retrieves the element set to appear after this element.
     *
     * @return TElement|string|null The after-element or null if not set.
     */
    public function getAfterElement()
    {
        return $this->afterElement;
    }
    
    /**
     * Sets the tag name of the element.
     *
     * @param string $tagname The new tag name.
     */
    public function setName($tagname)
    {
        $this->tagname = $tagname;
    }
    
    /**
     * Retrieves the tag name of the element.
     *
     * @return string The tag name.
     */
    public function getName()
    {
        return $this->tagname;
    }
    
    /**
     * Defines whether this element is wrapped inside another element.
     *
     * @param bool $bool TRUE if wrapped, FALSE otherwise.
     */
    public function setIsWrapped($bool)
    {
        $this->wrapped = $bool;
    }
    
    /**
     * Checks whether this element is wrapped inside another element.
     *
     * @return bool TRUE if wrapped, FALSE otherwise.
     */
    public function getIsWrapped()
    {
        return $this->wrapped;
    }
    
    /**
     * Sets an HTML attribute for the element.
     *
     * @param string $name  The attribute name.
     * @param mixed  $value The attribute value (must be a scalar).
     */
    public function setProperty($name, $value)
    {
        // objects and arrays are not set as properties
        if (is_scalar($value))
        {
            // store the property's value
            $this->properties[$name] = $value;
        }
    }
    
    /**
     * Sets multiple HTML attributes at once.
     *
     * @param array $properties An associative array of attribute names and values.
     */
    public function setProperties($properties)
    {
        foreach ($properties as $property => $value)
        {
            if (is_null($value))
            {
                unset($this->properties[$property]);
            }
            else
            {
                $this->properties[$property] = $value;
            }
        }
    }
    
    /**
     * Retrieves the value of a specific attribute.
     *
     * @param string $name The attribute name.
     *
     * @return mixed|null The attribute value or null if not set.
     */
    public function getProperty($name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }
    
    /**
     * Retrieves all attributes of the element.
     *
     * @return array An associative array of attributes.
     */
    public function getProperties()
    {
        return $this->properties;
    }
    
    /**
     * Dynamically sets an attribute for the element.
     *
     * @param string $name  The attribute name.
     * @param mixed  $value The attribute value (must be a scalar).
     */
    public function __set($name, $value)
    {
        // objects and arrays are not set as properties
        if (is_scalar($value))
        {
            // store the property's value
            $this->properties[$name] = $value;
        }
    }
    
    /**
     * Removes an attribute from the element.
     *
     * @param string $name The attribute name.
     */
    public function __unset($name)
    {
        unset($this->properties[$name]);
    }
    
    /**
     * Dynamically retrieves an attribute value.
     *
     * @param string $name The attribute name.
     *
     * @return mixed|null The attribute value or null if not set.
     */
    public function __get($name)
    {
        if (isset($this->properties[$name]))
        {              
            return $this->properties[$name];
        }
    }
    
    /**
     * Checks if a specific attribute is set.
     *
     * @param string $name The attribute name.
     *
     * @return bool TRUE if the attribute is set, FALSE otherwise.
     */
    public function __isset($name)
    {
        return isset($this->properties[$name]);
    }
    
    /**
     * Creates a deep copy of the element, cloning its children.
     */
    public function __clone()
    {
        // verify if the tag has child elements
        if ($this->children)
        {
            // iterate all child elements
            foreach ($this->children as $key => $child)
            {
                if (is_object($child))
                {
                    $this->children[$key] = clone $child;
                }
                else
                {
                    $this->children[$key] = $child;
                }
            }
        }
    }
    
    /**
     * Adds a child element to this element.
     *
     * @param mixed $child The child element or content to be added.
     */
    public function add($child)
    {
        $this->children[] = $child;
        if ($child instanceof TElement)
        {
            $child->setIsWrapped( TRUE );
        }
    }
    
    /**
     * Inserts a child element at a specific position.
     *
     * @param int   $position The index where the child should be inserted.
     * @param mixed $child    The child element or content to be added.
     */
    public function insert($position, $child)
    {
        array_splice( $this->children, $position, 0, array($child) );
        if ($child instanceof TElement)
        {
            $child->setIsWrapped( TRUE );
        }
    }
    
    /**
     * Sets whether line breaks should be used when rendering the element.
     *
     * @param bool $linebreaks TRUE to use line breaks, FALSE otherwise.
     */
    public function setUseLineBreaks($linebreaks)
    {
        $this->useLineBreaks = $linebreaks;
    }
    
    /**
     * Sets whether attributes should use single quotes in the output.
     *
     * @param bool $singlequotes TRUE to use single quotes, FALSE for double quotes.
     */
    public function setUseSingleQuotes($singlequotes)
    {
        $this->useSingleQuotes = $singlequotes;
    }
    
    /**
     * Removes a specific child element.
     *
     * @param mixed $object The child element to be removed.
     */
    public function del($object)
    {
        foreach ($this->children as $key => $child)
        {
            if ($child === $object) // same instance
            {
                unset($this->children[$key]);
            }
        }
    }

    /**
     * Retrieves all child elements of this element.
     *
     * @return array The list of child elements.
     */
    public function getChildren()
    {
        return $this->children;
    }
    
    /**
     * Searches for child elements by tag name and optional attributes.
     *
     * @param string $element    The tag name to search for.
     * @param array|null $properties Optional key-value pairs to match against attributes.
     *
     * @return array A list of matching elements.
     */
    public function find($element, $properties = null)
    {
        if ($this->children)
        {
            foreach ($this->children as $child)
            {
                if ($child instanceof TElement)
                {
                    if ($child->getName() == $element)
                    {
                        $match = true;
                        if ($properties)
                        {
                            foreach ($properties as $key => $value)
                            {
                                if ($child->getProperty($key) !== $value)
                                {
                                    $match = false;
                                }
                            }
                        }
                        
                        if ($match)
                        {
                            return array_merge([$child], $child->find($element, $properties));
                        }
                    }
                    return $child->find($element, $properties);
                }
            }
        }
        return [];
    }
    
    /**
     * Retrieves a child element by position.
     *
     * @param int $position The index of the child element.
     *
     * @return mixed|null The child element or null if not found.
     */
    public function get($position)
    {
        return $this->children[$position];
    }
    
    /**
     * Outputs the opening tag of the element with its attributes.
     */
    public function openTag()
    {
        // exibe a tag de abertura
        echo "<{$this->tagname}";
        if ($this->properties)
        {
            // percorre as propriedades
            foreach ($this->properties as $name=>$value)
            {
                if ($this->useSingleQuotes)
                {
                    $value = str_replace("'", '&#039;', $value);
                    echo " {$name}='{$value}'";
                }
                else
                {
                    $value = str_replace('"', '&quot;', $value);
                    echo " {$name}=\"{$value}\"";
                }
            }
        }
        
        if (in_array($this->tagname, self::$voidelements))
        {
            echo '/>';
        }
        else
        {
            echo '>';
        }
    }
    
    /**
     * Alias for `openTag()`, maintains backward compatibility.
     */
    public function open()
    {
        $this->openTag();
    }
    
    /**
     * Displays the element, including its children and attributes.
     */
    public function show()
    {
        if ($this->hidden)
        {
            return;
        }
        
        // open the tag
        $this->openTag();
        
        // verify if the tag has child elements
        if ($this->children)
        {
            if (count($this->children)>1)
            {
                if ($this->useLineBreaks)
                {
                    echo "\n";
                }
            }
            // iterate all child elements
            foreach ($this->children as $child)
            {
                if ($child instanceof self)
                {
                    $child->setUseLineBreaks($this->useLineBreaks);
                }
                
                // verify if the child is an object
                if (is_object($child))
                {
                    $child->show();
                }
                // otherwise, the child is a scalar
                else if ((is_string($child)) or (is_numeric($child)))
                {
                    echo $child;
                }
            }
        }
        
        if (!in_array($this->tagname, self::$voidelements))
        {
            // closes the tag
            $this->closeTag();
        }
        
        if (!empty($this->afterElement))
        {
            $this->afterElement->show();
        }
    }
    
    /**
     * Outputs the closing tag of the element.
     */
    public function closeTag()
    {
        echo "</{$this->tagname}>";
        if ($this->useLineBreaks)
        {
            echo "\n";
        }
    }
    
    /**
     * Alias for `closeTag()`, maintains backward compatibility.
     */
    public function close()
    {
        $this->closeTag();
    }
    
    /**
     * Converts the element to a string representation.
     *
     * @return string The HTML string representation of the element.
     */
    public function __toString()
    {
        return $this->getContents();
    }
    
    /**
     * Retrieves the element's contents as a string.
     *
     * @return string The rendered HTML content.
     */
    public function getContents()
    {
        ob_start();
        $this->show();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    /**
     * Removes all child elements from this element.
     */
    public function clearChildren()
    {
        $this->children = array();
    }
}
