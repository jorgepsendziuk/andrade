<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;

/**
 * Represents a clickable action link with customizable appearance.
 *
 * This class extends TTextDisplay and allows the creation of a link element (`<a>`) 
 * with an associated action (`TAction`). It supports optional customization of 
 * text color, size, decoration, and an icon.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TActionLink extends TTextDisplay
{
    private $action;

    /**
     * Initializes a new instance of the TActionLink class.
     *
     * @param string   $value      The text content of the link.
     * @param TAction  $action     The action associated with the link.
     * @param string|null $color   The text color (optional).
     * @param string|null $size    The text size (optional).
     * @param string|null $decoration Text decorations (optional, can be "b" for bold, "i" for italic, "u" for underline).
     * @param string|null $icon    The optional icon path to be displayed before the text.
     */
    public function __construct($value, TAction $action, $color = null, $size = null, $decoration = null, $icon = null)
    {
        if ($icon)
        {
            $value = new TImage($icon) . $value;
        }
        
        parent::__construct($value, $color, $size, $decoration);
        parent::setName('a');
        
        $this->action = $action;

        $this->{'href'} = $action->serialize();
        $this->{'generator'} = 'adianti';

        if($action->isDisabled())
        {
            unset($this->generator);
            $this->disabled = 'disabled';
        }
        elseif($action->isHidden())
        {
            $this->hide();
        }
    }

    /**
     * Retrieves the associated action of the link.
     *
     * @return TAction The action object linked to this element.
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Assigns a JavaScript function to be executed when the link is clicked.
     *
     * @param string $function The JavaScript code to execute on click.
     */
    public function addFunction($function)
    {
        if ($function)
        {
            $this->{'onclick'} = $function.';';
        }
    }

    /**
     * Adds a CSS class to the link element.
     *
     * @param string $class The CSS class to be added.
     */
    public function addStyleClass($class)
    {
        $this->{'class'} .= " {$class}";
    }
}
