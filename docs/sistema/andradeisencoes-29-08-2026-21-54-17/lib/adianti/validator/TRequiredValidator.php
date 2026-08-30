<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
  * Validates a required field.
 *
 * Ensures that the provided value is not empty, null, or an array with empty content.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TRequiredValidator extends TFieldValidator
{
    /**
     * Validates a given value as required.
     *
     * Checks whether the value is null, an empty string, or an empty array.
     *
     * @param string $label Identifies the value to be validated in case of an exception.
     * @param mixed $value Value to be validated.
     * @param mixed|null $parameters Additional parameters for validation (not used).
     *
     * @throws Exception If the value is empty or null.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        $scalar_empty = function($test) {
            return ( is_scalar($test) AND !is_bool($test) AND trim($test) == '' );
        };
        
        if ( (is_null($value))
          OR ($scalar_empty($value))
          OR (is_array($value) AND count($value)==1 AND isset($value[0]) AND $scalar_empty($value[0]))
          OR (is_array($value) AND empty($value)) )
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 is required', $label));
        }
    }
}