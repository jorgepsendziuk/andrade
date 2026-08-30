<?php
namespace Adianti\Widget\Container;

use Adianti\Widget\Base\TElement;

/**
 * Horizontal Box
 *
 * A container for horizontally arranged elements.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class THBox extends TElement
{
    /**
     * Class Constructor
     *
     * Initializes the horizontal box container.
     */
    public function __construct()
    {
        parent::__construct('div');
    }
    
    /**
     * Add a child element
     *
     * Adds an element to the horizontal box with an optional inline style.
     *
     * @param TElement $child The element to be added.
     * @param string   $style Optional CSS style for the wrapper div (default: 'display:inline-table;').
     *
     * @return TElement The wrapper div that contains the added element.
     */
    public function add($child, $style = 'display:inline-table;')
    {
        $wrapper = new TElement('div');
        $wrapper->{'style'} = $style;
        $wrapper->add($child);
        parent::add($wrapper);
        return $wrapper;
    }
    
    /**
     * Add a new row with multiple cells
     *
     * Adds multiple elements as a row to the horizontal box.
     *
     * @param mixed ...$cells Each argument represents a row cell.
     */
    public function addRowSet()
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
     * Static method for packing content
     *
     * Creates a new horizontal box and adds elements to it.
     *
     * @param mixed ...$cells Elements to be packed into the horizontal box.
     *
     * @return THBox The newly created horizontal box containing the elements.
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
