<?php
namespace Adianti\Widget\Base;

use Adianti\Widget\Base\TElement;

/**
 * Handles the creation and import of JavaScript scripts dynamically.
 *
 * This class provides methods to create inline JavaScript code and to import
 * external JavaScript files dynamically with optional execution delays.
 *
 * @version    7.5
 * @package    widget
 * @subpackage base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TScript
{
    /**
     * Creates a JavaScript script element and optionally outputs it.
     *
     * This method generates a JavaScript script element containing the provided source code.
     * It allows the script to be executed with an optional delay using `setTimeout`.
     *
     * @param string  $code    The JavaScript code to be executed.
     * @param bool    $show    Whether to immediately output the script (default: TRUE).
     * @param int|null $timeout The delay in milliseconds before executing the script (optional).
     *
     * @return TElement The generated script element.
     */
    public static function create( $code, $show = TRUE, $timeout = null )
    {
        if ($timeout)
        {
            $code = "setTimeout( function() { $code }, $timeout )";
        }
        
        $script = new TElement('script');
        $script->{'type'} = 'text/javascript';
        $script->setUseSingleQuotes(TRUE);
        $script->setUseLineBreaks(FALSE);
        $script->add( str_replace( ["\n", "\r"], [' ', ' '], $code) );
        if ($show)
        {
            $script->show();
        }
        return $script;
    }
    
    /**
     * Dynamically imports an external JavaScript file.
     *
     * This method loads an external JavaScript file using jQuery's `$.getScript` function.
     * An optional delay can be specified before executing the script.
     *
     * @param string   $script  The URL of the JavaScript file to be imported.
     * @param bool     $show    Whether to immediately output the script (default: TRUE).
     * @param int|null $timeout The delay in milliseconds before loading the script (optional).
     *
     * @return void
     */
    public static function importFromFile( $script, $show = TRUE, $timeout = null )
    {
        TScript::create('$.getScript("'.$script.'");', $show, $timeout);
    }
}
