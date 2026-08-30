<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;

/**
 * Page Step
 *
 * Represents a step indicator for multi-step forms or processes.
 * It visually highlights the current step and allows navigation between steps.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Matheus Agnes Dias
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TPageStep extends TElement
{
    protected $container;
    protected $items;
    protected $stepNumber = 1;
    
    /**
     * Class Constructor.
     * Initializes the step container as an unordered list and sets up the structure for step navigation.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->{'id'} = 'div_steps';
        
        $this->container = new TElement('ul');
        $this->container->{'class'} = 'steps';
        
        parent::add( $this->container );
    }
    
    /**
     * Adds a new step item to the step indicator.
     *
     * @param string       $title  The title of the step.
     * @param TAction|null $action An optional action to be executed when clicking the step.
     */
    public function addItem($title, $action = null)
    {
        $li = new TElement('li');
        $this->items[ $title ] = $li;
        $this->container->add( $li );
        
        if ($action)
        {
            $span_title = new TElement('a');
            $span_title->{'href'}      = $action->serialize(true);
            $span_title->{'generator'} = 'adianti';
        }
        else
        {
            $span_title = new TElement('span');
        }
        
        $span_title->{'class'} = 'step-title';
        $span_title->add( $title );
        
        $span_step = new TElement('span');
        $span_step->{'class'} = 'step-number';
        $span_step->add( $this->stepNumber );
        
        $li->add( $span_step );
        $li->add( $span_title );
        
        $this->stepNumber ++;
    }
    
    /**
     * Marks a step as the current active step.
     *
     * @param string $title The title of the step to be marked as active.
     */
    public function select($title)
    {
        $class = 'complete';
        
        if ($this->items)
        {
            foreach ($this->items as $key => $item)
            {
                $item->{'class'} = $class;
                
                if ($key == $title)
                {
                    $item->{'class'} = 'active';
                    $class = '';
                }
            }
        }
    }
}
