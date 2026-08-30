<?php
namespace Adianti\Validator;

/**
 * TFieldValidator abstract validation class
 *
 * This class serves as a base for validators that enforce constraints on field values.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
abstract class TFieldValidator
{
    /**
     * Validates a given value.
     *
     * @param string $label The field label used for error messages.
     * @param mixed $value The value to be validated.
     * @param mixed|null $parameters Additional parameters for validation.
     *
     * @throws Exception If validation fails.
     */
    abstract public function validate($label, $value, $parameters = NULL);
}
