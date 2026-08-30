<?php

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TConnection;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TSqlSelect;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TStyle;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TImage;
use Adianti\Control\TAction;
use Adianti\Widget\Form\AdiantiWidgetInterface;
/**
 * Class BIndicator
 *
 * This class represents an indicator widget that displays a value retrieved from a database.
 * It allows customization of colors, layout, icons, and transformations of the displayed value.
 *
 * @version    7.4
 * @package    widget
 * @subpackage builder
 * @author     Lucas Tomasi
 */
class BIndicator extends TElement implements AdiantiWidgetInterface
{
    private $html;
    private $value;
    private $name;
    protected $showMethods;

    /* data properties */
    protected $database;
    protected $model;
    protected $fieldValue;
    protected $joins;
    protected $total;

    protected $loaded;

    protected $criteria;

    /* layout properties */
    protected $layout;
    protected $alignLayout;
    protected $width;
    protected $height;
    protected $contentColor;

    /* title properties */
    protected $title;
    protected $titleSize;
    protected $titleColor;
    protected $titleDecoration;

    /* description properties */
    protected $description;
    protected $descriptionSize;
    protected $descriptionDecoration;

    protected $target;
    protected $targetColor;
    protected $transformerDescription;

    /* value properties */
    protected $valueColor;
    protected $valueDecoration;
    protected $valueSize;

    protected $transformerValue;

    /* icon properties */
    protected $icon;
    protected $backgroundIconColor;

    protected $clickAction;
    protected $formName;


    /**
     * BIndicator constructor.
     *
     * Initializes an indicator widget with database-related parameters and layout configurations.
     *
     * @param string      $name       The name of the widget.
     * @param string|null $database   The database name.
     * @param string|null $model      The model name (TRecord subclass).
     * @param string|null $fieldValue The field name to retrieve the value from.
     * @param string      $total      The type of totalization (sum, max, min, count, avg). Default is 'count'.
     * @param TCriteria|null $criteria An optional TCriteria object to filter the data.
     * @param array       $joins      An array of joins to be used in the query.
     */
    public function __construct(String $name, $database = null, $model = null, $fieldValue = null, $total = 'count', ?TCriteria $criteria = NULL, array $joins = [])
    {
        parent::__construct('div');

        $this->name = $name;
        $this->showMethods = [];
        $this->loaded = false;
        $this->layout = 'horizontal';

        $this->setDatabase($database);
        $this->setModel($model);
        $this->setFieldValue($fieldValue);
        $this->setTotal($total);
        $this->setSize('100%', 90);

        $this->setColors('#007bff', '#333333', 'white', "#555555");

        $this->setCriteria($criteria??new TCriteria);
        $this->setJoins($joins);
    }

    /**
     * Creates a style object based on text decorations.
     *
     * @param string $decoration The text decoration (b=bold, i=italic, u=underline).
     * 
     * @return TStyle The generated style object.
     */
    private function setFontStyle($decoration)
    {
        $style = new TStyle('title_style');
        if (strpos(strtolower((string) $decoration), 'b') !== FALSE)
        {
            $style->{'font-weight'} = 'bold';
        }

        if (strpos(strtolower((string) $decoration), 'i') !== FALSE)
        {
            $style->{'font-style'} = 'italic';
        }

        if (strpos(strtolower((string) $decoration), 'u') !== FALSE)
        {
            $style->{'text-decoration'} = 'underline';
        }

        return $style;
    }

    /**
     * Validates the widget.
     *
     * @return bool Always returns true.
     */
    public function validate()
    {
        return true;
    }

    /**
     * Checks if the widget should be displayed based on the allowed methods.
     *
     * @return bool True if the widget should be displayed, false otherwise.
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
     * Defines the methods that allow the widget to be displayed.
     *
     * @param array $methods An array of method names.
     */
    public function setShowMethods($methods = [])
    {
        $this->showMethods = $methods;
    }

    /**
     * Gets the widget name.
     *
     * @return string The name of the widget.
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Gets the database name.
     *
     * @return string|null The database name.
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Sets the database name.
     *
     * @param string|null $database The database name.
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * Sets the model name.
     *
     * @param string|null $model The model name (TRecord subclass).
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Sets the indicator's icon.
     *
     * @param TImage $icon The icon to be displayed.
     */
    public function setIcon(TImage $icon)
    {
        $this->icon = $icon;
    }

