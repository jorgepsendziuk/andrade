<?php

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;

/**
 * Class BTableColumnChart
 *
 * This class represents a column chart widget that can be used within a table.
 * It supports functionalities like alignment, ordering, width configuration, 
 * transformations, and aggregation of column values.
 *
 * @version    7.4
 * @package    widget
 * @subpackage builder
 * @author     Lucas Tomasi
 */
class BTableColumnChart extends TElement
{
    protected $name;
    protected $order;
    protected $width;
    protected $align;
    protected $transformer;
    protected $total;
    protected $aggregate;
    protected $label;
    protected $transformerTotal;

    /**
     * BTableColumnChart constructor.
     *
     * Initializes the column chart with a given name, label, alignment, width, and order.
     *
     * @param string $name  The column name.
     * @param string $label The header label of the column.
     * @param string $align The text alignment in the column (default: 'left'). Accepted values: 'left', 'right', 'center'.
     * @param string $width The width of the column (default: empty string).
     * @param string $order The order definition for the column (default: empty string).
     */
    public function __construct(String $name, String $label, $align = 'left', $width = '', $order = '')
    {
        parent::__construct('div');

        $this->name = $name;
        $this->order = $order;
        $this->label = $label;

        $this->setAlign($align);
        $this->setWidth($width);
    }

    /**
     * Retrieves the column name.
     *
     * @return string The name of the column.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the label (header text) of the column.
     *
     * @param string $label The label text to be displayed.
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Retrieves the label of the column.
     *
     * @return string The label text.
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets the column name.
     *
     * @param string $name The name of the column.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the width of the column.
     *
     * @param mixed $width The column width, either as a numeric value (in pixels) or a string.
     */
    public function setWidth($width)
    {
        $this->width = (is_numeric($width) !== FALSE) ? "{$width}px" : $width ;
    }

    /**
     * Retrieves the width of the column.
     *
     * @return string The width of the column.
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets the order property of the column.
     *
     * @param string $order The order configuration for the column.
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Retrieves the order property of the column.
     *
     * @return string The order configuration.
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets the text alignment for the column.
     *
     * @param string $align The text alignment ('left', 'right', 'center').
     *
     * @throws Exception If an invalid alignment is provided.
     */
    public function setAlign($align)
    {
        if (! in_array($align, ['left', 'right', 'center']))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $align, __METHOD__));
        }

        $this->align = $align;
    }

    /**
     * Retrieves the text alignment of the column.
     *
     * @return string The text alignment ('left', 'right', 'center').
     */
    public function getAlign()
    {
        return $this->align;
    }

    /**
     * Sets a transformer function to modify column values before display.
     *
     * @param callable $transformer A callable function to transform column values.
     */
    public function setTransformer(callable $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Retrieves the transformer function for the column.
     *
     * @return callable|null The transformer function, or null if not set.
     */
    public function getTransformer()
    {
        return $this->transformer;
    }

    /**
     * Sets the total calculation method for the column.
     *
     * @param string   $total            The type of total to apply ('sum', 'max', 'min', 'count', 'avg').
     * @param callable|null $transformerTotal An optional transformer function for the total.
     *
     * @throws Exception If an invalid total type is provided.
     */
    public function setTotal($total, ?callable $transformerTotal = null)
    {
        if (! in_array($total, ['sum', 'max', 'min', 'count', 'avg']))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $total, __METHOD__));
        }

        $this->total = $total;
        $this->transformerTotal = $transformerTotal;
    }

    /**
     * Retrieves the total calculation method for the column.
     *
     * @return string The total calculation type ('sum', 'max', 'min', 'count', 'avg').
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Retrieves the transformer function for the total calculation.
     *
     * @return callable|null The transformer function, or null if not set.
     */
    public function getTransformerTotal()
    {
        return $this->transformerTotal;
    }

    /**
     * Sets the aggregate calculation method for the column.
     *
     * @param string $aggregate The aggregate type ('sum', 'max', 'min', 'count', 'avg').
     *
     * @throws Exception If an invalid aggregate type is provided.
     */
    public function setAggregate($aggregate)
    {
        if (! in_array($aggregate, ['sum', 'max', 'min', 'count', 'avg']))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $aggregate, __METHOD__));
        }

        $this->aggregate = $aggregate;
    }

    /**
     * Retrieves the aggregate calculation method for the column.
     *
     * @return string The aggregate type ('sum', 'max', 'min', 'count', 'avg').
     */
    public function getAggregate()
    {
        return $this->aggregate;
    }
}