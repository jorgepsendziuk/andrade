<?php
namespace Adianti\Widget\Util;

use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;
use Adianti\Widget\Form\TDateTime;
use Adianti\Util\AdiantiTemplateHandler;
use Adianti\Widget\Template\THtmlRenderer;

use stdClass;
use ApplicationTranslator;

/**
  * Timeline widget for displaying events in a chronological order.
 *
 * This class allows creating and displaying a timeline with various events.
 * It supports defining icons, templates, labels, and actions associated with events.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Artur Comunello
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TTimeline extends TElement
{
    protected $useBothSides;
    protected $items;
    protected $finalIcon;
    protected $timeDisplayMask;
    protected $actions;
    protected $itemTemplate;
    protected $templatePath;
    protected $itemDatabase;
    
    /**
     * Class Constructor
     *
     * Initializes the timeline with default settings, including a unique identifier,
     * default time display format, and empty lists for items and actions.
     */
    public function __construct()
    {
        parent::__construct('ul');
        $this->{'id'} = 'ttimeline_'.mt_rand(1000000000, 1999999999);
        $this->{'class'} = 'timeline';
        $this->timeDisplayMask = 'yyyy-mm-dd';
        
        $this->items = [];
        $this->actions = [];
    }
    
    /**
     * Define the final icon for the timeline.
     *
     * @param string $icon The icon to be displayed at the end of the timeline.
     */
    public function setFinalIcon( $icon )
    {
        $this->finalIcon = $icon;
    }
    
    /**
     * Define the display format for the timeline dates.
     *
     * @param string $mask The date format mask (e.g., 'yyyy-mm-dd').
     */
    public function setTimeDisplayMask( $mask )
    {
        $this->timeDisplayMask = $mask;
    }
    
    /**
     * Enable the use of both sides for timeline items.
     *
     * When enabled, items are alternately aligned on both sides of the timeline.
     */
    public function setUseBothSides()
    {
        $this->useBothSides = true;
    }
    
    /**
     * Set a template for rendering timeline items.
     *
     * @param string $template The template content used for rendering each item.
     */
    public function setItemTemplate($template)
    {
        $this->itemTemplate = $template;
    }
    
    /**
     * Define the path to an external template file for timeline items.
     *
     * @param string $template_path The file path to the template.
     */
    public function setTemplatePath( $template_path )
    {
        $this->templatePath = $template_path;
    }
    
    /**
     * Define the database to be used for timeline items.
     *
     * @param string $database The database connection name.
     */
    public function setItemDatabase($database)
    {
        $this->itemDatabase = $database;
    }
    
    /**
     * Add an item to the timeline.
     *
     * @param string   $id       The unique identifier of the item.
     * @param string   $title    The title of the item.
     * @param string   $content  The content or description of the item.
     * @param string   $date     The date associated with the item.
     * @param string   $icon     The icon representing the item.
     * @param string|null $align The alignment of the item (left/right) [optional].
     * @param stdClass|null $object Additional data associated with the item [optional].
     */
    public function addItem( $id, $title, $content, $date, $icon, $align = null, $object = null  )
    {
        if (is_null($object))
        {
            $object = new stdClass;
        }
        
        if (empty($object->{'id'}))
        {
            $object->{'id'} = $id;
        }
        
        $item = new stdClass;
        $item->{'id'}      = $id;
        $item->{'title'}   = $title;
        $item->{'content'} = $content;
        $item->{'date'}    = $date;
        $item->{'icon'}    = $icon;
        $item->{'align'}   = $align;
        $item->{'object'}  = $object;
        
        $this->items[] = $item;
    }
    
    /**
     * Add an action to the timeline.
     *
     * Actions allow interaction with timeline items, such as opening a page or triggering an event.
     *
     * @param TAction    $action            The action to be executed.
     * @param string     $label             The label for the action button.
     * @param string     $icon              The icon for the action button.
     * @param callable|null $display_condition A callback function to determine if the action should be displayed [optional].
     */
    public function addAction(TAction $action, $label, $icon, $display_condition = null)
    {
        $action->setProperty('label', $label);
        $action->setProperty('icon',  $icon);
        $action->setProperty('display_condition', $display_condition );
        
        $this->actions[] = $action;
    }
    
    /**
     * Render available actions for a given timeline item.
     *
     * @param stdClass|null $object The item data object [optional].
     *
     * @return TElement|null The generated action buttons container, or null if no actions exist.
     */
    private function renderItemActions( $object = null )
    {
        if ($this->actions)
        {
            $footer = new TElement( 'div' );
            $footer->{'class'} = 'timeline-footer';
            
            foreach ($this->actions as $action_template)
            {
                if ( empty( $object ) )
                {
                    $action = clone $action_template;
                }
                else
                {
                    $action = $action_template->prepare($object);
                }
                
                // get the action properties
                $icon      = $action->getProperty('icon');
                $label     = $action->getProperty('label');
                $condition = $action->getProperty('display_condition');
                
                if (empty($condition) OR call_user_func($condition, $object))
                {
                    $button = new TElement('button');
                    $button->{'onclick'} = "__adianti_load_page('{$action->serialize()}');return false;";
                    
                    $span = new TElement('span');
                    $span->add( new TImage($icon) );
                    $span->add( $label );
                    $button->add( $span );
                    $button->{'class'} = $action->getProperty('btn-class') ?? 'btn btn-default';
                    $button->{'type'} = 'button';
                    
                    $footer->add( $button );
                }
            }
            return $footer;
        }
    }
    
    /**
     * Render a timeline item using the default template.
     *
     * @param stdClass $item The item data object.
     *
     * @return TElement The rendered item element.
     */
    private function defaultItemRender( $item )
    {
        $span = new TElement( 'span' );
        $span->{'class'} = 'time';
        
        if (strlen($item->{'date'}) > 10)
        {
            $span->add( new TImage( 'far:clock' ) );
            $span->add( TDateTime::convertToMask( $item->{'date'}, 'yyyy-mm-dd hh:ii:ss', 'hh:ii' ) );
        }
        
        $title = new TElement( 'a' );
        $title->add( AdiantiTemplateHandler::replace( $item->{'title'}, $item->{'object'} ) );
        
        if (!empty($item->{'title'}))
        {
            $h3 = new TElement( 'h3' );
            $h3->{'class'} = 'timeline-header';
            $h3->add( $title );
        }
        
        $div = new TElement( 'div' );
        $div->{'class'} = 'timeline-body';
        
        $div->add( AdiantiTemplateHandler::replace( $item->{'content'}, $item->{'object'} ) );
        
        $item_div = new TElement( 'div' );
        $item_div->{'class'} = 'timeline-item ';
        
        if( $this->useBothSides)
        {
            if ( empty( $item->{'align'} ) )
            {
                $item->{'align'} = 'left';
            }
            
            $item_div->{'class'} .= 'timeline-item-' . $item->{'align'};
        }
        
        $item_div->add( $span );
        if (!empty($h3))
        {
            $item_div->add( $h3 );
        }
        $item_div->add( $div );
        $item_div->add( $this->renderItemActions( $item->{'object'} ) );
        
        return $item_div;
    }
    
    /**
     * Render a timeline item.
     *
     * Uses either a predefined template or the default rendering method.
     *
     * @param stdClass $item The item data object.
     *
     * @return TElement The rendered item element.
     */
    private function renderItem( $item )
    {
        if ( !empty( $this->templatePath) AND !empty( $item->{'object'}) )
        {
            $template = new THtmlRenderer( $this->templatePath );
            $template->enableSection( 'main' );
            $content = $item->{'object'}->render( $template->getContents() );
        }
        else if (!empty($this->itemTemplate))
        {
            $item_template = ApplicationTranslator::translateTemplate($this->itemTemplate);
            $item_template = AdiantiTemplateHandler::replace($item_template, $item->{'object'});
            $content = $item_template;
        }
        else
        {
            $content = $this->defaultItemRender( $item );
        }
        
        $li = new TElement( 'li' );
        $li->add( new TImage( $item->{'icon'} . ' line-icon') );
        $li->add( $content );
        
        return $li;
    }
    
    /**
     * Render a date label for the timeline.
     *
     * Labels are displayed between timeline items to indicate different time periods.
     *
     * @param string $label The date label to be displayed.
     *
     * @return TElement The rendered label element.
     */
    private function renderLabel( $label )
    {
        $li = new TElement( 'li' );
        $li->{'class'} = 'time-label';
        
        if( $this->useBothSides )
        {
            $li->{'class'} .= ' time-label-bothsides';
        }
        
        $li->add( TElement::tag( 'span', $label ) );
        
        return $li;
    }
    
    /**
     * Render all timeline items.
     *
     * Iterates through the added items and renders them sequentially, inserting date labels when necessary.
     */
    private function renderItems()
    {
        if ($this->items)
        {
            if (!empty($this->itemDatabase))
            {
                TTransaction::open($this->itemDatabase);
            }
            
            $first = reset( $this->items );
            $label = TDateTime::convertToMask( $first->{'date'}, strlen($first->{'date'}) > 10 ? 'yyyy-mm-dd hh:ii:ss' : 'yyyy-mm-dd', $this->timeDisplayMask );
            parent::add( $this->renderLabel( $label ) );
            
            foreach ($this->items as $item)
            {
                $newLabel = TDateTime::convertToMask( $item->{'date'}, strlen($item->{'date'}) > 10 ? 'yyyy-mm-dd hh:ii:ss' : 'yyyy-mm-dd', $this->timeDisplayMask );
                
                if( $newLabel != $label)
                {
                    $label = $newLabel;
                    parent::add( $this->renderLabel( $label ) );
                }
                
                parent::add( $this->renderItem( $item ) );
            }
            
            if (!empty($this->itemDatabase))
            {
                TTransaction::close();
            }
        }
    }
    
    /**
     * Render the final icon at the end of the timeline.
     *
     * If a final icon has been defined, it is displayed as the last element of the timeline.
     */
    private function renderFinalIcon()
    {
        if( $this->finalIcon )
        {
            $li = new TElement( 'li' );
            $li->add( new TImage( $this->finalIcon . ' line-icon'));
            
            parent::add( $li );
        }
    }
    
    /**
     * Display the timeline.
     *
     * Renders all timeline items, inserts labels, applies styles, and outputs the timeline structure.
     */
    public function show()
    {
        $this->renderItems();
        $this->renderFinalIcon();
        
        if( $this->useBothSides )
        {
            $this->{'class'} .= ' timeline-bothsides';
        }
        
        parent::show();
    }
}
