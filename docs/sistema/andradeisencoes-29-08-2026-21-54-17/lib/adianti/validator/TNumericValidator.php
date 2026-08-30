<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Numeric validation
 *
 * Validates that a given value is numeric.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TNumericValidator extends TFieldValidator
{
    /**
     * Validates if the given value is numeric.
     *
     * @param string $label The label identifying the value to be validated.
     * @param mixed $value The value to be validated.
     * @param array|null $parameters Additional parameters (not used in this validation).
     *
     * @throws Exception If the value is not numeric.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        if (!is_numeric($value))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 must be numeric', $label));
        }
    }
}
