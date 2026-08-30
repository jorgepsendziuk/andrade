<?php
namespace Adianti\Widget\Util;

use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Util\AdiantiTemplateHandler;
use Adianti\Widget\Template\THtmlRenderer;

use stdClass;
use ApplicationTranslator;
use Exception;

/**
 * Kanban Board Widget
 *
 * This class provides a Kanban board implementation that allows adding stages, 
 * items, actions, and templates, with support for drag-and-drop functionality.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Artur Comunello
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TKanban extends TElement
{
    protected $kanban;
    protected $items;
    protected $stages;
    protected $itemActions;
    protected $stageActions;
    protected $stageShortcuts;
    protected $itemDropAction;
    protected $stageDropAction;
    protected $templatePath;
    protected $itemTemplate;
    protected $itemDatabase;
    protected $topScrollbar;
    protected $stageHeight;
    protected $miniMap;
    protected $loadMoreAction;
    protected $limitItems;
    
    /**
     * Class Constructor
     *
     * Initializes the Kanban board structure, setting default properties and 
     * creating the main container.
     */
	public function __construct()
    {
        parent::__construct('div');
        $this->items             = [];
        $this->stages            = [];
        $this->itemActions       = [];
        $this->stageActions      = [];
        $this->stageShortcuts    = [];
        $this->topScrollbar      = false;
        $this->miniMap           = false;
        $this->loadMoreAction    = null;
        $this->limitItems        = 20;
        
        $this->kanban                 = new TElement('div');
        $this->kanban->id             = 'tkanban_' . mt_rand(1000000000, 1999999999);
        $this->kanban->{'item_class'} = 'kanban-item-wrapper';
        $this->kanban->class          = 'kanban-board';
    }
    
    /**
     * Set the height of Kanban stages
     *
     * @param int|string $height Height value (can be in pixels or a CSS-compatible unit)
     */
    public function setStageHeight($height)
    {
        $this->stageHeight = $height;
    }
    
    /**
     * Enable the top scrollbar for horizontal scrolling
     */
    public function enableTopScrollbar()
    {
        $this->topScrollbar = true;
    }

    /**
     * Enable the minimap for the Kanban board
     */
    public function enableMiniMap()
    {
        $this->miniMap = true;
    }

    /**
     * Define the action to load more items dynamically
     *
     * @param TAction $action The action to be triggered for loading more items
     * @param int|null $limit The maximum number of items to be loaded at a time (optional)
     */
    public function setLoadMoreAction(TAction $action, $limit = null)
    {
        $this->loadMoreAction = $action;
        
        if ($limit) {
            $this->limitItems = $limit;
        }
    }
    
    /**
     * Set the limit for the number of items to load
     *
     * @param int $limit Number of items to load per request
     */
    public function setLimitItem($limit)
    {
        $this->limitItems = $limit;
    }
    
    /**
     * Add a stage to the Kanban board
     *
     * @param string|int $id Stage identifier
     * @param string $title Stage title
     * @param stdClass|null $object Additional data associated with the stage (optional)
     * @param string|null $color Stage background color (optional)
     */
    public function addStage($id, $title, $object = null, $color = null)
    {
        if (is_null($object))
        {
            $object = new stdClass;
        }
        
        $stage             = new stdClass;
        $stage->id     = $id;
        $stage->title  = $title;
        $stage->object = $object;
        $stage->color  = $color;
        
        $this->stages[] = $stage;
    }
    
    /**
     * Add an item to a specific stage in the Kanban board
     *
     * @param string|int $id Item identifier
     * @param string|int $stage_id Stage identifier where the item will be added
     * @param string $title Item title
     * @param string $content Item content
     * @param string|null $color Item background color (optional)
     * @param stdClass|null $object Additional data associated with the item (optional)
     *
     * @return stdClass The created item object
     */
    public function addItem($id, $stage_id, $title, $content, $color = null, $object = null)
    {
        if (is_null($object))
        {
            $object = new stdClass;
            $object->title = $title;
            $object->content = $content;
            $object->color = $color;
        }

        if (empty($object->id))
        {
            $object->id = $id;
        }

        $item              = new stdClass;
        $item->id      = $id;
        $item->title   = $title;
        $item->color   = $color;
        $item->content = $content;
        $item->object  = $object;
        
        $this->items[$stage_id][] = $item;
        
        return $item;
    }
    
    /**
     * Set the template path for Kanban item rendering
     *
     * @param string $path Path to the template file
     */
    public function setTemplatePath($path)
    {
        $this->templatePath = $path;
    }
    
    /**
     * Set the HTML template for rendering Kanban items
     *
     * @param string $template Template content
     */
    public function setItemTemplate($template)
    {
        $this->itemTemplate = $template;
    }
    
    /**
     * Set the database connection name for item retrieval
     *
     * @param string $database Database connection identifier
     */
    public function setItemDatabase($database)
    {
        $this->itemDatabase = $database;
    }
    
    /**
     * Set the action to be triggered when an item is dropped
     *
     * @param TAction $action The action to execute
     *
     * @throws Exception If the provided action is not static
     */
    public function setItemDropAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->itemDropAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Set the action to be triggered when a stage is dropped
     *
     * @param TAction $action The action to execute
     *
     * @throws Exception If the provided action is not static
     */
    public function setStageDropAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->stageDropAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Add an action to Kanban items
     *
     * @param string $label Action label
     * @param TAction $action Action callback
     * @param string|null $icon Icon associated with the action (optional)
     * @param callable|null $display_condition Condition callback to determine visibility (optional)
     * @param bool $useButton Whether to display the action as a button (default: false)
     *
     * @return stdClass The created action object
     */
    public function addItemAction($label, TAction $action, $icon = NULL, $display_condition = NULL, $useButton = FALSE)
    {
        $itemAction            = new stdClass;
        $itemAction->label     = $label;
        $itemAction->action    = $action;
        $itemAction->icon      = $icon;
        $itemAction->condition = $display_condition;
        $itemAction->useButton = $useButton;
        
        $this->itemActions[]   = $itemAction;

        return $itemAction;
    }
    
    /**
     * Add an action to Kanban stages
     *
     * @param string $label Action label
     * @param TAction $action Action callback
     * @param string|null $icon Icon associated with the action (optional)
     * @param callable|null $display_condition Condition callback to determine visibility (optional)
     */
    public function addStageAction($label, TAction $action, $icon = NULL, $display_condition = NULL)
    {
        $stageAction            = new stdClass;
        $stageAction->label     = $label;
        $stageAction->action    = $action;
        $stageAction->icon      = $icon;
        $stageAction->condition = $display_condition;
        
        $this->stageActions[] = $stageAction;
    }
    
    /**
     * Add a shortcut action to Kanban stages
     *
     * @param string $label Shortcut label
     * @param TAction $action Shortcut callback
     * @param string|null $icon Icon associated with the shortcut (optional)
     */
    public function addStageShortcut($label, TAction $action, $icon = NULL)
    {
        $stageAction          = new stdClass;
        $stageAction->label   = $label;
        $stageAction->action  = $action;
        $stageAction->icon    = $icon;
        
        $this->stageShortcuts[] = $stageAction;
    }
    
    /**
     * Render all items within a given stage
     *
     * This method generates the container for stage items, applies styles, and 
     * populates it with existing items.
     *
     * @param TElement $stage The stage element where items will be rendered
     */
    private function renderStageItems($stage)
    {
        $itemSortable               = new TElement('div');
        $itemSortable->class    = 'kanban-item-sortable ' . $this->kanban->item_class;
        $itemSortable->{'stage_id'} = $stage->{'stage_id'};
        $itemSortable->{'data-count'} = count($this->items[$stage->{'stage_id'}]??[]);
        $itemSortable->style = '';

        if($this->miniMap == true)
        {
            $itemSortable->style .= 'flex: 1 1 auto;';
        }

        if (!empty($this->stageHeight))
        {
            $itemSortable->style .= ';overflow-y:auto;height:'.$this->stageHeight; 
        }

        if (!empty($this->itemDatabase))
        {
            TTransaction::open($this->itemDatabase);
        }

        if (!empty($this->items[$stage->{'stage_id'}]))
        {
            foreach ($this->items[$stage->{'stage_id'}] as $key => $item)
            {
                $itemSortable->add(self::renderItem($item));
            }
        }

        if (!empty($this->itemDatabase))
        {
            TTransaction::close();
        }
        
        $stage->add($itemSortable);
    }

    
    /**
     * Render a single Kanban item
     *
     * This method creates the item structure using either a template or a standard
     * HTML structure, including title, content, and styling.
     *
     * @param stdClass $item The item object containing its properties
     *
     * @return TElement|string The generated HTML element or rendered template
     */
    private function renderItem($item)
    {
        if (!empty($this->templatePath))
        {
            $html = new THtmlRenderer($this->templatePath);
            $html->enableSection('main');
            $html->enableTranslation();
            $html = AdiantiTemplateHandler::replace($html->getContents(), $item->object);

            return $html;
        }
        
        $item_wrapper              = new TElement('div');
        $item_wrapper->{'item_id'} = $item->id;
        $item_wrapper->class   = 'kanban-item';
        
        if (!empty($item->color))
        {
            $item_wrapper->style = 'border-top: 3px solid '.$item->color;
        }

        $item_title = new TElement('div');
        $item_title->class = 'kanban-item-title';
        $item_title->add(AdiantiTemplateHandler::replace($item->title, $item->object));
        
        $item_content = new TElement('div');
        $item_content->class = 'kanban-item-content';
        $item_content->add(AdiantiTemplateHandler::replace($item->content, $item->object));
        
        if (!empty($this->itemTemplate))
        {
            $item_content = new TElement('div');
            $item_content->class = 'kanban-item-content';
            $item_template = ApplicationTranslator::translateTemplate($this->itemTemplate);
            $item_template = AdiantiTemplateHandler::replace($item_template, $item);
            $item_content->add($item_template);
        }
        
        $item_wrapper->add($item_title);
        $item_wrapper->add($item_content);

        if (!empty($this->itemActions))
        {
            $item_wrapper->add($this->renderItemActions($item->id, $item->object));
        }
        return $item_wrapper;
    }
    
    /**
     * Render all stages of the Kanban board
     *
     * This method iterates through all defined stages, creating their respective
     * elements, actions, and associated items.
     */
    private function renderStages()
    {
        foreach ($this->stages as $key => $stage)
        {
            $title            = new TElement('div');
            $title->class = 'kanban-title';
            $title->add(AdiantiTemplateHandler::replace($stage->title, $stage->object));
            
            $stageDiv               = new TElement('div');
            $stageDiv->{'stage_id'} = $stage->id;
            $stageDiv->class    = 'kanban-stage';
            
            if($this->miniMap)
            {
                $title->style    .= 'flex: 0 1 auto;';
                $stageDiv->style = 'height: 100%; display: flex; flex-flow: column; width: 330px;';
            }
            
            if (!empty($stage->color))
            {
                $stageDiv->style = 'background:'.$stage->color;
            }
            
            $stageDiv->add($title);
            
            if (!empty($this->stageActions))
            {
                $title = $stageDiv->children[0];
                $title->add($this->renderStageActions( $stage->id, $stage ));
            }
            
            $this->renderStageItems($stageDiv);
            $this->kanban->add($stageDiv);
            
            $stageDiv->add($this->renderStageShortcuts( $stage ));
            
        }
    }
    
    /**
     * Render actions for a Kanban item
     *
     * This method generates action buttons/icons for a given item, based on 
     * previously defined actions.
     *
     * @param string|int $itemId The ID of the item
     * @param stdClass|null $object The data object associated with the item (optional)
     *
     * @return TElement The generated action container
     */
    private function renderItemActions($itemId, $object = NULL)
    {
        $div            = new TElement('div');
        $div->class = 'kanban-item-actions';
        
        foreach ($this->itemActions as $key => $actionTemplate)
        {
            if($actionTemplate->action->isHidden())
            {
                return;
            }

            $itemAction = $actionTemplate->action->prepare($object);
            
            if (empty($actionTemplate->condition) OR call_user_func($actionTemplate->condition, $object))
            {
                $itemAction->setParameter('id', $itemId);
                $itemAction->setParameter('key', $itemId);
                $url = $itemAction->serialize();
                
                if ($actionTemplate->useButton)
                {
                    $icon = new TImage($actionTemplate->icon);
                    $icon->style .= ';cursor:pointer;margin-right:4px;border:unset;padding:0px;box-shadow:unset;background-color:transparent !important;';

                    $action = new TElement('button');
                    $action->class     = 'btn ' . (empty($actionTemplate->buttonClass) ? 'btn-default' : $actionTemplate->buttonClass);
                    $action->{'type'}      = 'button';
                    $action->generator = 'adianti';
                    $action->href      = $url;
                    $action->add($icon);

                    if($itemAction->isDisabled())
                    {
                        unset($action->generator);
                        $action->disabled = 'disabled';
                    }

                    $action->add(TElement::tag('span', $actionTemplate->label));
                    
                    $div->add($action);
                }
                else
                {
                    $icon                = new TImage($actionTemplate->icon);
                    $icon->style    .= ';cursor:pointer;margin-right:4px;';
                    $icon->title     = $actionTemplate->label;
                    $icon->generator = 'adianti';
                    $icon->href      = $url;
                    
                    if($itemAction->isDisabled())
                    {
                        unset($icon->generator);
                        $icon->disabled = 'disabled';
                    }

                    $div->add($icon);
                }
            }
        }
        
        return $div;
    }
    
    /**
     * Render actions for a Kanban stage
     *
     * This method creates a dropdown menu containing the available actions for 
     * a given stage.
     *
     * @param string|int $stage_id The ID of the stage
     * @param stdClass $stage The stage data object
     *
     * @return TElement The generated action container
     */
    private function renderStageActions($stage_id, $stage)
    {
        $icon                  = new TImage('mi:more_vert');
        $icon->{'data-toggle'} = 'dropdown';

        $ul = new TElement('ul');
        $ul->class = 'dropdown-menu pull-right';
        
        foreach ($this->stageActions as $key => $stageActionTemplate)
        {
            $stageAction = $stageActionTemplate->action->prepare($stage);

            if($stageAction->isHidden())
            {
                return;
            }
            
            if (empty($stageActionTemplate->condition) OR call_user_func($stageActionTemplate->condition, $stage))
            {
                $stageAction->setParameter('id',  $stage_id);
                $stageAction->setParameter('key', $stage_id);
                $url = $stageAction->serialize();
                
                $action            = new TElement('a');
                $action->generator = 'adianti';
                $action->href      = $url;

                if($stageAction->isDisabled())
                {
                    unset($action->generator);
                    $action->disabled = 'disabled';
                }
                if (!empty($stageActionTemplate->icon))
                {
                    $action->add(new TImage($stageActionTemplate->icon));
                }
                $action->add($stageActionTemplate->label);
                
                $li = new TElement('li');
                $li->add($action);
                $ul->add($li);
            }
        }
        
        $dropWrapper = new TElement('div');
        $dropWrapper->style = 'cursor:pointer;';
        $dropWrapper->class = 'btn-group user-helper-dropdown';
        $dropWrapper->add($icon);
        $dropWrapper->add($ul);

        $stageActions = new TElement('span');
        $stageActions->style = 'float: right;';
        $stageActions->class = 'kanban-stage-actions';
        $stageActions->add($dropWrapper);
        
        return $stageActions;
    }
    
    /**
     * Render shortcut actions for a Kanban stage
     *
     * This method generates shortcut buttons/icons for quick actions related 
     * to a stage.
     *
     * @param stdClass $stage The stage data object
     *
     * @return TElement The generated shortcut container
     */
    private function renderStageShortcuts($stage)
    {
        $actions_wrapper = new TElement('div');
        $actions_wrapper->class = 'kanban-shortcuts';

        if($this->miniMap == true)
        {
            $actions_wrapper->style = 'position: sticky; bottom: 0px; flex: 0 1 auto;';
        }

        foreach ($this->stageShortcuts as $key => $stageActionTemplate)
        {
            $stageAction = $stageActionTemplate->action->prepare($stage);

            if($stageAction->isHidden())
            {
                return;
            }
            
            $stageAction->setParameter('id',  $stage->id);
            $stageAction->setParameter('key', $stage->id);
            $url = $stageAction->serialize();
            
            $action              = new TElement('a');
            $action->generator   = 'adianti';
            $action->href        = $url;

            if($stageAction->isDisabled())
            {
                unset($action->generator);
                $action->disabled = 'disabled';
            }
            
            if (!empty($stageActionTemplate->icon))
            {
                $action->add(new TImage($stageActionTemplate->icon));
            }
            $action->add($stageActionTemplate->label);
            
            $actions_wrapper->add($action);
        }
        
        return $actions_wrapper;
    }
    
    /**
     * Render and display the Kanban board
     *
     * Generates the necessary HTML structure and executes the required JavaScript.
     */
    public function show()
    {
        $this->renderStages();
        
        if($this->miniMap)
        {
            $controller_size = (count($this->stages) * 36) + 10;
            
            $layout_controller        = new TElement('div');
            $layout_controller->id    = 'tkanban-layout-controller';
            $layout_controller->style = "background: white; padding: 0px 5px; height: 60px; width: {$controller_size}px; position: absolute; bottom:10px; right: 10px; border: 1px solid #dfe4ed; border-radius: 5px; display: flex;";

            $border_controler        = new TElement('div'); 
            $border_controler->class  = "tkanban-border-controler";
            $border_controler->style = 'max-width: calc(100% - 6px); cursor: move; border: 2px solid #000000;height: 50px;width: calc(10vw - 14px);border-radius: 5px;margin: 5px -2px;position: inherit;';
            
            $layout_controller->add($border_controler);
            
            foreach ($this->stages as $key => $stage)
            {
                $mini_div        = new TElement('div');
                $mini_div->style = "margin: 10px 2px; height: 40px; width: 31px; background: #dfe4ed; border-radius: 2px;";

                $layout_controller->add($mini_div);
            }

            $this->kanban->add($layout_controller);
        }

        $this->add($this->kanban);
        $this->style .= ';overflow-x:auto';
        $this->class  = 'kanban-board-wrapper';
        
        if ($this->topScrollbar && !$this->miniMap)
        {
            $this->class  = 'kanban-board-wrapper top-scroll';
        }
        
        if (!empty($this->stageDropAction))
        {
            $stage_drop_action_string = $this->stageDropAction->serialize();
            TScript::create("kanban_start_board('{$this->kanban->id}', '{$stage_drop_action_string}');");
        }

        if (!empty($this->itemDropAction))
        {
            $item_drop_action_string = $this->itemDropAction->serialize();
            TScript::create("kanban_start_item('{$this->kanban->item_class}', '{$item_drop_action_string}');");
        }
        
        if ($this->miniMap ) {
            TScript::create("kanban_start_minimap('{$this->kanban->id}');");
        }
        
        if ($this->loadMoreAction) {
            $this->loadMoreAction->setParameter('limit', $this->limitItems);
            $loadMoreAction = $this->loadMoreAction->serialize();
            TScript::create("kanban_start_load_more('{$this->kanban->id}', '{$loadMoreAction}');");
        }
        
        parent::show();
    }

    /**
     * Create and add a new Kanban item dynamically
     *
     * @param string|int $id Item identifier
     * @param string|int $stage_id Stage identifier
     * @param string $title Item title
     * @param string $content Item content
     * @param string|null $color Item background color (optional)
     * @param stdClass $object Additional data associated with the item
     * @param string|null $htmlTemplate Path to the template file (optional)
     * @param array $actions List of item actions (optional)
     */
    public static function createItem($id, $stage_id, $title, $content, $color, $object, $htmlTemplate = null, $actions = [])
    {
        $kanban = new self;
        if ($htmlTemplate) 
        {
            $kanban->setTemplatePath($htmlTemplate);
        }

        $item = $kanban->addItem($id, $stage_id, $title, $content, $color, $object);

        if($actions)
        {
            foreach($actions as $action)
            {
                $kanban->addItemAction($action[0], $action[1] ?? null, $action[2] ?? null,  $action[3] ?? false);
            }
        }

        $html = base64_encode($kanban->renderItem($item));

        TScript::create("tkanban_add_item('{$id}', '{$stage_id}', '{$html}');");
    }

    /**
     * Generate the HTML representation of a Kanban item
     *
     * @param string|int $id Item identifier
     * @param string|int $stage_id Stage identifier
     * @param string $title Item title
     * @param string $content Item content
     * @param string|null $color Item background color (optional)
     * @param stdClass $object Additional data associated with the item
     * @param string|null $htmlTemplate Path to the template file (optional)
     *
     * @return string Base64-encoded HTML representation of the item
     */
    public static function getHtmlItem($id, $stage_id, $title, $content, $color, $object, $htmlTemplate = null)
    {
        $kanban = new self;
        if ($htmlTemplate) 
        {
            $kanban->setTemplatePath($htmlTemplate);
        }

        $item = $kanban->addItem($id, $stage_id, $title, $content, $color, $object);

        $html = base64_encode($kanban->renderItem($item));

        return $html;
    }

    /**
     * Clear all items from a specific Kanban stage
     *
     * @param string|int $stage_id Identifier of the stage to clear
     */
    public static function clearStage($stage_id)
    {
        TScript::create("$(\".kanban-item-wrapper[stage_id={$stage_id}]\").empty();");
    }
    
}