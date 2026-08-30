<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TField;

/**
 * RadioButton Widget
 *
 * This class extends TField to provide a radio button input element.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TRadioButton extends TField implements AdiantiWidgetInterface
{
    private $checked;
   
    /**
     * Class Constructor
     *
     * Initializes a radio button input with a unique identifier.
     *
     * @param string $name Widget name
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'tradiobutton_' . mt_rand(1000000000, 1999999999);
        $this->tag->{'class'} = '';
    }
    
    /**
     * Displays the radio button on the screen.
     *
     * This method configures the radio button's properties and applies necessary restrictions
     * if the field is not editable.
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'name'}  = $this->name;
        $this->tag->{'value'} = $this->value;
        $this->tag->{'type'}  = 'radio';
        
        if ($this->id and empty($this->tag->{'id'}))
        {
            $this->tag->{'id'} = $this->id;
        }
        
        // verify if the field is not editable
        if (!parent::getEditable())
        {
            // make the widget read-only
            //$this->tag-> disabled   = "1"; // the value don't post
            $this->tag->{'onclick'} = "return false;";
            $this->tag->{'style'}   = 'pointer-events:none';
            $this->tag->{'tabindex'} = '-1';
        }
        // show the tag
        $this->tag->show();
    }
}
