<?php

use Adianti\Database\TCriteria;

/**
 * Class BLineChart
 *
 * This class represents a line chart widget, extending BChart. 
 * It supports data grouping, custom field values, joins, filtering criteria, and 
 * various aggregation types.
 *
 * @version    7.4
 * @package    widget
 * @subpackage builder
 * @author     Lucas Tomasi
 */
class BLineChart extends BChart
{
    /**
     * BLineChart constructor.
     *
     * Initializes a line chart widget with the specified parameters.
     *
     * @param string      $name        The name of the widget.
     * @param string|null $database    The name of the database (optional).
     * @param string|null $model       The model class name (optional).
     * @param array       $fieldGroup  The fields to be used as groups in the chart.
     * @param string|null $fieldValue  The field to be used for total calculations.
     * @param array       $joins       The joins to be used in the select query.
     * @param string      $totalChart  The aggregation type (sum, max, min, count, avg) (optional).
     * @param TCriteria|null $criteria The filtering criteria (optional).
     */
    public function __construct(String $name, ?String $database = null, ?String $model = null, array $fieldGroup = [], ?String $fieldValue = null, array $joins = [], $totalChart = 'sum', ?TCriteria $criteria = NULL)
    {
        parent::__construct($name, $database, $model, [], $fieldValue, $joins, $totalChart, $criteria);
        $this->setFieldGroup($fieldGroup);
        $this->setType('line');
    }

    /**
     * Defines a transformer for sub-legends (lines).
     *
     * @param callable $transformer A callable function to transform the sub-legends.
     */
    public function setTransformerSubLegend(callable $transformer)
    {
        $this->transformerSubLegend = $transformer;
    }

    /**
     * Sets whether the grid should be displayed on the chart.
     *
     * @param bool $showGrid Whether to show the grid (default: true).
     */
    public function showGrid($showGrid = true)
    {
        $this->grid = $showGrid;
    }

    /**
     * Sets the label for the chart values.
     *
     * @param string $labelValue The label to be used for the values.
     */
    public function setLabelValue($labelValue)
    {
        $this->labelValue = $labelValue;
    }

    /**
     * Enables the area display on the chart.
     *
     * @param bool $areaRounded Whether the area should be rounded (default: true).
     */
    public function showArea($areaRounded = true)
    {
        $this->area = TRUE;
        $this->areaRounded = $areaRounded;
    }
}