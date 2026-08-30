<?php
namespace Adianti\Control;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;

use Exception;
use ReflectionClass;

/**
 * Page Controller Pattern: used as container for all elements inside a page and also as a page controller
 *
 * This class represents a page structure and handles functionalities such as setting page titles, 
 * including JavaScript and CSS files, managing target containers, and detecting mobile devices.
 *
 * @version    7.5
 * @package    control
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
#[\AllowDynamicProperties]
class TPage extends TElement
{
    private $body;
    private $constructed;
    private static $loadedjs;
    private static $loadedcss;
    private static $registeredcss;
    protected $adianti_target_container;
    
    /**
     * Class Constructor
     *
     * Initializes a new page container as a <div> element, sets its properties, 
     * and marks the object as constructed.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->constructed = TRUE;
        
        $this->{'page-name'} = $this->getClassName();
        $this->{'page_name'} = $this->getClassName();
    }
    
    /**
     * Set the page name
     *
     * @param string $name The name to assign to the page
     */
    public function setPageName($name)
    {
        $this->setProperty('page-name', $name);
        $this->setProperty('page_name', $name);
    }
    
    /**
     * Get the class name of the current page instance
     *
     * @return string The short name of the class
     */
    public function getClassName()
    {
        $rc = new ReflectionClass( $this );
        return $rc-> getShortName ();
    }
    
    /**
     * Change the page title
     *
     * @param string $title The title to be set on the page
     */
	public static function setPageTitle($title)
    {
    	TScript::create("document.title='{$title}';");
    }
    
    /**
     * Set the target container for page content
     *
     * @param string|null $container The target container identifier. If null, removes the container setting.
     */
    public function setTargetContainer($container)
    {
        if ($container)
        {
            $this->adianti_target_container = $container;
            $this->setProperty('adianti_target_container', $container);
            $this->{'class'} = 'container-part';
        }
        else
        {
            $this->adianti_target_container = null;
            unset($this->{'adianti_target_container'});
            unset($this->{'class'});
        }
    }
    
    /**
     * Get the target container for the page content
     *
     * @return string|null The target container identifier, or null if not set
     */
    public function getTargetContainer()
    {
        return $this->adianti_target_container;
    }
    
    /**
     * Interpret an action based on URL parameters
     *
     * Executes a class method dynamically based on the 'class' and 'method' parameters in the URL.
     * If no class is specified, it checks for a globally defined function.
     */
    public function run()
    {
        if ($_GET)
        {
            $class  = isset($_GET['class'])  ? $_GET['class']  : NULL;
            $method = isset($_GET['method']) ? $_GET['method'] : NULL;
            
            if ($class)
            {
                $object = ($class == get_class($this)) ? $this : new $class;
                if (is_callable(array($object, $method) ) )
                {
                    call_user_func(array($object, $method), $_REQUEST);
                }
            }
            else if (function_exists($method))
            {
                call_user_func($method, $_REQUEST);
            }
        }
    }
    
    /**
     * Include a specific JavaScript file in the page
     *
     * @param string $js The JavaScript file location
     */
    public static function include_js($js)
    {
        self::$loadedjs[$js] = TRUE;
    }
    
    /**
     * Include a specific Cascading Stylesheet (CSS) file in the page
     *
     * @param string $css The CSS file location
     */
    public static function include_css($css)
    {
        self::$loadedcss[$css] = TRUE;
    }
    
    /**
     * Register a custom Cascading Stylesheet (CSS) definition
     *
     * @param string $cssname The name of the CSS rule
     * @param string $csscode The actual CSS code
     */
    public static function register_css($cssname, $csscode)
    {
        self::$registeredcss[$cssname] = $csscode;
    }
    
    /**
     * Open a file in the browser for download
     *
     * @param string      $file     The file path to be opened
     * @param string|null $basename The optional base name for the downloaded file
     */
    public static function openFile($file, $basename = null)
    {
        TScript::create("__adianti_download_file('{$file}', '{$basename}')");
    }
    
    /**
     * Open a page in a new browser tab
     *
     * @param string $page The URL or page identifier to open
     */
    public static function openPage($page)
    {
        TScript::create("__adianti_open_page('{$page}');");
    }
    
    /**
     * Retrieve the loaded CSS files and registered styles
     *
     * @return string The HTML representation of the loaded CSS files and styles
     */
    public static function getLoadedCSS()
    {
        $css = self::$loadedcss;
        $csc = self::$registeredcss;
        $css_text = '';
        
        if ($css)
        {
            foreach ($css as $cssfile => $bool)
            {
                $css_text .= "    <link rel='stylesheet' type='text/css' media='screen' href='$cssfile'/>\n";
            }
        }
        
        if ($csc)
        {
            $css_text .= "    <style type='text/css' media='screen'>\n";
            foreach ($csc as $cssname => $csscode)
            {
                $css_text .= $csscode;
            }
            $css_text .= "    </style>\n";
        }
        
        return $css_text;
    }
    
    /**
     * Retrieve the loaded JavaScript files
     *
     * @return string The HTML representation of the loaded JavaScript files
     */
    public static function getLoadedJS()
    {
        $js = self::$loadedjs;
        $js_text = '';
        if ($js)
        {
            foreach ($js as $jsfile => $bool)
            {
                $js_text .= "    <script language='JavaScript' src='$jsfile'></script>\n";;
            }
        }
        return $js_text;
    }
    
    /**
     * Detect whether the browser is a mobile device
     *
     * @return bool True if the browser is identified as a mobile device, false otherwise
     */
    public static function isMobile()
    {
        $isMobile = FALSE;
        
        if (PHP_SAPI == 'cli')
        {
            return FALSE;
        }
        
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE']))
        {
            $isMobile = TRUE;
        }
        
        $mobiBrowsers = array('android',   'audiovox', 'blackberry', 'epoc',
                              'ericsson', ' iemobile', 'ipaq',       'iphone', 'ipad', 
                              'ipod',      'j2me',     'midp',       'mmp',
                              'mobile',    'motorola', 'nitro',      'nokia',
                              'opera mini','palm',     'palmsource', 'panasonic',
                              'phone',     'pocketpc', 'samsung',    'sanyo',
                              'series60',  'sharp',    'siemens',    'smartphone',
                              'sony',      'symbian',  'toshiba',    'treo',
                              'up.browser','up.link',  'wap',        'wap',
                              'windows ce','htc');
                              
        foreach ($mobiBrowsers as $mb)
        {
            if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),$mb) !== FALSE)
            {
             	$isMobile = TRUE;
            }
        }
        
        return $isMobile;
    }
    
    /**
     * Intercepts property assignments and sets their values
     *
     * @param string $name  The property name
     * @param mixed  $value The property value
     */
    public function __set($name, $value)
    {
        parent::__set($name, $value);
        $this->$name = $value;
    }
    
    /**
     * Determines the appropriate action to take and displays the page
     *
     * Runs the page's main method if it's not nested within another container.
     * Throws an exception if the constructor was not called properly.
     *
     * @throws Exception If the constructor was not called
     */
    public function show()
    {
        if($this->adianti_target_container)
        {
            parent::setProperty('adianti_target_container', $this->adianti_target_container);
        }
        
        // just execute run() from toplevel TPage's, not nested ones
        if (!$this->getIsWrapped())
        {
            $this->run();
        }
        parent::show();
        
        if (!$this->constructed)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 constructor', __CLASS__ ) );
        }
    }
}
