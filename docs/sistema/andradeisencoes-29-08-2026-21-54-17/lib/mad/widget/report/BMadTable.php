<?php

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TConnection;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TSqlSelect;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Template\THtmlRenderer;

/**
 * BMadTable - MAD-Table Component for Adianti Framework
 * 
 * Professional pivot table component with drag-and-drop field configuration,
 * advanced aggregations, filtering, and cross-tab support.
 * Similar to BChart with database integration, joins, and automatic data loading.
 * 
 * @version    2.0
 * @package    widget
 * @author     MAD
 * @copyright  Copyright (c) 2025
 * 
 * @requires   Adianti\Widget\Base\TElement
 * @requires   Adianti\Widget\Template\THtmlRenderer
 * @requires   mad-table-template.html (template file)
 */
class BMadTable extends TElement
{
    protected $id;
    protected $data;
    protected $config;
    protected $fields;
    protected $onConfigChange;
    
    // Database properties (similar to BChart)
    protected $database;
    protected $model;
    protected $joins;
    protected $criteria;
    protected $loaded;
    protected $showMethods;
    protected $transformers;
    
    // Display properties (similar to BChart)
    protected $title;
    protected $subtitle;
    protected $width;
    protected $height;
    protected $showPanel;
    protected $showHeader;
    protected $class;
    protected $noDataLabel;
    protected $presets;
    protected $defaultCurrency;
    protected $defaultLocale;
    
    /**
     * Constructor
     * @param string $id Component ID
     * @param string|null $database Database name for auto-loading data
     * @param string|null $model Model class name
     */
    public function __construct($id, $database = null, $model = null)
    {
        parent::__construct('div');
        $this->id = $id;
        $this->{'id'} = $id;
        $this->{'class'} = 'mad-table-container';
        
        // Initialize database properties
        $this->database = $database;
        $this->model = $model;
        $this->joins = [];
        $this->criteria = new TCriteria();
        $this->loaded = false;
        $this->showMethods = [];
        $this->transformers = [];
        
        // Initialize display properties
        $this->title = '';
        $this->subtitle = '';
        $this->width = '100%';
        $this->height = '600px';
        $this->showPanel = true;
        $this->showHeader = true;
        $this->class = 'mad-table-wrapper';
        $this->noDataLabel = 'Sem dados para exibir';
        $this->presets = [];
        $this->defaultCurrency = 'BRL';
        $this->defaultLocale = 'pt-BR';
        
        // Initialize default config
        $this->config = [
            'fields' => [],
            'filters' => [],
            'sorts' => [],
            'conditionalFormats' => [],
            'subtotals' => [
                'enabled' => false,
                'position' => 'below',
                'fields' => []
            ],
            'grandTotals' => [
                'rowTotals' => true,
                'columnTotals' => true,
                'position' => [
                    'row' => 'bottom',
                    'column' => 'right'
                ]
            ],
            'showFieldList' => false,
            'fieldListLayout' => 'horizontal',
            'virtualScrolling' => true,
            'rowsPerPage' => 50,
            'language' => 'pt-BR',
            'presets' => []
        ];
        
        $this->fields = [];
        $this->data = [];
    }
    
    /**
     * Sets the database name for data loading
     * @param string $database Database name
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }
    
    /**
     * Gets the database name
     * @return string|null
     */
    public function getDatabase()
    {
        return $this->database;
    }
    
    /**
     * Sets the model class for data loading
     * @param string $model Model class name
     */
    public function setModel($model)
    {
        $this->model = $model;
    }
    
    /**
     * Sets database joins for complex queries
     * @param array $joins Array of join conditions
     * Example: [['Customer', 'customer_id', '=', 'id']]
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;
    }
    
    /**
     * Sets the filtering criteria
     * @param TCriteria $criteria Filtering criteria
     */
    public function setCriteria(TCriteria $criteria)
    {
        $this->criteria = $criteria;
    }
    
    /**
     * Adds a filter to the criteria
     * @param string $field Field name
     * @param string $operator Operator (=, !=, >, <, etc)
     * @param mixed $value Value to compare
     */
    public function addCriteria($field, $operator, $value)
    {
        $this->criteria->add(new TFilter($field, $operator, $value));
    }
    
    /**
     * Defines which methods are allowed to display the table
     * @param array $methods List of allowed methods
     */
    public function setShowMethods($methods = [])
    {
        $this->showMethods = $methods;
    }
    
