<?php
namespace Adianti\Base;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;

use Exception;

/**
 * Standard List Trait
 *
 * Provides standard list functionalities, including inline editing, 
 * total row display, and batch deletion for Adianti Framework.
 *
 * This trait must be used within a class that has attributes such as 
 * `$database`, `$activeRecord`, and `$formgrid` defined.
 *
 * @version    7.5
 * @package    base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
trait AdiantiStandardListTrait
{
    protected $totalRow;
    
    use AdiantiStandardCollectionTrait;
    use AdiantiStandardListExportTrait;
    
    /**
     * Enables the display of a total row at the bottom of the list.
     *
     * The total row will display the count of records.
     *
     * @return void
     */
    public function enableTotalRow()
    {
        $this->setAfterLoadCallback( function($datagrid, $information) {
            $tfoot = new TElement('tfoot');
            $tfoot->{'class'} = 'tdatagrid_footer';
            $row = new TElement('tr');
            $tfoot->add($row);
            $datagrid->add($tfoot);
            
            $row->{'style'} = 'height: 30px';
            $cell = new TElement('td');
            $cell->add( $information['count'] . ' ' . AdiantiCoreTranslator::translate('Records'));
            $cell->{'colspan'} = $datagrid->getTotalColumns();
            $cell->{'style'} = 'text-align:center';
            
            $row->add($cell);
        });
    }
    
    /**
     * Handles inline record editing.
     *
     * Updates a specific field of a record directly from the list interface.
     *
     * @param array $param Associative array containing:
     *     - `key` (mixed) The ID of the record.
     *     - `field` (string) The attribute name to be updated.
     *     - `value` (mixed) The new value for the attribute.
     *
     * @return void
     */
    public function onInlineEdit($param)
    {
        try
        {
            // get the parameter $key
            $field = $param['field'];
            $key   = $param['key'];
            $value = $param['value'];
            
            // open a transaction with database
            TTransaction::open($this->database);
            
            // instantiates object {ACTIVE_RECORD}
            $class = $this->activeRecord;
            
            // instantiates object
            $object = new $class($key);
            
            // deletes the object from the database
            $object->{$field} = $value;
            $object->store();
            
            // close the transaction
            TTransaction::close();
            
            // reload the listing
            $this->onReload($param);
            // shows the success message
            new TMessage('info', AdiantiCoreTranslator::translate('Record updated'));
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Displays a confirmation dialog before deleting multiple records.
     *
     * Retrieves selected records from the form grid and prompts the user 
     * for confirmation before proceeding with deletion.
     *
     * @param array $param Request parameters.
     *
     * @return void
     */
    public function onDeleteCollection( $param )
    {
        $data = $this->formgrid->getData(); // get selected records from datagrid
        $this->formgrid->setData($data); // keep form filled
        
        if ($data)
        {
            $selected = array();
            
            // get the record id's
            foreach ($data as $index => $check)
            {
                if ($check == 'on')
                {
                    $selected[] = substr($index,5);
                }
            }
            
            if ($selected)
            {
                // encode record id's as json
                $param['selected'] = json_encode($selected);
                
                // define the delete action
                $action = new TAction(array($this, 'deleteCollection'));
                $action->setParameters($param); // pass the key parameter ahead
                
                // shows a dialog to the user
                new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
            }
        }
    }
    
    /**
     * Deletes multiple records from the database.
     *
     * This method is executed after user confirmation and removes all 
     * selected records in a batch operation.
     *
     * @param array $param Associative array containing:
     *     - `selected` (string) JSON-encoded array of record IDs to be deleted.
     *
     * @return void
     */
    public function deleteCollection($param)
    {
        // decode json with record id's
        $selected = json_decode($param['selected']);
        
        try
        {
            TTransaction::open($this->database);
            if ($selected)
            {
                // delete each record from collection
                foreach ($selected as $id)
                {
                    $class = $this->activeRecord;
                    $object = new $class;
                    $object->delete( $id );
                }
                $posAction = new TAction(array($this, 'onReload'));
                $posAction->setParameters( $param );
                new TMessage('info', AdiantiCoreTranslator::translate('Records deleted'), $posAction);
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
