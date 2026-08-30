<?php
namespace Adianti\Widget\Template;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Util\AdiantiTemplateHandler;

use Exception;
use ApplicationTranslator;

/**
 * HTML Renderer for processing and rendering HTML templates with dynamic sections and variable replacements.
 *
 * @version    7.5
 * @package    widget
 * @subpackage template
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class THtmlRenderer
{
    private $path;
    private $buffer;
    private $template;
    private $sections;
    private $replacements;
    private $enabledSections;
    private $repeatSection;
    private $enabledTranslation;
    private $HTMLOutputConversion;
    
    /**
     * Initializes the HTML Renderer with a given HTML template file.
     *
     * @param string $path The path to the HTML template file.
     * 
     * @throws Exception If the provided file does not exist.
     */
    public function __construct($path)
    {
        if (!file_exists($path))
        {
            throw new Exception(AdiantiCoreTranslator::translate('File not found').': ' . $path);
        }
        $this->enabledSections = array();
        $this->enabledTranslation = FALSE;
        $this->buffer = array();
        $this->HTMLOutputConversion = true;
        
        if (file_exists($path))
        {
            $this->template = file_get_contents($path);
        }
    }
    
    /**
     * Creates an instance of THtmlRenderer and enables the main section with replacements.
     *
     * @param string $path The path to the HTML template file.
     * @param array $replaces An associative array of replacement values.
     *
     * @return THtmlRenderer The created instance of THtmlRenderer.
     */
    public static function create($path, $replaces)
    {
        $html = new self($path);
        $html->enableSection('main', $replaces);
        return $html;
    }
    
    /**
     * Disables the automatic conversion of special characters to HTML entities.
     */
    public function disableHtmlConversion()
    {
        $this->HTMLOutputConversion = false;
    }
    
    /**
     * Enables translation for the template using the ApplicationTranslator.
     */
    public function enableTranslation()
    {
        $this->enabledTranslation = TRUE;
    }
    
    /**
     * Enables a section in the template for rendering.
     *
     * @param string $sectionName The name of the section to enable.
     * @param array|null $replacements An associative array of replacements for this section (optional).
     * @param bool $repeat Whether the section should be repeated (default: false).
     */
    public function enableSection($sectionName, $replacements = NULL, $repeat = FALSE)
    {
        $this->enabledSections[] = $sectionName;
        $this->replacements[$sectionName] = $replacements;
        $this->repeatSection[$sectionName] = $repeat;
    }
    
    /**
     * Disables a previously enabled section, preventing it from being rendered.
     *
     * @param string $sectionName The name of the section to disable.
     */
    public function disableSection($sectionName)
    {
        $this->enabledSections = array_diff($this->enabledSections, [$sectionName]);
        unset($this->replacements[$sectionName]);
        unset($this->repeatSection[$sectionName]);
    }
    
    /**
     * Replaces placeholders in the content with the provided replacement values.
     *
     * @param array|null &$replacements An associative array of replacement values.
     * @param string $content The content in which to perform the replacements.
     *
     * @return string The content with replacements applied.
     */
    private function replace(&$replacements, $content)
    {
        if (is_array($replacements))
        {
            foreach ($replacements as $variable => $value)
            {
                if (is_scalar($value))
                {
                    $value_original = $value;
                    
                    if (substr($value,0,4) == 'RAW:')
                    {
                        $value = substr($value,4);
                    }
                    else if ($this->HTMLOutputConversion)
                    {
                        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // TAG value
                    }
                    
                    $content = str_replace('{$'.$variable.'}',  $value, $content);
                    $content = str_replace('{{'.$variable.'}}', $value, $content);
                    $content = str_replace('{$'.$variable.'|raw}',  $value_original, $content);
                    $content = str_replace('{{'.$variable.'|raw}}', $value_original, $content);
                }
                else if (is_object($value))
                {
                    if (method_exists($value, 'show'))
                    {
                        ob_start();
                        $value->show();
                        $output = ob_get_contents();
                        ob_end_clean();
                        
                        $content = str_replace('{$'.$variable.'}',  $output, $content);
                        $content = str_replace('{{'.$variable.'}}', $output, $content);
                        
                        $replacements[$variable] = 'RAW:' . $output;
                    }
                    
                    if (method_exists($value, 'getAttributes'))
                    {
                        $vars = $value->getAttributes();
                        $vars[] = $value->getPrimaryKey();
                    }
                    else if (!$value instanceof self)
                    {
                        $vars = array_keys(get_object_vars($value));
                    }
                    
                    if (isset($vars))
                    {
                        foreach ($vars as $propname)
                        {
                            if (is_scalar($variable.'->'.$propname))
                            {
                                $replace = $value->$propname;
                                if (is_scalar($replace))
                                {
                                    if ($this->HTMLOutputConversion)
                                    {
                                        $replace = htmlspecialchars($replace, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // TAG value
                                    }
                                    
                                    $content = str_replace('{$'.$variable.'->'.$propname.'}',   $replace, $content);
                                    $content = str_replace('{{'.$variable.'->'.$propname.'}}',  $replace, $content);
                                }
                            }
                        }
                    }
                }
                else if (is_null($value))
                {
                    $content = str_replace('{$'.$variable.'}',  '', $content);
                    $content = str_replace('{{'.$variable.'}}', '', $content);
                }
                else if (is_array($value)) // embedded repeated section
                {
                    // there is a template for this variable
                    if (isset($this->buffer[$variable]))
                    {
                        $tpl = $this->buffer[$variable];
                        $agg = '';
                        foreach ($value as $replace)
                        {
                            $agg .= $this->replace($replace, $tpl);
                        }
                        $content = str_replace('{{'.$variable.'}}', $agg, $content);
                    }
                }
            }
        }
        
        // replace some php functions
        $content = AdiantiTemplateHandler::replaceFunctions($content);
        
        return $content;
    }
    
    /**
     * Processes and displays the template with enabled sections and applied replacements.
     *
     * @throws Exception If there are unclosed sections in the template.
     */
    public function show()
    {
        $opened_sections = array();
        $sections_stack = array('main');
        $array_content = array();
        
        if ($this->template)
        {
            $content = $this->template;
            if ($this->enabledTranslation)
            {
                $content  = ApplicationTranslator::translateTemplate($content);
            }
            
            $array_content = preg_split('/\n|\r\n?/', $content);
            $sectionName = null;
            
            // iterate line by line
            foreach ($array_content as $line)
            {
                $line_clear = trim($line);
                $line_clear = str_replace("\n", '', $line_clear);
                $line_clear = str_replace("\r", '', $line_clear);
                $delimiter  = FALSE;
                
                // detect section start
                if ( (substr($line_clear, 0,5)=='<!--[') AND (substr($line_clear, -4) == ']-->') AND (substr($line_clear, 0,6)!=='<!--[/') )
                {
                    $previousSection = $sectionName;
                    $sectionName = substr($line_clear, 5, strpos($line_clear, ']-->')-5);
                    $sections_stack[] = $sectionName;
                    $this->buffer[$sectionName] = '';
                    $opened_sections[$sectionName] = TRUE;
                    $delimiter  = TRUE;
                    
                    $found = self::recursiveKeyArraySearch($previousSection, $this->replacements);
                    
                    // turns section repeatable if it occurs inside parent section
                    if (isset($this->replacements[$previousSection][$sectionName]) OR
                        isset($this->replacements[$previousSection][0][$sectionName]) OR
                        isset($found[$sectionName]) OR
                        isset($found[0][$sectionName]) )
                    {
                        $this->repeatSection[$sectionName] = TRUE;
                    }
                    
                    // section inherits replacements from parent session
                    if (isset($this->replacements[$previousSection][$sectionName]) && is_array($this->replacements[$previousSection][$sectionName]))
                    {
                        $this->replacements[$sectionName] = $this->replacements[$previousSection][$sectionName];
                    }
                }
                // detect section end
                else if ( (substr($line_clear, 0,6)=='<!--[/') )
                {
                    $delimiter  = TRUE;
                    $sectionName = substr($line_clear, 6, strpos($line_clear, ']-->')-6);
                    $opened_sections[$sectionName] = FALSE;
                    
                    array_pop($sections_stack);
                    $previousSection = end($sections_stack);
                    
                    // embbed current section as a variable inside the parent section
                    if (isset($this->repeatSection[$previousSection]) AND $this->repeatSection[$previousSection])
                    {
                        $this->buffer[$previousSection] .= '{{'.$sectionName.'}}';
                    }
                    else
                    {
                        // if the section is repeatable and the parent is not (else), process replaces recursively
                        if ((isset($this->repeatSection[$sectionName]) AND $this->repeatSection[$sectionName]))
                        {
                            $processed = '';
                            // if the section is repeatable, repeat the content according to its replacements
                            if (isset($this->replacements[$sectionName]))
                            {
                                foreach ($this->replacements[$sectionName] as $iteration_replacement)
                                {
                                    $processed .= $this->replace($iteration_replacement, $this->buffer[$sectionName]);
                                }
                                AdiantiTemplateHandler::processAttribution($processed, $this->replacements);
                                print $processed;
                                $processed = '';
                            }
                        }
                    }
                    
                    $sectionName = end($sections_stack);
                }
                else if (in_array($sectionName, $this->enabledSections)) // if the section is enabled
                {
                    if (!$this->repeatSection[$sectionName]) // not repeatable, just echo
                    {
                        // print the line with the replacements
                        if (isset($this->replacements[$sectionName]))
                        {
                            print $this->replace($this->replacements[$sectionName], $line . "\n");
                        }
                        else
                        {
                            print $line . "\n";
                        }
                    }

                }
                
                if (!$delimiter)
                {
                    if (!isset($sectionName))
                    {
                        $sectionName = 'main';
                        if (empty($this->buffer[$sectionName]))
                        {
                            $this->buffer[$sectionName] = '';
                        }
                    }
                    
                    $this->buffer[$sectionName] .= $line . "\n";
                }
            }
        }
        
        // check for unclosed sections
        if ($opened_sections)
        {
            foreach ($opened_sections as $section => $opened)
            {
                if ($opened)
                {
                    throw new Exception(AdiantiCoreTranslator::translate('The section (^1) was not closed properly', $section));
                }
            }
        }
    }
    
    /**
     * Recursively searches for a key in a nested array.
     *
     * @param mixed $needle The key to search for.
     * @param array $haystack The array in which to search.
     *
     * @return mixed The value associated with the found key, or false if not found.
     */
    public static function recursiveKeyArraySearch($needle,$haystack)
    {
        if ($haystack)
        {
            foreach($haystack as $key=>$value)
            {
                if($needle === $key)
                {
                    return $value;
                }
                else if (is_array($value) && self::recursiveKeyArraySearch($needle,$value) !== false)
                {
                    return self::recursiveKeyArraySearch($needle,$value);
                }
            }
        }
        return false;
    }
    
    /**
     * Returns the rendered HTML content as a string.
     *
     * @return string The processed HTML content.
     */
    public function getContents()
    {
        ob_start();
        $this->show();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
