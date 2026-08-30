<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Minimum length validation
 *
 * Validates that a given value meets a specified minimum length.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMinLengthValidator extends TFieldValidator
{
    /**
     * Validates if the given value meets the specified minimum length.
     *
     * @param string $label The label identifying the value to be validated.
     * @param string $value The value to be validated.
     * @param array|null $parameters An array containing the minimum required length as the first element.
     *
     * @throws Exception If the value length is less than the minimum required.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        $length = $parameters[0];
        
        if (strlen(trim($value)) < $length)
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 can not be less than ^2 characters', $label, $length));
        }
    }
}
