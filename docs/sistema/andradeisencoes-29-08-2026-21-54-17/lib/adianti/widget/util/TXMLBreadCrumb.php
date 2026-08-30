<?php
namespace Adianti\Widget\Util;

use Adianti\Widget\Util\TBreadCrumb;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Menu\TMenuParser;
use SimpleXMLElement;
use Exception;

/**
 * Class TXMLBreadCrumb
 *
 * This class extends TBreadCrumb and constructs a breadcrumb navigation
 * based on an XML file that defines menu paths.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TXMLBreadCrumb extends TBreadCrumb
{
    private $parser;
    
    /**
     * TXMLBreadCrumb constructor.
     *
     * Initializes a breadcrumb navigation based on a provided XML file.
     *
     * @param string $xml_file Path to the XML file containing menu definitions.
     * @param string $controller The controller name to look up in the XML file.
     *
     * @throws Exception If the controller is not found in the XML file.
     */
    public function __construct($xml_file, $controller)
    {
        parent::__construct();
        
        $this->parser = new TMenuParser($xml_file);
        $paths = $this->parser->getPath($controller);
        if (!empty($paths))
        {
            parent::addHome();
            
            $count = 1;
            foreach ($paths as $path)
            {
                if (!empty($path))
                {
                    parent::addItem($path, $count == count($paths));
                    $count++;
                }
            }
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('Class ^1 not found in ^2', $controller, $xml_file));
        }
    }
    
    /**
     * Retrieves the breadcrumb path for a given controller.
     *
     * @param string $controller The controller name to look up in the XML file.
     *
     * @return array An array representing the breadcrumb path.
     */
    public function getPath($controller)
    {
        return $this->parser->getPath($controller);
    }
}
