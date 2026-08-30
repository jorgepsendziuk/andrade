<?php

/**
 * Class BContainer
 *
 * This class extends BootstrapFormBuilder and provides additional customization
 * options such as title styling, border customization, and an expandable feature.
 *
 * @version    1.0
 * @package    widget
 * @subpackage base
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class BContainer extends BootstrapFormBuilder
{
    private $title;
    private $expanderEnabled = false;
    private $startExpanderOpened = false;
    private $id;
    private $borderColor = '#c0c0c0';
    private $titleFontSize;
    private $titelDecoration;
    private $titleBackgroundColor;
    private $titleFontColor;
    private $titleStyle;

    /**
     * BContainer constructor.
     *
     * Initializes the container with a given name.
     *
     * @param string $name The name of the container.
     */
    public function __construct($name)
    {
        parent::__construct($name);
    }

    /**
     * Sets the title and its visual properties.
     *
     * @param string      $title                The title text.
     * @param string|null $titleFontColor       The color of the title font (optional).
     * @param string|null $titleFontSize        The size of the title font (optional).
     * @param string|null $titelDecoration      The decoration style of the title (bold, italic, underline) (optional).
     * @param string|null $titleBackgroundColor The background color of the title (optional).
     */
    public function setTitle($title, $titleFontColor = null, $titleFontSize = null, $titelDecoration = null, $titleBackgroundColor = null)
    {
        $this->title = $title;
        $this->titleFontSize = $titleFontSize;
        $this->titelDecoration = $titelDecoration;
        $this->titleBackgroundColor = $titleBackgroundColor;
        $this->titleFontColor = $titleFontColor;

        if (strpos(strtolower((string) $this->titelDecoration), 'b') !== FALSE)
        {
            $this->titleStyle .= 'font-weight: bold;';
        }
        
        if (strpos(strtolower( (string) $this->titelDecoration), 'i') !== FALSE)
        {
            $this->titleStyle .= 'font-style: italic;';
        }
        
        if (strpos(strtolower( (string) $this->titelDecoration), 'u') !== FALSE)
        {
            $this->titleStyle .= 'text-decoration: underline;';
        }

        if($titleFontColor)
        {
            $this->titleStyle .= "color: {$titleFontColor};";
        }

        if($titleFontSize)
        {
            $this->titleStyle .= "font-size: {$titleFontSize};";
        }

        if($titleBackgroundColor)
        {
            $this->titleStyle .= "background-color: {$titleBackgroundColor};";
        }
    }

    /**
     * Enables the expander feature, allowing the container to be expandable.
     */
    public function enableExpander()
    {
        $this->expanderEnabled = true;
    }

    /**
     * Disables the expander feature, making the container static.
     */
    public function disableExpander()
    {
        $this->expanderEnabled = false;
    }

    /**
     * Sets the expander to start in an opened state by default.
     */
    public function enableStartExpanderOpened()
    {
        $this->startExpanderOpened = true;
    }

    /**
     * Sets the border color of the container.
     *
     * @param string $borderColor The border color in HEX or CSS color format.
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = $borderColor;
    }

    /**
     * Sets the ID of the container.
     *
     * @param string $id The unique identifier for the container.
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * Displays the container with all its configurations, including title, styling,
     * and expandability features.
     */
    public function show()
    {
        if($this->title)
        {
            if($this->id)
            {
                $this->setProperty('id', $this->id);
            }
            
            $this->setProperty('class', 'bContainer-fieldset');
            $this->setProperty('style', "border:1px solid {$this->borderColor};");

            $this->titleStyle .= "border:1px solid {$this->borderColor};";

            $titleContainer = new TElement('div');
            $titleContainer->setProperty('class', 'bContainer-title');
            $titleContainer->setProperty('style', $this->titleStyle);
            $titleContainer->add($this->title);

            if($this->expanderEnabled)
            {
                $titleContainer->onClick = "BContainer.toggle(this);";

                if($this->startExpanderOpened)
                {
                    $this->setProperty('class', 'bContainer-fieldset bContainer-accordion');
                    $titleContainer->add("<i style='display:none' class='fas fa-plus bContainer-accordion-icon bContainer-accordion-icon-show'></i>");
                    $titleContainer->add("<i class='fas fa-minus bContainer-accordion-icon bContainer-accordion-icon-hide'></i>");
                }
                else
                {
                    $this->setProperty('class', 'bContainer-fieldset bContainer-accordion bContainer-accordion-hide');
                    $titleContainer->add("<i class='fas fa-plus bContainer-accordion-icon bContainer-accordion-icon-show'></i>");
                    $titleContainer->add("<i style='display:none' class='fas fa-minus bContainer-accordion-icon bContainer-accordion-icon-hide'></i>");
                }
            }

            $this->add($titleContainer);
        }
        else
        {
            $this->setProperty('style', 'border:none; box-shadow:none;');
        }
        
        parent::show();
    }

}
