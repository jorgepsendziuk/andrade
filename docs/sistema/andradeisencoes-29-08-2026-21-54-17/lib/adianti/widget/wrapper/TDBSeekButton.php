<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Form\TSeekButton;
use Adianti\Base\TStandardSeek;
use Adianti\Database\TCriteria;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;

use Exception;

/**
 * TDBSeekButton is a lookup field that allows users to search for values in an associated database entity.
 *
 * This widget extends TSeekButton and performs a lookup operation to retrieve values dynamically.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDBSeekButton extends TSeekButton
{
    /**
     * Constructor method
     *
     * Initializes the lookup field and sets up the action parameters for searching in the database.
     *
     * @param string      $name                  The name of the form field
     * @param string      $database              The name of the database connection
     * @param string      $form                  The name of the parent form
     * @param string      $model                 The Active Record model to be searched
     * @param string      $display_field         The field to be searched and displayed
     * @param string|null $receive_key           The form field that receives the primary key (optional)
     * @param string|null $receive_display_field The form field that receives the display field value (optional)
     * @param TCriteria|null $criteria           The filtering criteria for search (optional)
     * @param string      $operator              The comparison operator to use in search (default: 'like')
     * 
     * @throws Exception If any required parameter is missing
     */
    public function __construct($name, $database, $form, $model, $display_field, $receive_key = null, $receive_display_field = null, ?TCriteria $criteria = NULL, $operator = 'like')
    {
        parent::__construct($name);
        
        if (empty($database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }
        
        if (empty($model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }
        
        if (empty($display_field))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'display_field', __CLASS__));
        }
        
        $obj  = new TStandardSeek;
        $ini  = AdiantiApplicationConfig::get();
        $seed = APPLICATION_NAME . ( !empty($ini['general']['seed']) ? $ini['general']['seed'] : 's8dkld83kf73kf094' );
        
        // define the action parameters
        $action = new TAction(array($obj, 'onSetup'));
        $action->setParameter('hash',          md5("{$seed}{$database}{$model}{$display_field}"));
        $action->setParameter('database',      $database);
        $action->setParameter('parent',        $form);
        $action->setParameter('model',         $model);
        $action->setParameter('display_field', $display_field);
        $action->setParameter('receive_key',   !empty($receive_key) ? $receive_key : $name);
        $action->setParameter('receive_field', !empty($receive_display_field) ? $receive_display_field : null);
        $action->setParameter('criteria',      base64_encode(serialize($criteria)));
        $action->setParameter('operator',      ($operator == 'ilike') ? 'ilike' : 'like');
        $action->setParameter('mask',          '');
        $action->setParameter('label',         AdiantiCoreTranslator::translate('Description'));
        parent::setAction($action);
    }
    
    /**
     * Sets a search criteria for the lookup field.
     *
     * @param TCriteria $criteria The filtering criteria to be applied in the lookup search
     */
    public function setCriteria(TCriteria $criteria)
    {
        $this->getAction()->setParameter('criteria', base64_encode(serialize($criteria)));
    }
    
    /**
     * Sets the search operator.
     *
     * Defines whether the search should use 'like' or 'ilike'.
     *
     * @param string $operator The search operator ('like' or 'ilike')
     */
    public function setOperator($operator)
    {
        $this->getAction()->setParameter('operator', ($operator == 'ilike') ? 'ilike' : 'like');
    }
    
    /**
     * Sets the display mask for the lookup field.
     *
     * @param string $mask The format mask to be applied to the displayed value
     */
    public function setDisplayMask($mask)
    {
        $this->getAction()->setParameter('mask', $mask);
    }
    
    /**
     * Sets the display label for the lookup field.
     *
     * @param string $label The label to be displayed
     */
    public function setDisplayLabel($label)
    {
        $this->getAction()->setParameter('label', $label);
    }
    
    /**
     * Defines the field's value and updates the auxiliary field if necessary.
     *
     * If a display field is available, it retrieves its value from the database.
     *
     * @param mixed $value The value to be assigned to the field
     */
    public function setValue($value)
    {
        parent::setValue($value);
        
        if (!empty($this->auxiliar))
        {
            $database = $this->getAction()->getParameter('database');
            $model    = $this->getAction()->getParameter('model');
            $mask     = $this->getAction()->getParameter('mask');
            $display_field = $this->getAction()->getParameter('display_field');
            
            if (!empty($value))
            {
                TTransaction::open($database);
                $activeRecord = new $model($value);
                
                if (!empty($mask))
                {
                    $this->auxiliar->setValue($activeRecord->render($mask));
                }
                else if (isset($activeRecord->$display_field))
                {
                    $this->auxiliar->setValue( $activeRecord->$display_field );
                }
                TTransaction::close();
            }
        }
    }
}
