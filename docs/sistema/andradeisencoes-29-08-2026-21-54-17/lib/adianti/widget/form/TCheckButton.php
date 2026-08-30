<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TLabel;
use Adianti\Control\TAction;

/**
 * CheckButton widget
 *
 * This class represents a checkbox button widget.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCheckButton extends TField implements AdiantiWidgetInterface
{
    private $indexValue;
    private $useSwitch;
    private $labelClass;
    private $inactiveIndexValue;
    private $changeAction;
    protected $changeFunction;
    private $cardTitle;
    private $cardDescription;
    private $cardLayout;
    
    /**
     * Class Constructor
     *
     * Initializes a new instance of the TCheckButton class.
     *
     * @param string $name The name of the widget.
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'tcheckbutton_' . mt_rand(1000000000, 1999999999);
        $this->tag->{'class'} = '';
        $this->useSwitch  = FALSE;
        $this->cardLayout = false;
    }
    
    /**
     * Enables or disables the switch display mode
     *
     * @param bool   $useSwitch  Whether to display the checkbox as a switch (default: TRUE).
     * @param string $labelClass The CSS class to be applied to the switch label (default: 'blue').
     */
    public function setUseSwitch($useSwitch = TRUE, $labelClass = 'blue')
    {
       $this->labelClass = 'tswitch ' . $labelClass;
       $this->useSwitch  = $useSwitch;
    }

    /**
     * Sets the index value for the checkbox button
     *
     * @param mixed $index The value that represents the checked state.
     */
    public function setIndexValue($index)
    {
        $this->indexValue = $index;
    }

    /**
     * Sets the index value for the checkbox button when inactive
     *
     * @param mixed $inactiveIndexValue The value representing the unchecked state.
     */
    public function setInactiveIndexValue($inactiveIndexValue)
    {
        $this->inactiveIndexValue = $inactiveIndexValue;
    }

    /**
     * Sets an action to be executed when the checkbox state changes
     *
     * @param TAction $action The action to be executed.
     *
     * @throws Exception If the provided action is not static.
     */
    public function setChangeAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->changeAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Sets a JavaScript function to be executed when the checkbox state changes
     *
     * @param string $function The JavaScript function to execute on change.
     */
    public function setChangeFunction($function)
    {
        $this->changeFunction = $function;
    }

    /**
     * Retrieves the posted data for the checkbox
     *
     * @return mixed The value of the checkbox if checked, the inactive index value if defined, or an empty string.
     */
    public function getPostData()
    {
        if (isset($_POST[$this->name]))
        {
            return $_POST[$this->name];
        }
        elseif($this->inactiveIndexValue)
        {
            return $this->inactiveIndexValue;
        }

        return '';
    }

    public function enableCardLayout($title, $description)
    {
        $this->cardTitle = $title;
        $this->cardDescription = $description;
        $this->cardLayout = true;
    }
    
    /**
     * Renders the checkbox widget
     *
     * Displays the checkbox on the screen, handling attributes, change actions, and switch mode if enabled.
     *
     * @throws Exception If the form is not properly set when using change actions.
     */
    public function show()
    {
        // define the tag properties for the checkbutton
        $this->tag->{'name'}  = $this->name;    // tag name
        $this->tag->{'type'}  = 'checkbox';     // input type
        $this->tag->{'value'} = $this->indexValue;   // value
        
        if ($this->id and empty($this->tag->{'id'}))
        {
            $this->tag->{'id'} = $this->id;
        }
        
        // compare current value with indexValue
        if ($this->indexValue == $this->value AND !(is_null($this->value)) AND strlen((string) $this->value) > 0)
        {
            $this->tag->{'checked'} = '1';
        }
        
        $this->tag->{"data-value-on"} = '';
        $this->tag->{"data-value-off"} = '';
        if($this->indexValue)
        {
            $this->tag->{"data-value-on"} = $this->indexValue;
        }
        
        if($this->inactiveIndexValue)
        {
            $this->tag->{"data-value-off"} = $this->inactiveIndexValue;
        }
        
        if (isset($this->changeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->changeAction->serialize(FALSE);
            
            $this->tag->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
            $this->tag->setProperty('onChange', $this->tag->getProperty('changeaction'), FALSE);
        }
        
        if (isset($this->changeFunction))
        {
            $this->tag->setProperty('changeaction', $this->changeFunction, FALSE);
            $this->tag->setProperty('onChange', $this->changeFunction, FALSE);
        }

        // check whether the widget is non-editable
        if (!parent::getEditable())
        {
            // make the widget read-only
            //$this->tag-> disabled   = "1"; // the value don't post
            $this->tag->{'onclick'} = "return false;";
            $this->tag->{'style'}   = 'pointer-events:none';
            $this->tag->{'tabindex'} = '-1';
        }

        if($this->cardLayout)
        {
            // Container principal
            $checkCardItem = new TElement('div');
            $checkCardItem->class = 'checkCard-item';

            // Container do conteúdo (título e descrição)
            $checkCardContent = new TElement('div');
            $checkCardContent->class = 'checkCard-content';

            // Título
            $checkCardTitle = new TElement('div');
            $checkCardTitle->class = 'checkCard-title';
            $checkCardTitle->add($this->cardTitle);

            // Descrição
            $checkCardDescription = new TElement('div');
            $checkCardDescription->class = 'checkCard-description';
            $checkCardDescription->add($this->cardDescription);

            // Label do toggle
            $toggleLabel = new TElement('label');
            $toggleLabel->class = 'toggle';

            // Span do slider
            $toggleSlider = new TLabel('');
            $toggleSlider->class = 'tswitch ' . $this->labelClass;
            $toggleSlider->for = $this->id;

            $this->tag->class = 'filled-in btn-tswitch';

            // Montando a estrutura
            $checkCardContent->add($checkCardTitle);
            $checkCardContent->add($checkCardDescription);

            $toggleLabel->add($this->tag);
            $toggleLabel->add($toggleSlider);

            $checkCardItem->add($checkCardContent);
            $checkCardItem->add($toggleLabel);

            $checkCardItem->show();
        }
        else if ($this->useSwitch)
        {
            $obj = new TLabel('');
            $obj->{'class'} = 'tswitch ' . $this->labelClass;
            $obj->{'for'} = $this->id;

            $this->tag->{'class'} = 'filled-in btn-tswitch';

            $wrapper = new TElement('div');
            $wrapper->{'style'} = 'display:inline-flex;align-items:center;';
            $wrapper->add($this->tag);
            $wrapper->add($obj);
            $wrapper->show();
        }
        else
        {
            // shows the tag
            $this->tag->show();
        }

    }
}
