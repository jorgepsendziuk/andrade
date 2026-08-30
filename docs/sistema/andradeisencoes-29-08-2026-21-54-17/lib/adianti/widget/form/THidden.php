<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TField;

/**
 * Hidden field
 *
 * This class represents a hidden input field, extending the TField class.
 * It is used to store data that should not be visible in the user interface
 * but can be submitted with a form.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class THidden extends TField implements AdiantiWidgetInterface
{
    protected $id;
    
    /**
     * Class Constructor
     *
     * Initializes a hidden field with a unique identifier.
     *
     * @param string $name The name of the field
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'thidden_' . mt_rand(1000000000, 1999999999);
    }
    
    /**
     * Retrieve the submitted value from the request
     *
     * This method fetches the posted data for the hidden field from the $_POST superglobal.
     *
     * @return string The value submitted in the request, or an empty string if not set
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            return $_POST[$name];
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Render the hidden field
     *
     * This method sets up the attributes for the HTML input element and displays it on the page.
     * The field is rendered as a hidden input with a unique identifier and predefined attributes.
     *
     * @return void
     */
    public function show()
    {
        // set the tag properties
        $this->tag->{'name'}   = $this->name;  // tag name
        $this->tag->{'value'}  = $this->value; // tag value
        $this->tag->{'type'}   = 'hidden';     // input type
        $this->tag->{'widget'} = 'thidden';
        $this->tag->{'style'}  = "width:{$this->size}";
        
        if ($this->id and empty($this->tag->{'id'}))
        {
            $this->tag->{'id'} = $this->id;
        }
        
        // shows the widget
        $this->tag->show();
    }
}