<?php
namespace Adianti\Widget\Container;

use Adianti\Widget\Base\TElement;

/**
 * Vertical Box: represents a vertical box container.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TVBox extends TElement
{
    /**
     * Class constructor.
     *
     * Initializes a vertical box container with inline-block display.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->{'style'} = 'display: inline-block';
    }
    
    /**
     * Adds a child element to the vertical box.
     *
     * @param mixed $child Any object that implements the show() method.
     *
     * @return TElement The wrapper element containing the child.
     */
    public function add($child)
    {
        $wrapper = new TElement('div');
        $wrapper->{'style'} = 'clear:both';
        $wrapper->add($child);
        parent::add($wrapper);
        return $wrapper;
    }
    
    /**
     * Adds multiple elements as columns inside the vertical box.
     *
     * @param mixed ...$cells Each argument represents a column element.
     */
    public function addColSet()
    {
        $args = func_get_args();
        if ($args)
        {
            foreach ($args as $arg)
            {
                $this->add($arg);
            }
        }
    }
    
    /**
     * Creates a new vertical box and adds multiple elements to it.
     *
     * @param mixed ...$cells Each argument represents an element to be added to the box.
     *
     * @return TVBox The created vertical box containing the provided elements.
     */
    public static function pack()
    {
        $box = new self;
        $args = func_get_args();
        if ($args)
        {
            foreach ($args as $arg)
            {
                $box->add($arg);
            }
        }
        return $box;
    }
}
