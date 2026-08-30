<?php
namespace Adianti\Log;

use Adianti\Log\TLogger;

/**
 * Register LOG in Standard Output
 *
 * Logger that outputs log messages to standard output.
 * Messages are printed directly to the console or web page.
 *
 * @version    7.5
 * @package    log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TLoggerSTD extends TLogger
{
    /**
     * Writes a log message to the standard output.
     * Messages are printed with a timestamp and log level.
     *
     * @param string $message The message to be displayed.
     */
    public function write($message)
    {
        $level = 'Debug';
        
        $time = date("Y-m-d H:i:s");
        $eol = PHP_SAPI == 'cli' ? "\n" : '<br>';
        
        // define the LOG content
        print "$level: $time - $message" . $eol;
    }
}
