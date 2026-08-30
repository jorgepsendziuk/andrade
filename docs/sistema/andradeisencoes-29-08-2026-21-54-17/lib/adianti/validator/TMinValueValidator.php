<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Minimum value validation
 *
 * Validates that a given value meets a specified minimum value.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMinValueValidator extends TFieldValidator
{
    /**
     * Validates if the given value meets the specified minimum value.
     *
     * @param string $label The label identifying the value to be validated.
     * @param float|int $value The numeric value to be validated.
     * @param array|null $parameters An array containing the minimum required value as the first element.
     *
     * @throws Exception If the value is less than the minimum required.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        if(!empty($parameters[0]))
        {
            $minvalue = $parameters[0];
            
            if ($value < $minvalue)
            {
                throw new Exception(AdiantiCoreTranslator::translate('The field ^1 can not be less than ^2', $label, $minvalue));
            }
        }
    }
}
