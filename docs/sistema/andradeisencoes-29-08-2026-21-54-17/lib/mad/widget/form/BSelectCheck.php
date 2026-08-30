
<?php
use Adianti\Widget\Form\TSelect;
use Adianti\Widget\Base\TScript;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Form\TForm;

/**
 * BSelectCheck Widget
 * A select widget with checkboxes for multiple selection
 * 
 * @version    4.0
 * @package    widget
 * @subpackage form
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */
class BSelectCheck extends TSelect
{
    /**
     * Class Constructor
     * @param string $name widget's name
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'bselectcheck_' . mt_rand(1000000000, 1999999999);

        $this->withTitles = false;
    }
    
    /**
     * Shows the widget on the screen
     * Renders the select with checkboxes and initializes the JavaScript functionality
     * 
     * The widget will:
     * - Add multiple selection capability (name becomes an array)
     * - Apply the defined size and height styles
     * - Handle change actions and functions if defined
     * - Apply read-only state if not editable
     * - Initialize the bselectcheck JavaScript functionality
     */
    public function show()
    {
        $this->setDefaultOption(FALSE);

        // define the tag properties
        $this->tag->{'name'}  = $this->name.'[]';    // tag name
        $this->tag->{'id'}    = $this->id;
        
        $this->setProperty('style', (strstr((string) $this->size, '%') !== FALSE)   ? "width:{$this->size}"    : "width:{$this->size}px",   false); //aggregate style info
        
        if (!empty($this->height))
        {
            $this->setProperty('style', (strstr($this->height, '%') !== FALSE) ? "height:{$this->height}" : "height:{$this->height}px", false); //aggregate style info
        }
        
        // verify whether the widget is editable
        if (parent::getEditable())
        {
            if (isset($this->changeAction))
            {
                if (!TForm::getFormByName($this->formName) instanceof TForm)
                {
                    throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
                }
                
                $string_action = $this->changeAction->serialize(FALSE);
                $this->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
                $this->setProperty('onChange', $this->getProperty('changeaction'));
            }
            
            if (isset($this->changeFunction))
            {
                $this->setProperty('changeaction', $this->changeFunction, FALSE);
                $this->setProperty('onChange', $this->changeFunction, FALSE);
            }
        }
        else
        {
            // make the widget read-only
            $this->tag->{'onclick'} = "return false;";
            $this->tag->{'style'}  .= ';pointer-events:none';
            $this->tag->{'class'}   = 'tselect_disabled'; // CSS
        }
        
        $search_word = !empty($this->getProperty('placeholder'))? $this->getProperty('placeholder') : AdiantiCoreTranslator::translate('Select');

        $this->tag->{'role'} = 'bselectcheck';
        $this->tag->{'placeholder'} = $search_word;

        // shows the widget
        $this->renderItems( $this->withTitles );
        $this->tag->show();
        
        TScript::create("bselectcheck_start('#{$this->id}', '{$search_word}');");
    }
}
