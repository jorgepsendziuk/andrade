<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;

/**
 * TProgressBar
 *
* A progress bar widget that visually represents a percentage-based progress.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Ademilson Nunes
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TProgressBar extends TElement
{
    private $value;
    private $mask;
    private $className;
    
    /**
     * Constructor
     *
     * Initializes the progress bar with default values and styling.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->{'class'} = 'progress';
        $this->{'id'} = 'tprogressbar_'.mt_rand(1000000000, 1999999999);
        $this->{'style'} = 'margin-bottom:0; text-shadow: none;';
        $this->mask = '{value}%';
        $this->className = 'info';
    }
    
    /**
     * Sets the mask for the progress bar display.
     *
     * The mask defines how the progress value is shown (e.g., "{value}%").
     *
     * @param string $mask The mask format where `{value}` will be replaced with the actual progress value.
     */
    public function setMask($mask)
    {
        $span = new TElement("span");
        $span->add($mask);
        $this->mask = $span;
    }
    
    /**
     * Sets the CSS class for the progress bar.
     *
     * @param string $class The CSS class name that defines the visual style of the progress bar.
     */
    public function setClass($class)
    {
        $this->className = $class;
    }
    
    /**
     * Sets the progress bar value.
     *
     * @param int|float $value The progress value (percentage) ranging from 0 to 100.
     */
    public function setValue($value)
    {
       $this->value = $value;
    }
            
    /**
     * Renders and displays the progress bar on the screen.
     */
    public function show()
    {                   
        $progressBar = new TElement('div');
        $progressBar->{'class'} = "progress-bar progress-bar-{$this->className}";
        $progressBar->{'role'} = 'progressbar';
        $progressBar->{'arial-valuenow'} = $this->value;
        $progressBar->{'arial-valuemin'} = '0';
        $progressBar->{'arial-valuemax'} = '100';
        $progressBar->{'style'} = 'width: ' . $this->value . '%;';
         
        $value = str_replace('{value}', $this->value, $this->mask);
         
        $progressBar->add($value);
        parent::add($progressBar);
       
        parent::show();
    }
}
