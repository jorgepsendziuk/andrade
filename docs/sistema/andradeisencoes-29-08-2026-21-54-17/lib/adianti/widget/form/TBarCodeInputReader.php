<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;
use Adianti\Control\TAction;

/**
 * Barcode Widget
 *
 * This widget extends TEntry and provides a barcode input reader component. 
 * It allows users to scan barcodes and process the input through change functions or actions.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Lucas Tomasi
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TBarCodeInputReader extends TEntry implements AdiantiWidgetInterface
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
     * Initializes a barcode input reader with a unique identifier and default HTML attributes.
     *
     * @param string $name Name of the widget
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'tbarcodeinputreader_'.mt_rand(1000000000, 1999999999);
        //$this->tag->{'inputmode'} = 'numeric';
        $this->tag->{'widget'} = 'tbarcodeinputreader';
        $this->tag->{'autocomplete'} = 'off';
    }

    /**
     * Set a JavaScript function to be executed when the input content changes.
     *
     * @param string $function JavaScript function name or inline script
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Define an action to be executed when the input content changes.
     *
     * @param TAction $action A TAction object representing the action to execute
     *
     * @throws Exception If the form associated with the widget is not properly set
     */
    public function setChangeAction(TAction $action)
    {
        $this->changeAction = $action;
    }

    /**
     * Render the barcode input reader widget on the screen.
     *
     * This method wraps the input field inside a styled div and adds an icon for barcode scanning.
     * If a change action or function is set, it applies the respective JavaScript handlers.
     *
     * @throws Exception If the form name is not set and an action is defined
     */
    public function show()
    {
        $wrapper = new TElement('div');
        $wrapper->{'class'} = 'input-group';
        $wrapper->{'style'} = 'float:inherit;width: 100%';

        $span = new TElement('span');
        $span->{'class'} = 'input-group-addon tbarcodeinputreader';

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
        $i->{'class'} = 'fa fa-barcode';
        $span->{'onclick'} = "tbarcodeinputreader_open_reader('{$this->id}');";
        
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
