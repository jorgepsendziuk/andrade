<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;

/**
  * TreeView widget
 *
 * This class represents a hierarchical tree view that can be populated with nodes,
 * allowing customization of icons, actions, and transformations for both items and folders.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TTreeView extends TElement
{
    private $itemIcon;
    private $itemAction;
    private $collapsed;
    private $callback;
    private $folderCallback;
    
    /**
     * Class Constructor
     *
     * Initializes the tree view as an unordered list (`ul` element),
     * assigns a unique identifier, and sets default properties.
     */
    public function __construct()
    {
        parent::__construct('ul');
        $this->{'id'} = 'ttreeview_'.mt_rand(1000000000, 1999999999);
        $this->collapsed = FALSE;
        $this->resort = false;
    }
    
    /**
     * Sets a callback function to transform node elements.
     *
     * @param callable $callback A function to process and modify tree nodes.
     */
    public function setTransformer($callback)
    {
        $this->callback = $callback;
    }
    
    /**
     * Sets a callback function to transform folder elements.
     *
     * @param callable $callback A function to process and modify tree folders.
     */
    public function setFolderTransformer($callback)
    {
        $this->folderCallback = $callback;
    }
    
    /**
     * Sets the width of the tree view.
     *
     * @param int $width The width in pixels.
     */
    public function setSize($width)
    {
        $this->{'style'} = "width: {$width}px";
    }
    
    /**
     * Sets the width of the tree view.
     *
     * @param int $width The width in pixels.
     */
    public function setItemIcon($icon)
    {
        $this->itemIcon = $icon;
    }
    
    /**
     * Sets the action for tree items when clicked.
     *
     * @param TAction $action The action to be executed when an item is clicked.
     */
    public function setItemAction($action)
    {
        $this->itemAction = $action;
    } 
    
    /**
     * Collapses all tree nodes.
     */
    public function collapse()
    {
        $this->collapsed = TRUE;
    }
    
    /**
     * Expands the tree to a specific node.
     *
     * @param string $key The key of the node to expand to.
     */
    public function expandTo($key)
    {
        $objectId = $this->{'id'};
        $id = md5($key);
        $script = new TElement('script');
        $script->{'type'} = 'text/javascript';
        $script->add("setTimeout(function(){ \$('#{$objectId}_{$id}').parents('ul').show()  },1);");
        $script->show();
    }
    
    /**
     * Populates the tree view from a multi-dimensional array.
     *
     * @param array $array The multi-dimensional array containing tree data.
     */
    public function fromArray($array)
    {
        if (is_array($array))
        {
            foreach ($array as $key => $option)
            {
                if (is_scalar($option))
                {
                    $element = new TElement('li');
                    $span = new TElement('span');
                    $span->{'class'} = 'file';
                    $span->add($option);
                    if ($this->itemIcon)
                    {
                        $element->{'style'} = "background-image:url(app/images/{$this->itemIcon})";
                    }
                    
                    if ($this->itemAction)
                    {
                        $this->itemAction->setParameter('key', $key);
                        $this->itemAction->setParameter('value', $option);
                        $string_action = $this->itemAction->serialize(FALSE);
                        $element->{'onClick'} = "__adianti_ajax_exec('{$string_action}')";
                        $element->{'id'} = $this->{'id'} . '_' . md5($key);
                    }
                    $span->{'key'} = $key;
                    
                    if (is_callable($this->callback))
                    {
                        $span = call_user_func($this->callback, $span);
                    }

                    $element->add($span);
                    
                    parent::add($element);
                }
                else if (is_array($option))
                {
                    $element = new TElement('li');
                    $span = new TElement('span');
                    $span->{'class'} = 'folder';
                    $span->add($key);
                    $element->add($span);
                    $element->add($this->fromOptions($option, $key));
                    parent::add($element);
                }
            }
        }
    }
    
    /**
     * Fills a level of the tree view with given options.
     *
     * @param array       $options An array of options representing child nodes.
     * @param string|null $parent  The parent node key (optional).
     *
     * @return TElement The unordered list (`ul`) element containing the generated tree structure.
     */
    private function fromOptions($options, $parent = null)
    {
        if (is_array($options))
        {
            $ul = new TElement('ul');
            
            $files = [];
            $folders = [];
            
            foreach ($options as $key => $option)
            {
                if (is_scalar($option))
                {
                    $element = new TElement('li');
                    $span = new TElement('span');
                    $span->{'class'} = 'file';
                    $span->add($option);
                    if ($this->itemIcon)
                    {
                        $element->{'style'} = "background-image:url(app/images/{$this->itemIcon})";
                    }
                    
                    if ($this->itemAction)
                    {
                        $this->itemAction->setParameter('key', $key);
                        $this->itemAction->setParameter('value', $option);
                        $string_action = $this->itemAction->serialize(FALSE);
                        $element->{'onClick'} = "__adianti_ajax_exec('{$string_action}')";
                        $element->{'id'} = $this->{'id'} . '_' . md5($key);
                    }
                    $span->{'key'} = $key;
                    
                    if (is_callable($this->callback))
                    {
                        $span = call_user_func($this->callback, $span);
                    }

                    $element->add($span);
                    
                    $files[ implode('',$span->getChildren()) . ' ' . $key ] = $element;
                }
                else if (is_array($option))
                {
                    //echo "$parent - $key<br>";
                   
                    $element = new TElement('li');
                    $span = new TElement('span');
                    $span->{'class'} = 'folder';
                    $span->add($key);
                    
                    $span->{'key'} = $key;
                    $span->{'parent'} = $parent;
                    
                    if (is_callable($this->folderCallback))
                    {
                        $span = call_user_func($this->folderCallback, $span);
                    }
                    
                    $element->add($span);
                    $element->add($this->fromOptions($option, $key));
                    
                    $folders[ implode('',$span->getChildren()) . ' ' . $key ] = $element;
                }
                else if (is_object($option))
                {
                    $element = new TElement('li');
                    $element->add($option);
                }
            }
            
            array_multisort(array_keys($folders), SORT_NATURAL | SORT_FLAG_CASE, $folders);
            array_multisort(array_keys($files), SORT_NATURAL | SORT_FLAG_CASE, $files);
            
            if ($folders)
            {
                foreach ($folders as $element)
                {
                    $ul->add($element);
                }
            }
            
            if ($files)
            {
                foreach ($files as $element)
                {
                    $ul->add($element);
                }
            }
            
            return $ul;
        }
    }
    
    /**
     * Displays the tree view.
     *
     * This method initializes the tree view script and renders the tree.
     */
    public function show()
    {
        $objectId = $this->{'id'};
        $collapsed = $this->collapsed ? 'true' : 'false';
        
        parent::add(TScript::create(" ttreeview_start( '#{$objectId}', {$collapsed} ); ", FALSE));
        parent::show();
    }
}
