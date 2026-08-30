<?php
namespace Adianti\Widget\Wrapper;

use Adianti\Widget\Wrapper\TQuickForm;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Container\TNotebook;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;

/**
 * Provides a quick form with a notebook (tabbed interface) wrapper.
 * Extends TQuickForm and allows for structured form organization.
 *
 * @version    7.5
 * @package    widget
 * @subpackage wrapper
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TQuickNotebookForm extends TQuickForm
{
    protected $notebook;
    protected $table;
    protected $vertical_box;
    
    /**
     * Constructor method.
     *
     * @param string $name Form name (default: 'my_form').
     */
    public function __construct($name = 'my_form')
    {
        parent::__construct($name);
        
        $this->vertical_box = new TVBox;
        $this->vertical_box->{'style'} = 'width: 100%';
        $this->notebook = new TNotebook;
        $this->hasAction = FALSE;
        
        $this->fieldsByRow = 1;
    }
    
    /**
     * Sets the notebook wrapper.
     *
     * @param TNotebook $notebook The notebook component to wrap the form content.
     */
    public function setNotebookWrapper($notebook)
    {
        $this->notebook = $notebook;
    }
    
    /**
     * Sets the form title.
     *
     * @param string $title The title of the form.
     */
    public function setFormTitle($title)
    {
        parent::setFormTitle($title);
        $this->vertical_box->add($this->table);
    }
    
    /**
     * Appends a new page to the notebook.
     *
     * @param string    $title     Title of the notebook page.
     * @param TElement|null $container Optional container for the page content (default: TTable).
     */
    public function appendPage($title, $container = NULL)
    {
        if (empty($container))
        {
            $container = new TTable;
            $container->{'width'} = '100%';
        }
        
        if ($this->notebook->getPageCount() == 0)
        {
            $this->vertical_box->add($this->notebook);
        }
        
        $this->table = $container;
        $this->notebook->appendPage($title, $this->table);
        $this->fieldPositions = 0;
    }
    
    /**
     * Adds a quick action to the form.
     *
     * @param string  $label  Action label.
     * @param TAction $action Form action object.
     * @param string  $icon   Action icon (default: 'fa:save').
     */
    public function addQuickAction($label, TAction $action, $icon = 'fa:save')
    {
        $this->table = new TTable;
        $this->table->{'width'} = '100%';
        $this->vertical_box->add($this->table);
        
        parent::addQuickAction($label, $action, $icon);
    }
    
    /**
     * Displays the component.
     */
    public function show()
    {
        $this->notebook->{'style'} = 'margin:10px';
        
        // add the table to the form
        parent::pack($this->vertical_box);
        parent::show();
    }
}
