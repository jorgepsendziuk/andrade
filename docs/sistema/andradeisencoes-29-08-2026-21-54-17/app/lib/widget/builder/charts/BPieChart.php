<?php

use Adianti\Database\TCriteria;

/**
 * Class BPieChart
 *
 * This class represents a pie chart widget, extending BChart.
 * It supports data grouping, custom field values, joins, filtering criteria, and 
 * various aggregation types.
 *
 * @version    7.4
 * @package    widget
 * @subpackage builder
 * @author     Lucas Tomasi
 */
class BPieChart extends BChart
{
    /**
     * BPieChart constructor.
     *
     * Initializes a pie chart widget with the specified parameters.
     *
     * @param string      $name        The name of the widget.
     * @param string|null $database    The name of the database (optional).
     * @param string|null $model       The model class name (optional).
     * @param string      $fieldGroup  The field to be used as a group in the chart.
     * @param string|null $fieldValue  The field to be used for total calculations.
     * @param array       $joins       The joins to be used in the select query.
     * @param string      $totalChart  The aggregation type (sum, max, min, count, avg) (optional).
     * @param TCriteria|null $criteria The filtering criteria (optional).
     */
    public function __construct(String $name, ?String $database = null, ?String $model = null, String $fieldGroup = '', ?String $fieldValue = null, array $joins = [], $totalChart = 'sum', ?TCriteria $criteria = NULL)
    {
        parent::__construct($name, $database, $model, [], $fieldValue, $joins, $totalChart, $criteria);
        $this->setFieldGroup($fieldGroup);
        $this->setType('pie');
    }

    /**
     * Sets the field group for the chart.
     *
     * @param string $fieldGroup The field to be used as a group in the pie chart.
     */
    public function setFieldGroup($fieldGroup)
    {
        parent::setFieldGroup([$fieldGroup]);
    }
}