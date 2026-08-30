<?php
namespace Adianti\Widget\Menu;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Util\AdiantiStringConversion;
use SimpleXMLElement;
use Exception;
use DomDocument;
use DomElement;

/**
 * Parses an XML menu file and provides access to menu paths and modules.
 *
 * This class reads an XML file containing menu definitions and extracts 
 * paths, labels, and actions, allowing indexed access to program paths 
 * and checking module existence.
 *
 * @version    7.5
 * @package    widget
 * @subpackage menu
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMenuParser
{
    private $paths;
    private $path;
    
    /**
     * Initializes the menu parser by loading and processing an XML menu file.
     *
     * @param string $xml_file Path to the XML menu file.
     *
     * @throws Exception If the file does not exist.
     */
    public function __construct($xml_file)
    {
        $this->path  = $xml_file;
        $this->paths = [];
        
        if (file_exists($xml_file))
        {
            $menu_string = AdiantiStringConversion::assureUnicode(file_get_contents($xml_file));
            $xml = new SimpleXMLElement($menu_string);
            
            foreach ($xml as $xmlElement)
            {
                $atts   = $xmlElement->attributes();
                $label  = (string) $atts['label'];
                $action = (string) $xmlElement-> action;
                $icon   = (string) $xmlElement-> icon;
                
                if ($action)
                {
                    $this->paths[$action] = [$label];
                }
                
                if (substr($label, 0, 3) == '_t{')
                {
                    $label = _t(substr($label,3,-1), 3, -1);
                }
                
                $this->parse($xmlElement-> menu-> menuitem, array($label));
            }
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('File not found') . ': ' . $xml_file);
        }
    }
    
    /**
     * Recursively parses a SimpleXMLElement structure, extracting menu entries.
     *
     * @param SimpleXMLElement $xml  The XML structure to parse.
     * @param array            $path The hierarchical path of the current menu item.
     */
    private function parse($xml, $path)
    {
        $i = 0;
        if ($xml)
        {
            foreach ($xml as $xmlElement)
            {
                $atts   = $xmlElement->attributes();
                $label  = (string) $atts['label'];
                $action = (string) $xmlElement-> action;
                
                if (substr($label, 0, 3) == '_t{')
                {
                    $label = _t(substr($label,3,-1), 3, -1);
                }
                
                if (strpos($action, '#') !== FALSE)
                {
                    list($action, $method) = explode('#', $action);
                }
                $icon   = (string) $xmlElement-> icon;
                
                if ($xmlElement->menu)
                {
                    $this->parse($xmlElement-> menu-> menuitem, array_merge($path, array($label)));
                }
                
                // just child nodes have actions
                if ($action)
                {
                    $this->paths[$action] = array_merge($path, array($label));
                }
            }
        }
    }
    
    /**
     * Retrieves an indexed array of programs with their last menu label.
     *
     * @return array Associative array where keys are program actions 
     *               and values are the corresponding menu labels.
     */
    public function getIndexedPrograms()
    {
        $programs = [];
        foreach ($this->paths as $action => $path)
        {
            $programs[$action] = array_pop($path);
        }
        return $programs;
    }
    
    /**
     * Retrieves the hierarchical path of a given controller.
     *
     * @param string $controller The controller name.
     *
     * @return array|null The hierarchical path of the controller, or null if not found.
     */
    public function getPath($controller)
    {
        return isset($this->paths[$controller]) ? $this->paths[$controller] : null;
    }
    
    /**
     * Checks whether a specific module exists in the menu XML.
     *
     * @param string $module The name of the module to check.
     *
     * @return bool True if the module exists, false otherwise.
     */
    public function moduleExists($module)
    {
        $xml_doc = new DomDocument;
        $xml_doc->load($this->path);
        $xml_doc->encoding = 'utf-8';
        
        foreach ($xml_doc->getElementsByTagName('menuitem') as $node)
        {
            $node_label = $node->getAttribute('label');
            foreach ($node->childNodes as $subnode)
            {
                if ($subnode instanceof DOMElement)
                {
                    if ($subnode->tagName == 'menu' and $node_label == $module)
                    {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Retrieves an array of all top-level modules defined in the menu XML.
     *
     * @return array Associative array where keys and values are module labels.
     */
    public function getModules()
    {
        $xml_doc = new DomDocument;
        $xml_doc->preserveWhiteSpace = false;
        $xml_doc->formatOutput = true;
        $xml_doc->load($this->path);
        $xml_doc->encoding = 'utf-8';
        
        $modules = [];
        
        foreach ($xml_doc->getElementsByTagName('menuitem') as $node)
        {
            $node_label = $node->getAttribute('label');
            foreach ($node->childNodes as $subnode)
            {
                if ($subnode instanceof DOMElement)
                {
                    if ($subnode->tagName == 'menu')
                    {
                        $modules[ $node_label ] = $node_label;
                    }
                }
            }
        }
        
        return $modules;
    }
}
