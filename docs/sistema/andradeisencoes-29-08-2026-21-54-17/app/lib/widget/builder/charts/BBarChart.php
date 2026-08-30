<?php

use Adianti\Database\TCriteria;

/**
 * Bar chart widget
 *
 * This class represents a bar chart widget that extends BChart.
 * It allows setting up a grouped bar chart with different configurations.
 *
 * @version    7.4
 * @package    widget
 * @subpackage builder
 * @author     Lucas Tomasi
 */
class BBarChart extends BChart
{
    /**
     * BBarChart constructor.
     *
     * Initializes a bar chart with the provided parameters.
     *
     * @param string        $name        The widget name.
     * @param string|null   $database    The database name.
     * @param string|null   $model       The model class name.
     * @param array         $fieldGroup  The table fields to be used as group in the chart.
     * @param string|null   $fieldValue  The table field to be used for total calculation.
     * @param array         $joins       An array with joins to be used in the select query.
     * @param string        $totalChart  The type of total calculation (default: 'sum'). Options: 'sum', 'max', 'min', 'count', 'avg'.
     * @param TCriteria|null $criteria   A TCriteria object to filter the model (optional).
     */
    public function __construct(String $name, ?String $database = null, ?String $model = null, array $fieldGroup = [], ?String $fieldValue = null, array $joins = [], $totalChart = 'sum', ?TCriteria $criteria = NULL)
    {
        parent::__construct($name, $database, $model, [], $fieldValue, $joins, $totalChart, $criteria);
        $this->setFieldGroup($fieldGroup);
        $this->setType('bar');
        $this->grid = true;
    }

    /**
     * Set the bar chart layout direction.
     *
     * @param string $direction The bar chart direction ('horizontal' or 'vertical').
     */
    public function setLayout($direction)
    {
        $this->barDirection = $direction;
    }

    /**
     * Enable or disable bar stacking.
     *
     * @param bool $stack Whether to stack the bars (default: true).
     */
    public function setStack($stack = true)
    {
        $this->barStack = $stack;
    }

    /**
     * Define a transformer for sub legends (bars).
     *
     * @param callable $transformer A callable function to transform the sub legends.
     */
    public function setTransformerSubLegend(callable $transformer)
    {
        $this->transformerSubLegend = $transformer;
    }

    /**
     * Set the label for the chart values.
     *
     * @param string $labelValue The label to be displayed for chart values.
     */
    public function setLabelValue($labelValue)
    {
        $this->labelValue = $labelValue;
    }

    /**
     * Enable or disable the chart grid.
     *
     * @param bool $showGrid Whether to display the grid (default: true).
     */
    public function showGrid($showGrid = true)
    {
        $this->grid = $showGrid;
    }
}
