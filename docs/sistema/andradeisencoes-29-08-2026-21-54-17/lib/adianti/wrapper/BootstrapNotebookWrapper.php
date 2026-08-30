<?php
namespace Adianti\Wrapper;
use Adianti\Widget\Container\TNotebook;
use Adianti\Widget\Base\TElement;

/**
 * Bootstrap notebook decorator for Adianti Framework.
 *
 * This class acts as a wrapper around TNotebook, applying Bootstrap styles.
 *
 * @version    7.5
 * @package    wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 * @wrapper    TNotebook
 */
class BootstrapNotebookWrapper
{
    private $decorated;
    private $properties;
    private $direction;
    private $divisions;
    
    /**
     * Constructor method.
     * Initializes the BootstrapNotebookWrapper and applies Bootstrap styling to the decorated notebook.
     *
     * @param TNotebook $notebook The notebook instance to be decorated.
     */
    public function __construct(TNotebook $notebook)
    {
        $this->decorated = $notebook;
        $this->properties = array();
        $this->direction = '';
        $this->divisions = array(2,10);
    }
    
    /**
     * Magic method to redirect method calls to the decorated notebook.
     *
     * @param string $method     The method name being called.
     * @param array  $parameters The arguments passed to the method.
     *
     * @return mixed The return value of the called method.
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->decorated, $method),$parameters);
    }
    
    /**
     * Magic method to set properties on the decorated notebook.
     *
     * @param string $property The name of the property being set.
     * @param mixed  $value    The value to be assigned to the property.
     */
    public function __set($property, $value)
    {
        $this->properties[$property] = $value;
    }
    
    /**
     * Sets the tabs direction for the notebook.
     *
     * @param string $direction  The direction of the tabs ('left' or 'right').
     * @param array|null $divisions Optional array defining column width distribution.
     */
    public function setTabsDirection($direction, $divisions = null)
    {
        if ($direction)
        {
            $this->direction = 'tabs-'.$direction;
            if ($divisions)
            {
                $this->divisions = $divisions;
            }
        } 
    }
    
    /**
     * Renders the decorated notebook with Bootstrap styles.
     * Applies Bootstrap formatting, manages tab positions, and structures the layout accordingly.
     */
    public function show()
    {
        $rendered = $this->decorated->render();
        $rendered->{'role'} = 'tabpanel';
        unset($rendered->{'class'});
        $rendered->{'class'} = 'tabwrapper';
        
        foreach ($this->properties as $property => $value)
        {
            $rendered->$property = $value;
        }
        
        $sessions = $rendered->getChildren();
        if ($sessions)
        {
            foreach ($sessions as $section)
            {
                if ($section->{'class'} == 'nav nav-tabs')
                {
                    $section->{'class'} = "nav nav-tabs " . $this->direction;
                    if ($this->direction)
                    {
                        $section->{'class'} .= " flex-column";

                    }
                    $section->{'role'}  = "tablist";
                    $tabs = $section;
                }
                if ($section->{'class'} == 'spacer')
                {
                    $section->{'style'} = "display:none";
                }
                if ($section->{'class'}  == 'frame tab-content')
                {
                    $section->{'class'} = 'tab-content';
                    $panel = $section;
                }
            }
        }
        
        if ($this->direction == 'tabs-left')
        {
            $rendered->clearChildren();
            $left_pack = TElement::tag('div', '', array('class'=> 'left-pack col-'.$this->divisions[0], 'style' => 'padding:0'));
            $right_pack = TElement::tag('div', '', array('class'=> 'right-pack col-'.$this->divisions[1], 'style' => 'padding-right:0; margin-right:0'));
            $rendered->add($left_pack);
            $rendered->add($right_pack);
            $left_pack->add($tabs);
            $right_pack->add($panel);
        }
        else if ($this->direction == 'tabs-right')
        {
            $rendered->clearChildren();
            $left_pack = TElement::tag('div', '', array('class'=> 'left-pack col-'.$this->divisions[1]));
            $right_pack = TElement::tag('div', '', array('class'=> 'right-pack col-'.$this->divisions[0]));
            $rendered->add($left_pack);
            $rendered->add($right_pack);
            $left_pack->add($panel);
            $right_pack->add($tabs);
        }
        
        if (!empty($this->direction))
        {
            $rendered->{'style'} .= ';display: flex';
        }

        $rendered->show();
    }
}
