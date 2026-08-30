<?php
namespace Adianti\Control;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Container\TJQueryDialog;
use Adianti\Widget\Base\TScript;

use ReflectionClass;
use Exception;

/**
 * Window Container (JQueryDialog wrapper)
 *
 * This class represents a modal window component using the TJQueryDialog widget.
 * It provides functionalities to set properties such as title, size, modal behavior, 
 * position, and close actions, allowing dynamic window management in web applications.
 *
 * @version    7.5
 * @package    control
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TWindow extends TPage
{
    private $wrapper;
    
    /**
     * TWindow constructor.
     *
     * Initializes a new window instance with default properties, including:
     * - A `TJQueryDialog` wrapper.
     * - Default size (1000x500).
     * - Modal behavior enabled.
     * - Randomized unique ID for the window.
     *
     * The window is added as a child of the current page.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->wrapper = new TJQueryDialog;
        $this->wrapper->setUseOKButton(FALSE);
        $this->wrapper->setTitle('');
        $this->wrapper->setSize(1000, 500);
        $this->wrapper->setModal(TRUE);
        $this->wrapper->{'widget'} = 'T'.'Window';
        $this->wrapper->{'name'} = $this->getClassName();
        
        $this->{'id'} = 'window_' . mt_rand(1000000000, 1999999999);
        $this->{'window_name'} = $this->wrapper->{'name'};
        $this->{'role'} = 'window-wrapper';
        parent::add($this->wrapper);
    }
    
    /**
     * Throws an exception if a target container is set.
     *
     * Windows do not support target containers.
     *
     * @param string $container Target container name.
     *
     * @throws Exception If attempting to set a target container.
     */
    public function setTargetContainer($container)
    {
        throw new Exception( AdiantiCoreTranslator::translate('Use of target containers along with windows is not allowed') );
    }
    
    
    /**
     * Returns the ID of the window.
     *
     * @return string Window ID.
     */
    public function getId()
    {
        return $this->wrapper->getId();
    }
    
    /**
     * Creates a new window instance.
     *
     * @param string  $title  Window title.
     * @param int     $width  Window width.
     * @param int     $height Window height.
     * @param mixed   $params Optional parameters.
     *
     * @return TWindow The created window instance.
     */
    public static function create($title, $width, $height, $params = null)
    {
        $inst = new static($params);
        $inst->setIsWrapped(TRUE);
        $inst->setTitle($title);
        $inst->setSize($width, $height);
        unset($inst->wrapper->{'widget'});
        return $inst;
    }
    
    /**
     * Removes the window's padding.
     */
    public function removePadding()
    {
        $this->setProperty('class', 'window_modal');
    }
    
    /**
     * Removes the title bar from the window.
     */
    public function removeTitleBar()
    {
        $this->setDialogClass('no-title');
    }
    
    /**
     * Sets a CSS class for the dialog.
     *
     * @param string $class CSS class name.
     */
    public function setDialogClass($class)
    {
        $this->wrapper->setDialogClass($class);
    }
    
    /**
     * Defines the stack order (z-index) of the window.
     *
     * @param int $order Stack order value.
     */
    public function setStackOrder($order)
    {
        $this->wrapper->setStackOrder($order);
    }
    
    /**
     * Sets the title of the window.
     *
     * @param string $title Window title.
     */
    public function setTitle($title)
    {
        $this->wrapper->setTitle($title);
    }
    
    /**
     * Enables or disables modal mode for the window.
     *
     * @param bool $modal Whether the window should be modal.
     */
    public function setModal($modal)
    {
        $this->wrapper->setModal($modal);
    }
    
    /**
     * Disables the escape key for closing the window.
     */
    public function disableEscape()
    {
        $this->wrapper->disableEscape();
    }
    
    /**
     * Disables scrolling within the window.
     */
    public function disableScrolling()
    {
        $this->wrapper->disableScrolling();
    }
    
    /**
     * Sets the size of the window.
     *
     * @param int $width  Window width.
     * @param int $height Window height.
     */
    public function setSize($width, $height)
    {
        $this->wrapper->setSize($width, $height);
    }
    
    /**
     * Sets the minimum width of the window.
     *
     * @param int $percent  Minimum width as a percentage.
     * @param int $absolute Minimum absolute width.
     */
    public function setMinWidth($percent, $absolute)
    {
        $this->wrapper->setMinWidth($percent, $absolute);
    }
    
    /**
     * Sets the top-left position of the window.
     *
     * @param int $x X coordinate (left position).
     * @param int $y Y coordinate (top position).
     */
    public function setPosition($x, $y)
    {
        $this->wrapper->setPosition($x, $y);
    }
    
    /**
     * Sets a custom property value for the window.
     *
     * @param string $property Property name.
     * @param mixed  $value    Property value.
     */
    public function setProperty($property, $value)
    {
        $this->wrapper->$property = $value;
    }
    
    /**
     * Adds content to the window.
     *
     * @param mixed $content Content to be added, must implement show() method.
     */
    public function add($content)
    {
        $this->wrapper->add($content);
    }
    
    /**
     * Sets an action to be executed when the window is closed.
     *
     * @param TAction $action Action object to execute on close.
     */
    public function setCloseAction(TAction $action)
    {
        $this->wrapper->setCloseAction($action);
    }
    
    /**
     * Blocks the user interface.
     *
     * @param int|null $timeout Optional timeout before blocking takes effect.
     */
    public static function blockUI($timeout = null)
    {
        TScript::create('tjquerydialog_block_ui()', true, $timeout);
    }
    
    /**
     * Unblocks the user interface.
     *
     * @param int|null $timeout Optional timeout before unblocking takes effect.
     */
    public static function unBlockUI($timeout = null)
    {
        TScript::create('tjquerydialog_unblock_ui()', true, $timeout);
    }
    
    /**
     * Closes a window by its ID or the most recent one if no ID is provided.
     *
     * @param string|null $id ID of the window to close.
     */
    public static function closeWindow($id = null)
    {
        if (!empty($id))
        {
            TJQueryDialog::closeById($id);
        }
        else
        {
            TJQueryDialog::closeLatest();
        }
    }
    
    /**
     * Closes all open windows.
     */
    public static function closeAll()
    {
        TJQueryDialog::closeAll();
    }
    
    /**
     * Closes a window by its controller name.
     *
     * @param string $name Name of the window to close.
     */
    public static function closeWindowByName($name)
    {
        TScript::create( ' $(\'[window_name="'.$name.'"]\').remove(); ' );
    }
}
