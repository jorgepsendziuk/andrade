<?php
namespace Adianti\Widget\Container;

use Adianti\Control\TAction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Container\TFrame;

/**
 * Represents a notebook (tabbed interface) container widget.
 *
 * This class provides an interface to create a notebook with multiple pages,
 * where each page has a title and content. It allows setting actions on tabs,
 * defining visibility and sensitivity of tabs, and managing page navigation.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TNotebook extends TElement
{
    private $width;
    private $height;
    private $currentPage;
    private $pages;
    private $counter;
    private $id;
    private $tabAction;
    private $tabsVisibility;
    private $tabsSensibility;
    private $container;
    private static $noteCounter;
    
    /**
     * Initializes a new instance of the TNotebook class.
     *
     * @param int|null $width  The width of the notebook.
     * @param int|null $height The height of the notebook.
     */
    public function __construct($width = null, $height = null)
    {
        parent::__construct('div');
        $this->id = 'tnotebook_' . mt_rand(1000000000, 1999999999);
        $this->counter = ++ self::$noteCounter;
        
        // define some default values
        $this->pages = [];
        $this->width = $width;
        $this->height = $height;
        $this->currentPage = 0;
        $this->tabsVisibility = TRUE;
        $this->tabsSensibility = TRUE;
    }
    
    /**
     * Sets the visibility of the notebook's tabs.
     *
     * @param bool $visible If true, tabs will be visible; otherwise, they will be hidden.
     */
    public function setTabsVisibility($visible)
    {
        $this->tabsVisibility = $visible;
    }
    
    /**
     * Sets the click sensitivity of the notebook's tabs.
     *
     * @param bool $sensibility If true, tabs will respond to clicks; otherwise, they will be disabled.
     */
    public function setTabsSensibility($sensibility)
    {
        $this->tabsSensibility = $sensibility;
    }
    
    /**
     * Gets the unique identifier of the notebook.
     *
     * @return string The unique ID of the notebook.
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets the dimensions of the notebook.
     *
     * @param int|null $width  The width of the notebook.
     * @param int|null $height The height of the notebook.
     */
    public function setSize($width, $height)
    {
        // define the width and height
        $this->width  = $width;
        $this->height = $height;
    }
    
    /**
     * Gets the dimensions of the notebook.
     *
     * @return array An array containing the width and height of the notebook.
     */
    public function getSize()
    {
        return array($this->width, $this->height);
    }
    
    /**
     * Sets the current active page in the notebook.
     *
     * @param int $i The index of the page to be displayed (starting from 0).
     */
    public function setCurrentPage($i)
    {
        // atribui a página corrente
        $this->currentPage = $i;
    }
    
    /**
     * Gets the index of the currently active page.
     *
     * @return int The index of the current page (starting from 0).
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }
    
    /**
     * Adds a new page to the notebook.
     *
     * @param string $title  The title of the tab.
     * @param mixed  $object The content associated with the tab.
     */
    public function appendPage($title, $object)
    {
        $this->pages[$title] = $object;
    }

    /**
     * Gets the total number of pages in the notebook.
     *
     * @return int The number of pages.
     */
    public function getPageCount()
    {
        return count($this->pages);
    }
    
    /**
     * Sets an action to be executed when a tab is clicked.
     *
     * @param TAction $action The action to be executed when a tab is clicked.
     */
    public function setTabAction(TAction $action)
    {
        $this->tabAction = $action;
    }
    
    /**
     * Renders the notebook interface.
     *
     * This method creates the HTML structure for the notebook, including the tabs
     * and their respective content.
     *
     * @return TElement The generated HTML structure for the notebook.
     */
    public function render()
    {
        // count the pages
        $pages = $this->getPageCount();
        
        $this->container = new TElement('div');
        if ($this->width)
        {
            $this->container->{'style'} = ";min-width:{$this->width}px";
        }
        $this->container->{'class'} = 'tnotebook';
        
        $ul = new TElement('ul');
        $ul->{'class'} = 'nav nav-tabs';
        $this->container->add($ul);
        
        $space = new TElement('div');
        if ($this->width)
        {
            $space->{'style'} = "min-width:{$this->width}px";
        }
        $space->{'class'} = 'spacer';
        $this->container->add($space);
        
        $i = 0;
        $id = $this->id;
        
        
        if ($this->pages)
        {
            // iterate the tabs, showing them
            foreach ($this->pages as $title => $content)
            {
                // verify if the current page is to be shown
                $classe = ($i == $this->currentPage) ? 'active' : '';
                
                // add a cell for this tab
                if ($this->tabsVisibility)
                {
                    $item = new TElement('li');
                    $link = new TElement('a');
                    $link->{'aria-controls'} = "home";
                    $link->{'role'} = "tab";
                    $link->{'data-toggle'} = "tab";
                    $link->{'href'} = "#"."panel_{$id}_{$i}";
                    $link->{'class'} = $classe . " nav-link";
                    
                    if (!$this->tabsSensibility)
                    {
                        $link->{'style'} = "pointer-events:none";
                    }
                    
                    $item->add($link);
                    $link->add("$title");
                    $item->{'class'} = $classe . " nav-item";
                    $item->{'role'} = "presentation";
                    $item->{'id'} = "tab_{$id}_{$i}";
                    
                    if ($this->tabAction)
                    {
                        $this->tabAction->setParameter('current_page', $i+1);
                        $string_action = $this->tabAction->serialize(FALSE);
                        $link-> onclick = "__adianti_ajax_exec('$string_action')";
                    }
                    
                    $ul->add($item);
                    $i ++;
                }
            }
        }
        
        // creates a <div> around the content
        $quadro = new TElement('div');
        $quadro->{'class'} = 'frame tab-content';
        
        $width = $this->width;
        $height= $this->height;// -30;
        
        if ($width)
        {
            $quadro->{'style'} .= ";min-width:{$width}px";
        }
        
        if($height)
        {
            $quadro->{'style'} .= ";min-height:{$height}px";
        }
        
        $i = 0;
        // iterate the tabs again, now to show the content
        if ($this->pages)
        {
            foreach ($this->pages as $title => $content)
            {
                $panelClass = ($i == $this->currentPage) ? 'active': '';
                
                // creates a <div> for the contents
                $panel = new TElement('div');
                $panel->{'role'}  = "tabpanel";
                $panel->{'class'} = "tab-pane " . $panelClass;
                $panel->{'id'}    = "panel_{$id}_{$i}"; // ID
                $quadro->add($panel);
                
                // check if the content is an object
                if (is_object($content))
                {
                    $panel->add($content);
                }
                
                $i ++;
            }
        }
        
        $this->container->add($quadro);
        return $this->container;
    }
    
    /**
     * Displays the notebook.
     *
     * If the notebook has not been rendered yet, this method will generate its structure
     * before displaying it.
     */
    public function show()
    {
        if (empty($this->container))
        {
            $this->container = $this->render();
        }
        parent::add($this->container);
        parent::show();
    }
}