    /**
     * Checks if the table can be displayed
     * @return bool
     */
    public function canDisplay()
    {
        if ($this->showMethods)
        {
            return in_array($_REQUEST['method'] ?? '', $this->showMethods);
        }
        return true;
    }
    
    /**
     * Sets the default currency for value fields
     * @param string $currency Currency code (e.g., 'BRL', 'USD', 'EUR')
     */
    public function setDefaultCurrency($currency)
    {
        $this->defaultCurrency = $currency;
    }
    
    /**
     * Sets the default locale for formatting
     * @param string $locale Locale code (e.g., 'pt-BR', 'en-US', 'es-ES')
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }
    
    /**
     * Sets the language for the interface
     * @param string $language Language code: 'pt-BR', 'en', 'es'
     */
    public function setLanguage($language)
    {
        $this->config['language'] = $language;
    }
    
    /**
     * Adds a preset (view) configuration
     * @param string $id Preset ID
     * @param string $name Preset name
     * @param array $fields Array of field configurations
     * @param string|null $description Optional description
     * @param array|null $filters Optional filters
     * @param array|null $sorts Optional sorts
     */
    public function addPreset($id, $name, $description, $isDefault = false, $fields = [], $sorts = null, $filters = null)
    {
        $preset = [
            'id' => $id,
            'name' => $name,
            'fields' => $fields,
            'isDefault' => $isDefault,
        ];
        
        if ($description) {
            $preset['description'] = $description;
        }
        
        if ($filters) {
            $preset['filters'] = $filters;
        }
        
        if ($sorts) {
            $preset['sorts'] = $sorts;
        }
        
        $this->presets[] = $preset;
        $this->config['presets'] = $this->presets;
    }
    
    /**
     * Generates and processes multiple tables by handling database transactions
     * Similar to BChart::generate()
     * @param BMadTable[] ...$tables Variable-length list of table instances
     */
    public static function generate(...$tables)
    {
        $tablesDBs = [];
        
        foreach($tables as $table)
        {
            if (! $table->canDisplay())
            {
                continue;
            }
            
            if (! $table instanceof BMadTable)
            {
                throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', 'tables', __METHOD__));
            }
            
            $db = $table->getDatabase();
            
            if (empty($db))
            {
                continue;
            }
            
            if (empty($tablesDBs[$db]))
            {
                $tablesDBs[$db] = [];
            }
            
            $tablesDBs[$db][] = $table;
        }
        
        foreach($tablesDBs as $db => $tables)
        {
            TTransaction::open($db);
            
            foreach($tables as $table)
            {
                $table->loadData();
            }
            
            TTransaction::close();
        }
    }
    
