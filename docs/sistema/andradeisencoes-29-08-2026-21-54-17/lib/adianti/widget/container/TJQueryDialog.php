<?php
namespace Adianti\Widget\Container;

use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * TJQueryDialog is a container for displaying modal dialogs using jQuery UI.
 * It supports customization of size, position, actions, and various properties such as draggable, resizable, and modal behavior.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TJQueryDialog extends TElement
{
    private $actions;
    private $width;
    private $height;
    private $top;
    private $left;
    private $modal;
    private $draggable;
    private $resizable;
    private $useOKButton;
    private $stackOrder;
    private $closeAction;
    private $closeEscape;
    private $dialogClass;
    
    /**
     * Class constructor.
     * Initializes a new jQuery dialog container with default properties.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->useOKButton = TRUE;
        $this->top = NULL;
        $this->left = NULL;
        $this->modal = 'true';
        $this->draggable = 'true';
        $this->resizable = 'true';
        $this->stackOrder = 2000;
        $this->closeEscape = true;
        $this->dialogClass = '';
        
        $this->{'id'} = 'jquery_dialog_'.mt_rand(1000000000, 1999999999);
        $this->{'style'} = "overflow:auto";
    }
    
    /**
     * Disables closing the dialog using the Escape key.
     */
    public function disableEscape()
    {
        $this->closeEscape = false;
    }
    
    /**
     * Disables scrolling within the dialog.
     */
    public function disableScrolling()
    {
        $this->{'style'} = "overflow: hidden";
    }
    
    /**
     * Sets the CSS class for the dialog.
     *
     * @param string $class The class name to be applied.
     */
    public function setDialogClass($class)
    {
        $this->dialogClass = $class;
    }
    
    /**
     * Sets the action to be executed when the dialog is closed.
     *
     * @param TAction $action The action to be executed on close. Must be static.
     *
     * @throws Exception If the action is not static.
     */
    public function setCloseAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->closeAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Enables or disables the default OK button in the dialog.
     *
     * @param bool $bool Whether to display the OK button (true or false).
     */
    public function setUseOKButton($bool)
    {
        $this->useOKButton = $bool;
    }
    
    /**
     * Sets the dialog title.
     *
     * @param string $title The title of the dialog.
     */
    public function setTitle($title)
    {
        $this->{'title'} = $title;
    }
    
    /**
     * Enables or disables modal mode for the dialog.
     *
     * @param bool $bool If true, the dialog will be modal; otherwise, it will not.
     */
    public function setModal($bool)
    {
        $this->modal = $bool ? 'true' : 'false';
    }
    
    /**
     * Enables or disables the ability to resize the dialog.
     *
     * @param bool $bool If true, the dialog will be resizable; otherwise, it will not.
     */
    public function setResizable($bool)
    {
        $this->resizable = $bool ? 'true' : 'false';
    }
    
    /**
     * Enables or disables the ability to drag the dialog.
     *
     * @param bool $bool If true, the dialog will be draggable; otherwise, it will not.
     */
    public function setDraggable($bool)
    {
        $this->draggable = $bool ? 'true' : 'false';
    }
    
    /**
     * Returns the unique ID of the dialog element.
     *
     * @return string The ID of the dialog.
     */
    public function getId()
    {
        return $this->{'id'};
    }
    
    /**
     * Sets the dimensions of the dialog.
     *
     * @param int|float $width  The width of the dialog. Can be absolute or a percentage of the window width.
     * @param int|float|null $height The height of the dialog. Can be absolute, a percentage of the window height, or 'auto'.
     */
    public function setSize($width, $height)
    {
        $this->width  = $width  < 1 ? "\$(window).width() * $width" : $width;
        
        if (is_null($height))
        {
            $this->height = "'auto'";
        }
        else
        {
            $this->height = $height < 1 ? "\$(window).height() * $height" : $height;
        }
    }
    
    /**
     * Sets the minimum width of the dialog as a percentage of the window width or an absolute value.
     *
     * @param float $percent  The percentage of the window width.
     * @param int   $absolute The absolute minimum width in pixels.
     */
    public function setMinWidth($percent, $absolute)
    {
        $this->width  = "Math.min(\$(window).width() * $percent, $absolute)";
    }
    
    /**
     * Sets the position of the dialog on the screen.
     *
     * @param int|string $left The left position of the dialog.
     * @param int|string $top  The top position of the dialog.
     */
    public function setPosition($left, $top)
    {
        $this->left = $left;
        $this->top  = $top;
    }
    
    /**
     * Adds a button with a JavaScript action to the dialog.
     *
     * @param string $label  The button label.
     * @param string $action The JavaScript action to be executed when the button is clicked.
     */
    public function addAction($label, $action)
    {
        $this->actions[] = array($label, $action);
    }
    
    /**
     * Sets the stack order (z-index) of the dialog.
     *
     * @param int $order The z-index value.
     */
    public function setStackOrder($order)
    {
        $this->stackOrder = $order;
    }
    
    /**
     * Displays the dialog on the screen with the configured properties and actions.
     */
    public function show()
    {
        $action_code = '';
        if ($this->actions)
        {
            foreach ($this->actions as $action_array)
            {
                $label  = $action_array[0];
                $action = $action_array[1];
                $action_code .= "\"{$label}\": function() {  $action },";
            }
        }
        
        $ok_button = '';
        if ($this->useOKButton)
        {
            $ok_button = '  OK: function() { $( this ).remove(); }';
        }
        
        $left = $this->left ? $this->left : 0;
        $top  = $this->top  ? $this->top  : 0;
        
        $pos_string = '';
        $id = $this->{'id'};
        
        $close_action = 'undefined'; // cannot be function, because it is tested inside tjquerydialog.js
        
        if (isset($this->closeAction))
        {
            $string_action = $this->closeAction->serialize(FALSE);
            $close_action = "function() { __adianti_ajax_exec('{$string_action}') }";
        }
        
        $close_on_escape = $this->closeEscape ? 'true' : 'false';
        parent::add(TScript::create("tjquerydialog_start( '#{$id}', {$this->modal}, {$this->draggable}, {$this->resizable}, {$this->width}, {$this->height}, {$top}, {$left}, {$this->stackOrder}, { {$action_code} {$ok_button} }, $close_action, $close_on_escape, '{$this->dialogClass}' ); ", FALSE));
        parent::show();
    }
    
    /**
     * Closes and removes the dialog from the DOM.
     */
    public function close()
    {
        parent::add(TScript::create('$( "#' . $this->{'id'} . '" ).remove();', false));
    }
    
    /**
     * Closes a dialog by its ID.
     *
     * @param string $id The ID of the dialog to be closed.
     */
    public static function closeById($id)
    {
        TScript::create('$( "#' . $id . '" ).remove();');
    }
    
    /**
     * Closes all open TJQueryDialog instances.
     */
    public static function closeAll()
    {
        if (!isset($_REQUEST['ajax_lookup']) OR $_REQUEST['ajax_lookup'] !== '1')
        {
            // it has to be inline (not external function call)
            TScript::create( ' $(\'[widget="TWindow"]\').remove(); ' );
        }
    }
    
    /**
     * Closes the most recently opened TJQueryDialog instance.
     */
    public static function closeLatest()
    {
        if (!isset($_REQUEST['ajax_lookup']) OR $_REQUEST['ajax_lookup'] !== '1')
        {
            // it has to be inline (not external function call)
            TScript::create( ' $(\'[role=window-wrapper]\').last().remove(); ' );
        }
    }
}
