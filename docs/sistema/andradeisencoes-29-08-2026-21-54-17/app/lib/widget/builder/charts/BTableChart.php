<?php

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TConnection;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TSqlSelect;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TStyle;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Wrapper\BootstrapDatagridWrapper;

/**
 * Class BTableChart
 *
 * This widget represents a table-based chart that integrates with Adianti's DataGrid.
 * It allows dynamic column definitions, database connections, custom styling, and group columns.
 *
 * @version    7.4
 * @package    widget
 * @subpackage builder
 * @author     Lucas Tomasi
 */
class BTableChart extends TElement
{
    protected $datagrid;

    protected $name;
    protected $database;
    protected $model;
    protected $columns;
    protected $joins;
    protected $criteria;
    protected $data;
    protected $loaded;
    protected $title;
    protected $subTitle;
    protected $customClass;
    protected $showPanel;
    protected $height;
    protected $width;
    protected $groupColumns;
    protected $showMethods;
    
    // Colors
    private $rowColorOdd;
    private $rowColorEven;
    private $fontRowColorOdd;
    private $fontRowColorEven;
    private $borderColor;
    private $tableHeaderColor;
    private $tableHeaderFontColor;
    private $tableFooterColor;
    private $tableFooterFontColor;

    /**
     * BTableChart constructor.
     *
     * Initializes the table chart with a name, database, model, and optional joins.
     *
     * @param string      $name     The name of the chart.
     * @param string|null $database The database name (optional).
     * @param string|null $model    The model class name (optional).
     * @param array       $joins    An array of joins to be used in the SQL query (optional).
     */
    public function __construct(String $name, ?String $database = null, ?String $model = null, array $joins = [])
    {
        parent::__construct('div');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid($name));
        
        $this->columns = [];
        $this->groupColumns = [];
        $this->showMethods = [];

        $this->name = $name;
        $this->setDatabase($database);
        $this->setModel($model);
        $this->setCriteria($criteria??new TCriteria);
        $this->setJoins($joins);

        $this->hidePanel(FALSE);
        $this->setSize('100%', 300);

