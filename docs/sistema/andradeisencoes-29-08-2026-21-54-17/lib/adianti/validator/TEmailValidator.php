<?php
namespace Adianti\Validator;

use Adianti\Validator\TFieldValidator;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Validator for email addresses.
 *
 * This class ensures that the provided value is a valid email format.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TEmailValidator extends TFieldValidator
{
    /**
     * Validates a given email address or an array of email addresses.
     *
     * @param string $label The field label used for error messages.
     * @param string|array $value The email address or an array of email addresses to be validated.
     * @param mixed|null $parameters Additional parameters for validation (not used).
     *
     * @throws Exception If one or more email addresses are invalid.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        if (!empty($value))
        {
            if(is_array($value))
            {
                foreach($value as $v)
                {
                    self::validate($label, $v, $parameters);
                }
            }
            else
            {
                $filter = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
            
                if ($filter === FALSE)
                {
                    throw new Exception(AdiantiCoreTranslator::translate('The field ^1 contains an invalid e-mail', $label));
                }
            }
        }
    }
}
