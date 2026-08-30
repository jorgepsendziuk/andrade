<?php
namespace Adianti\Base;

use Adianti\Core\AdiantiCoreTranslator;
use Exception;
use ReflectionClass;

/**
 * Standard Control Trait
 *
 * Provides standard functionalities for managing database connections
 * and Active Record classes in the Adianti framework.
 *
 * @version    7.5
 * @package    base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
trait AdiantiStandardControlTrait
{
    protected $database; // Database name
    protected $activeRecord;    // Active Record class name
    
    /**
     * Sets the database connection name.
     *
     * @param string $database The name of the database to be used.
     *
     * @return void
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }
    
    /**
     * Sets the Active Record class to be used.
     *
     * @param string $activeRecord The name of the Active Record class.
     *
     * @throws Exception If the class does not exist or is not a subclass of TRecord.
     * @return void
     */
    public function setActiveRecord($activeRecord)
    {
        if (class_exists($activeRecord))
        {
            if (is_subclass_of($activeRecord, 'TRecord'))
            {
                $this->activeRecord = $activeRecord;
            }
            else
            {
                throw new Exception(AdiantiCoreTranslator::translate('The class ^1 was not accepted as argument. The class informed as parameter must be subclass of ^2.', $activeRecord, 'TRecord'));
            }
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('The class ^1 was not found. Check the class name or the file name. They must match', $activeRecord));
        }
    }
}