        $this->loaded = false;
        parent::add($this->html);
    }
    
    /**
     * Sets the background color for odd rows.
     *
     * @param string $rowColorOdd The color code for odd rows.
     */
    public function setRowColorOdd($rowColorOdd)
    {
        $this->rowColorOdd = $rowColorOdd;
    }

    /**
     * Checks if the chart should be displayed based on allowed methods.
     *
     * @return bool True if the chart can be displayed, false otherwise.
     */
    public function canDisplay()
    {
        if ($this->showMethods)
        {
            return in_array($_REQUEST['method']??'', $this->showMethods);
        }

        return true;
    }

    /**
     * Sets the allowed methods for displaying the chart.
     *
     * @param array $methods An array of method names that can display the chart.
     */
    public function setShowMethods($methods = [])
    {
        $this->showMethods = $methods;
    }

    /**
     * Gets the background color for odd rows.
     *
     * @return string The color code for odd rows.
     */
    public function getRowColorOdd()
    {
        return $this->rowColorOdd;
    }

    /**
     * Sets the background color for even rows.
     *
     * @param string $rowColorEven The color code for even rows.
     */
    public function setRowColorEven($rowColorEven)
    {
        $this->rowColorEven = $rowColorEven;
    }

    /**
     * Gets the background color for even rows.
     *
     * @return string The color code for even rows.
     */
    public function getRowColorEven()
    {
        return $this->rowColorEven;
    }

    /**
     * Sets the font color for odd rows.
     *
     * @param string $fontRowColorOdd The font color for odd rows.
     */
    public function setFontRowColorOdd($fontRowColorOdd)
    {
        $this->fontRowColorOdd = $fontRowColorOdd;
    }

    /**
     * Gets the font color for odd rows.
     *
     * @return string The font color for odd rows.
     */
    public function getFontRowColorOdd()
    {
        return $this->fontRowColorOdd;
    }

    /**
     * Sets the font color for even rows.
     *
     * @param string $fontRowColorEven The font color for even rows.
     */
    public function setFontRowColorEven($fontRowColorEven)
    {
        $this->fontRowColorEven = $fontRowColorEven;
    }

    /**
     * Gets the font color for even rows.
     *
     * @return string The font color for even rows.
     */
    public function getFontRowColorEven()
    {
        return $this->fontRowColorEven;
    }

    /**
     * Sets the border color of table rows.
     *
     * @param string $borderColor The border color.
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = $borderColor;
    }

    /**
     * Gets the border color of table rows.
     *
     * @return string The border color.
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    /**
     * Sets the table header background color.
     *
     * @param string $tableHeaderColor The header background color.
     */
    public function setTableHeaderColor($tableHeaderColor)
    {
        $this->tableHeaderColor = $tableHeaderColor;
    }

    /**
     * Gets the table header background color.
     *
     * @return string The header background color.
     */
    public function getTableHeaderColor()
    {
        return $this->tableHeaderColor;
    }

    /**
     * Sets the font color for the table header.
     *
     * @param string $tableHeaderFontColor The font color for the table header.
     */
    public function setTableHeaderFontColor($tableHeaderFontColor)
    {
        $this->tableHeaderFontColor = $tableHeaderFontColor;
    }

    /**
     * Gets the font color for the table header.
     *
     * @return string The font color for the table header.
     */
    public function getTableHeaderFontColor()
    {
        return $this->tableHeaderFontColor;
    }

    /**
     * Sets the table footer background color.
     *
     * @param string $tableFooterColor The footer background color.
     */
    public function setTableFooterColor($tableFooterColor)
    {
        $this->tableFooterColor = $tableFooterColor;
    }

    /**
     * Gets the table footer background color.
     *
     * @return string The footer background color.
     */
    public function getTableFooterColor()
    {
        return $this->tableFooterColor;
    }

    /**
     * Sets the font color for the table footer.
     *
     * @param string $tableFooterFontColor The font color for the table footer.
     */
    public function setTableFooterFontColor($tableFooterFontColor)
    {
        $this->tableFooterFontColor = $tableFooterFontColor;
    }

    /**
     * Gets the font color for the table footer.
     *
     * @return string The font color for the table footer.
     */
    public function getTableFooterFontColor()
    {
        return $this->tableFooterFontColor;
    }

    /**
     * Gets the name of the chart.
     *
     * @return string The name of the chart.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the chart.
     *
     * @param string $name The name of the chart.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Defines a custom CSS class for the chart.
     *
     * @param string $class The CSS class name.
     */
    public function setCustomClass($class)
    {
        $this->customClass = $class;
    }

    /**
     * Gets the database name.
     *
     * @return string The database name.
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Sets the database name.
     *
     * @param string $database The database name.
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * Sets the model class name.
     *
     * @param string $model The model class name.
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Adds a column to the table chart.
     *
     * @param BTableColumnChart $column The column object to add.
     */
    public function addColumn(BTableColumnChart $column)
    {
        if(empty($this->columns[$column->getName()]))
        {
            $this->columns[$column->getName()] = $column;    
        }
        else
        {
            $column->alias = $column->getName().'_'.uniqid();
            $this->columns[$column->alias] = $column;
        }
        
    }

    /**
     * Defines a group column with an optional transformer and total calculation.
     *
     * @param string        $column           The column name.
     * @param callable|null $transformer      A function to transform values (optional).
     * @param bool         $showTotal         Whether to show totals (default: false).
     * @param callable|null $transformerTotal A function to transform total values (optional).
     */
    public function setGroupColumn($column, ?Callable $transformer = null, bool $showTotal = false, ?callable $transformerTotal = null)
    {
        $groupColumn = new stdClass;
        $groupColumn->column = $column;
        $groupColumn->transformer = $transformer;
        $groupColumn->showTotal = $showTotal;
        $groupColumn->transformerTotal = $transformerTotal;

        $this->groupColumns[] = $groupColumn;
    }

    /**
     * Sets multiple columns in the table chart.
     *
     * @param array $columns An array of BTableColumnChart objects.
     */
    public function setColumns($columns)
    {
        if (! is_array($columns))
        {
            return;
        }
        
        foreach($columns as $column)
        {
            $this->addColumn($column);
        }
    }

    /**
     * Gets all columns in the table chart.
     *
     * @return array An array of BTableColumnChart objects.
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Gets a specific column by name.
     *
     * @param string $name The column name.
     *
     * @return BTableColumnChart|null The column object, or null if not found.
     */
    public function getColumn($name)
    {
        $filter = array_filter($this->columns, function($c) use ($name) { return $c->getName() === $name; });

        return $filter ? $filter[0] : null;
    }

    /**
     * Sets database joins for queries.
     *
     * @param array $joins An array of join definitions.
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;
    }

    /**
     * Sets a filtering criteria for the table chart.
     *
     * @param TCriteria $criteria The criteria object containing filters.
     */
    public function setCriteria(TCriteria $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Sets the title of the panel.
     *
     * @param string $title The panel title.
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Set subtitle panel
     * @param $subtitle String subtitle
     */
    public function setSubTitle($subtitle)
    {
        $this->subTitle = $subtitle;
    }

    /**
     * Hides or shows the panel.
     *
     * @param bool $hide Whether to hide the panel (default: true).
     */
    public function hidePanel($hide = true)
    {
        $this->showPanel = ! $hide;
    }

    /**
     * Sets the width and height of the chart.
     *
     * @param string|int $width  The width of the chart.
     * @param string|int $height The height of the chart.
     */
    public function setSize($width, $height)
    {
        $height = (strstr($height, '%') !== FALSE) ? $height : "{$height}px";
        $width  = (strstr($width, '%') !== FALSE) ? $width : "{$width}px";

        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Gets the size of the chart.
     *
     * @return null This method currently does not return a value.
     */
    public function getSize()
    {
        return null;
    }

    /**
     * Loads data from the database according to the defined model, columns, and criteria.
     *
     * @throws Exception If the database, model, or columns are not set.
     */
    private function loadData()
    {
        $items = [];

        if (empty($this->database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }

        if (empty($this->model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }

        if (empty($this->columns))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'columns', __CLASS__));
        }

        $cur_conn = serialize(TTransaction::getDatabaseInfo());
        $new_conn = serialize(TConnection::getDatabaseInfo($this->database));

        $open_transaction = ($cur_conn !== $new_conn);

        // open transaction case not opened
        if ($open_transaction)
        {
            TTransaction::open($this->database);
        }

        $conn = TTransaction::get();

        $entity = (new $this->model)->getEntity();
        $entities = array_keys($this->joins??[]);
        if(!in_array($entity, $entities))
        {
            $entities[] = $entity;
        }

        $entities = implode(', ', $entities);

        if ($this->joins)
        {
            foreach ($this->joins as $join)
            {
                $key = $join[0];

                // Not find dot, insert table name before
                if (strpos($key, '.') === FALSE)
                {
                    $key = "{$entity}.{$key}";
                }

                if(count($join) > 2)
                {
                    $operator = $join[1];
                    $value    = $join[2];
                }
                else
                {
                    $operator = '=';
                    $value    = $join[1];
                }

                // Not find dot, insert table name before
                if (strpos($value, '.') === FALSE)
                {
                    $value = "{$entity}.{$value}";
                }

                $this->criteria->add(new TFilter($key, $operator, "NOESC: {$value}"));
            }
        }

        $sql = new TSqlSelect();
        $groups = [];
        $orders = [];

        foreach($this->columns AS $key => $tableColumn)
        {
            $nameColumn = $tableColumn->getName();
            $name = $tableColumn->getName();
            
            if(isset($tableColumn->alias))
            {
                $name = $tableColumn->alias;
            }

            // Not find dot, insert table name before
            if (strpos($nameColumn, '.') === FALSE && strpos($nameColumn, ':') === FALSE && strpos($nameColumn, '(') === FALSE)
            {
                if(preg_match('/builder_db_query_temp/i', $entity))
                {
                    $entity = 'builder_db_query_temp';
                }
                
                $nameColumn = "{$entity}.{$nameColumn}";
            }

            if ($tableColumn->getAggregate())
            {
                $nameColumn = "{$tableColumn->getAggregate()}($nameColumn)";
            }
            else
            {
                $groups[] = ($nameColumn);
            }
            
            $sql->addColumn("{$nameColumn} as \"{$name}\" ");

            if ($tableColumn->getOrder())
            {
                $orders[] = "{$nameColumn} {$tableColumn->getOrder()}";
            }
        }

        if ($this->groupColumns)
        {
            foreach($this->groupColumns AS $key => $tableColumn)
            {
                $nameColumn = $tableColumn->column;
                $name = $tableColumn->column;

                // Not find dot, insert table name before
                if (strpos($nameColumn, '.') === FALSE && strpos($nameColumn, ':') === FALSE && strpos($nameColumn, '(') === FALSE)
                {
                    $nameColumn = "{$entity}.{$nameColumn}";
                }

                $groups[] = count($this->columns) + $key + 1;
                $sql->addColumn("{$nameColumn} as \"{$name}\" ");            
            }
        }

        $group = implode(', ', $groups);

        $this->criteria->setProperty('group', $group);
        if($orders)
        {
            $this->criteria->setProperty('order', implode(', ', $orders));
        }

        $sql->setEntity($entities);
        $sql->setCriteria($this->criteria);

        $stmt = $conn->prepare($sql->getInstruction(TRUE), array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $result = $stmt->execute($this->criteria->getPreparedVars());

        if($result)
        {
            $items = $stmt->fetchAll(PDO::FETCH_CLASS, 'stdClass');
        }

        // close connection
        if ($open_transaction)
        {
            TTransaction::close();
        }

        $this->data = $items;
    }

    /**
     * Executes the necessary processes before displaying the chart.
     */
    public function create()
    {
        $this->loaded = true;
        $this->loadData();
    }

    /**
     * Creates the DataGrid structure, including columns, groups, and styles.
     */
    private function makeDatagrid()
    {
        $this->datagrid->{'class'} .= " {$this->customClass} table-responsive ";
        $this->datagrid->{'style'} = "width: {$this->width};";
        $this->datagrid->setHeight($this->height);
        $this->datagrid->makeScrollable();

        if ($this->groupColumns)
        {
            foreach( $this->groupColumns as $groupColumn )
            {
                $this->datagrid->setGroupColumn($groupColumn->column, null, $groupColumn->transformer);
                $this->datagrid->useGroupTotal($groupColumn->showTotal);
            }
        }

        foreach( $this->columns as $tableColumn)
        {
            $name = $tableColumn->getName();
            
            if(isset($tableColumn->alias))
            {
                $name = $tableColumn->alias;
            }
            
            $tdcolumn = new TDataGridColumn($name, $tableColumn->getLabel(), $tableColumn->getAlign() , $tableColumn->getWidth());
            
            $totalFunction = $tableColumn->getTotal();
            $transformerTotal = $tableColumn->getTransformerTotal();

            if ($totalFunction)
            {
                $tdcolumn->setTotalFunction(
                    function($values) use ($totalFunction, $transformerTotal) {
                        $total = null;

                        if ($totalFunction === 'avg' && count($values??[]) > 0)
                        {
                            $total = array_sum($values) / count($values??[]);
                        }
                        else if ($totalFunction === 'count')
                        {
                            $total = count($values??[]);
                        }
                        else if ($totalFunction === 'sum')
                        {
                            $total = array_sum($values??[]);
                        }
                        else if ($totalFunction === 'max')
                        {
                            $total = max($values??[]);
                        }
                        else if ($totalFunction === 'min')
                        {
                            $total = min($values??[]);
                        }

                        if ($transformerTotal)
                        {
                            return call_user_func($transformerTotal, $total, null, null);
                        }

                        return $total;
                    },
                    FALSE
                );
            }

            if ($tableColumn->getTransformer())
            {
                $tdcolumn->setTransformer($tableColumn->getTransformer());
            }


            $this->datagrid->addColumn($tdcolumn);
        }

        $this->datagrid->createModel();
    }

    /**
     * Populates the DataGrid with the loaded data.
     */
    public function setDataDatagrid()
    {
        $this->datagrid->clear();

        if (empty($this->data))
        {
            return;
        }

        foreach($this->data as $data)
        {
            $this->datagrid->addItem($data);
        }
    }

    /**
     * Sets the colors for table rows, including odd/even row background colors and font colors.
     *
     * @param string      $rowColorOdd      The background color for odd rows.
     * @param string      $rowColorEven     The background color for even rows.
     * @param string|null $fontRowColorOdd  The font color for odd rows (optional).
     * @param string|null $fontRowColorEven The font color for even rows (optional).
     * @param string|null $borderColor      The border color for table rows (optional).
     */
    public function setRowColors(string $rowColorOdd, string $rowColorEven, ?string $fontRowColorOdd = null, ?string $fontRowColorEven = null, ?string $borderColor = null)
    {
        $this->rowColorOdd = $rowColorOdd;
        $this->rowColorEven = $rowColorEven;
        $this->fontRowColorOdd = $fontRowColorOdd;
        $this->fontRowColorEven = $fontRowColorEven;
        $this->borderColor = $borderColor;
    }

    /**
     * Sets the background and font color for the table header.
     *
     * @param string      $tableHeaderColor     The background color of the table header.
     * @param string|null $tableHeaderFontColor The font color of the table header (optional).
     */
    public function setTableHeaderColors(string $tableHeaderColor, ?string $tableHeaderFontColor = null)
    {
        $this->tableHeaderColor = $tableHeaderColor;
        $this->tableHeaderFontColor = $tableHeaderFontColor;
    }

    /**
     * Sets the background and font color for the table footer.
     *
     * @param string      $tableFooterColor     The background color of the table footer.
     * @param string|null $tableFooterFontColor The font color of the table footer (optional).
     */
    public function setTableFooterColors(string $tableFooterColor, ?string $tableFooterFontColor = null)
    {
        $this->tableFooterColor = $tableFooterColor;
        $this->tableFooterFontColor = $tableFooterFontColor;
    }

    /**
     * Applies the defined styles to the table, including row colors, header styles, and borders.
     */
    private function showStyle()
    {
        if ($this->tableHeaderColor)
        {
            $style = new TStyle($this->name . ' thead');
            $style->background = $this->tableHeaderColor;

            if ($this->tableHeaderFontColor)
            {
                $style->color = $this->tableHeaderFontColor;
            }

            $style->show();
            
            $style = new TStyle($this->name . ' thead tr th');
            $style->{'border-color'} = $this->tableHeaderColor;
            $style->show();
        }

        if ($this->tableFooterColor)
        {
            $style = new TStyle($this->name . ' tfoot');
            $style->background = $this->tableFooterColor;

            if ($this->tableFooterFontColor)
            {
                $style->color = $this->tableFooterFontColor;
            }

            $style->show();
        }

        if ($this->rowColorOdd)
        {
            $style = new TStyle($this->name . ' table tbody tr:nth-of-type(odd)');
            $style->background = $this->rowColorOdd;

            if ($this->fontRowColorOdd)
            {
                $style->color = $this->fontRowColorOdd;
            }

            $style->show();
        }

        if ($this->rowColorEven)
        {
            $style = new TStyle($this->name . ' table tbody tr:nth-of-type(even)');
            $style->background = $this->rowColorEven;

            if ($this->fontRowColorEven)
            {
                $style->color = $this->fontRowColorEven;
            }

            $style->show();
        }

        if ($this->borderColor)
        {
            $style = new TStyle($this->name . ' table tbody tr td');
            $style->{"border-color-top"} = $this->borderColor;
            $style->show();
        }
    }

    /**
     * Renders the chart on the screen.
     */
    public function show()
    {
        if (! $this->canDisplay())
        {
            return;
        }

        if (! $this->loaded)
        {
            $this->create();
        }

        $this->{'class'} = 'btablechart chart-container ' . $this->name;

        if (empty($this->data))
        {
            $panel = new TElement('div');
            $panel->{'class'} = 'panel panel-default chart-header';

            $panelHeader = new TElement('div');
            $panelHeader->{'class'} = 'panel-heading chart-title';
            $panelHeader->add("<div class='panel-title card-title'>{$this->title}</div> <p class='chart-subtitle'>{$this->subTitle}</p>");
            $panel->add($panelHeader);

            $panelBody = new TElement('div');
            $panelBody->{'class'} = 'panel-body';
            $panelBody->{'style'} = 'text-align:center';
            $panelBody->add(_t('No records found'));
            $panel->add($panelBody);
            
            $this->add($panel);
        }
        else
        {
            $this->makeDatagrid();
            $this->setDataDatagrid();

            if ($this->showPanel)
            {
                $panel = new TElement('div');
                $panel->{'class'} = 'panel panel-default chart-header';

                $panelHeader = new TElement('div');
                $panelHeader->{'class'} = 'panel-heading chart-title';
                $panelHeader->add("<div class='panel-title card-title'>{$this->title}</div> <p class='chart-subtitle'>{$this->subTitle}</p>");
                $panel->add($panelHeader);

                $panelBody = new TElement('div');
                $panelBody->{'class'} = 'panel-body';
                $panelBody->{'style'} = 'padding: 0px';
                $panelBody->add($this->datagrid);
                $panel->add($panelBody);

                $this->add($panel);
            }
            else
            {
                $this->add($this->datagrid);
            }
        }

        $this->showStyle();

        parent::show();
    }
}