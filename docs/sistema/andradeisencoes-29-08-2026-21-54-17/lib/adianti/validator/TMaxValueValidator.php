<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Maximum value validation
 *
 * Validates that a given value does not exceed a specified maximum value.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMaxValueValidator extends TFieldValidator
{
    /**
     * Validates if the given value does not exceed the specified maximum value.
     *
     * @param string $label The label identifying the value to be validated.
     * @param float|int $value The numeric value to be validated.
     * @param array|null $parameters An array containing the maximum allowed value as the first element.
     *
     * @throws Exception If the value exceeds the maximum allowed.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        $maxvalue = $parameters[0];
        
        if ($value > $maxvalue)
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 can not be greater than ^2', $label, $maxvalue));
        }
    }
}