    /**
     * Sets the background color of the icon.
     *
     * @param string $color The background color.
     */
    public function setBackgroundIconColor(String $color)
    {
        $this->backgroundIconColor = $color;
    }

    /**
     * Sets the title color.
     *
     * @param string $color The title color.
     */
    public function setTitleColor(String $color)
    {
        $this->titleColor = $color;
    }

    /**
     * Sets the content color.
     *
     * @param string $color The content color.
     */
    public function setContentColor(String $color)
    {
        $this->contentColor = $color;
    }

    /**
     * Sets the value color and optional text decoration.
     *
     * @param string $color       The value color.
     * @param string|null $decoration Text decorations (b=bold, i=italic, u=underline).
     */
    public function setValueColor(String $color, $decoration = null)
    {
        $this->valueColor = $color;

        if ($decoration)
        {
            $this->valueDecoration = $this->setFontStyle($decoration);
        }
    }

    /**
     * Sets various colors for the indicator.
     *
     * @param string $backgroundIconColor The background color of the icon.
     * @param string $titleColor          The title color.
     * @param string $contentColor        The content color.
     * @param string $valueColor          The value color.
     */
    public function setColors(String $backgroundIconColor, String $titleColor, String $contentColor, String $valueColor)
    {
        $this->backgroundIconColor = $backgroundIconColor;
        $this->titleColor = $titleColor;
        $this->contentColor = $contentColor;
        $this->valueColor = $valueColor;
    }

    /**
     * Sets a transformer function for the value.
     *
     * @param callable $transformer The callable function to transform the value.
     */
    public function setTransformerValue(callable $transformer)
    {
        $this->transformerValue = $transformer;
    }

    /**
     * Sets a target value for progress bar display.
     *
     * @param float $target The target value.
     * @param string $targetColor The progress bar color.
     * @param callable|null $transformerDescription A callable function to transform the description.
     * @param string $size The size of the progress bar.
     * @param string|null $decoration Text decorations (b=bold, i=italic, u=underline).
     */
    public function setTarget($target, $targetColor, ?callable $transformerDescription = null, $size = '80%', $decoration = null)
    {
        $this->target = $target;
        $this->targetColor = $targetColor;
        $this->transformerDescription = $transformerDescription;

        $this->descriptionDecoration = $this->setFontStyle($decoration);
        $this->descriptionSize = (strstr($size, '%') !== FALSE) ? $size : "{$size}px";
    }

