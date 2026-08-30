<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Validates the maximum length of a stripped text.
 *
 * Ensures that the text, after stripping HTML tags and unnecessary spaces, does not exceed the specified maximum length.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TStrippedTextMaxLengthValidator extends TFieldValidator
{
    /**
     * Validates whether a stripped text meets the maximum length requirement.
     *
     * Strips HTML tags and non-breaking spaces before checking the text length.
     *
     * @param string $label Identifies the value to be validated in case of an exception.
     * @param string $value Text value to be validated.
     * @param array $parameters Array containing the maximum allowed length as the first element.
     *
     * @throws Exception If the text exceeds the maximum length.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        $maxvalue = $parameters[0];

        $value = preg_replace('/(<p><br><\/p>)/i', ' ', $value);
        $value = preg_replace('/(<([^>]+)>)/i', '', $value);
        $value = preg_replace('/(&nbsp;)/i', ' ', $value);
        
        $value = strlen($value);
        
        if ($value > $maxvalue)
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 can not be greater than ^2 characters', $label, $maxvalue));
        }
    }
}
