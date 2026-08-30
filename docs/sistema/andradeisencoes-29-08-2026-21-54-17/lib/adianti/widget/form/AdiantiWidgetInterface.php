<?php
namespace Adianti\Widget\Form;

/**
 * Widget Interface
 *
 * Interface for widget components.
 * Defines a contract for setting and retrieving values, names, and rendering.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
interface AdiantiWidgetInterface
{
    public function setName($name);
    public function getName();
    public function setValue($value);
    public function getValue();
    public function show();
}
