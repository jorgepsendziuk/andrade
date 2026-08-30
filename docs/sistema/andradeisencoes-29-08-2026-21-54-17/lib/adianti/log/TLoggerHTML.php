<?php
namespace Adianti\Log;

use Adianti\Log\TLogger;

/**
 * Register LOG in HTML files
 *
 * Logger that registers log messages in an HTML file.
 * Messages are formatted as HTML paragraphs and appended to the file.
 *
 * @version    7.5
 * @package    log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TLoggerHTML extends TLogger
{
    /**
     * Writes a log message to the HTML log file.
     * Each log entry is stored as a formatted HTML paragraph.
     *
     * @param string $message The message to be written in the log.
     */
    public function write($message)
    {
        $level = 'Debug';
        
        $time = date("Y-m-d H:i:s");
        // define the LOG content
        $text = "<p>\n";
        $text.= "   <b>$level</b>: \n";
        $text.= "   <b>$time</b> - \n";
        $text.= "   <i>$message</i> <br>\n";
        $text.= "</p>\n";
        // add the message to the end of file
        $handler = fopen($this->filename, 'a');
        fwrite($handler, $text);
        fclose($handler);
    }
}
