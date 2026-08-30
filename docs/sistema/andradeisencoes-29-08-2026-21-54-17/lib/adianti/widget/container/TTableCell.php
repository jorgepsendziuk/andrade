<?php
namespace Adianti\Widget\Container;

use Adianti\Widget\Base\TElement;

/**
 * TableCell: represents a table cell element.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TTableCell extends TElement
{
    /**
     * Class constructor.
     *
     * @param mixed  $value The content of the table cell.
     * @param string $tag   The HTML tag to be used for the cell (default: 'td').
     */
    public function __construct($value, $tag = 'td')
    {
        parent::__construct($tag);
        parent::add($value);
    }
}
