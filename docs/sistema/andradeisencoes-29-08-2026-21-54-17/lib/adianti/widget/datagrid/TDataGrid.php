<?php
namespace Adianti\Widget\Datagrid;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Util\TImage;
use Adianti\Util\AdiantiTemplateHandler;

use Math\Parser;
use Exception;

/**
 * DataGrid Widget: Allows creating datagrids with rows, columns, and actions.
 * It supports scrollable tables, grouping, popovers, inline editing, 
 * column totalizers, and different action placements.
 *
 * @version    7.5
 * @package    widget
 * @subpackage datagrid
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TDataGrid extends TTable
{
    protected $columns;
    protected $actions;
    protected $action_groups;
    protected $rowcount;
    protected $thead;
    protected $tbody;
    protected $tfoot;
    protected $height;
    protected $scrollable;
    protected $modelCreated;
    protected $pageNavigation;
    protected $defaultClick;
    protected $groupColumn;
    protected $groupTransformer;
    protected $groupTotal;
    protected $groupContent;
    protected $groupMask;
    protected $popover;
    protected $poptitle;
    protected $popside;
    protected $popcontent;
    protected $popcondition;
    protected $objects;
    protected $objectsGroup;
    protected $actionWidth;
    protected $groupCount;
    protected $groupRowCount;
    protected $columnValues;
    protected $columnValuesGroup;
    protected $HTMLOutputConversion;
    protected $searchAttributes;
    protected $outputData;
    protected $hiddenFields;
    protected $prependRows;
    protected $hasInlineEditing;
    protected $hasTotalFunction;
    protected $actionSide;
    protected $mutationAction;
    protected $propertiesEnabled;
    protected $propertiesButtonIcon;
    protected $propertiesButtonClass;
    protected $propertiesButtonAction;
    protected $hideColumns;
    protected $hasHiddenColumns;
    
    /**
     * Class constructor.
     * Initializes default properties and sets up the datagrid structure.
     */
    public function __construct()
    {
        parent::__construct();
        $this->modelCreated = FALSE;
        $this->defaultClick = TRUE;
        $this->popover = FALSE;
        $this->groupColumn = NULL;
        $this->groupContent = NULL;
        $this->groupMask = NULL;
        $this->groupCount = 0;
        $this->actions = array();
        $this->action_groups = array();
        $this->actionWidth = '28px';
        $this->objects = array();
        $this->objectsGroup = array();
        $this->columnValues = array();
        $this->columnValuesGroup = array();
        $this->HTMLOutputConversion = true;
        $this->searchAttributes = [];
        $this->outputData = [];
        $this->hiddenFields = false;
        $this->prependRows = 0;
        $this->hasInlineEditing = false;
        $this->hasTotalFunction = false;
        $this->actionSide = 'left';
        $this->propertiesEnabled = FALSE;
        $this->propertiesButtonIcon = 'fa fa-cog';
        $this->propertiesButtonClass = 'btn-datagrid-properties';
        $this->propertiesButtonAction = null;
        
        $this->rowcount = 0;
        $this->{'class'} = 'tdatagrid_table';
        $this->{'id'}    = 'tdatagrid_' . mt_rand(1000000000, 1999999999);
    }
    
    /**
     * Sets the datagrid ID.
     *
     * @param string $id The ID to be set for the datagrid.
     */
    public function setId($id)
    {
        $this->{'id'} = $id;
    }
    
    /**
     * Defines a mutation action for the datagrid.
     * This action is triggered when rows are modified.
     *
     * @param TAction $action The mutation action to set.
     */
    public function setMutationAction(TAction $action)
    {
        $this->mutationAction = $action;
    }
    
    /**
     * Sets the side where actions are displayed in the datagrid.
     *
     * @param string $side The side where actions should be placed ('left' or 'right').
     */
    public function setActionSide($side)
    {
        $this->actionSide = $side;
    }
    
    /**
     * Enables the generation of hidden fields for the datagrid.
     * This allows certain field values to be stored in hidden inputs.
     */
    public function generateHiddenFields()
    {
        $this->hiddenFields = true;
    }
    
    /**
     * Disables the automatic conversion of special characters into HTML entities.
     * This allows raw HTML content to be rendered in the datagrid.
     */
    public function disableHtmlConversion()
    {
        $this->HTMLOutputConversion = false;
    }
    
    /**
     * Retrieves the processed output data of the datagrid.
     *
     * @return array The array containing the processed output data.
     */
    public function getOutputData()
    {
        return $this->outputData;
    }
    
    /**
     * Enables a popover for each row in the datagrid.
     *
     * @param string      $title       The title of the popover.
     * @param string      $content     The content displayed inside the popover.
     * @param string|null $popside     The position of the popover (e.g., 'top', 'left').
     * @param callable|null $popcondition A callable condition to determine whether to show the popover.
     */
    public function enablePopover($title, $content, $popside = null, $popcondition = null)
    {
        $this->popover = TRUE;
        $this->poptitle = $title;
        $this->popcontent = $content;
        $this->popside = $popside;
        $this->popcondition = $popcondition;
    }
    
    /**
     * Makes the datagrid scrollable by enabling horizontal or vertical scrolling.
     */
    public function makeScrollable()
    {
        $this->scrollable = TRUE;
        
        if (isset($this->thead))
        {
            $this->thead->{'style'} = 'display: block';
        }
    }
    
    /**
     * Checks if the datagrid is scrollable.
     *
     * @return bool True if the datagrid is scrollable, false otherwise.
     */
    public function isScrollable()
    {
        return $this->scrollable;
    }
    
    /**
     * Checks if the datagrid has a custom width set.
     *
     * @return bool True if a custom width is defined, false otherwise.
     */
    private function hasCustomWidth()
    {
        return ( (strpos((string) $this->getProperty('style'), 'width') !== false) OR !empty($this->getProperty('width')));
    }
    
    /**
     * Sets the width of the action column in the datagrid.
     *
     * @param string $width The width value (e.g., '50px').
     */
    public function setActionWidth($width)
    {
        $this->actionWidth = $width;
    }
    
    /**
     * Disables the default click action on rows.
     */
    public function disableDefaultClick()
    {
        $this->defaultClick = FALSE;
    }
    
    /**
     * Defines the height of the datagrid.
     *
     * @param int|string $height The height value, either as an integer (pixels) or a CSS unit (e.g., '100px').
     */
    public function setHeight($height)
    {
        if (is_numeric($height))
        {
            $this->height = $height . 'px';
        }
        else
        {
            $this->height = $height;
        }
    }
    
    /**
     * Retrieves the height of the datagrid.
     *
     * @return string|null The height of the datagrid.
     */
    public function getHeight()
    {
        return $this->height;
    }
    
    /**
     * Adds a column to the datagrid.
     *
     * @param TDataGridColumn $object The column object to be added.
     * @param TAction|null    $action An optional action to be associated with the column.
     *
     * @return TDataGridColumn The added column.
     * @throws Exception If the model has already been created.
     */
    public function addColumn(TDataGridColumn $object, ?TAction $action = null)
    {
        if ($this->modelCreated)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', __METHOD__ , 'createModel') );
        }
        else
        {
            $this->columns[] = $object;
            
            if (!empty($action))
            {
                $object->setAction($action);
            }
        }
        
        return $object;
    }
    
    /**
     * Retrieves all columns in the datagrid.
     *
     * @return TDataGridColumn[] An array of TDataGridColumn objects.
     */
    public function getColumns()
    {
        return $this->columns;
    }
    
    /**
     * Adds an action to the datagrid.
     *
     * @param TDataGridAction $action The action object.
     * @param string|null     $label  The label of the action.
     * @param string|null     $image  The image icon associated with the action.
     *
     * @throws Exception If the action field is not defined or the model has already been created.
     */
    public function addAction(TDataGridAction $action, $label = null, $image = null)
    {
        if (!$action->fieldDefined())
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must define the field for the action (^1)', $action->toString()) );
        }
        
        if ($this->modelCreated)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', __METHOD__ , 'createModel') );
        }
        else
        {
            if($action->isHidden())
            {
                return;
            }
            
            $this->actions[] = $action;
            
            if (!empty($label))
            {
                $action->setLabel($label);
            }
            
            if (!empty($image))
            {
                $action->setImage($image);
            }
        }
    }

    /**
     * Sets multiple actions for the datagrid.
     *
     * @param TDataGridAction[] $actions An array of TDataGridAction objects.
     */
    public function setActions($actions)
    {
        $this->actions = [];

        if (! empty($actions))
        {
            foreach($actions as $action)
            {
                $this->addAction($action);
            }
        }
    }
    
    /**
     * Prepares the datagrid for printing by removing actions and resetting the model.
     */
    public function prepareForPrinting()
    {
        parent::clearChildren();
        $this->actions = [];
        $this->action_groups = [];
        $this->prependRows = 0;
        
        if ($this->columns)
        {
            foreach ($this->columns as $column)
            {
                $column->removeAction();
            }
        }
        
        $this->createModel();
    }
    
    /**
     * Adds an action group to the datagrid.
     *
     * @param TDataGridActionGroup $object The action group to be added.
     *
     * @throws Exception If the model has already been created.
     */
    public function addActionGroup(TDataGridActionGroup $object)
    {
        if ($this->modelCreated)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', __METHOD__ , 'createModel') );
        }
        else
        {
            $this->action_groups[] = $object;
        }
    }
    
    /**
     * Returns the total number of columns including action columns.
     *
     * @return int The total number of columns.
     */
    public function getTotalColumns()
    {
        return count($this->columns) + count($this->actions) + count($this->action_groups);
    }
    
    /**
     * Sets the column used for grouping rows.
     *
     * @param string        $column      The column name used for grouping.
     * @param string        $mask        The format mask for group display.
     * @param callable|null $transformer A transformer function applied to the group values.
     */
    public function setGroupColumn($column, $mask, $transformer = null)
    {
        $this->groupColumn      = $column;
        $this->groupMask        = $mask;
        $this->groupTransformer = $transformer;
    }

    /**
     * Enables or disables the group total feature.
     *
     * @param bool|null $groupTotal Whether to enable group total calculations.
     */
    public function useGroupTotal($groupTotal = null)
    {
        $this->groupTotal = $groupTotal;
    }
    
    /**
     * Clears the datagrid contents while optionally preserving the header.
     *
     * @param bool $preserveHeader Whether to preserve the header row.
     * @param int  $rows           The number of rows to keep.
     */
    public function clear( $preserveHeader = TRUE, $rows = 0)
    {
        if ($this->prependRows > 0)
        {
            $rows += $this->prependRows;
        }
        
        if ($this->modelCreated)
        {
            // copy the headers
            $current_header = $this->children[0];
            $current_body   = $this->children[1];
            
            if ($preserveHeader)
            {
                // reset the row array
                $this->children = array();
                // add the header again
                $this->children[] = $current_header;
            }
            else
            {
                // reset the row array
                $this->children = array();
            }
            
            // add an empty body
            $this->tbody = new TElement('tbody');
            $this->tbody->{'class'} = 'tdatagrid_body';
            if ($this->scrollable)
            {
                $this->tbody->{'style'} = "height: {$this->height}; display: block; overflow-y:scroll; overflow-x:hidden;";
            }
            parent::add($this->tbody);
            
            if ($rows)
            {
                for ($n=0; $n < $rows; $n++)
                {
                    $this->tbody->add($current_body->getChildren()[$n]);
                }
            }
            
            // restart the row count
            $this->rowcount = 0;
            $this->objects = array();
            $this->objectsGroup = array();
            $this->columnValues = array();
            $this->columnValuesGroup = array();
            $this->groupContent = NULL;
        }
    }
    
    /**
     * Creates header cells for action columns in the datagrid.
     *
     * @param TElement $row The row element where action header cells will be added.
     */
    private function createHeaderActionCells( $row )
    {
        $actions_count = count($this->actions) + count($this->action_groups);
        
        if ($actions_count >0)
        {
            for ($n=0; $n < $actions_count; $n++)
            {
                $cell = new TElement('th');
                $row->add($cell);
                $cell->add('<span style="min-width:calc('.$this->actionWidth.' - 2px);display:block"></span>');
                $cell->{'class'} = 'tdatagrid_action';
                $cell->{'style'} = 'padding:0';
                $cell->{'width'} = $this->actionWidth;
            }
        }
    }
    
    /**
     * Enables properties panel for the datagrid.
     * Adds a new header cell with a button at the end of the thead.
     * 
     * @param string $buttonIcon  The icon to be displayed in the button (FontAwesome class)
     * @param string $buttonClass The CSS class for the button
     * @param TAction $action     The action to be executed when the button is clicked
     * @return void
     */
    public function enableUserProperties($buttonIcon = 'fa fa-cog', $buttonClass = 'btn btn-default', ?TAction $action = null)
    {
        $this->propertiesEnabled = TRUE;
        $this->propertiesButtonIcon = $buttonIcon;
        $this->propertiesButtonClass = $buttonClass;
        $this->propertiesButtonAction = $action;
    }
    
    /**
     * Creates the datagrid structure, including headers and body.
     *
     * @param bool $create_header Whether to create the table header.
     */
    public function createModel( $create_header = true )
    {
        if (!$this->columns)
        {
            return;
        }
        
        if ($create_header)
        {
            $this->thead = new TElement('thead');
            $this->thead->{'class'} = 'tdatagrid_head';
            parent::add($this->thead);
            
            $row = new TElement('tr');
            if ($this->scrollable)
            {
                $this->thead->{'style'} = 'display:block';
                if ($this->hasCustomWidth())
                {
                    $row->{'style'} = 'display: inline-table; width: calc(100% - 20px)';
                }
            }
            $this->thead->add($row);
            
            if ($this->actionSide == 'left')
            {
                $this->createHeaderActionCells($row);
            }
            
            // add some cells for the data
            if ($this->columns)
            {
                $output_row = [];
                // iterate the DataGrid columns
                foreach ($this->columns as $column)
                {
                    // get the column properties
                    $name  = $column->getName();

                    if(!empty($this->hideColumns[md5($name)]) || $column->isHidden())
                    {
                        $this->hasHiddenColumns = true;

                        $label = $column->getLabel();

                        $cell = new TElement('th');
                        $cell->setProperty('data-column-id', md5($name));
                        $row->add($cell);
                        
                        $cell->setProperty('class', 'tdatagrid_col');
                        $cell->setProperty('data-start-hide', 'true');
                        $cell->style .= ";display:none;";

                        $cell->add($label);

                        $output_row[] = $column->getLabel();

                        continue;
                    }

                    $label = $column->getLabel();
                    $align = $column->getAlign();
                    $width = $column->getWidth();
                    $props = $column->getProperties();
                    
                    if ($column->isSearchable())
                    {
                        $input_search = $column->getInputSearch();
                        $this->enableSearch($input_search, $name);
                        $label .= '&nbsp;'.$input_search;
                    }
                    
                    $col_action = $column->getAction();
                    if ($col_action)
                    {
                        $action_params = $col_action->getParameters();
                    }
                    else
                    {
                        $action_params = null;
                    }
                    
                    $output_row[] = $column->getLabel();
                    
                    if (isset($_GET['order']))
                    {
                        if ($_GET['order'] == $name || (isset($action_params['order']) && $action_params['order'] == $_GET['order']))
                        {
                            if (isset($_GET['direction']) AND $_GET['direction'] == 'asc')
                            {
                                $label .= '<span class="fa fa-chevron-down blue" aria-hidden="true"></span>';
                            }
                            else
                            {
                                $label .= '<span class="fa fa-chevron-up blue" aria-hidden="true"></span>';
                            }
                        }
                    }
                    // add a cell with the columns label
                    $cell = new TElement('th');
                    $cell->setProperty('data-column-id', md5($name));
                    $row->add($cell);
                    $cell->add($label);
                    
                    $cell->{'class'} = 'tdatagrid_col';
                    $cell->{'style'} = "text-align:$align;user-select:none";
                    
                    if ($props)
                    {
                        $cell->setProperties($props);
                    }
                    
                    if ($width)
                    {
                        $cell->{'width'} = (strpos($width, '%') !== false || strpos($width, 'px') !== false) ? $width : ($width + 8).'px';
                    }
                    
                    // verify if the column has an attached action
                    if ($column->getAction())
                    {
                        $action = $column->getAction();
                        if (isset($_GET['direction']) AND $_GET['direction'] == 'asc' AND isset($_GET['order']) AND ($_GET['order'] == $name || (isset($action_params['order']) && $action_params['order'] == $_GET['order'])) )
                        {
                            $action->setParameter('direction', 'desc');
                        }
                        else
                        {
                            $action->setParameter('direction', 'asc');
                        }
                        $url    = $action->serialize();
                        $cell->{'href'}        = htmlspecialchars($url);
                        $cell->{'style'}      .= ";cursor:pointer;";
                        $cell->{'generator'}   = 'adianti';
                    }
                }
                
                $this->outputData[] = $output_row;
            }
            
            if ($this->actionSide == 'right')
            {
                $this->createHeaderActionCells($row);
            }
            
            // Add properties button at the end of header if enabled
            if ($this->propertiesEnabled)
            {
                $cell = new TElement('th');
                $row->add($cell);
                $cell->{'class'} = 'tdatagrid_properties';
                $cell->{'style'} = 'text-align:right';
                $cell->{'width'} = $this->actionWidth;
                
                $button = new TElement('span');
                $button->setProperty('class', $this->propertiesButtonClass . ' tdatagrid-property-btn');
                $button->add('<i class="' . $this->propertiesButtonIcon . '"></i>');
                
                if ($this->propertiesButtonAction)
                {
                    $url = $this->propertiesButtonAction->serialize();
                    $url = str_replace('index.php?', '', $url);
                    $button->{'onclick'} = "tdatagrid_show_properties(this, '{$url}'); return false;";
                }
                
                $cell->add($button);
            }
        }
        
        // add one row to the DataGrid
        $this->tbody = new TElement('tbody');
        $this->tbody->{'class'} = 'tdatagrid_body';
        if ($this->scrollable)
        {
            $this->tbody->{'style'} = "height: {$this->height}; display: block; overflow-y:scroll; overflow-x:hidden;";
        }
        parent::add($this->tbody);
        
        $this->modelCreated = TRUE;
    }
    
    /**
     * Retrieves the table header (thead) element of the datagrid.
     *
     * @return TElement|null The table header element or null if not created.
     */
    public function getHead()
    {
        return $this->thead;
    }
    
    /**
     * Retrieves the table body (tbody) element of the datagrid.
     *
     * @return TElement|null The table body element or null if not created.
     */
    public function getBody()
    {
        return $this->tbody;
    }
    
    /**
     * Prepends a row to the datagrid body.
     *
     * @param TElement $row The row element to be added at the beginning.
     */
    public function prependRow($row)
    {
        $this->getBody()->add($row);
        $this->getHead()->{'noborder'} = '1';
        $this->prependRows ++;
    }
    
    /**
     * Inserts content into the datagrid at a specific position.
     *
     * @param int      $position The index where the content should be inserted.
     * @param TElement $content  The content to be inserted.
     */
    public function insert($position, $content)
    {
        $this->tbody->insert($position, $content);
    }
    
    /**
     * Adds multiple objects (rows) to the datagrid.
     *
     * @param array $objects An array of objects to be added as rows.
     */
    public function addItems($objects)
    {
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $this->addItem($object);
            }
        }
    }
    
    /**
     * Creates action cells for a given row based on the available actions.
     *
     * @param TElement $row    The row element to which actions will be added.
     * @param object   $object The data object representing the row.
     *
     * @return string|null The first action URL if available, otherwise null.
     */
    private function createItemActions($row, $object)
    {
        $first_url = null;
        
        if ($this->actions)
        {
            // iterate the actions
            foreach ($this->actions as $action_template)
            {
                // validate, clone, and inject object parameters
                $action = $action_template->prepare($object);
                
                // get the action properties
                $label     = $action->getLabel();
                $image     = $action->getImage();
                $condition = $action->getDisplayCondition();
                $usePostAction = $action->getUsePostAction();
                
                if (empty($condition) OR call_user_func($condition, $object))
                {
                    $url       = $action->serialize();
                    $first_url = isset($first_url) ? $first_url : $url;
                    
                    // creates a link
                    $link = new TElement('a');
                    $link->{'href'}      = htmlspecialchars($url);
                    $link->{'generator'} = 'adianti';

                    if($usePostAction)
                    {
                        $link = new TElement('span');
                        $link->style = 'cursor:pointer;';

                        $wait_message = AdiantiCoreTranslator::translate('Loading');
                        $link->onclick = "Adianti.waitMessage = '{$wait_message}';__adianti_post_action('".htmlspecialchars($url)."')";
                    }
                    
                    // verify if the link will have an icon or a label
                    if ($image)
                    {
                        $image_tag = is_object($image) ? clone $image : new TImage($image);
                        $image_tag->{'title'} = $label;
                        
                        if ($action->getUseButton())
                        {
                            // add the label to the link
                            $span = new TElement('span');
                            $span->{'class'} = $action->getButtonClass() ? $action->getButtonClass() : 'btn btn-default';
                            $span->add($image_tag);
                            $span->add($label);
                            $link->add($span);
                        }
                        else
                        {
                            $link->add( $image_tag );
                        }
                    }
                    else
                    {
                        // add the label to the link
                        $span = new TElement('span');
                        $span->{'class'} = $action->getButtonClass() ? $action->getButtonClass() : 'btn btn-default';
                        $span->add($label);
                        $link->add($span);
                    }

                    if($action->isDisabled())
                    {
                        $link->disabled = 'disabled';
                        $link->href = '#';
                        unset($link->generator);
                        if($url == $first_url)
                        {
                            $first_url = false;
                        }
                    }
                }
                else
                {
                    $link = '';
                }
                
                // add the cell to the row
                $cell = new TElement('td');
                $row->add($cell);
                $cell->add($link);
                $cell->{'style'} = 'min-width:'. $this->actionWidth;
                $cell->{'class'} = 'tdatagrid_cell action';
            }
        }
        
        if ($this->action_groups)
        {
            foreach ($this->action_groups as $action_group)
            {
                $actions    = $action_group->getActions();
                $headers    = $action_group->getHeaders();
                $separators = $action_group->getSeparators();
                
                if ($actions)
                {
                    $dropdown = new TDropDown($action_group->getLabel(), $action_group->getIcon());
                    $last_index = 0;
                    foreach ($actions as $index => $action_template)
                    {
                        $action = $action_template->prepare($object);
                        
                        // add intermediate headers and separators
                        for ($n=$last_index; $n<$index; $n++)
                        {
                            if (isset($headers[$n]))
                            {
                                $dropdown->addHeader($headers[$n]);
                            }
                            if (isset($separators[$n]))
                            {
                                $dropdown->addSeparator();
                            }
                        }
                        
                        // get the action properties
                        $label  = $action->getLabel();
                        $image  = $action->getImage();
                        $condition = $action->getDisplayCondition();
                        
                        if (empty($condition) OR call_user_func($condition, $object))
                        {
                            $url       = $action->serialize();
                            $first_url = isset($first_url) ? $first_url : $url;

                            if($url == $first_url && $action->isDisabled())
                            {
                                $first_url = false;
                            }

                            $dropdown->addAction($label, $action, $image);
                        }
                        $last_index = $index;
                    }
                    // add the cell to the row
                    $cell = new TElement('td');
                    $row->add($cell);
                    $cell->add($dropdown);
                    $cell->{'class'} = 'tdatagrid_cell action';
                }
            }
        }
        
        return $first_url;
    }
    
    /**
     * Adds an object (row) to the datagrid.
     * Handles grouping, styling, inline editing, and action linking.
     *
     * @param object $object The object to be added as a row in the datagrid.
     *
     * @return TElement The created row element.
     * @throws Exception If the model has not been created before calling this method.
     */
    public function addItem($object)
    {
        if ($this->modelCreated)
        {
            $valueGroup = null;

            if ($this->groupColumn AND
                (is_null($this->groupContent) OR $this->groupContent !== $object->{$this->groupColumn} ) )
            {

                if ($this->groupMask)
                {
                    $valueGroup = AdiantiTemplateHandler::replace($this->groupMask, $object);
                }
                else if ($this->groupTransformer)
                {
                    $valueGroup = call_user_func($this->groupTransformer, $object->{$this->groupColumn}, $object, $this);
                }
                else
                {
                    $valueGroup = $object->{$this->groupColumn};
                }

                if (! is_null($this->groupContent) && $this->groupTotal)
                {
                    $this->processGroupTotals($this->groupContent);
                }

                $row = new TElement('tr');
                $row->{'class'} = 'tdatagrid_group';
                $row->{'level'} = ++ $this->groupCount;
                $this->groupRowCount = 0;
                if ($this->isScrollable() AND $this->hasCustomWidth())
                {
                    $row->{'style'} = 'display: inline-table; width: 100%';
                }
                $this->tbody->add($row);
                $cell = new TElement('td');
                $cell->colspan = count($this->actions)+count($this->action_groups)+count($this->columns);
                
                // Aumenta o colspan quando o properties está habilitado
                if ($this->propertiesEnabled)
                {
                    $cell->colspan++;
                }
                
                $row->add($cell);

                $cell->add($valueGroup);
                
                $this->groupContent = $object->{$this->groupColumn};
            }
            
            // define the background color for that line
            $classname = ($this->rowcount % 2) == 0 ? 'tdatagrid_row_even' : 'tdatagrid_row_odd';
            
            $row = new TElement('tr');
            $this->tbody->add($row);
            $row->{'class'} = $classname;
            
            if ($this->isScrollable() AND $this->hasCustomWidth())
            {
                $row->{'style'} = 'display: inline-table; width: 100%';
            }
            
            if ($this->groupColumn)
            {
                if (empty($this->objectsGroup[$this->groupContent]))
                {
                    $this->objectsGroup[$this->groupContent] = array();
                }

                $this->objectsGroup[$this->groupContent][] = $object;

                $this->groupRowCount ++;
                $row->{'childof'} = $this->groupCount;
                $row->{'level'}   = $this->groupCount . '.'. $this->groupRowCount;
            }
            
            if ($this->actionSide == 'left')
            {
                $first_url = $this->createItemActions( $row, $object );
            }
            
            $output_row = [];
            $used_hidden = [];
            
            if ($this->columns)
            {
                // iterate the DataGrid columns
                foreach ($this->columns as $column)
                {
                    // get the column properties
                    $name     = $column->getName();
                    $align    = $column->getAlign();
                    $width    = $column->getWidth();
                    $function = $column->getTransformer();
                    $props    = $column->getDataProperties();

                    $cell = new TElement('td');
                    
                    if(!empty($this->hideColumns[md5($name)]) || $column->isHidden())
                    {
                        $this->hasHiddenColumns = true;

                        $cell->style .= ";display:none;";
                        $cell->add('');
                        $output_row[] = '';
                        
                        $row->add($cell);
                        continue;
                    }
                    
                    // calculated column
                    if (substr($name,0,1) == '=')
                    {
                        $content = AdiantiTemplateHandler::replace($name, $object, 'float');
                        $content = AdiantiTemplateHandler::evaluateExpression(substr($content,1));
                        $object->$name = $content;
                    }
                    else
                    {
                        try
                        {
                            @$content  = $object->$name; // fire magic methods
                            
                            if (is_null($content))
                            {
                                $content = AdiantiTemplateHandler::replace($name, $object);
                                
                                if ($content === $name)
                                {
                                    $content = '';
                                }
                            }
                        }
                        catch (Exception $e)
                        {
                            $content = AdiantiTemplateHandler::replace($name, $object);
                            
                            if (empty(trim($content)) OR $content === $name)
                            {
                                $content = $e->getMessage();
                            }
                        }
                    }
                    
                    if (isset($this->columnValues[$name]))
                    {
                        $this->columnValues[$name][] = $content;
                    }
                    else
                    {
                        $this->columnValues[$name] = [$content];
                    }

                    if (isset($this->columnValuesGroup[$this->groupContent][$name]))
                    {
                        $this->columnValuesGroup[$this->groupContent][$name][] = $content;
                    }
                    else
                    {
                        $this->columnValuesGroup[$this->groupContent][$name] = [$content];
                    }
                    
                    $data = is_null($content) ? '' : $content;
                    $raw_data = $data;
                    
                    if ( ($this->HTMLOutputConversion && $column->hasHtmlConversionEnabled()) && is_scalar($data))
                    {
                        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // TAG value
                    }
                    
                    // verify if there's a transformer function
                    if ($function)
                    {
                        $last_row = isset($this->objects[ $this->rowcount -1 ])? $this->objects[ $this->rowcount -1 ] : null;
                        // apply the transformer functions over the data
                        $data = call_user_func($function, $raw_data, $object, $row, $cell, $last_row);
                    }
                    
                    $output_row[] = is_scalar($data) ? strip_tags($data) : '';
                    
                    if ($editaction = $column->getEditAction())
                    {
                        $editaction_field = $editaction->getField();
                        $div = new TElement('div');
                        $div->{'class'}  = 'inlineediting';
                        $div->{'style'}  = 'padding-left:5px;padding-right:5px';
                        $div->{'action'} = $editaction->serialize();
                        $div->{'field'}  = $name;
                        $div->{'key'}    = isset($object->{$editaction_field}) ? $object->{$editaction_field} : NULL;
                        $div->{'pkey'}   = $editaction_field;
                        $div->add($data);
                        
                        $this->hasInlineEditing = true;
                        
                        $row->add($cell);
                        $cell->add($div);
                        $cell->{'class'} = 'tdatagrid_cell';
                    }
                    else
                    {
                        // add the cell to the row
                        $row->add($cell);
                        $cell->add($data);
                        
                        if ($this->hiddenFields AND !isset($used_hidden[$name]))
                        {
                            $hidden = new THidden($this->id . '_' . $name.'[]');
                            $hidden->{'data-hidden-field'} = 'true';
                            $hidden->setValue($raw_data);
                            $cell->add($hidden);
                            $used_hidden[$name] = true;
                        }
                        
                        $cell->{'class'} = 'tdatagrid_cell';
                        $cell->{'align'} = $align;
                        
                        if (isset($first_url) && $this->defaultClick && empty($cell->{'href'}))
                        {
                            $cell->{'href'}      = $first_url;
                            $cell->{'generator'} = 'adianti';
                            $cell->{'class'}     = 'tdatagrid_cell';
                        }
                    }
                    
                    if ($props)
                    {
                        $cell->setProperties($props);
                    }
                    
                    if ($width)
                    {
                        $cell->{'width'} = (strpos($width, '%') !== false || strpos($width, 'px') !== false) ? $width : ($width + 8).'px';
                    }
                }
                
                $this->outputData[] = $output_row;
            }
            
            if ($this->actionSide == 'right')
            {
                $this->createItemActions( $row, $object );
            }
            
            // Add properties cell if enabled
            if ($this->propertiesEnabled)
            {
                $cell = new TElement('td');
                $cell->add('&nbsp;');
                $row->add($cell);
            }
            
            if ($this->popover && (empty($this->popcondition) OR call_user_func($this->popcondition, $object)))
            {
                $poptitle   = $this->poptitle;
                $popcontent = $this->popcontent;
                $poptitle   = AdiantiTemplateHandler::replace($poptitle, $object);
                $popcontent = AdiantiTemplateHandler::replace($popcontent, $object, null, true);
                
                $row->{'popover'} = 'true';
                $row->{'poptitle'} = $poptitle;
                $row->{'popcontent'} = htmlspecialchars(str_replace("\n", '', nl2br($popcontent)));
                
                if ($this->popside)
                {
                    $row->{'popside'} = $this->popside;
                }
            }
            
            if (count($this->searchAttributes) > 0)
            {
                $row->{'id'} = 'row_' . mt_rand(1000000000, 1999999999);
                
                foreach ($this->searchAttributes as $search_att)
                {
                    @$search_content = $object->$search_att; // fire magic methods
                    if (!empty($search_content))
                    {
                        $row_dom_search_att = 'search_' . str_replace(['-', '>'],['_', ''],$search_att);
                        $row->$row_dom_search_att = $search_content;
                    }
                }
            }
            
            $this->objects[ $this->rowcount ] = $object;
            
            // increments the row counter
            $this->rowcount ++;
            
            return $row;
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', 'createModel', __METHOD__ ) );
        }
    }
    
    /**
     * Appends a row to the datagrid via JavaScript.
     *
     * @param string   $table_id The ID of the datagrid table.
     * @param TElement $row      The row element to be appended.
     */
    public static function appendRow( $table_id, $row )
    {
        $row64 = base64_encode($row->getContents());
        TScript::create("ttable_add_row('{$table_id}', 'body', '{$row64}')");
    }
    
    /**
     * Removes a row from the datagrid by its ID via JavaScript.
     *
     * @param string $table_id The ID of the datagrid table.
     * @param string $id       The ID of the row to be removed.
     */
    public static function removeRowById( $table_id, $id)
    {
        TScript::create("ttable_remove_row_by_id('{$table_id}', '{$id}')");
    }
    
    /**
     * Replaces an existing row in the datagrid by its ID via JavaScript.
     *
     * @param string   $table_id The ID of the datagrid table.
     * @param string   $id       The ID of the row to be replaced.
     * @param TElement $row      The new row element to replace the old one.
     */
    public static function replaceRowById( $table_id, $id, $row)
    {
        $row64 = base64_encode($row->getContents());
        TScript::create("ttable_replace_row_by_id('{$table_id}', '{$id}', '{$row64}')");
    }
    
    /**
     * Retrieves the objects added to the datagrid.
     *
     * @return array The list of objects in the datagrid.
     */
    public function getItems()
    {
        return $this->objects;
    }
    
    /**
     * Processes and adds group total rows to the datagrid when grouping is enabled.
     *
     * @param string $valueGroup The current group value used to compute totals.
     */
    private function processGroupTotals($valueGroup)
    {  
        $row = new TElement('tr');
        
        if ($this->isScrollable() AND $this->hasCustomWidth())
        {
            $row->{'style'} = 'display: inline-table; width: 100%';
        }
        
        if ($this->actionSide == 'left')
        {
            if ($this->actions)
            {
                // iterate the actions
                foreach ($this->actions as $action)
                {
                    $cell = new TElement('td');
                    $row->add($cell);
                }
            }
            
            if ($this->action_groups)
            {
                foreach ($this->action_groups as $action_group)
                {
                    $cell = new TElement('td');
                    $row->add($cell);
                }
            }
        }
        
        if ($this->columns)
        {
            // iterate the DataGrid columns
            foreach ($this->columns as $column)
            {
                $cell = new TElement('td');
                $row->add($cell);
                
                // get the column total function
                $totalFunction = $column->getTotalFunction();
                $totalMask     = $column->getTotalMask();
                $totalCallback = $column->getTotalCallback();
                $transformer   = $column->getTransformer();
                $name          = $column->getName();
                $align         = $column->getAlign();
                $width         = $column->getWidth();
                $props         = $column->getDataProperties();
                $totalFormField = $column->getTotalFormField();
                $cell->{'style'} = "text-align:$align";
                
                if ($width)
                {
                    $cell->{'width'} = (strpos($width, '%') !== false || strpos($width, 'px') !== false) ? $width : ($width + 8).'px';
                }
                
                if ($props)
                {
                    $cell->setProperties($props);
                }
                
                if ($totalCallback)
                {
                    $raw_content = 0;
                    $content     = 0;
                    
                    if (count($this->objectsGroup[$valueGroup]) > 0)
                    {
                        $raw_content = $totalCallback($this->columnValuesGroup[$valueGroup][$name], $this->objectsGroup[$valueGroup]);
                        $content     = $raw_content;
                        
                        if ($transformer && $column->totalTransformed())
                        {
                            // apply the transformer functions over the data
                            // $content = call_user_func($transformer, $content, null, null, null, null);
                        }
                    }
                    
                    if (!empty($totalFunction) || !empty($totalCallback))
                    {
                        $this->hasTotalFunction = true;
                        $cell->{'data-total-function'} = $totalFunction;
                        $cell->{'data-column-name'}    = $name;
                        $cell->{'data-total-mask'}     = $totalMask;
                        $cell->{'data-value'}          = $raw_content;

                        if($totalFormField)
                        {
                            $cell->{'data-total-form-field'} = $totalFormField;
                        }
                    }
                    
                    if(count($this->objectsGroup[$valueGroup]) > 0)
                    {
                        $cell->add($content);
                    }
                }
                else
                {
                    $cell->add('&nbsp;');
                }
            }
        }
        
        // Adiciona célula vazia para a coluna de propriedades
        if ($this->propertiesEnabled)
        {
            $cell = new TElement('td');
            $row->add($cell);
            $cell->add('&nbsp;');
        }

        $this->tbody->add($row);
    }

    /**
     * Processes and adds total rows at the bottom of the datagrid.
     * Computes column totals if total functions are defined.
     */
    private function processTotals()
    {
        if ($this->groupColumn && $this->groupTotal)
        {
            $this->processGroupTotals($this->groupContent);
        }

        $has_total = false;
        
        $this->tfoot = new TElement('tfoot');
        $this->tfoot->{'class'} = 'tdatagrid_footer';
        
        if ($this->scrollable)
        {
            $this->tfoot->{'style'} = "display: block";
            $this->tfoot->{'style'} = "display: block; padding-right: 15px";
        }
        
        $row = new TElement('tr');
        
        if ($this->isScrollable() AND $this->hasCustomWidth())
        {
            $row->{'style'} = 'display: inline-table; width: 100%';
        }
        $this->tfoot->add($row);
        
        if ($this->actionSide == 'left')
        {
            if ($this->actions)
            {
                // iterate the actions
                foreach ($this->actions as $action)
                {
                    $cell = new TElement('td');
                    $row->add($cell);
                }
            }
            
            if ($this->action_groups)
            {
                foreach ($this->action_groups as $action_group)
                {
                    $cell = new TElement('td');
                    $row->add($cell);
                }
            }
        }
        
        if ($this->columns)
        {
            // iterate the DataGrid columns
            foreach ($this->columns as $column)
            {
                $cell = new TElement('td');
                $name = $column->getName();

                if(!empty($this->hideColumns[md5($name)]) || $column->isHidden())
                {
                    $this->hasHiddenColumns = true;

                    $cell->style .= ";display:none;";
                    $cell->add('');
                    $output_row[] = '';

                    $row->add($cell);
                    continue;
                }

                $row->add($cell);
                
                // get the column total function
                $totalFunction = $column->getTotalFunction();
                $totalMask     = $column->getTotalMask();
                $totalCallback = $column->getTotalCallback();
                $transformer   = $column->getTransformer();
                $align         = $column->getAlign();
                $width         = $column->getWidth();
                $props         = $column->getDataProperties();
                $totalFormField = $column->getTotalFormField();

                $cell->{'style'} = "text-align:$align";
                
                if ($width)
                {
                    $cell->{'width'} = (strpos($width, '%') !== false || strpos($width, 'px') !== false) ? $width : ($width + 8).'px';
                }
                
                if ($props)
                {
                    $cell->setProperties($props);
                }
                
                if ($totalCallback)
                {
                    $raw_content = 0;
                    $content     = 0;
                    
                    if (count($this->objects) > 0 && isset($this->columnValues[$name]))
                    {
                        $raw_content = $totalCallback($this->columnValues[$name], $this->objects);
                        $content     = $raw_content;
                        
                        if ($transformer && $column->totalTransformed())
                        {
                            // apply the transformer functions over the data
                            $content = call_user_func($transformer, $content, null, null, null, null);
                        }
                    }
                    
                    if (!empty($totalFunction) || !empty($totalCallback))
                    {
                        $this->hasTotalFunction = true;
                        $cell->{'data-total-function'} = $totalFunction;
                        $cell->{'data-column-name'}    = $name;
                        $cell->{'data-total-mask'}     = $totalMask;
                        $cell->{'data-value'}          = $raw_content;

                        if($totalFormField)
                        {
                            $cell->{'data-total-form-field'} = $totalFormField;
                        }
                    }
                    if(count($this->objects))
                    {
                        $cell->add($content);
                    }
                }
                else
                {
                    $cell->add('&nbsp;');
                }
            }
        }
        
        // Adiciona célula vazia para a coluna de propriedades no footer
        if ($this->propertiesEnabled)
        {
            $cell = new TElement('td');
            $row->add($cell);
            $cell->add('&nbsp;');
        }
        
        if ($this->hasTotalFunction)
        {
            parent::add($this->tfoot);
        }
    }
    
    /**
     * Finds the index of a row by an object attribute.
     *
     * @param string $attribute The object attribute to search for.
     * @param mixed  $value     The value to match.
     *
     * @return int|null The index of the row or null if not found.
     */
    public function getRowIndex($attribute, $value)
    {
        foreach ($this->objects as $pos => $object)
        {
            if ($object->$attribute == $value)
            {
                return $pos;
            }
        }
        return NULL; 
    }
    
    /**
     * Retrieves a row by its position in the datagrid.
     *
     * @param int $position The index of the row.
     *
     * @return mixed The row element.
     */
    public function getRow($position)
    {
        return $this->tbody->get($position);
    }
    
    /**
     * Calculates the total width of the datagrid based on its columns and actions.
     *
     * @return int The total width in pixels.
     */
    public function getWidth()
    {
        $width=0;
        if ($this->actions)
        {
            // iterate the DataGrid Actions
            foreach ($this->actions as $action)
            {
                $width += 22;
            }
        }
        
        if ($this->columns)
        {
            // iterate the DataGrid Columns
            foreach ($this->columns as $column)
            {
                if (is_numeric($column->getWidth()))
                {
                    $width += $column->getWidth();
                }
            }
        }
        
        // Adiciona a largura da coluna de propriedades, se habilitada
        if ($this->propertiesEnabled)
        {
            $width += 22;
        }
        
        return $width;
    }
    
    /**
     * Assigns a PageNavigation object to the datagrid.
     *
     * @param mixed $pageNavigation The PageNavigation object.
     */
    public function setPageNavigation($pageNavigation)
    {
        $this->pageNavigation = $pageNavigation;
    }
    
    /**
     * Retrieves the assigned PageNavigation object.
     *
     * @return mixed The PageNavigation object.
     */
    public function getPageNavigation()
    {
        return $this->pageNavigation;
    }
    
    /**
     * Defines the attributes used for search within the datagrid.
     *
     * @param array $attributes An array of attribute names to be used in search.
     */
    public function setSearchAttributes($attributes)
    {
        $this->searchAttributes = $attributes;
    }
    
    /**
     * Enables a search input for filtering rows in the datagrid.
     *
     * @param TField $input      The input field used for search.
     * @param string $attributes The attributes to search in.
     *
     * @throws Exception If search is enabled after adding items.
     */
    public function enableSearch(TField $input, $attributes) 
    {
        if (count($this->objects)>0)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', 'enableSearch()', 'addItem()'));
        }
        
        $input_id    = $input->getId();
        $datagrid_id = $this->{'id'};
        $att_names   = explode(',', $attributes);
        $dom_atts    = [];
        
        if ($att_names)
        {
            foreach ($att_names as $att_name)
            {
                $att_name = trim($att_name);
                $this->searchAttributes[] = $att_name;
                $dom_search_atts[] = str_replace(['-', '>'], ['_', ''], "search_{$att_name}");
            }
            
            $dom_att_string = implode(',', $dom_search_atts);
            TScript::create("__adianti_input_fuse_search('#{$input_id}', '{$dom_att_string}', '#{$datagrid_id} tr')");
        }
    }

    public function setHideColumns($hideColumns)
    {
        $this->hideColumns = $hideColumns;
    }

    public function unhideColumns()
    {
        if($this->columns)
        {
            foreach($this->columns as $column)
            {
                $column->unhide();
            }
        }
    }

    public function initPopoverHeaderFilters()
    {
        TScript::create("tdatagrid_init_header_popover_filters('{$this->id}');");
    }
    
    /**
     * Displays the datagrid and processes totals, groups, and inline editing.
     */
    public function show()
    {
        $this->processTotals();
        
        if (!$this->hasCustomWidth())
        {
            $this->{'style'} .= ';width:unset';
        }
        
        // shows the datagrid
        parent::show();
        
        $params = $_REQUEST;
        unset($params['class']);
        unset($params['method']);
        // to keep browsing parameters (order, page, first_page, ...)
        $urlparams='&'.http_build_query($params);
        
        // inline editing treatment
        if ($this->hasInlineEditing)
        {
            TScript::create(" tdatagrid_inlineedit( '{$urlparams}' );");
        }
        
        if ($this->groupColumn)
        {
            TScript::create(" tdatagrid_enable_groups();");
        }
        
        if ($this->hasTotalFunction)
        {
            TScript::create(" tdatagrid_update_total('#{$this->{'id'}}');");
        }
        
        if ($this->mutationAction)
        {
            $url = $this->mutationAction->serialize(false);
            TScript::create(" tdatagrid_mutation_action('#{$this->{'id'}}', '$url');");
        }

        if(!empty($this->hideColumns) || $this->hasHiddenColumns)
        {
            TScript::create(" tdatagrid_start_hide_columns('#{$this->id}');");
        }
    }
}
