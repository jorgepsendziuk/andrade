<?php
namespace Adianti\Widget\Container;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TTableCell;
use Exception;

/**
 * TableRow: represents table row element.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TTableRow extends TElement
{
    private $section;
    
    /**
     * Class constructor.
     *
     * @param string $section The section type of the row ('thead', 'tbody', or 'tfoot'). Default is 'tbody'.
     */
    public function __construct($section = 'tbody')
    {
        parent::__construct('tr');
        $this->section = $section;
    }
    
    /**
     * Adds a new cell (TTableCell) to the table row.
     *
     * @param mixed $value The content of the cell.
     *
     * @return TTableCell The created table cell.
     * @throws Exception If a null value is passed.
     */
    public function addCell($value)
    {
        if (is_null($value))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Method ^1 does not accept null values', __METHOD__));
        }
        else
        {
            // creates a new Table Cell
            $cell = new TTableCell($value, $this->section == 'thead' ? 'th' : 'td');
            
            parent::add($cell);
            // returns the cell object
            return $cell;
        }
    }
    
    /**
     * Adds multiple elements inside a single table cell.
     *
     * @param mixed ...$cells Each argument represents a cell content.
     *
     * @return TTableCell The created table cell containing multiple elements.
     */
    public function addMultiCell()
    {
        $wrapper = new THBox;
        
        $args = func_get_args();
        if ($args)
        {
            foreach ($args as $arg)
            {
                $wrapper->add($arg);
            }
        }
        
        return $this->addCell($wrapper);
    }
    
    /**
     * Clears all child elements from the row.
     */
    public function clearChildren()
    {
        $this->children = array();
    }
}
