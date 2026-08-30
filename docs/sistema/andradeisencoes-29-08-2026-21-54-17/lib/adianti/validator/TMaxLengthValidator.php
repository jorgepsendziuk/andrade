<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Maximum length validation
 *
 * Validates that a given value does not exceed a specified maximum length.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMaxLengthValidator extends TFieldValidator
{
    /**
     * Validates if the given value does not exceed the specified maximum length.
     *
     * @param string $label The label identifying the value to be validated.
     * @param string $value The value to be validated.
     * @param array|null $parameters An array containing the maximum allowed length as the first element.
     *
     * @throws Exception If the value length exceeds the maximum allowed.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        $length = $parameters[0];
        
        if (strlen($value) > $length)
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 can not be greater than ^2 characters', $label, $length));
        }
    }
}