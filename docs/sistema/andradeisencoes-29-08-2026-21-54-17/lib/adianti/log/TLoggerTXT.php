<?php
namespace Adianti\Log;

use Adianti\Log\TLogger;

/**
 * Register LOG in TXT files
 *
 * Logger that registers log messages in a plain text file.
 * Messages are appended to the file with timestamps.
 *
 * @version    7.5
 * @package    log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TLoggerTXT extends TLogger
{
    /**
     * Writes a log message to the text file.
     * Messages are appended to the file with a timestamp.
     *
     * @param string $message The message to be recorded.
     */
    public function write($message)
    {
        $level = 'Debug';
        
        $time = date("Y-m-d H:i:s");
        // define the LOG content
        $text = "$level: $time - $message\n";
        // add the message to the end of file
        $handler = fopen($this->filename, 'a');
        fwrite($handler, $text);
        fclose($handler);
    }
}
