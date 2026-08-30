<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Util\TImage;

use Adianti\Core\AdiantiCoreTranslator;
use Exception;
use ReflectionClass;

/**
 * Record Lookup Widget
 *
 * TSeekButton is a lookup field used to search values from associated entities.
 * It extends TEntry and includes a search button with customizable actions.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TSeekButton extends TEntry implements AdiantiWidgetInterface
{
    private $action;
    private $useOutEvent;
    private $button;
    private $extra_size;
    private $input_size;
    protected $size;
    protected $auxiliar;
    protected $id;
    protected $formName;
    protected $name;
    
    /**
     * Class Constructor
     *
     * @param string      $name Name of the field
     * @param string|null $icon Icon for the lookup button (optional)
     */
    public function __construct($name, $icon = NULL)
    {
        parent::__construct($name);
        $this->useOutEvent = TRUE;
        $this->setProperty('class', 'tfield tseekentry', TRUE);   // classe CSS
        $this->extra_size = 24;
        $this->input_size = '100%';
        $this->size = "100%";
        $this->button = self::createButton($this->name, $icon);
    }
    
    /**
     * Creates a seek button element.
     *
     * @param string      $name Name of the associated field
     * @param string|null $icon Icon for the button (default is a search icon)
     *
     * @return TElement The created button element
     */
    public static function createButton($name, $icon)
    {
        $image = new TImage( $icon ? $icon : 'fa:search');
        $button = new TElement('span');
        $button->{'class'} = 'btn btn-default tseekbutton';
        $button->{'type'} = 'button';
        $button->{'onmouseover'} = "style.cursor = 'pointer'";
        $button->{'name'} = '_' . $name . '_seek';
        $button->{'for'} = $name;
        $button->{'onmouseout'}  = "style.cursor = 'default'";
        $button->add($image);
        
        return $button;
    }
    
    /**
     * Magic method to retrieve properties.
     *
     * @param string $name Property name
     *
     * @return mixed The requested property value
     */
    public function __get($name)
    {
        if ($name == 'button')
        {
            return $this->button;
        }
        else
        {
            return parent::__get($name);
        }
    }
    
    /**
     * Defines the field's width.
     *
     * @param string|int      $width  Width of the field (in pixels or percentage)
     * @param string|int|null $height Height of the field (optional)
     */
    public function setSize($width, $height = NULL)
    {
        if ($this->hasAuxiliar() && empty($height)) // height is passed by BootstrapFormBuilder::wrapField() only
        {
            $this->input_size = $width;
        }
        else
        {
            $this->size = $width;
        }
    }
    
    /**
     * Defines whether the out event should be fired.
     *
     * @param bool $bool True to enable the out event, false to disable it
     */
    public function setUseOutEvent($bool)
    {
        $this->useOutEvent = $bool;
    }
    
    /**
     * Sets the action for the seek button.
     *
     * @param TAction $action Action triggered when clicking the seek button
     */
    public function setAction(TAction $action)
    {
        $this->action = $action;
    }
    
    /**
     * Returns the action associated with the seek button.
     *
     * @return TAction|null The configured action or null if none is set
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Sets an auxiliary field.
     *
     * @param TField $object Auxiliary field object
     */
    public function setAuxiliar($object)
    {
        if (method_exists($object, 'show'))
        {
            $this->auxiliar = $object;
            $this->extra_size *= 2;
            $this->input_size = $this->size;
            $this->size = '100%';
            
            if ($object instanceof TField)
            {
                $this->action->setParameter('receive_field', $object->getName());
            }
        }
    }
    
    /**
     * Checks if an auxiliary field is set.
     *
     * @return bool True if an auxiliary field exists, false otherwise
     */
    public function hasAuxiliar()
    {
        return !empty($this->auxiliar);
    }
    
    /**
     * Sets the extra size for the input field.
     *
     * @param int $extra_size Additional size in pixels
     */
    public function setExtraSize($extra_size)
    {
        $this->extra_size = $extra_size;
    }
    
    /**
     * Retrieves the extra size of the input field.
     *
     * @return int Extra size in pixels
     */
    public function getExtraSize()
    {
        return $this->extra_size;
    }
    
    /**
     * Enables a field in the specified form.
     *
     * @param string $form_name Name of the form
     * @param string $field     Name of the field to be enabled
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tseekbutton_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disables a field in the specified form.
     *
     * @param string $form_name Name of the form
     * @param string $field     Name of the field to be disabled
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tseekbutton_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Displays the widget.
     *
     * @throws Exception If the form name is not set in TForm::setFields()
     */
    public function show()
    {
        
        if (!TForm::getFormByName($this->formName) instanceof TForm)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
        }
        
        $serialized_action = '';
        if ($this->action)
        {
            // get the action class name
            if (is_array($callback = $this->action->getAction()))
            {
                if (is_object($callback[0]))
                {
                    $rc = new ReflectionClass($callback[0]);
                    $class = $rc-> getName ();
                }
                else
                {
                    $class  = $callback[0];
                }
                
                if ($this->useOutEvent)
                {
                    $ajaxAction = new TAction(array($class, 'onSelect'));
                    if (in_array($class, ['TStandardSeek']))
                    {
                        $ajaxAction->setParameter('parent',  $this->action->getParameter('parent'));
                        $ajaxAction->setParameter('database',$this->action->getParameter('database'));
                        $ajaxAction->setParameter('model',   $this->action->getParameter('model'));
                        $ajaxAction->setParameter('display_field', $this->action->getParameter('display_field'));
                        $ajaxAction->setParameter('receive_key',   $this->action->getParameter('receive_key'));
                        $ajaxAction->setParameter('receive_field', $this->action->getParameter('receive_field'));
                        $ajaxAction->setParameter('criteria',      $this->action->getParameter('criteria'));
                        $ajaxAction->setParameter('mask',          $this->action->getParameter('mask'));
                        $ajaxAction->setParameter('operator',      $this->action->getParameter('operator') ? $this->action->getParameter('operator') : 'like');
                    }
                    else
                    {
                        if ($actionParameters = $this->action->getParameters())
                        {
                            foreach ($actionParameters as $key => $value) 
                            {
                                $ajaxAction->setParameter($key, $value);
                            }                    		
                        }                    	                    
                    }
                    $ajaxAction->setParameter('form_name',  $this->formName);
                    
                    $string_action = $ajaxAction->serialize(FALSE);
                    $this->setProperty('seekaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
                    $this->setProperty('onBlur', $this->getProperty('seekaction'), FALSE);
                }
            }
            
            $this->action->setParameter('_field_id',   $this->id);
            $this->action->setParameter('_field_name', $this->name);
            $this->action->setParameter('_form_name',  $this->formName);
            
            $this->action->setParameter('field_name', $this->name);
            $this->action->setParameter('form_name',  $this->formName);
            
            $serialized_action = $this->action->serialize(FALSE);
        }
        
        $this->button->{'onclick'} = "__adianti_post_page_lookup('{$this->formName}', '{$serialized_action}', '{$this->id}', 'callback')";
        
        $wrapper = new TElement('div');
        $wrapper->{'class'} = 'tseek-group';
        
        if (strstr((string) $this->size, '%') !== FALSE)
        {
            $wrapper->{'style'} .= ";width:{$this->size};";
        }
        else
        {
            $wrapper->{'style'} .= ";width:{$this->size}px;";
        }
        
        $this->size = ($this->hasAuxiliar() ? $this->input_size : "calc(100% - {$this->extra_size}px)");
        
        $wrapper->open();
        parent::show();
        
        if(!parent::getEditable())
        {
            $this->button->style .= ' ;display:none; ';
        }
        
        $this->button->show();
        
        if ($this->auxiliar)
        {
            $this->auxiliar->show();
        }
        $wrapper->close();
        
    }
}
