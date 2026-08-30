<?php
namespace Adianti\Log;

/**
 * Abstract class for logging messages into various formats.
 * Provides an interface to register log messages in different storage formats.
 *
 * @version    7.5
 * @package    log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
abstract class TLogger implements AdiantiLoggerInterface
{
    protected $filename; // path for LOG file
    
    /**
     * Constructor method.
     * Initializes the logger and optionally clears the log file.
     *
     * @param string|null $filename Path to the log file. If provided, the file contents will be cleared upon initialization.
     */
    public function __construct($filename = NULL)
    {
        if ($filename)
        {
            $this->filename = $filename;
            // clear the file contents
            file_put_contents($filename, '');
        }
    }
    
    /**
     * Abstract method to write a log message.
     * This method must be implemented in child classes to define the specific log format.
     *
     * @param string $message The log message to be recorded.
     */
    abstract function write($message);
}
