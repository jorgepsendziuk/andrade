<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Validates a list of required fields.
 *
 * This validator ensures that each value in a list is validated as required.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TRequiredListValidator extends TFieldValidator
{
    /**
     * Validates a given list of values.
     *
     * Iterates through each value in the list and applies the required field validation.
     *
     * @param string $label Identifies the value to be validated in case of an exception.
     * @param array $values List of values to be validated.
     * @param mixed|null $parameters Additional parameters for validation (not used).
     *
     * @throws Exception If any value in the list is empty or null.
     */
    public function validate($label, $values, $parameters = NULL)
    {
        if($values)
        {
            foreach ($values as $value)
            {
                (new TRequiredValidator)->validate($label, $value);
            }
        }
    }
}