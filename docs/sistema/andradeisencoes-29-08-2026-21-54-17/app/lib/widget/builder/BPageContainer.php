<?php

/**
 * Class BPageContainer
 *
 * This class represents a container element that can dynamically load content based on actions,
 * manage its size, visibility, and parameters.
 *
 * @version    1.0
 * @package    widget
 * @subpackage base
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class BPageContainer extends TElement
{
    protected $size;
    protected $height;
    protected $action;
    protected $hide;
    protected $instantLoad;

    /**
     * BPageContainer constructor.
     *
     * Initializes the container as a <div> element and sets its visibility to visible by default.
     */
    public function __construct()
    {
        $this->hide = false;
        $this->instantLoad = false;
        parent::__construct('div');
    }
    
    public function enableInstantLoad()
    {
        $this->instantLoad = true;
    }
    
    /**
     * Sets the widget's width and height.
     *
     * @param string|int $width  The width of the container (can be in pixels or percentage).
     * @param string|int|null $height The height of the container (optional, can be in pixels or percentage).
     */
    public function setSize($width, $height = NULL)
    {
        $this->size   = $width;
        if ($height)
        {
            $this->height = $height;
        }
        
        if ($this->size)
        {
            $this->size = str_replace('px', '', $this->size);
            $size = (strstr($this->size, '%') !== FALSE) ? $this->size : "{$this->size}px";
            $this->setProperty('style', "width:{$size};", FALSE); //aggregate style info
        }
        
        if ($this->height)
        {
            $this->height = str_replace('px', '', $this->height);
            $height = (strstr($this->height, '%') !== FALSE) ? $this->height : "{$this->height}px";
            $this->setProperty('style', "height:{$height}", FALSE); //aggregate style info
        }
        
    }
    
    /**
     * Sets the ID of the container.
     *
     * @param string $id The ID to be assigned to the container.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Retrieves the current size of the container.
     *
     * @return array An array containing the width and height of the container.
     */
    public function getSize()
    {
        return array( $this->size, $this->height );
    }

    /**
     * Sets an action to be executed within the container.
     *
     * @param TAction $action The action to be associated with the container.
     */
    public function setAction($action)
    {
        $parameters = $action->getParameters();
        $parameters['target_container'] = $this->id;
        $parameters['register_state'] = 'false';
        $parameters['show_loading'] = 'false';
        $action->setParameters($parameters);
        $this->action = $action;
    }

    /**
     * Adds a parameter to the associated action.
     *
     * @param string $key   The parameter name.
     * @param mixed  $value The parameter value.
     */
    public function setParameter($key, $value)
    {
        if($this->action)
        {
            $this->action->setParameter($key, $value);
        }
    }

    /**
     * Makes the container visible.
     */
    public function unhide()
    {
        $this->hide = false;
    }

    /**
     * Hides the container.
     */
    public function hide()
    {
        $this->hide = true;
    }

    /**
     * Displays the container and loads content dynamically if an action is set.
     */
    public function show()
    {
        if($this->hide && ! $this->instantLoad)
        {
            $child = parent::getChildren();
            if($child && !empty($child[0]))
            {
                $child[0]->style = 'display:none';   
            }
            parent::show();
            return;
        }
        $action = $this->action->getAction();

        $parameters = $this->action->getParameters();
        $parameters['target_container'] = $this->id;
        
        $controller = $action[0];
        $method = $action[1];

        if ($this->instantLoad)
        {
            $page = new $controller($parameters);
            $page->{$method}($parameters);
            $page->setTargetContainer(null);

            $this->clearChildren();
            $this->add($page);
        }
        else
        {
            ob_start();
            TApplication::loadPage($controller, $method, $parameters);
            $this->add(ob_get_contents());
            ob_end_clean();
        }

        parent::show();
    }
}
