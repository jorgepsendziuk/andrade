<?php
namespace Adianti\Base;

use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Database\TTransaction;
use Adianti\Database\TRecord;
use Exception;

/**
 * Standard Form Trait
 *
 * This trait provides standard form handling functionality for Adianti framework applications.
 * It includes methods to handle form saving, clearing, and editing, while managing database transactions.
 *
 * @version    7.5
 * @package    base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
trait AdiantiStandardFormTrait
{
    protected $afterSaveAction;
    protected $useMessages;
    
    use AdiantiStandardControlTrait;
    
    /**
     * Sets the action to be executed after saving the form.
     *
     * @param mixed $action The action to be executed after save (typically a TAction instance).
     */
    public function setAfterSaveAction($action)
    {
        $this->afterSaveAction = $action;
    }
    
    /**
     * Defines whether messages should be displayed after form operations.
     *
     * @param bool $bool If true, messages will be shown; otherwise, they will be suppressed.
     */
    public function setUseMessages($bool)
    {
        $this->useMessages = $bool;
    }
    
    /**
     * Saves the form data to the database.
     *
     * This method performs the following operations:
     * - Validates the existence of the database and active record.
     * - Opens a transaction.
     * - Retrieves and validates form data.
     * - Stores the object in the database.
     * - Closes the transaction.
     * - Displays a success message or redirects to the after-save action.
     *
     * If an exception occurs, it rolls back the transaction and displays an error message.
     *
     * @return object|null The saved object if successful, or null in case of an error.
     * @throws Exception If the database or active record is not defined.
     */
    public function onSave()
    {
        try
        {
            if (empty($this->database))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', AdiantiCoreTranslator::translate('Database'), 'setDatabase()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (empty($this->activeRecord))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', 'Active Record', 'setActiveRecord()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            // open a transaction with database
            TTransaction::open($this->database);
            
            // get the form data
            $object = $this->form->getData($this->activeRecord);
            
            // validate data
            $this->form->validate();
            
            // stores the object
            $object->store();
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            // close the transaction
            TTransaction::close();
            
            // shows the success message
            if (isset($this->useMessages) AND $this->useMessages === false)
            {
                AdiantiCoreApplication::loadPageURL( $this->afterSaveAction->serialize() );
            }
            else
            {
                new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction);
            }
            
            return $object;
        }
        catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData();
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Clears the form data.
     *
     * @param array $param The parameters received from the request.
     */
    public function onClear($param)
    {
        $this->form->clear( true );
    }
    
    /**
     * Loads an existing record into the form based on the provided key.
     *
     * If no key is provided, the form is cleared.
     * If an exception occurs, it rolls back the transaction and displays an error message.
     *
     * @param array $param An associative array containing the request parameters, including 'key' to identify the record.
     *
     * @return object|null The retrieved object if found, or null otherwise.
     * @throws Exception If the database or active record is not defined.
     */
    public function onEdit($param)
    {
        try
        {
            if (empty($this->database))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', AdiantiCoreTranslator::translate('Database'), 'setDatabase()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (empty($this->activeRecord))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', 'Active Record', 'setActiveRecord()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (isset($param['key']))
            {
                // get the parameter $key
                $key=$param['key'];
                
                // open a transaction with database
                TTransaction::open($this->database);
                
                $class = $this->activeRecord;
                
                // instantiates object
                $object = new $class($key);
                
                // fill the form with the active record data
                $this->form->setData($object);
                
                // close the transaction
                TTransaction::close();
                
                return $object;
            }
            else
            {
                $this->form->clear( true );
            }
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
}
