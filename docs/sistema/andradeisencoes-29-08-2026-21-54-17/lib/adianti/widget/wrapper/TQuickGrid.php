<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Control\TAction;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;

/**
 * Provides a simplified interface for creating data grids with columns and actions.
 * This class extends TDataGrid and allows for quick setup of data grids.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TQuickGrid extends TDataGrid
{
    /**
     * Adds a column to the data grid.
     *
     * @param string   $label   Column label.
     * @param string   $name    Column field name.
     * @param string   $align   Column alignment (default: 'left').
     * @param int      $size    Column width in pixels (default: 200).
     * @param TAction|null $action  Sorting action associated with the column (optional).
     * @param array|null   $param   Parameters for the action (optional).
     *
     * @return TDataGridColumn The created data grid column instance.
     */
    public function addQuickColumn($label, $name, $align = 'left', $size = 200, ?TAction $action = null, $param = NULL)
    {
        // creates a new column
        $object = new TDataGridColumn($name, $label, $align, $size);
        
        if ($action instanceof TAction)
        {
            // create ordering
            $action->setParameter($param[0], $param[1]);
            $object->setAction($action);
        }
        // add the column to the datagrid
        parent::addColumn($object);
        return $object;
    }
    
    /**
     * Adds an action to the data grid.
     *
     * @param string          $label  Action label.
     * @param TDataGridAction $action Data grid action object.
     * @param string|array    $field  Field(s) to be used in the action.
     * @param string|null     $icon   Action icon (optional).
     *
     * @return TDataGridAction The created data grid action instance.
     */
    public function addQuickAction($label, TDataGridAction $action, $field, $icon = NULL)
    {
        $action->setLabel($label);
        if ($icon)
        {
            $action->setImage($icon);
        }
        
        if (is_array($field))
        {
            $action->setFields($field);
        }
        else
        {
            $action->setField($field);
        }
        
        // add the datagrid action
        parent::addAction($action);
        
        return $action;
    }
}
