<?php
namespace Adianti\Widget\Util;

use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;
use Adianti\Util\AdiantiTemplateHandler;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Form\TButton;

use stdClass;
use ApplicationTranslator;

/**
 * TCardView
 *
 * A widget that represents a collection of cards with customizable attributes,
 * templates, and actions. It allows defining titles, content, colors, 
 * actions, and search functionalities.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCardView extends TElement
{
    protected $items;
    protected $itemActions;
    protected $templatePath;
    protected $itemTemplate;
    protected $titleTemplate;
    protected $useButton;
    protected $titleField;
    protected $contentField;
    protected $colorField;
    protected $searchAttributes;
    protected $itemHeight;
    protected $contentHeight;
    protected $itemDatabase;
    protected $itemClass;
    
    /**
     * Class Constructor
     *
     * Initializes a new instance of TCardView, setting up the default attributes
     * and configuration.
     */
	public function __construct()
    {
        parent::__construct('div');
        $this->items          = [];
        $this->itemActions    = [];
        $this->useButton      = FALSE;
        $this->searchAttributes = [];
        $this->itemHeight     = NULL;
        $this->contentHeight  = NULL;
        $this->{'id'}         = 'tcard_' . mt_rand(1000000000, 1999999999);
        $this->{'class'}      = 'card-wrapper';
    }
    
    /**
     * Set the minimum height for each card item.
     *
     * @param int|string $height Minimum height in pixels or percentage.
     */
    public function setItemHeight($height)
    {
        $this->itemHeight = $height;
    }
    
    /**
     * Set the database connection for retrieving card items.
     *
     * @param string $database Database connection name.
     */
    public function setItemDatabase($database)
    {
        $this->itemDatabase = $database;
    }
    
    /**
     * Set the minimum height for the card content area.
     *
     * @param int|string $height Minimum height in pixels or percentage.
     */
    public function setContentHeight($height)
    {
        $this->contentHeight = $height;
    }
    
    /**
     * Define the attribute to be used as the card title.
     *
     * @param string $field The name of the attribute to be used as the title.
     */
    public function setTitleAttribute($field)
    {
        $this->titleField = $field;
    }
    
    /**
     * Define the attribute to be used as the card content.
     *
     * @param string $field The name of the attribute to be used as the content.
     */
    public function setContentAttribute($field)
    {
        $this->contentField = $field;
    }
    
    /**
     * Define the attribute to be used for setting the card color.
     *
     * @param string $field The name of the attribute to be used for color.
     */
    public function setColorAttribute($field)
    {
        $this->colorField = $field;
    }

    /**
     * Set a custom CSS class for card items.
     *
     * @param string $class CSS class name to be applied to each card item.
     */
    public function setItemClass($class)
    {
        $this->itemClass = $class;
    }
    
    /**
     * Clear all items from the card view.
     */
    public function clear()
    {
        $this->items = [];
    }
    
    /**
     * Add an item to the card view.
     *
     * @param object $object The data object representing a card item.
     */
    public function addItem($object)
    {
        $this->items[] = $object;
    }
    
    /**
     * Enable the display of item actions as buttons instead of icons.
     */
    public function setUseButton()
    {
        $this->useButton = TRUE;
    }
    
    /**
     * Set the template path for rendering the card items.
     *
     * @param string $path Path to the template file.
     */
    public function setTemplatePath($path)
    {
        $this->templatePath = $path;
    }
    
    /**
     * Set the item template content for rendering the card items.
     *
     * @param string $template The HTML content of the template.
     */
    public function setItemTemplate($template)
    {
        $this->itemTemplate = $template;
    }
    
    /**
     * Set the template for rendering the card title.
     *
     * @param string $template The HTML content of the title template.
     */
    public function setTitleTemplate($template)
    {
        $this->titleTemplate = $template;
    }
    
    /**
     * Add an action to the card items.
     *
     * @param TAction      $action            The action to be executed.
     * @param string       $label             The label of the action.
     * @param string|null  $icon              The icon to be displayed with the action.
     * @param callable|null $display_condition A callable function to determine whether the action should be displayed.
     * @param string|null  $title             Tooltip title for the action.
     * @param bool         $useButton         Whether to display the action as a button.
     */
    public function addAction(TAction $action, $label, $icon = NULL, $display_condition = NULL, $title = NULL, $useButton = false)
    {
        $itemAction            = new stdClass;
        $itemAction->label     = $label;
        $itemAction->action    = $action;
        $itemAction->icon      = $icon;
        $itemAction->condition = $display_condition;
        $itemAction->title     = $title;
        $itemAction->useButton = $useButton;
        
        $this->itemActions[]   = $itemAction;
    }
    
    /**
     * Render a single card item based on the provided data object.
     *
     * @param object $item The data object representing a card item.
     *
     * @return TElement The rendered card item element.
     */
    public function renderItem($item)
    {
        if (!empty($this->templatePath))
        {
            $html = new THtmlRenderer($this->templatePath);
            $html->enableSection('main');
            $html->enableTranslation();
            $html = AdiantiTemplateHandler::replace($html->getContents(), $item);
            
            return $html;
        }
        
        $titleField   = $this->titleField;
        $contentField = $this->contentField;
        $colorField   = $this->colorField;
        
        $item_wrapper              = new TElement('div');
        $item_wrapper->{'class'}   = 'panel card panel-default card-item';

        if ($this->itemClass)
        {
            $item_wrapper->{'class'} .= " {$this->itemClass}";
        }
        
        if ($colorField && $item->$colorField)
        {
            $item_wrapper->{'style'}   = 'border-top: 3px solid '.$item->$colorField;
        }
        
        if ($titleField)
        {
            $item_title = new TElement('div');
            $item_title->{'class'} = 'panel-heading card-header card-item-title';
            $titleField = (strpos($titleField, '{') === FALSE) ? ( '{' . $titleField . '}') : $titleField;
            $item_title->add(AdiantiTemplateHandler::replace($titleField, $item));
        }
        
        if (!empty($this->titleTemplate))
        {
            $item_title = new TElement('div');
            $item_title->{'class'} = 'panel-heading card-header card-item-title';
            $item_title->add(AdiantiTemplateHandler::replace($this->titleTemplate, $item));
        }
        
        if ($contentField)
        {
            $item_content = new TElement('div');
            $item_content->{'class'} = 'panel-body card-body card-item-content';
            $contentField = (strpos($contentField, '{') === FALSE) ? ( '{' . $contentField . '}') : $contentField;
            $item_content->add(AdiantiTemplateHandler::replace($contentField, $item));
        }
        
        if (!empty($this->itemTemplate))
        {
            $item_content = new TElement('div');
            $item_content->{'class'} = 'panel-body card-body card-item-content';
            $item_template = ApplicationTranslator::translateTemplate($this->itemTemplate);
            $item_template = AdiantiTemplateHandler::replace($item_template, $item);
            $item_content->add($item_template);
        }
        
        if (!empty($item_title))
        {
            $item_wrapper->add($item_title);
        }
        
        if (!empty($item_content))
        {
            $item_wrapper->add($item_content);
            
            if (!empty($this->contentHeight))
            {
                $item_content->{'style'}   = 'min-height:'.$this->contentHeight;
                
                if (strstr((string) $this->size, '%') !== FALSE)
                {
                    $item_content->{'style'}   = 'min-height:'.$this->contentHeight;
                }
                else
                {
                    $item_content->{'style'}   = 'min-height:'.$this->contentHeight.'px';
                }
            }
        }
        
        if (!empty($this->itemHeight))
        {
            $item_wrapper->{'style'}   = 'min-height:'.$this->itemHeight;
            
            if (strstr((string) $this->size, '%') !== FALSE)
            {
                $item_wrapper->{'style'}   = 'min-height:'.$this->itemHeight;
            }
            else
            {
                $item_wrapper->{'style'}   = 'min-height:'.$this->itemHeight.'px';
            }
        }
        
        if (count($this->searchAttributes) > 0)
        {
            $item_wrapper->{'id'} = 'row_' . mt_rand(1000000000, 1999999999);
            
            foreach ($this->searchAttributes as $search_att)
            {
                if (isset($item->$search_att))
                {
                    $row_dom_search_att = 'search_' . $search_att;
                    $item_wrapper->$row_dom_search_att = $item->$search_att;
                }
            }
        }
        
        if (!empty($this->itemActions))
        {
            $item_wrapper->add($this->renderItemActions($item));
        }
        
        return $item_wrapper;
    }
    
    /**
     * Enable search functionality using a specified input field and attribute.
     *
     * @param TField $input The input field used for searching.
     * @param string $attribute The attribute to be used for search filtering.
     */
    public function enableSearch(TField $input, $attribute) 
    {
        $input_id    = $input->getId();
        $card_id = $this->{'id'};
        $this->searchAttributes[] = $attribute;
        TScript::create("__adianti_input_fuse_search('#{$input_id}', 'search_{$attribute}', '#{$card_id} .card-item')");
    }
    
    /**
     * Render the actions associated with a given card item.
     *
     * @param object|null $object The data object for which actions should be rendered.
     *
     * @return TElement The rendered actions container.
     */
    private function renderItemActions($object = NULL)
    {
        $div            = new TElement('div');
        $div->{'class'} = 'panel-footer card-footer card-item-actions';
        
        foreach ($this->itemActions as $key => $action)
        {
            if (empty($action->condition) OR call_user_func($action->condition, $object))
            {
                $item_action = clone $action->action;
                if ($item_action->getFieldParameters())
                {
                    $key = $item_action->getFieldParameters()[0];
                    $item_action->setParameter('key', $object->$key);
                }
                
                $url = $item_action->prepare($object)->serialize();
                
                if ($this->useButton || $action->useButton)
                {
                    $button = new TElement('a');
                    $button->{'class'} = 'btn btn-default';
                    $button->{'href'} = $url;
                    $button->{'generator'} = 'adianti';
                    $button->add(new TImage($action->icon));
                    $button->add($action->label); 
                    
                    if (!empty($action->title))
                    {
                        $button->{'title'} = $action->title;
                        $button->{'titside'} = 'bottom';
                    }
                    
                    $div->add($button);
                }
                else
                {
                    $icon                = new TImage($action->icon);
                    $icon->{'style'}    .= ';cursor:pointer;margin-right:4px;';
                    $icon->{'title'}     = $action->label;
                    $icon->{'generator'} = 'adianti';
                    $icon->{'href'}      = $url;
                    
                    $div->add($icon);
                }
            }
        }
        
        return $div;
    }
    
    
    /**
     * Display the card view, rendering all items and applying database transactions if needed.
     */
    public function show()
    {
        if ($this->items)
        {
            if (!empty($this->itemDatabase))
            {
                TTransaction::open($this->itemDatabase);
            }
            
            foreach ($this->items as $item)
            {
                $this->add($this->renderItem($item));
            }
            
            if (!empty($this->itemDatabase))
            {
                TTransaction::close();
            }
        }
        
        parent::show();
    }
}
