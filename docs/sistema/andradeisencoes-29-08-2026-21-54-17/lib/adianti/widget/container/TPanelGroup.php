<?php
namespace Adianti\Widget\Container;

use Adianti\Wrapper\BootstrapFormWrapper;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;
use Adianti\Widget\Util\TActionLink;

/**
 * Bootstrap-based panel container for the Adianti Framework.
 *
 * This class provides a Bootstrap-styled panel with support for titles, headers, footers, 
 * and actions inside a structured container.
 *
 * @version    7.5
 * @package    widget
 * @subpackage container
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TPanelGroup extends TElement
{
    private $title;
    private $head;
    private $body;
    private $footer;
    private $actionsContainer;
    
    /**
     * Static factory method for creating a panel instance with content.
     *
     * @param string      $title   The title of the panel.
     * @param TElement    $element The main content element of the panel.
     * @param TElement|null $footer Optional footer element for the panel.
     *
     * @return TPanelGroup The newly created panel instance.
     */
    public static function pack($title, $element, $footer = null)
    {
        $panel = new self($title);
        $panel->add($element);
        
        if ($footer)
        {
            $panel->addFooter($footer);
        }
        
        return $panel;
    }
    
    /**
     * Constructor method.
     *
     * Initializes the panel with an optional title and background color.
     *
     * @param string|null $title      The title to be displayed in the panel header.
     * @param string|null $background The background color of the header (optional).
     */
    public function __construct($title = NULL, $background = NULL)
    {
        parent::__construct('div');
        $this->{'class'} = 'card panel';
        
        $this->head = new TElement('div');
        $this->head->{'class'} = 'card-header panel-heading';
        $this->head->{'style'} = 'display:none';
        parent::add($this->head);
        
        $panel_title = new TElement('div');
        $panel_title->{'class'} = 'card-title panel-title';
        $this->head->add($panel_title);
        
        $this->title = new TElement('div');
        $this->title->{'style'} = 'width: 100%';
        $this->title->add($title);
        $panel_title->add($this->title);
        
        if (!empty($background))
        {
            $this->head->{'style'} .= ';background:'.$background;
        }
        
        $this->actionsContainer = new TElement('div');
        $this->actionsContainer->{'class'} = 'header-actions';
        $this->head->add( $this->actionsContainer );
        
        if (!empty($title))
        {
            $this->head->{'style'} = str_replace('display:none', '', $this->head->{'style'});
        }
        
        $this->body = new TElement('div');
        $this->body->{'class'} = 'card-body panel-body';
        parent::add($this->body);
        
        $this->footer = new TElement('div');
        $this->footer->{'class'} = 'card-footer panel-footer';
    }
    
    /**
     * Set the panel title.
     *
     * @param string $title The new title to be displayed in the panel header.
     */
    public function setTitle($title)
    {
        $this->title->clearChildren();
        $this->title->add($title);
    }
    
    /**
     * Add an action link to the panel header.
     *
     * @param string  $label  The label of the button.
     * @param TAction $action The action to be executed when clicking the button.
     * @param string  $icon   The icon for the button (default: 'fa:save').
     *
     * @return TActionLink The created action link.
     */
    public function addHeaderActionLink($label, TAction $action, $icon = 'fa:save')
    {
        $this->head->{'style'} = str_replace('display:none', '', $this->head->{'style'});
        
        $this->title->{'style'} = 'display:inline-block;';
        $label_info = ($label instanceof TLabel) ? $label->getValue() : $label;
        $button = new TActionLink($label_info, $action, null, null, null, $icon);
        $button->{'class'} = 'btn btn-sm btn-default';
        
        $this->actionsContainer->add($button);
        
        return $button;
    }
    
    /**
     * Add a widget to the panel header.
     *
     * @param TElement $widget The widget to be added.
     *
     * @return TElement The added widget.
     */
    public function addHeaderWidget($widget)
    {
        $this->head->{'style'} = str_replace('display:none', '', $this->head->{'style'});
        $this->title->{'style'} = 'display:inline-block;';
        
        $this->actionsContainer->add($widget);
        
        return $widget;
    }
    
    /**
     * Add content to the panel body.
     *
     * If the content is a BootstrapFormWrapper, its action buttons will be detached and added to the footer.
     *
     * @param TElement $content The content element to be added.
     *
     * @return TElement The panel body element.
     */
    public function add($content)
    {
        $this->body->add($content);
        
        if ($content instanceof BootstrapFormWrapper)
        {
            $buttons = $content->detachActionButtons();
            if ($buttons)
            {
                foreach ($buttons as $button)
                {
                    $this->footer->add( $button );
                }
                parent::add($this->footer);
            }
        }
        
        return $this->body;
    }
    
    /**
     * Get the panel header element.
     *
     * @return TElement The header element of the panel.
     */
    public function getHeader()
    {
        return $this->head;
    }
    
    /**
     * Get the panel body element.
     *
     * @return TElement The body element of the panel.
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Get the panel footer element.
     *
     * @return TElement The footer element of the panel.
     */
    public function getFooter()
    {
        return $this->footer;
    }
    
    /**
     * Add content to the panel footer.
     *
     * @param TElement $footer The footer content to be added.
     */
    public function addFooter($footer)
    {
        $this->footer->add( $footer );
        parent::add($this->footer);
    }
}
