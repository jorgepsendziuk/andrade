<?php
namespace Adianti\Wrapper;
use Adianti\Widget\Datagrid\TDataGrid;

/**
 * Bootstrap datagrid decorator for Adianti Framework.
 *
 * This class acts as a wrapper around TDataGrid, applying Bootstrap styles.
 *
 * @version    7.5
 * @package    wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 * @wrapper    TDataGrid
 * @wrapper    TQuickGrid
 */
class BootstrapDatagridWrapper
{
    private $decorated;
    
    /**
     * Constructor method.
     * Initializes the BootstrapDatagridWrapper and applies Bootstrap styling to the decorated datagrid.
     *
     * @param TDataGrid $datagrid The datagrid instance to be decorated.
     */
    public function __construct(TDataGrid $datagrid)
    {
        $this->decorated = $datagrid;
        $this->decorated->{'class'} = 'table table-striped table-hover';
        $this->decorated->{'type'}  = 'bootstrap';
    }
    
    /**
     * Clone method.
     * Clones the decorated datagrid instance to ensure a new independent copy.
     */
    public function __clone()
    {
        $this->decorated = clone $this->decorated;
    }
    
    /**
     * Magic method to redirect method calls to the decorated datagrid.
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
     * Magic method to set properties on the decorated datagrid.
     *
     * @param string $property The name of the property being set.
     * @param mixed  $value    The value to be assigned to the property.
     */
    public function __set($property, $value)
    {
        $this->decorated->$property = $value;
    }
    
    /**
     * Magic method to get properties from the decorated datagrid.
     *
     * @param string $property The name of the property being accessed.
     *
     * @return mixed The value of the property.
     */
    public function __get($property)
    {
        return $this->decorated->$property;
    }
    
    /**
     * Renders the decorated datagrid with Bootstrap styles.
     * Applies Bootstrap styling and ensures proper formatting of rows and sections.
     */
    public function show()
    {
        $this->decorated->{'style'} .= ';border-collapse:collapse';
        
        $sessions = $this->decorated->getChildren();
        if ($sessions)
        {
            foreach ($sessions as $section)
            {
                unset($section->{'class'});
                
                $rows = $section->getChildren();
                if ($rows)
                {
                    foreach ($rows as $row)
                    {
                        if ($row->{'class'} == 'tdatagrid_group')
                        {
                            $row->{'class'} = 'info';
                            $row->{'style'} = $row->{'style'} . ';user-select:none';
                        }
                        else
                        {
                            unset($row->{'class'});
                            
                            if (!empty($row->{'className'}))
                            {
                                $row->{'class'} = $row->{'className'};
                            }
                        }
                    }
                }
            }
        }
        $this->decorated->show();
    }
}
