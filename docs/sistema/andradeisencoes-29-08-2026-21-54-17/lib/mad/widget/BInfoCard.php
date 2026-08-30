<?php

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TImage;
use Adianti\Control\TAction;

/**
 * @version    4.0
 * @package    widget
 * @subpackage builder
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

class BInfoCard extends TElement
{
    private $template;
    private $name;
    
    protected $width;
    protected $height;
    protected $title;
    protected $description;
    protected $icon;
    protected $clickAction;
    protected $iconBackgroundColor;

    /**
     * Class Constructor for BInfoCard
     * @param string $name         widget's name
     * @param string $title        card's title
     * @param string $description  card's description
     * @param TImage|null $icon    card's icon
     */
    public function __construct($name, $title = '', $description = '', $icon = null)
    {
        parent::__construct('div');

        $this->class = 'b-info-card';
        $this->id = $name.'_'.uniqid();
        $this->name = $name;
        $this->title = $title;
        $this->description = $description;
        $this->icon = $icon;
    }

    public function setIconBackgroundColor($color)
    {
        $this->iconBackgroundColor = $color;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    /**
     * Get name of the widget
     * @return string widget's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set icon for the info card
     * @param TImage $icon The icon to be displayed
     */
    public function setIcon(TImage $icon)
    {
        $this->icon = $icon;
    }

    /**
     * Return sizes of the widget
     * @return mixed|null Returns null by default
     */
    public function getSize()
    {
        return null;
    }

    /**
     * Load the HTML template for the info card
     */
    private function loadTemplate()
    {
        $this->template = new THtmlRenderer(__DIR__.'/bInfoCard.html');
        $this->template->disableHtmlConversion();
    }

    /**
     * Set the action to be executed when the card is clicked
     * @param TAction $action The action to be executed
     */
    public function setClickAction(TAction $action)
    {
        $this->clickAction = $action;
    }

    /**
     * Get the action associated with clicking the card
     * @return TAction|null The click action if set
     */
    public function getClickAction()
    {
        return $this->clickAction;
    }

    public function setParameter($key, $value)
    {
        if($this->clickAction)
        {
            $this->clickAction->setParameter($key, $value);
        }
    }

    /**
     * Set the form name for the info card
     * @param string $formName The name of the form
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;
    }

    /**
     * Get the form name associated with the info card
     * @return string The form name
     */
    public function getFormName()
    {
        return $this->formName;
    }
    

    /**
     * Show the info card
     * Renders the card with all its properties (title, description, icon)
     * and sets up click actions if defined
     */
    public function show()
    {
        $this->loadTemplate();

        $url = '';

        if($this->clickAction)
        {  
            if($this->clickAction->isHidden())
            {
                return '';
            }
            
            parent::setName('a');

            if(!$this->clickAction->isDisabled())
            {
                $url = $this->clickAction->serialize(true);
                if ($this->clickAction->isStatic())
                {
                    $url .= '&static=1';
                }

                $url = htmlspecialchars($url);

                $this->href = $url;
                $this->generator = 'adianti';
            }
            else
            {
                $this->href = '#';
                $this->disabled = 'disabled';
            }
        }

        $this->template->enableSection('main', [
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'iconBackgroundColor' => $this->iconBackgroundColor ? "background-color: {$this->iconBackgroundColor};" : ''
        ]);

        parent::add($this->template);
        parent::show();
    }
}
