<?php

use Adianti\Database\TCriteria;

/**
 * Donut chart widget
 *
 * This class represents a donut chart widget that extends BChart.
 * It allows setting up a grouped donut chart with various configurations.
 *
 * @version    7.4
 * @package    widget
 * @subpackage builder
 * @author     Lucas Tomasi
 */
class BDonutChart extends BChart
{

    /**
     * BDonutChart constructor.
     *
     * Initializes a donut chart with the provided parameters.
     *
     * @param string        $name        The widget name.
     * @param string|null   $database    The database name.
     * @param string|null   $model       The model class name.
     * @param string        $fieldGroup  The table field to be used as a group in the chart.
     * @param string|null   $fieldValue  The table field to be used for total calculation.
     * @param array         $joins       An array with joins to be used in the select query.
     * @param string        $totalChart  The type of total calculation (default: 'sum'). Options: 'sum', 'max', 'min', 'count', 'avg'.
     * @param TCriteria|null $criteria   A TCriteria object to filter the model (optional).
     */
    public function __construct(String $name, ?String $database = null, ?String $model = null, String $fieldGroup = '', ?String $fieldValue = null, array $joins = [], $totalChart = 'sum', ?TCriteria $criteria = NULL)
    {
        parent::__construct($name, $database, $model, [], $fieldValue, $joins, $totalChart, $criteria);
        $this->setFieldGroup($fieldGroup);
        $this->setType('donut');
    }

    /**
     * Set the field group for the donut chart.
     *
     * @param string $fieldGroup The field to be used as a group in the donut chart.
     */
    public function setFieldGroup($fieldGroup)
    {
        parent::setFieldGroup([$fieldGroup]);
    }
}