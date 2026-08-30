<?php


/**
 * Class TStep
 *
 * A step indicator widget for multi-step processes.
 * It visually represents the progress of a process with steps.
 *
 * @version    3.0
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @author     Nataniel Rabaioli
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TStep extends TElement
{
    protected $container;
    protected $items;
    private   $stepNumber = 1;
    /**
     * TStep constructor.
     *
     * Initializes the step container and creates an unordered list for steps.
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
     * Adds a step item to the step indicator.
     *
     * @param string  $title    The title of the step.
     * @param bool    $active   Whether the step is currently active.
     * @param bool    $complete Whether the step is marked as completed.
     */
    public function addItem($title, $active = false, $complete = false)
    {
        $li = new TElement('li');

        if($complete)
        {
            $li->class = 'complete';   
        }
        elseif($active)
        {
            $li->class = 'active';
        }

        $this->container->add( $li );
        
        $spanTitle = new TElement('span');
        $spanTitle->class = 'step-title';
        $spanTitle->add( $title );

        $spanStep = new TElement('span');
        $spanStep->class = 'step-number';
        $spanStep->add( $this->stepNumber );
    
        
        $li->add( $spanStep );
        $li->add( $spanTitle );
        
        $this->stepNumber++;
    }
}
