<?php
namespace Adianti\Widget\Dialog;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TScript;

/**
 * Class for displaying message dialogs.
 * This class creates a message dialog with different types (info, warning, error).
 *
 * @version    7.5
 * @package    widget
 * @subpackage dialog
 * @author     Pablo Dall'Oglio
 * @author     Victor Feitoza <vfeitoza [at] gmail.com> (process action after OK)
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMessage
{
    /**
     * TMessage constructor.
     * Initializes a message dialog with a type, message, action, and optional title.
     *
     * @param string      $type       The type of the message (info, warning, error).
     * @param string      $message    The message content to be displayed.
     * @param TAction|null $action    The action to be executed when the dialog is closed (optional).
     * @param string      $title_msg  The title of the dialog (optional).
     */
    public function __construct($type, $message, ?TAction $action = null, $title_msg = '')
    {
        if (!empty($title_msg))
        {
            $title = $title_msg;
        }
        else
        {
            $titles = [];
            $titles['info']    = AdiantiCoreTranslator::translate('Information');
            $titles['error']   = AdiantiCoreTranslator::translate('Error');
            $titles['warning'] = AdiantiCoreTranslator::translate('Warning');
            $title = !empty($titles[$type])? $titles[$type] : '';
        }
        
        $callback = 'undefined';
        
        if ($action)
        {
            $callback = "function () { __adianti_load_page('{$action->serialize()}') }";
        }
        
        $title = addslashes((string) $title);
        $message = addslashes((string) $message);
        
        if ($type == 'info')
        {
            TScript::create("__adianti_message('{$title}', '{$message}', $callback)");
        }
        else if ($type == 'warning')
        {
            TScript::create("__adianti_warning('{$title}', '{$message}', $callback)");
        }
        else
        {
            TScript::create("__adianti_error('{$title}', '{$message}', $callback)");
        }
    }
}
