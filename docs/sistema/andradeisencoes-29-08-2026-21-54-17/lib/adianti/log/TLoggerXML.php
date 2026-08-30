<?php
namespace Adianti\Log;

use Adianti\Log\TLogger;

/**
 * Register LOG in HTML files
 *
 * Logger that registers log messages in an XML file.
 * Messages are stored in structured XML format.
 *
 * @version    7.5
 * @package    log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TLoggerXML extends TLogger
{
    /**
     * Writes a log message to the XML log file.
     * Messages are structured as XML nodes.
     *
     * @param string $message The message to be stored in XML format.
     */
    public function write($message)
    {
        $level = 'Debug';
        
        $time = date("Y-m-d H:i:s");
        // define the LOG content
        $text = "<log>\n";
        $text.= "   <level>$level</level>\n";
        $text.= "   <time>$time</time>\n";
        $text.= "   <message>$message</message>\n";
        $text.= "</log>\n";
        // add the message to the end of file
        $handler = fopen($this->filename, 'a');
        fwrite($handler, $text);
        fclose($handler);
    }
}
