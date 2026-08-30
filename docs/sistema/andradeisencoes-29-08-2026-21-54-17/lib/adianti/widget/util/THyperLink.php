<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;

/**
 * Represents a hyperlink element in the Adianti Framework.
 * This class extends TTextDisplay to create a clickable text element
 * that can redirect users to a specified location.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class THyperLink extends TTextDisplay
{
    /**
     * Class Constructor
     * Initializes a hyperlink with text content, destination URL, optional styles, and an optional icon.
     *
     * @param string      $value      The text content of the hyperlink.
     * @param string      $location   The URL or file location the hyperlink points to.
     * @param string|null $color      (Optional) The text color.
     * @param int|null    $size       (Optional) The text size.
     * @param string|null $decoration (Optional) Text decoration: 'b' for bold, 'i' for italic, 'u' for underline.
     * @param string|null $icon       (Optional) Path to an image icon to be displayed before the text.
     */
    public function __construct($value, $location, $color = null, $size = null, $decoration = null, $icon = null)
    {
        if ($icon)
        {
            $value = new TImage($icon) . $value;
        }
        
        parent::__construct($value, $color, $size, $decoration);
        parent::setName('a');
        
        if (file_exists($value))
        {
            $this->{'href'} = 'download.php?file='.$location;
        }
        else
        {
            $this->{'href'} = $location;
        }
        
        $this->{'target'} = 'newwindow';
    }
}
