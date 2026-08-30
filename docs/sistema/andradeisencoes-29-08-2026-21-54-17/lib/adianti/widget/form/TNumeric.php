<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TEntry;

/**
 * Numeric Widget
 *
 * This class extends TEntry and provides numeric input capabilities.
 * It allows customization of decimal places, separators, and number formatting.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TNumeric extends TEntry implements AdiantiWidgetInterface
{
    /**
     * Class Constructor
     *
     * Initializes a numeric input field with specific formatting options.
     *
     * @param string  $name              Widget name
     * @param int     $decimals          Number of decimal places
     * @param string  $decimalsSeparator Character used as the decimal separator
     * @param string  $thousandSeparator Character used as the thousands separator
     * @param bool    $replaceOnPost     Whether to replace the formatted value on form post
     * @param bool    $reverse           Whether to reverse number input behavior
     * @param bool    $allowNegative     Whether negative values are allowed
     */
    public function __construct($name, $decimals, $decimalsSeparator, $thousandSeparator, $replaceOnPost = true, $reverse = FALSE, $allowNegative = TRUE)
    {
        parent::__construct($name);
        parent::setNumericMask($decimals, $decimalsSeparator, $thousandSeparator, $replaceOnPost, $reverse, $allowNegative);
    }

    /**
     * Sets whether negative values are allowed in the numeric input.
     *
     * @param bool $allowNegative Whether negative values should be permitted
     */
    public function setAllowNegative($allowNegative)
    {
        $this->allowNegative = $allowNegative;
    }
}