    /**
     * Sets the field name that holds the value.
     *
     * @param string|null $fieldValue The field name.
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;
    }

    /**
     * Sets database joins.
     *
     * @param array $joins An array of joins.
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;
    }

    /**
     * Sets the totalization type.
     *
     * @param string $total The type of totalization (sum, max, min, count, avg).
     *
     * @throws Exception If an invalid totalization type is provided.
     */
    public function setTotal($total)
    {
        if (! in_array($total, ['sum', 'max', 'min', 'count', 'avg']))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $total, __METHOD__));
        }

        $this->total = $total;
    }

    /**
     * Sets the layout and alignment of the indicator.
     *
     * @param string $layout The layout type (horizontal|vertical).
     * @param string $align  The alignment type (left|right|center).
     *
     * @throws Exception If an invalid layout or alignment type is provided.
     */
    public function setLayout(string $layout, String $align = 'left')
    {
        if (! in_array($layout, ["horizontal", "vertical", 'flat-horizontal', 'flat-vertical']))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $layout, __METHOD__));
        }

        if ($align && ! in_array($align, ["left", "right", "center"]))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $align, __METHOD__));
        }

        if ($align)
        {
            $this->alignLayout = $align;
        }

        $this->layout = $layout;
    }

    /**
     * Sets the filtering criteria.
     *
     * @param TCriteria $criteria The filtering criteria.
     */
    public function setCriteria(TCriteria $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Sets the title and its appearance.
     *
     * @param string $title The title text.
     * @param string $color The title color.
     * @param string|null $size The title size.
     * @param string|null $decoration Text decorations (b=bold, i=italic, u=underline).
     */
    public function setTitle($title, $color, $size = null, $decoration = null)
    {
        if ($decoration)
        {
            $this->titleDecoration = $this->setFontStyle($decoration);
        }

        $this->title = $title;
        $this->titleColor = $color;
        $this->titleSize = (strstr($size, '%') !== FALSE) ? $size : "{$size}px";
    }

    /**
     * Sets the description text.
     *
     * @param string $description The description text.
     * @param string $descriptionSize The size of the description text.
     * @param string|null $decoration Text decorations (b=bold, i=italic, u=underline).
     */
    public function setDescription($description, $descriptionSize = '80%', $decoration = null)
    {
        $this->description = $description;
        $this->descriptionSize = (strstr($descriptionSize, '%') !== FALSE) ? $descriptionSize : "{$descriptionSize}px";

        if ($decoration)
        {
            $this->descriptionDecoration = $this->setFontStyle($decoration);
        }
    }


    /**
     * Sets the size of the indicator panel.
     *
     * @param string $width The width.
     * @param string|null $height The height.
     */
    public function setSize($width, $height = null)
    {
        $this->width = (strstr($width, '%') !== FALSE) ? $width : "{$width}px";

        if ($height)
        {
            $this->height = (strstr($height, '%') !== FALSE) ? $height : "{$height}px";
        }
    }

    /**
     * Gets the size of the widget.
     *
     * @return null Always returns null.
     */
    public function getSize()
    {
        return null;
    }

    /**
     * Sets the font size of the value.
     *
     * @param string $valueSize The size of the value text.
     */
    public function setValueSize($valuSize)
    {
        $this->valueSize = (strstr($valuSize, '%') !== FALSE) ? $valuSize : "{$valuSize}px";;
    }
    /**
     * Loads data from the database based on the configured model, field, and criteria.
     *
     * @throws Exception If the database, model, or field value is not defined.
     */
    private function loadData()
    {
        if($this->loaded)
        {
            return $this->value;
        }
        
        $this->value = 0;

        if (empty($this->database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }

        if (empty($this->model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }

        if (empty($this->fieldValue))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'fieldValue', __CLASS__));
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
        $entities[] = $entity;

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

        // Not find dot, insert table name before
        if (strpos($this->fieldValue, '.') === FALSE && strpos($this->fieldValue, ':') === FALSE && strpos($this->fieldValue, '(') === FALSE)
        {
            $this->fieldValue = "{$entity}.{$this->fieldValue}";
        }
        $sql->addColumn("{$this->total}({$this->fieldValue})");
        $sql->setEntity($entities);
        $sql->setCriteria($this->criteria);

        $stmt = $conn->prepare($sql->getInstruction(TRUE), array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $result = $stmt->execute($this->criteria->getPreparedVars());

        if($result)
        {
            $num = $stmt->fetch(PDO::FETCH_NUM);
            $this->value = $num ? $num[0] : NULL;
        }

        // close connection
        if ($open_transaction)
        {
            TTransaction::close();
        }
    }

    /**
     * Loads the HTML template for the indicator.
     *
     * @throws Exception If the layout is not defined.
     */
    private function loadTemplate()
    {
        if (empty($this->layout))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'layout', __CLASS__));
        }

        $this->html = new THtmlRenderer(__DIR__.'/bindicator.html');
    }

    /**
     * Enables the description section of the indicator.
     *
     * If a target value is set, it displays a progress bar.
     */
    private function enableDescription()
    {
        $styles  = $this->descriptionDecoration ? $this->descriptionDecoration->getInline() : '';
        $styles .= "font-size: {$this->descriptionSize};color: {$this->titleColor}";

        if ($this->target)
        {
            $value = floor(($this->value / $this->target) * 100);
            $description = $this->transformerDescription ? call_user_func($this->transformerDescription, $value, $this->target, $this->value) : $this->description;

            if($this->targetColor == 'auto')
            {
                if($value < 30)
                {
                    $this->targetColor = '#d93025';
                }
                elseif($value >= 30 && $value < 70)
                {
                    $this->targetColor = '#f9ab00';
                }
                else
                {
                    $this->targetColor = '#188038';
                }
            }

            $this->html->enableSection(
                $this->layout . '-progress',
                [
                    'targetColor' => $this->targetColor,
                    'value' => $value,
                    'styles' => $styles,
                    'description' => $description,
                ]
            );
        }
        elseif ($this->description)
        {
            $this->html->enableSection(
                $this->layout . '-description',
                [
                    'description' => $this->description,
                    'styles' => $styles
                ]
            );
        }
    }

    /**
     * Enables the icon section of the indicator if an icon is set.
     */
    private function enableIcon()
    {
        if (! $this->icon)
        {
            return;
        }

        $this->html->enableSection(
            $this->layout . '-icon',
            [
                'height' => $this->height,
                'icon' => $this->icon,
                'backgroundIconColor' => $this->backgroundIconColor
            ]
        );
    }

    /**
     * Loads the data and prepares the indicator before displaying it.
     */
    public function create()
    {
        $this->loadData();
        $this->loaded = true;
    }

    /**
     * Sets the value of the indicator.
     *
     * @param mixed $value The value to be set.
     */
    public function setValue($value)
    {
        $this->loaded = true;
        $this->value = $value;
    }

    /**
     * Gets the current value of the indicator.
     *
     * @return mixed The current value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the action triggered when the indicator is clicked.
     *
     * @param TAction $action The action object.
     */
    public function setClickAction(TAction $action)
    {
        $this->clickAction = $action;
    }

    /**
     * Gets the action assigned to the indicator.
     *
     * @return TAction|null The action object or null if not set.
     */
    public function getClickAction()
    {
        return $this->clickAction;
    }

    /**
     * Sets the form name associated with the indicator.
     *
     * @param string $formName The form name.
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;
    }

    /**
     * Gets the form name associated with the indicator.
     *
     * @return string|null The form name.
     */
    public function getFormName()
    {
        return $this->formName;
    }

    /**
     * Gets the value as post data.
     *
     * @return mixed The value of the indicator.
     */
    public function getPostData()
    {
        return $this->value;
    }
    
    /**
     * Renders the indicator widget.
     */
    public function show()
    {
        if (! $this->canDisplay())
        {
            return;
        }

        $this->loadTemplate();

        if (! $this->loaded)
        {
            $this->create();
        }

        $transformerdValue = $this->value;
        if ($this->transformerValue)
        {
            $transformerdValue = call_user_func($this->transformerValue, $this->value);
        }

        $this->enableDescription();
        $this->enableIcon();

        $style  = $this->titleDecoration ? $this->titleDecoration->getInline() : '';
        $style .= "color: {$this->titleColor}; font-size: {$this->titleSize};";

        $styleValue  = $this->valueDecoration ? $this->valueDecoration->getInline() : '';
        $styleValue .= "color: {$this->valueColor}; font-size: {$this->valueSize} !important;";

        $action = '';
        $actionCursor = '';
        if($this->clickAction)
        {
            $this->clickAction->setParameter($this->name, $this->value ?? 0);
            
            $url = $this->clickAction->serialize(FALSE);
            if ($this->clickAction->isStatic())
            {
                $url .= '&static=1';
            }

            $url = htmlspecialchars($url);
            $wait_message = AdiantiCoreTranslator::translate('Loading');
            // define the button's action (ajax post)
            $action = "Adianti.waitMessage = '$wait_message';";
            $action.= "__adianti_post_data('{$this->formName}', '{$url}');";
            $action.= "return false;";

            $action = "RAW:onclick=\"{$action}\" ";

            $actionCursor = 'cursor:pointer';
        }

        $this->html->enableSection(
            $this->layout,
            [
                'name' => $this->name,
                'marginContent' => $this->icon ? '' :  0,
                'alignLayout' => $this->alignLayout,
                'title' => $this->title,
                'style' => $style,
                'styleValue' => $styleValue,
                'data' =>  $transformerdValue,
                'height' => $this->height,
                'width' => $this->width,
                'contentColor' => $this->contentColor,
                'action' => $action,
                'actionCursor' => $actionCursor,
            ]
        );

        parent::add($this->html);
        parent::show();
    }
}