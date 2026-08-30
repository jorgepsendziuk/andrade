<?php
namespace Adianti\Widget\Datagrid;

use Adianti\Control\TAction;

/**
 * Represents a group of Actions for datagrids
 *
 * This class allows grouping multiple actions together, including separators and headers.
 *
 * @version    7.5
 * @package    widget
 * @subpackage datagrid
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDataGridActionGroup
{
    private $actions;
    private $headers;
    private $separators;
    private $label;
    private $icon;
    private $index;
    
    /**
     * Class constructor.
     *
     * Initializes the action group with a label and an optional icon.
     *
     * @param string      $label Action group label.
     * @param string|null $icon  Action group icon (optional).
     */
    public function __construct( $label, $icon = NULL)
    {
        $this->index = 0;
        $this->actions = array();
        $this->label = $label;
        $this->icon = $icon;
    }
    
    /**
     * Returns the label of the action group.
     *
     * @return string The action group label.
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Returns the icon of the action group.
     *
     * @return string|null The action group icon or null if not set.
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the icon for the action group.
     *
     * @param string|null $icon The icon to be set.
     *
     * @return void
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Sets the label for the action group.
     *
     * @param string $label The label to be set.
     *
     * @return void
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    /**
     * Adds an action to the action group.
     *
     * @param TAction $action The action to be added.
     *
     * @return void
     */
    public function addAction(TAction $action)
    {
        $this->actions[ $this->index ] = $action;
        $this->index ++;
    }
    
    /**
     * Adds a separator to the action group.
     *
     * Separators visually divide actions within the group.
     *
     * @return void
     */
    public function addSeparator()
    {
        $this->separators[ $this->index ] = TRUE;
        $this->index ++;
    }
    
    /**
     * Adds a header to the action group.
     *
     * Headers are used to group related actions under a common title.
     *
     * @param string $header The header text.
     *
     * @return void
     */
    public function addHeader($header)
    {
        $this->headers[ $this->index ] = $header;
        $this->index ++;
    }
    
    /**
     * Retrieves the list of actions in the group.
     *
     * @return TAction[] An array of actions in the group.
     */
    public function getActions()
    {
        return $this->actions;
    }
    
    /**
     * Retrieves the list of headers in the group.
     *
     * @return array|null An associative array of headers indexed by position, or null if no headers exist.
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Retrieves the list of separators in the group.
     *
     * @return array|null An associative array of separators indexed by position, or null if no separators exist.
     */
    public function getSeparators()
    {
        return $this->separators;
    }
}
