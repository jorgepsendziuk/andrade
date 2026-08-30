<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;
use Adianti\Control\TAction;

/**
 * QR Code Input Reader
 *
 * This class extends TEntry to provide an input field for QR code scanning.
 * It allows defining actions triggered upon input changes.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Lucas Tomasi
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TQRCodeInputReader extends TEntry implements AdiantiWidgetInterface
{
    protected $formName;
    protected $name;
    protected $id;
    protected $size;
    protected $changeFunction;
    protected $changeAction;

    /**
     * Class Constructor
     *
     * Creates a QR code input field with a unique identifier.
     *
     * @param string $name Widget name
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'tqrcodeinputreader_'.mt_rand(1000000000, 1999999999);
        $this->tag->{'inputmode'} = 'numeric';
        $this->tag->{'widget'} = 'tqrcodeinputreader';
        $this->tag->{'autocomplete'} = 'off';
    }

    /**
     * Sets a JavaScript function to be executed when the input value changes.
     *
     * @param string $function JavaScript function name
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Sets an action to be executed when the input value changes.
     *
     * @param TAction $action Action object to be executed on change
     *
     * @throws Exception If the form associated with the field is not properly defined
     */
    public function setChangeAction(TAction $action)
    {
        $this->changeAction = $action;
    }

    /**
     * Displays the QR code input field along with the scan button.
     *
     * This method generates the HTML structure and sets up event handling.
     * It also ensures proper action binding if a change action is set.
     *
     * @throws Exception If the form associated with the field is not properly defined
     */
    public function show()
    {
        $wrapper = new TElement('div');
        $wrapper->{'class'} = 'input-group';
        $wrapper->{'style'} = 'float:inherit;width: 100%';

        $span = new TElement('span');
        $span->{'class'} = 'input-group-addon tqrcodeinputreader';

        $outer_size = 'undefined';
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $outer_size = $this->size;
            $this->size = '100%';
        }

        if ($this->changeAction)
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }

            $string_action = $this->changeAction->serialize(FALSE);
            $this->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
            $this->setProperty('onChange', $this->getProperty('changeaction'), FALSE);
        }
        
        if (isset($this->changeFunction))
        {
            $this->setProperty('changeaction', $this->changeFunction, FALSE);
            $this->setProperty('onChange', $this->changeFunction, FALSE);
        }
        
        $i = new TElement('i');
        $i->{'class'} = 'fa fa-qrcode';
        $span->{'onclick'} = "tqrcodeinputreader_open_reader('{$this->id}');";
        
        $span->add($i);
        ob_start();
        parent::show();
        $child = ob_get_contents();
        ob_end_clean();
        
        $wrapper->add($child);
        $wrapper->add($span);
        $wrapper->show();

        if (!parent::getEditable())
        {
            self::disableField($this->formName, $this->name);
        }
    }
}
