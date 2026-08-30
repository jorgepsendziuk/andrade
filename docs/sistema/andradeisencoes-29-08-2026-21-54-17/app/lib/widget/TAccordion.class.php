<?php
/**
 * Class TAccordion
 *
 * A container widget that represents an accordion structure.
 * It allows adding collapsible content sections.
 *
 * Copyright (c) 2006-2010 Pablo Dall'Oglio
 * @author  Pablo Dall'Oglio <pablo [at] adianti.com.br>
 * @version 2.0, 2007-08-01
 */
class TAccordion extends TElement
{
    protected $elements;
    
    /**
     * TAccordion constructor.
     *
     * Initializes the accordion container and assigns a unique ID.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->id = 'taccordion_' . uniqid();
        $this->elements = array();
    }
    
    /**
     * Adds a new page to the accordion.
     *
     * @param string   $title  The title of the accordion section.
     * @param mixed    $object The content to be displayed inside the section.
     */
    public function appendPage($title, $object)
    {
        $this->elements[] = array($title, $object);
    }
    
    /**
     * Renders the accordion on the screen.
     *
     * It iterates over the added elements and generates the necessary HTML structure,
     * also importing required CSS and JavaScript files.
     */
    public function show()
    {
        foreach ($this->elements as $child)
        {
            $title = new TElement('span');
            $title->class = 'taccordion';
            $title->add($child[0]);
            
            $content = new TElement('div');
            $content->class = 'taccordion-content';
            $content->add($child[1]);
            
            parent::add($title);
            parent::add($content);
        }
        
        TStyle::importFromFile('app/lib/include/taccordion/taccordion.css');
        TScript::importFromFile('app/lib/include/taccordion/taccordion.js');
        
        parent::show();
    }
}
