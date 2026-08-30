<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Exception;

/**
 * Multi Entry Widget
 *
 * This widget allows the selection of multiple items in a form field.
 * It extends the TSelect component and implements AdiantiWidgetInterface.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Matheus Agnes Dias
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMultiEntry extends TSelect implements AdiantiWidgetInterface
{
    protected $id;
    protected $items;
    protected $size;
    protected $height;
    protected $maxSize;
    protected $editable;
    protected $changeAction;
    protected $changeFunction;
    
    /**
     * Class Constructor
     *
     * Initializes the widget with a unique ID and default configurations.
     *
     * @param string $name The name of the widget
     */
    public function __construct($name)
    {
        // executes the parent class constructor
        parent::__construct($name);
        $this->id   = 'tmultientry_'.mt_rand(1000000000, 1999999999);

        $this->height = 38;
        $this->maxSize = 0;
        
        $this->tag->{'component'} = 'multientry';
        $this->tag->{'widget'} = 'tmultientry';
    }
    
    /**
     * Defines the widget's size
     *
     * @param string|int $width  The width of the widget (can be percentage or pixels)
     * @param string|int|null $height The height of the widget (optional, defaults to null)
     */
    public function setSize($width, $height = NULL)
    {
        $this->size   = $width;
        if ($height)
        {
            $this->height = $height;
        }
    }

    /**
     * Defines the maximum number of items that can be selected
     *
     * @param int $maxsize The maximum number of selectable items
     */
    public function setMaxSize($maxsize)
    {
        $this->maxSize = $maxsize;
    }
    
    /**
     * Enables the specified field
     *
     * @param string $form_name The name of the form
     * @param string $field The name of the field to enable
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tmultisearch_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables the specified field
     *
     * @param string $form_name The name of the form
     * @param string $field The name of the field to disable
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tmultisearch_disable_field('{$form_name}', '{$field}'); " );
    }

    /**
     * Clears the specified field
     *
     * @param string $form_name The name of the form
     * @param string $field The name of the field to clear
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tmultisearch_clear_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Renders the selected items as HTML option elements
     *
     * @param bool $with_titles Whether to include titles in the options
     */
    protected function renderItems( $with_titles = true)
    {
        if (parent::getValue())
        {
            // iterate the combobox items
            foreach (parent::getValue() as $item)
            {
                // creates an <option> tag
                $option = new TElement('option');
                $option->{'value'} = $item;  // define the index
                $option->add($item);      // add the item label
                
                if ($with_titles)
                {
                    $option->{'title'} = $item;  // define the title
                }
                
                // mark as selected
                $option->{'selected'} = 1;
                
                $this->tag->add($option);
            }
        }
    }
    
    /**
     * Displays the widget on the screen
     *
     * It renders the component, applies styles, and initializes necessary scripts.
     *
     * @throws Exception If the form is not properly set when using change actions
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'name'}  = $this->name.'[]';    // tag name
        $this->tag->{'id'}  = $this->id;    // tag name
        
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
            $size  = "{$this->size}";
        }
        else
        {
            $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
            $size  = "{$this->size}px";
        }
        
        $change_action = 'function() {}';
        
        $this->renderItems( false );
        
        if ($this->editable)
        {
            if (isset($this->changeAction))
            {
                if (!TForm::getFormByName($this->formName) instanceof TForm)
                {
                    throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
                }
                
                $string_action = $this->changeAction->serialize(FALSE);
                $change_action = "function() { __adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback'); }";
            }
            else if (isset($this->changeFunction))
            {
                $change_action = "function() { $this->changeFunction }";
            }
            $this->tag->show();
            TScript::create(" tmultientry_start( '{$this->id}', '{$this->maxSize}', '{$size}', '{$this->height}px', $change_action ); ");
        }
        else
        {
            $this->tag->show();
            TScript::create(" tmultientry_start( '{$this->id}', '{$this->maxSize}', '{$size}', '{$this->height}px', $change_action ); ");
            TScript::create(" tmultientry_disable_field( '{$this->formName}', '{$this->name}'); ");
        }
    }
}
