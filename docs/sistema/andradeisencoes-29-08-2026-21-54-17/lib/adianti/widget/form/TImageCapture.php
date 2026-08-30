<?php
namespace Adianti\Widget\Form;

use Adianti\Widget\Util\TImage;

/**
 * TImageCapture Class
 *
 * A form widget that allows capturing images using the webcam.
 * It extends TImageCropper and provides webcam capture functionality.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Lucas Tomasi
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TImageCapture extends TImageCropper
{
    /**
     * Class constructor
     *
     * Initializes the TImageCapture component, enabling webcam support 
     * and setting a default camera icon as a placeholder.
     *
     * @param string $name The name of the form field
     */
    public function __construct($name)
    {
        parent::__construct($name);
        // $this->enableFileHandling(TRUE);
        $this->enableWebCam(TRUE);
        $this->setImagePlaceholder(new TImage('fa:camera'));
    }
}
