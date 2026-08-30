<?php

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TImage;

/**
 * @version    4.0
 * @package    widget
 * @subpackage builder
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

class BNotificationBanner extends TElement
{
    private $template;
    private $name;
    
    protected $width;
    protected $height;
    protected $title;
    protected $message;
    protected $icon;
    protected $borderLeftColor;

    /**
     * Class Constructor for BNotificationBanner
     * @param string $name    widget's name
     * @param string $title   banner's title
     * @param string $message banner's message
     * @param TImage|null $icon banner's icon
     */
    public function __construct($name, $title = '', $message = '', $icon = null)
    {
        parent::__construct('div');

        $this->class = 'b-notification-banner';
        $this->id = $name.'_'.uniqid();
        $this->name = $name;
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->borderLeftColor = '#3b82f6';
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
     * Set icon for the notification banner
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
     * Load the HTML template for the notification banner
     */
    private function loadTemplate()
    {
        $this->template = new THtmlRenderer(__DIR__.'/bNotificationBanner.html');
        $this->template->disableHtmlConversion();
    }

    /**
     * Set the form name for the notification banner
     * @param string $formName The name of the form
     */
    public function setFormName($formName)
    {
        $this->formName = $formName;
    }

    /**
     * Get the form name associated with the notification banner
     * @return string The form name
     */
    public function getFormName()
    {
        return $this->formName;
    }

    /**
     * Set the color for the notification banner's left border
     * @param string $color The color in hex format (e.g. '#3b82f6')
     */
    public function setBorderLeftColor($color)
    {
        $this->borderLeftColor = $color;
    }

    public function setColor($color)
    {

    }

    public function getBorderLeftColor()
    {
        return $this->borderLeftColor;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Show the notification banner
     * Renders the banner with all its properties (title, message, icon, color)
     * and applies the color to the left border
     */
    public function show()
    {
        $this->loadTemplate();

        $this->template->enableSection('main', [
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon
        ]);

        $this->style = "border-left: 6px solid {$this->borderLeftColor};";

        parent::add($this->template);
        parent::show();
    }
}
