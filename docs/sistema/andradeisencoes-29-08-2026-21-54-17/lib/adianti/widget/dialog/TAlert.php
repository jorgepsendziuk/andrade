<?php
namespace Adianti\Widget\Dialog;

use Adianti\Widget\Base\TElement;

/**
 * Class representing an alert message.
 * This class creates a dismissible alert box with a message.
 *
 * @version    7.5
 * @package    widget
 * @subpackage dialog
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TAlert extends TElement
{
    /**
     * TAlert constructor.
     * Initializes an alert message with a specified type and message.
     *
     * @param string $type    The type of the alert (success, info, warning, danger).
     * @param string $message The message to be displayed inside the alert.
     */
    public function __construct($type, $message)
    {
        parent::__construct('div');
        $this->{'class'} = 'talert alert alert-dismissible alert-'.$type;
        $this->{'role'}  = 'alert';
        
        $button = new TElement('button');
        $button->{'type'} = 'button';
        $button->{'class'} = 'close';
        $button->{'data-dismiss'} = 'alert';
        $button->{'aria-label'}   = 'Close';
        
        $span = new TElement('span');
        $span->{'aria-hidden'} = 'true';
        $span->add('&times;');
        $button->add($span);
        
        parent::add($button);
        parent::add($message);
    }
}