    /**
     * Loads data from database based on model and criteria
     * Uses TSqlSelect for proper handling of multiple joined tables
     */
    public function loadData()
    {
        if (empty($this->database))
        {
            // If no database, use manually set data
            $this->loaded = true;
            return;
        }
        
        if (empty($this->model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }
        
        $cur_conn = serialize(TTransaction::getDatabaseInfo());
        $new_conn = serialize(TConnection::getDatabaseInfo($this->database));
        
        $open_transaction = ($cur_conn !== $new_conn);
        
        // Open transaction if not already open
        if ($open_transaction)
        {
            TTransaction::open($this->database);
        }
        
        try
        {
            $conn = TTransaction::get();
            
            $entity = (new $this->model)->getEntity();
            $entities = array_keys($this->joins??[]);
            if(!in_array($entity, $entities))
            {
                $entities[] = $entity;
            }

            $entities = implode(', ', $entities);
            
            // Apply joins if defined
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
            
            // Build SQL select
            $sql = new TSqlSelect();
            
            // Extract unique fields (by columnName to avoid duplicates)
            $uniqueFields = [];
            foreach ($this->fields as $field)
            {
                $columnName = $field['columnName'] ?? $field['id'];
                if (!isset($uniqueFields[$columnName])) {
                    $uniqueFields[$columnName] = [
                        'id' => $field['id'],
                        'columnName' => $columnName
                    ];
                }
            }
            
            // Add each column to the select
            foreach ($uniqueFields as $field)
            {
                $columnName = $field['columnName'];
                $columnId = $field['id'];
                
                // Not find dot, insert table name before
                if (strpos($columnName, '.') === FALSE && strpos($columnName, ':') === FALSE && strpos($columnName, '(') === FALSE && stripos($columnName, ' as ') === FALSE)
                {
                    $columnName = "{$entity}.{$columnName}";
                }
                
                if(stripos($columnName, ' as ') === FALSE)
                {
                    $sql->addColumn("{$columnName} as \"{$columnId}\"");
                }
                else
                {
                    $sql->addColumn($columnName);
                }
            }
            
            $sql->setEntity($entities);
            $sql->setCriteria($this->criteria);

            // echo $sql->getInstruction(TRUE);
            
            // Execute query
            $stmt = $conn->prepare($sql->getInstruction(TRUE), array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $result = $stmt->execute($this->criteria->getPreparedVars());
            
            $data = [];
            if ($result)
            {
                // Apply transformers if defined
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
                {
                    foreach ($this->transformers as $field => $transformer)
                    {
                        if (isset($row[$field]))
                        {
                            $row[$field] = call_user_func($transformer, $row[$field], $row);
                        }
                    }
                    
                    $data[] = $row;
                }
                $stmt->closeCursor();
            }
            
            $this->data = $data;
            $this->loaded = true;
            
            TTransaction::log("BMadTable: Loaded " . count($data) . " records from {$this->model}");
        }
        catch (Exception $e)
        {
            if ($open_transaction)
            {
                TTransaction::close();
            }
            throw $e;
        }
        
        // Close transaction if we opened it
        if ($open_transaction)
        {
            TTransaction::close();
        }
    }
    
    /**
     * Set table data manually (alternative to loadData)
     * @param array $data Array of data rows
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->loaded = true;
    }
    
    /**
     * Sets a transformer function for a specific field
     * @param string $field Field name
     * @param callable $transformer Transformation function
     */
    public function setTransformer($field, callable $transformer)
    {
        $this->transformers[$field] = $transformer;
    }
    
    /**
     * Loads data automatically before rendering
     * Similar to BChart::create()
     */
    public function create()
    {
        if (!$this->loaded && $this->database)
        {
            $this->loadData();
        }
    }
    
    /**
     * Add a field to the configuration
     * @param string $id Field ID (column name)
     * @param string $name Display name
     * @param string $type Field type: 'string', 'number', 'date'
     * @param string $area Area: 'rows', 'columns', 'values', 'filters'
     * @param int $order Order in the area
     * @param string $aggregation Aggregation: 'sum', 'avg', 'count', 'min', 'max', 'countDistinct'
     * @param string $format Format: 'number', 'currency', 'percent', 'string'
     * @param string|null $currency Currency code (e.g., 'BRL', 'USD') - for currency format
     * @param string|null $locale Locale code (e.g., 'pt-BR', 'en-US') - for number/date formatting
     * @param string|null $dateFormat Date format: 'short', 'medium', 'long', 'full', 'iso', 'datetime-short', 'datetime-medium', 'datetime-long', 'time-short', 'time-medium', 'time-long'
     */
    public function addField($id, $name, $type = 'string', $area = 'rows', $order = 0, $aggregation = null, $format = null, $currency = null, $locale = null, $dateFormat = null)
    {
        // Extrai o alias se o campo contém ' as '
        $fieldId = $id;
        if (stripos($id, ' as ') !== false) {
            $parts = preg_split('/\s+as\s+/i', $id);
            $fieldId = trim($parts[1]);
        }
        
        $field = [
            'id' => $fieldId,
            'name' => $name,
            'type' => $type,
            'area' => $area,
            'order' => $order,
            'columnName' => $id  // Mantém o nome original da coluna para a query SQL
        ];
        
        // Add aggregation for value fields
        if ($area === 'values') {
            $field['aggregation'] = $aggregation ?? 'sum';
            $field['format'] = $format ?? ($type === 'number' ? 'number' : 'string');
            
            // Add currency if format is currency
            if ($format === 'currency') {
                $field['currency'] = $currency ?? $this->defaultCurrency;
            }
            
            // Add locale for number formatting
            if ($locale) {
                $field['locale'] = $locale;
            } else if ($this->defaultLocale) {
                $field['locale'] = $this->defaultLocale;
            }
        }
        
        // Add date format for date fields
        if ($type === 'date' && $dateFormat) {
            $field['format'] = $dateFormat;
        }
        
        // Add locale for date fields if not set
        if ($type === 'date' && !isset($field['locale'])) {
            $field['locale'] = $locale ?? $this->defaultLocale;
        }
        
        $this->fields[] = $field;
    }
    
    /**
     * Add a row field (for grouping in rows)
     * @param string $id Field ID
     * @param string $name Display name
     * @param string $type Field type
     * @param int $order Order
     */
    public function addRowField($id, $name, $type = 'string', $order = null)
    {
        $order = $order ?? count(array_filter($this->fields, fn($f) => $f['area'] === 'rows'));
        $this->addField($id, $name, $type, 'rows', $order);
    }
    
    /**
     * Add a column field (for cross-tab)
     * @param string $id Field ID
     * @param string $name Display name
     * @param string $type Field type
     * @param int $order Order
     */
    public function addColumnField($id, $name, $type = 'string', $order = null)
    {
        $order = $order ?? count(array_filter($this->fields, fn($f) => $f['area'] === 'columns'));
        $this->addField($id, $name, $type, 'columns', $order);
    }
    
    /**
     * Add a value field (for aggregation)
     * @param string $id Field ID
     * @param string $name Display name
     * @param string $aggregation Aggregation type
     * @param string $format Display format
     * @param string|null $currency Currency code (for currency format)
     * @param string|null $locale Locale code
     * @param int $order Order
     */
    public function addValueField($id, $name, $aggregation = 'sum', $format = 'number', $currency = null, $locale = null, $order = null)
    {
        $order = $order ?? count(array_filter($this->fields, fn($f) => $f['area'] === 'values'));
        $this->addField($id, $name, 'number', 'values', $order, $aggregation, $format, $currency, $locale);
    }
    
    /**
     * Add a date field with specific format
     * @param string $id Field ID
     * @param string $name Display name
     * @param string $area Area: 'rows', 'columns', 'filters'
     * @param string $dateFormat Date format: 'short', 'medium', 'long', 'full', 'iso', 'datetime-short', 'datetime-medium', 'datetime-long', 'time-short', 'time-medium', 'time-long'
     * @param string|null $locale Locale code
     * @param int $order Order
     */
    public function addDateField($id, $name, $area = 'rows', $dateFormat = 'short', $locale = null, $order = null)
    {
        $order = $order ?? count(array_filter($this->fields, fn($f) => $f['area'] === $area));
        $this->addField($id, $name, 'date', $area, $order, null, null, null, $locale, $dateFormat);
    }
    
    /**
     * Add a filter field
     * @param string $id Field ID
     * @param string $name Display name
     * @param string $type Field type
     * @param int $order Order
     */
    public function addFilterField($id, $name, $type = 'string', $order = null)
    {
        $order = $order ?? count(array_filter($this->fields, fn($f) => $f['area'] === 'filters'));
        $this->addField($id, $name, $type, 'filters', $order);
    }
    
    /**
     * Enable/disable subtotals
     * @param bool $enabled Enable subtotals
     * @param string $position Position: 'above' or 'below'
     * @param array $fields Fields to show subtotals for
     */
    public function setSubtotals($enabled = true, $position = 'below', $fields = [])
    {
        $this->config['subtotals'] = [
            'enabled' => $enabled,
            'position' => $position,
            'fields' => $fields
        ];
    }
    
    /**
     * Configure grand totals
     * @param bool $rowTotals Show row totals
     * @param bool $columnTotals Show column totals
     * @param string $rowPosition Row position: 'top' or 'bottom'
     * @param string $columnPosition Column position: 'left' or 'right'
     */
    public function setGrandTotals($rowTotals = true, $columnTotals = true, $rowPosition = 'bottom', $columnPosition = 'right')
    {
        $this->config['grandTotals'] = [
            'rowTotals' => $rowTotals,
            'columnTotals' => $columnTotals,
            'position' => [
                'row' => $rowPosition,
                'column' => $columnPosition
            ]
        ];
    }
    
    /**
     * Set field list visibility and layout
     * @param bool $show Show field list
     * @param string $layout Layout: 'horizontal', 'vertical-left', 'vertical-right'
     */
    public function setFieldList($show = true, $layout = 'horizontal')
    {
        $this->config['showFieldList'] = $show;
        $this->config['fieldListLayout'] = $layout;
    }
    
    /**
     * Enable/disable virtual scrolling
     * @param bool $enabled Enable virtual scrolling
     * @param int $rowsPerPage Rows per page
     */
    public function setVirtualScrolling($enabled = true, $rowsPerPage = 50)
    {
        $this->config['virtualScrolling'] = $enabled;
        $this->config['rowsPerPage'] = $rowsPerPage;
    }
    
    /**
     * Add a sort configuration
     * @param string $fieldId Field ID
     * @param string $direction Direction: 'asc' or 'desc'
     */
    public function addSort($fieldId, $direction = 'asc')
    {
        $this->config['sorts'][] = [
            'fieldId' => $fieldId,
            'direction' => $direction
        ];
    }
    
    /**
     * Add a filter
     * @param string $fieldId Field ID
     * @param string $type Filter type: 'multiSelect', 'range', 'search'
     * @param array $values Filter values
     */
    public function addFilter($fieldId, $type = 'multiSelect', $values = [])
    {
        $this->config['filters'][] = [
            'fieldId' => $fieldId,
            'type' => $type,
            'values' => $values
        ];
    }
    
    /**
     * Add conditional formatting
     * @param string $fieldId Field ID
     * @param string $condition Condition: 'greaterThan', 'lessThan', 'between', 'equals'
     * @param mixed $value Comparison value
     * @param string $color Background color
     * @param string $textColor Text color
     */
    public function addConditionalFormat($fieldId, $condition, $value, $color = '#ffeb3b', $textColor = '#000')
    {
        $this->config['conditionalFormats'][] = [
            'fieldId' => $fieldId,
            'condition' => $condition,
            'value' => $value,
            'color' => $color,
            'textColor' => $textColor
        ];
    }
    
    /**
     * Set table title
     * @param string $title Title text
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * Set table subtitle
     * @param string $subtitle Subtitle text
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }
    
    /**
     * Set table width
     * @param string $width Width (e.g., '100%', '800px')
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }
    
    /**
     * Set table height
     * @param string $height Height (e.g., '600px', '100vh', or just '600')
     */
    public function setHeight($height)
    {
        // Se for um valor numérico sem unidade, adiciona 'px'
        if (is_numeric($height)) {
            $height .= 'px';
        }
        $this->height = $height;
    }
    
    /**
     * Enable/disable panel wrapper
     * @param bool $show Show panel
     */
    public function setShowPanel($show = true)
    {
        $this->showPanel = $show;
    }
    
    /**
     * Enable/disable header (title/subtitle)
     * @param bool $show Show header
     */
    public function setShowHeader($show = true)
    {
        $this->showHeader = $show;
    }
    
    /**
     * Set CSS class for wrapper
     * @param string $class CSS class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }
    
    /**
     * Set label for when there's no data
     * @param string $label No data label
     */
    public function setNoDataLabel($label)
    {
        $this->noDataLabel = $label;
    }
    
    
    /**
     * Set theme
     * @param string $theme Theme name: 'default', 'dark', 'compact'
     */
    public function setTheme($theme = 'default')
    {
        $this->config['theme'] = $theme;
    }
    
    /**
     * Set style configuration
     * @param array $style Style configuration array
     * 
     * Available options:
     * - rowHeight: int (default: 40)
     * - headerHeight: int (default: 48)
     * - fontSize: int (default: 14)
     * - cellPadding: string (default: '8px 12px')
     * - headerBgColor: string (default: '#f8fafc')
     * - headerTextColor: string (default: '#1e293b')
     * - rowBgColor: string (default: '#ffffff')
     * - rowAltBgColor: string (default: '#f8fafc')
     * - rowHoverBgColor: string (default: '#f1f5f9')
     * - borderColor: string (default: '#e2e8f0')
     * - subtotalBgColor: string (default: '#e0e7ff')
     * - subtotalFontWeight: string (default: '600')
     * - grandTotalBgColor: string (default: '#dbeafe')
     * - grandTotalFontWeight: string (default: '700')
     * - borderWidth: string (default: '1px')
     * - firstColumnWidth: int|string (default: 200)
     * - compact: bool (default: false)
     */
    public function setStyle($style = [])
    {
        if (!isset($this->config['style']))
        {
            $this->config['style'] = [];
        }
        
        $this->config['style'] = array_merge($this->config['style'], $style);
    }
    
    /**
     * Set row height
     * @param int $height Row height in pixels
     */
    public function setRowHeight($height = 40)
    {
        $this->setStyle(['rowHeight' => $height]);
    }
    
    /**
     * Set header height
     * @param int $height Header height in pixels
     */
    public function setHeaderHeight($height = 48)
    {
        $this->setStyle(['headerHeight' => $height]);
    }
    
    /**
     * Set font size
     * @param int $size Font size in pixels
     */
    public function setFontSize($size = 14)
    {
        $this->setStyle(['fontSize' => $size]);
    }
    
    /**
     * Set first column width
     * @param int|string $width Width in pixels or percentage
     */
    public function setFirstColumnWidth($width = 200)
    {
        $this->setStyle(['firstColumnWidth' => $width]);
    }
    
    /**
     * Enable compact mode
     * @param bool $compact Enable compact mode
     */
    public function setCompact($compact = true)
    {
        $this->setStyle(['compact' => $compact]);
    }
    
    /**
     * Set header colors
     * @param string $bgColor Background color
     * @param string $textColor Text color
     */
    public function setHeaderColors($bgColor = '#1e40af', $textColor = '#ffffff')
    {
        $this->setStyle([
            'headerBgColor' => $bgColor,
            'headerTextColor' => $textColor
        ]);
    }
    
    /**
     * Set row colors
     * @param string $bgColor Background color
     * @param string $altBgColor Alternate row background color
     * @param string $hoverBgColor Hover background color
     */
    public function setRowColors($bgColor = '#ffffff', $altBgColor = '#f8fafc', $hoverBgColor = '#f1f5f9')
    {
        $this->setStyle([
            'rowBgColor' => $bgColor,
            'rowAltBgColor' => $altBgColor,
            'rowHoverBgColor' => $hoverBgColor
        ]);
    }
    
    /**
     * Set border style
     * @param string $color Border color
     * @param string $width Border width
     */
    public function setBorderStyle($color = '#e2e8f0', $width = '1px')
    {
        $this->setStyle([
            'borderColor' => $color,
            'borderWidth' => $width
        ]);
    }
    
    /**
     * Set subtotal style
     * @param string $bgColor Background color
     * @param string $fontWeight Font weight
     */
    public function setSubtotalStyle($bgColor = '#e0e7ff', $fontWeight = '600')
    {
        $this->setStyle([
            'subtotalBgColor' => $bgColor,
            'subtotalFontWeight' => $fontWeight
        ]);
    }
    
    /**
     * Set grand total style
     * @param string $bgColor Background color
     * @param string $fontWeight Font weight
     */
    public function setGrandTotalStyle($bgColor = '#dbeafe', $fontWeight = '700')
    {
        $this->setStyle([
            'grandTotalBgColor' => $bgColor,
            'grandTotalFontWeight' => $fontWeight
        ]);
    }
    
    /**
     * Set callback for configuration changes
     * @param string $callback JavaScript callback function name
     */
    public function setOnConfigChange($callback)
    {
        $this->onConfigChange = $callback;
    }
    
    /**
     * Render the component
     * @return string HTML output
     */
    public function show()
    {
        // Check if can display
        if (!$this->canDisplay())
        {
            return;
        }
        
        // Auto-load data if not loaded
        if (!$this->loaded)
        {
            $this->create();
        }
        
        // Update config with fields (remove columnName que é apenas para uso interno)
        $fieldsForFrontend = array_map(function($field) {
            $cleanField = $field;
            unset($cleanField['columnName']);
            return $cleanField;
        }, $this->fields);
        $this->config['fields'] = $fieldsForFrontend;
        
        // Render template
        $html = new THtmlRenderer('lib/mad/widget/report/bmad-table.html');
        $html->disableHtmlConversion();
        $html->enableSection('main');
        
        // Check if there's data
        if (!empty($this->data))
        {
            // Convert data and config to JSON
            $dataJson = json_encode($this->data, JSON_UNESCAPED_UNICODE);
            $configJson = json_encode($this->config, JSON_UNESCAPED_UNICODE);
            
            // Generate unique variable name
            $varName = 'madTable_' . $this->id;
            
            // Prepare template replacements
            $replaces = [
                'instance_var' => $varName,
                'container_id' => $this->id,
                'width' => $this->width,
                'height' => $this->height,
                'class' => $this->class,
                'title' => $this->title,
                'data_json' => $dataJson,
                'config_json' => $configJson,
                'on_config_change' => $this->onConfigChange 
                    ? ",\n        onConfigChange: {$this->onConfigChange}" 
                    : ''
            ];
            
            // Enable data section
            $html->enableSection('data', $replaces);
            
            // Enable panel or nopanel
            if ($this->showPanel)
            {
                $html->enableSection('panel', $replaces);
                
                // Enable header if title is set
                if ($this->showHeader && !empty($this->title))
                {
                    $html->enableSection('header', $replaces);
                    
                    // Enable subtitle if set
                    if (!empty($this->subtitle))
                    {
                        $html->enableSection('subtitle', ['subtitle' => $this->subtitle. ' ']);
                    }
                }
            }
            else
            {
                $html->enableSection('nopanel', $replaces);
            }
        }
        else
        {
            // No data - show message
            $replaces = [
                'container_id' => $this->id,
                'width' => $this->width,
                'height' => $this->height,
                'class' => $this->class,
                'title' => $this->title ?: 'MAD-Table',
                'label' => $this->noDataLabel
            ];
            
            $html->enableSection('no-data', $replaces);
        }
        
        // Output
        $html->show();
    }
    
    /**
     * Get the table instance variable name (for JavaScript access)
     * @return string
     */
    public function getInstanceVar()
    {
        return 'madTable_' . $this->id;
    }
    
    /**
     * Generate method to update data dynamically
     * @param array $data New data
     * @return string JavaScript code
     */
    public function getUpdateDataScript($data)
    {
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        $varName = $this->getInstanceVar();
        
        return "if (window.{$varName}) { window.{$varName}.updateData({$dataJson}); }";
    }
    
    /**
     * Generate method to update config dynamically
     * @param array $config New config
     * @return string JavaScript code
     */
    public function getUpdateConfigScript($config)
    {
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
        $varName = $this->getInstanceVar();
        
        return "if (window.{$varName}) { window.{$varName}.updateConfig({$configJson}); }";
    }
    
    /**
     * Generate method to destroy the table
     * @return string JavaScript code
     */
    public function getDestroyScript()
    {
        $varName = $this->getInstanceVar();
        
        return "if (window.{$varName}) { window.{$varName}.destroy(); window.{$varName} = null; }";
    }
}

/**
 * ============================================================================
 * EXEMPLO DE USO COMPLETO COM NOVAS FUNCIONALIDADES
 * ============================================================================
 * 
 * // 1. Criar tabela com configurações de idioma, moeda e locale
 * $table = new BMadTable('sales_table', 'database', 'Sale');
 * $table->setLanguage('pt-BR');
 * $table->setDefaultCurrency('BRL');
 * $table->setDefaultLocale('pt-BR');
 * 
 * // 2. Adicionar campos com formatação
 * 
 * // Campos de linha (agrupamento)
 * $table->addRowField('region', 'Região', 'string', 0);
 * $table->addRowField('country', 'País', 'string', 1);
 * 
 * // Campo de data com formato específico
 * $table->addDateField('sale_date', 'Data da Venda', 'rows', 'short', 'pt-BR', 2);
 * $table->addDateField('created_at', 'Criado em', 'rows', 'datetime-medium', 'pt-BR', 3);
 * $table->addDateField('time_only', 'Hora', 'rows', 'time-short', 'pt-BR', 4);
 * 
 * // Campos de coluna (cross-tab)
 * $table->addColumnField('category', 'Categoria', 'string', 0);
 * 
 * // Campos de valor com moeda e locale
 * $table->addValueField('total_sales', 'Vendas Totais', 'sum', 'currency', 'BRL', 'pt-BR', 0);
 * $table->addValueField('profit', 'Lucro', 'sum', 'currency', 'BRL', 'pt-BR', 1);
 * $table->addValueField('quantity', 'Quantidade', 'sum', 'number', null, 'pt-BR', 2);
 * 
 * // Campos com moedas diferentes
 * $table->addValueField('sales_usd', 'Vendas (USD)', 'sum', 'currency', 'USD', 'en-US', 3);
 * $table->addValueField('sales_eur', 'Vendas (EUR)', 'sum', 'currency', 'EUR', 'de-DE', 4);
 * 
 * // 3. Adicionar Presets (Visões)
 * 
 * // Preset 1: Vendas por Região
 * $table->addPreset(
 *     'sales-by-region',
 *     'Vendas por Região',
 *     [
 *         ['id' => 'region', 'name' => 'Região', 'type' => 'string', 'area' => 'rows', 'order' => 0],
 *         ['id' => 'country', 'name' => 'País', 'type' => 'string', 'area' => 'rows', 'order' => 1],
 *         ['id' => 'total_sales', 'name' => 'Vendas', 'type' => 'number', 'area' => 'values', 'aggregation' => 'sum', 'format' => 'currency', 'currency' => 'BRL', 'locale' => 'pt-BR', 'order' => 0],
 *         ['id' => 'profit', 'name' => 'Lucro', 'type' => 'number', 'area' => 'values', 'aggregation' => 'sum', 'format' => 'currency', 'currency' => 'BRL', 'locale' => 'pt-BR', 'order' => 1]
 *     ],
 *     'Análise de vendas e lucro por região e país'
 * );
 * 
 * // Preset 2: Análise Temporal
 * $table->addPreset(
 *     'temporal-analysis',
 *     'Análise Temporal',
 *     [
 *         ['id' => 'sale_date', 'name' => 'Data', 'type' => 'date', 'area' => 'rows', 'order' => 0, 'dateFormat' => 'short', 'locale' => 'pt-BR'],
 *         ['id' => 'category', 'name' => 'Categoria', 'type' => 'string', 'area' => 'columns', 'order' => 0],
 *         ['id' => 'total_sales', 'name' => 'Vendas', 'type' => 'number', 'area' => 'values', 'aggregation' => 'sum', 'format' => 'currency', 'currency' => 'BRL', 'locale' => 'pt-BR', 'order' => 0]
 *     ],
 *     'Vendas por data e categoria (cross-tab)'
 * );
 * 
 * // Preset 3: Visão Detalhada com DateTime
 * $table->addPreset(
 *     'detailed-datetime',
 *     'Visão Detalhada',
 *     [
 *         ['id' => 'created_at', 'name' => 'Data/Hora', 'type' => 'date', 'area' => 'rows', 'order' => 0, 'dateFormat' => 'datetime-long', 'locale' => 'pt-BR'],
 *         ['id' => 'region', 'name' => 'Região', 'type' => 'string', 'area' => 'rows', 'order' => 1],
 *         ['id' => 'total_sales', 'name' => 'Vendas', 'type' => 'number', 'area' => 'values', 'aggregation' => 'sum', 'format' => 'currency', 'currency' => 'BRL', 'locale' => 'pt-BR', 'order' => 0],
 *         ['id' => 'quantity', 'name' => 'Quantidade', 'type' => 'number', 'area' => 'values', 'aggregation' => 'sum', 'format' => 'number', 'locale' => 'pt-BR', 'order' => 1]
 *     ],
 *     'Análise detalhada com data e hora completa'
 * );
 * 
 * // 4. Configurar critérios e joins
 * $table->addCriteria('status', '=', 'active');
 * $table->setJoins([
 *     ['Customer', 'customer_id', '=', 'id'],
 *     ['Product', 'product_id', '=', 'id']
 * ]);
 * 
 * // 5. Configurar aparência
 * $table->setTitle('Relatório de Vendas');
 * $table->setSubtitle('Análise completa de vendas por região e período');
 * $table->setHeight('700px');
 * $table->setTheme('default');
 * 
 * // 6. Renderizar
 * $table->show();
 * 
 * // OU usar geração em lote
 * BMadTable::generate($table1, $table2, $table3);
 * 
 * ============================================================================
 * FORMATOS DE DATA DISPONÍVEIS
 * ============================================================================
 * 
 * - 'short'           : 01/01/2025
 * - 'medium'          : 1 de jan. de 2025
 * - 'long'            : 1 de janeiro de 2025
 * - 'full'            : quarta-feira, 1 de janeiro de 2025
 * - 'iso'             : 2025-01-01
 * - 'datetime-short'  : 01/01/2025, 14:30
 * - 'datetime-medium' : 1 de jan. de 2025, 14:30:45
 * - 'datetime-long'   : 1 de janeiro de 2025 às 14:30:45
 * - 'time-short'      : 14:30
 * - 'time-medium'     : 14:30:45
 * - 'time-long'       : 14:30:45 GMT-3
 * 
 * ============================================================================
 * MOEDAS SUPORTADAS
 * ============================================================================
 * 
 * - 'BRL' : Real Brasileiro (R$)
 * - 'USD' : Dólar Americano ($)
 * - 'EUR' : Euro (€)
 * - 'GBP' : Libra Esterlina (£)
 * - 'JPY' : Iene Japonês (¥)
 * - E outras moedas ISO 4217
 * 
 * ============================================================================
 * LOCALES SUPORTADOS
 * ============================================================================
 * 
 * - 'pt-BR' : Português do Brasil
 * - 'en-US' : Inglês Americano
 * - 'es-ES' : Espanhol da Espanha
 * - 'fr-FR' : Francês da França
 * - 'de-DE' : Alemão da Alemanha
 * - 'it-IT' : Italiano da Itália
 * - E outros locales padrão
 * 
 */
