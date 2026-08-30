<?php
namespace Adianti\Validator;

use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Validator for CNPJ (Brazilian business identification number).
 *
 * Supports both the legacy numeric format and the new alphanumeric format
 * introduced by Instrução Normativa RFB nº 2.229/2024, effective for new
 * registrations from July 2026. The first 12 positions may contain digits
 * (0-9) or uppercase letters (A-Z); the last 2 positions (check digits)
 * remain strictly numeric.
 *
 * The check-digit routine still uses módulo 11. Each character is converted
 * to a numeric value using its ASCII code minus 48 (so '0'-'9' => 0-9 and
 * 'A'-'Z' => 17-42), which keeps the algorithm fully backward-compatible
 * with existing numeric CNPJs.
 *
 * @version    7.5
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCNPJValidator extends TFieldValidator
{
    /**
     * Validates a given CNPJ (numeric or alphanumeric).
     *
     * @param string $label The field label used for error messages.
     * @param string $value The CNPJ value to be validated.
     * @param mixed|null $parameters Additional parameters for validation (not used).
     *
     * @throws Exception If the provided CNPJ is invalid.
     */
    public function validate($label, $value, $parameters = NULL)
    {
        // strip the usual mask characters and normalize letters to uppercase
        $cnpj = strtoupper( preg_replace( '@[./-]@', '', (string) $value ) );

        // 12 alphanumeric positions (0-9, A-Z) followed by 2 numeric check digits
        if( !preg_match( '/^[A-Z0-9]{12}[0-9]{2}$/', $cnpj ) )
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 has not a valid CNPJ', $label));
        }

        $digito1 = self::checkDigit( substr($cnpj, 0, 12) );
        $digito2 = self::checkDigit( substr($cnpj, 0, 13) );

        $valid = ( (int) $cnpj[12] === $digito1 and (int) $cnpj[13] === $digito2 );

        if (!$valid)
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 has not a valid CNPJ', $label));
        }
    }

    /**
     * Calculates a módulo 11 check digit for the given CNPJ slice.
     *
     * Each character is converted to a number using (ASCII code - 48),
     * then multiplied by weights running from 2 to 9, right to left.
     *
     * @param string $base The 12 or 13 leading characters of the CNPJ.
     *
     * @return int The resulting check digit (0-9).
     */
    private static function checkDigit($base)
    {
        $sum    = 0;
        $weight = 2;

        for( $i = strlen($base) - 1; $i >= 0; $i-- )
        {
            $sum   += ( ord( $base[$i] ) - 48 ) * $weight;
            $weight = $weight == 9 ? 2 : $weight + 1;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
