<?php
namespace Adianti\Widget\Dialog;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Util\AdiantiStringConversion;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;

use Exception;

/**
 * Class for displaying toast messages.
 * This class provides a method to display toast notifications on the screen.
 *
 * @version    7.5
 * @package    widget
 * @subpackage dialog
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TToast
{
    /**
     * Displays a toast notification.
     *
     * @param string      $type   The type of the toast (show, info, success, warning, error).
     * @param string      $message The message to be displayed in the toast.
     * @param string      $place   The position where the toast will appear (default: 'bottom center').
     * @param string|null $icon    The icon to be displayed with the toast (optional).
     *
     * @throws Exception If an invalid toast type is provided.
     */
    public static function show($type, $message, $place = 'bottom center', $icon = null)
    {
        if (in_array($type, ['show', 'info', 'success', 'warning', 'error']))
        {
            
            $message64 = base64_encode($message);
            TScript::create("__adianti_show_toast64('{$type}', '{$message64}', '{$place}', '{$icon}')");
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $type, __METHOD__));
        }
    }
}
