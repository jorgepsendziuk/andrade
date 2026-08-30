<?php

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Util\TImage;

/**
 * Class BSignatureDrawCapture
 *
 * A widget that provides a signature drawing pad functionality.
 * It extends TField and implements AdiantiWidgetInterface, allowing 
 * users to draw signatures and handle file storage options.
 *
 * @version    7.3
 * @package    widget
 * @subpackage form
 * @author     Lucas Tomasi
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class BSignatureDrawCapture extends TField implements AdiantiWidgetInterface
{
    protected $id;
    protected $penColor;
    protected $size;
    protected $height;
    protected $heightPreview;
    protected $icon;
    protected $uploaderClass;
    protected $fileHandling;
    protected $changeAction;
    protected $drawWidth;
    protected $drawHeight;
    
    /**
     * BSignatureDrawCapture constructor.
     *
     * Initializes the signature drawing pad with default settings.
     *
     * @param string $name The widget's name.
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = 'bsignaturedrawcapture_' . mt_rand(1000000000, 1999999999);
        
        $this->penColor = '#000000';
        $this->icon = new TImage('fa:file-signature grey');
        $this->fileHandling = false;
        $this->tag = new TElement('canvas');
        $this->tag->{'widget'} = 'bsignaturedrawcapture';
        $this->uploaderClass = 'AdiantiUploaderService';

        $this->drawHeight = '50%';
        $this->drawWidth = '50%';
    }

    /**
     * Retrieves the pen color used in the signature pad.
     *
     * @return string The pen color in hexadecimal format.
     */
    public function getPenColor()
    {
        return $this->penColor;
    }

    /**
     * Sets the pen color for the signature pad.
     *
     * @param string $color The pen color in hexadecimal format.
     */
    public function setPenColor($color)
    {
        $this->penColor = $color;
    }
    
    /**
     * Retrieves the posted data for this widget.
     *
     * @return string The signature data or an empty string if no data is posted.
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            return $_POST[$name];
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Defines the widget's dimensions.
     *
     * @param string|int $width  The width of the widget (e.g., '100%', '300px').
     * @param string|int|null $height The height of the widget (optional).
     */
    public function setSize($width, $height = NULL)
    {
        $width = (strstr($width, '%') !== FALSE) ? $width : "{$width}px";
        $height = (strstr($height, '%') !== FALSE) ? $height : "{$height}px";

        $this->size = $width;
        $this->height = $height;

        if (empty($this->heightPreview))
        {
            $this->heightPreview = $height;
        }
    }

    /**
     * Defines the preview height of the signature pad.
     *
     * @param string|int $height The height value (e.g., '100%', '300px').
     */
    public function setheightPreview($height)
    {
        $this->heightPreview = $height;
    }

    /**
     * Defines the drawing area size within the signature pad.
     *
     * @param string|int $width  The width of the drawing area.
     * @param string|int|null $height The height of the drawing area (optional).
     */
    public function setDrawSize($width, $height = NULL)
    {
        $width = (!is_numeric($width)) ? $width : "{$width}px";
        $height = (!is_numeric($height)) ? $height : "{$height}px";

        $this->drawWidth = $width;
        $this->drawHeight = $height;
    }
    

    /**
     * Retrieves the widget's dimensions.
     *
     * @return array An array containing the width and height of the widget.
     */
    public function getSize()
    {
        return [str_replace('px', '', $this->size), str_replace('px', '', $this->height)];
    }

    /**
     * Enables file handling, allowing the signature to be processed as a file.
     */
    public function enableFileHandling()
    {
        $this->fileHandling = true;
    }

    /**
     * Disables file handling, treating the signature as raw data.
     */
    public function disableFileHandling()
    {
        $this->fileHandling = false;
    }

    /**
     * Sets an image as a placeholder for the signature pad.
     *
     * @param TImage $icon The image to be used as a placeholder.
     */
    public function setImagePlaceholder(TImage $icon)
    {
        $this->icon = $icon;
    }

    /**
     * Defines the action to be executed when the signature changes.
     *
     * @param TAction $action The action to be executed.
     */
    public function setChangeAction(TAction $action)
    {
        $this->changeAction = $action;
    }

    /**
     * Defines the service class responsible for handling the uploaded signature.
     *
     * @param string $service The service class name.
     */
    public function setService($service)
    {
        $this->uploaderClass = $service;
    }
    
    /**
     * Sets the value of the signature pad.
     *
     * If file handling is enabled, the value is encoded as a JSON object containing
     * file metadata.
     *
     * @param mixed $value The value to be set (raw signature data or file reference).
     */
    public function setValue($value)
    {
        if (is_scalar($value))
        {
            if ($this->fileHandling)
            {
                if (substr( (string) $value, 0, 3) !== '%7B')
                {
                    if (!empty($value))
                    {
                        $this->value = urlencode(json_encode(['fileName'=>$value]));
                    }
                    else
                    {
                        $this->value = $value;
                    }
                }
                else
                {
                    $value_object = json_decode(urldecode($value));
                    
                    if (!empty($value_object->{'delFile'}) AND $value_object->{'delFile'} == $value_object->{'fileName'})
                    {
                        $value = '';
                    }
                    
                    parent::setValue($value);
                }
            }
            else
            {
                parent::setValue($value);
            }
        }
        else if (is_null($value))
        {
            parent::setValue($value);
        }
    }
    
    /**
     * Displays the signature pad widget.
     *
     * This method renders the canvas for signature drawing, 
     * along with buttons for clearing, adding, and closing the pad.
     * It also integrates with file handling if enabled.
     *
     * @throws Exception If the widget is not properly associated with a form.
     */
    public function show()
    {
        $this->tag->{'name'}  = "canvas_{$this->name}";   // tag name
        $this->tag->{'id'}  = "canvas_{$this->id}";   // tag name
        $this->tag->{'class'}  = 'form-control tfield';   // tag name
        $this->tag->{'style'} = "width: {$this->drawWidth};height: {$this->drawHeight}";
        
        $hidden = new TElement('input');
        $hidden->style = 'display: none';
        $hidden->{'widget'}  = 'bsignaturedrawcapture';
        $hidden->name = $this->name;
        $hidden->id = $this->id;
        $hidden->value = $this->value;

        if (isset($this->changeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->changeAction->serialize(FALSE);
            $hidden->setProperty('changeaction', "__adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback')");
            $hidden->setProperty('onblur', $hidden->getProperty('changeaction'));
        }

        $buttonClear = new TElement('button');
        $buttonClear->{'type'} = 'button';
        $buttonClear->{'class'} = 'btn btn-default';
        $buttonClear->{'id'} = "clear_{$this->id}";
        $buttonClear->add(new TImage('fa:eraser red'));
        $buttonClear->add(AdiantiCoreTranslator::translate('Clear'));

        $buttonAdd = new TElement('button');
        $buttonAdd->{'type'} = 'button';
        $buttonAdd->{'class'} = 'btn btn-default';
        $buttonAdd->{'id'} = "add_{$this->id}";
        $buttonAdd->add(new TImage('fa:check green'));
        $buttonAdd->add(AdiantiCoreTranslator::translate('Add'));

        $buttonClose = new TElement('button');
        $buttonClose->{'type'} = 'button';
        $buttonClose->{'class'} = 'btn btn-default';
        $buttonClose->{'id'} = "close_{$this->id}";
        $buttonClose->add(new TImage('fa:times grey'));
        $buttonClose->add(AdiantiCoreTranslator::translate('Close'));

        $actions = new TElement('div');
        $actions->{'class'} = 'bsignaturedrawcapture_actions';
        $actions->{'style'} = "bottom: calc( (50% - ({$this->drawHeight}/2)) + 5px ); left: calc( (50% - ({$this->drawWidth}/2)) + 5px );";

        $actions->add($buttonAdd);
        $actions->add($buttonClear);
        $actions->add($buttonClose);

        $image = new TElement('div');
        $image->id = "image_{$this->id}";
        $image->{'class'} = 'bsignaturedrawcapture_image form-control tfield';
        $image->{'style'} = "width: {$this->size};height: {$this->height};";

        if ($this->fileHandling && $this->value)
        {
            $dados_file = json_decode(urldecode($this->value));
            
            if (!empty($dados_file->fileName))
            {
                $src = 'download.php?file=' . $dados_file->fileName . '&v=' . uniqid();
                $image->{'style'} .= "background-image: url('{$src}')";
            }
        }
        else if ($this->value)
        {
            $image->{'style'} .= "background-image: url('{$this->value}')";
        }

        $this->icon->{'style'} .= "font-size: calc( {$this->heightPreview} / 3);";
        
        $span = new TElement('span');
        $span->add($this->icon);

        $container = new TElement('div');
        $container->{'id'}    = "container_{$this->id}";
        $container->{'class'} = "bsignaturedrawcapture";
        $container->{'style'} = 'display: none';
        $container->add(TElement::tag('div', [$this->tag, $actions]));
        
        $signaturePage = new TElement('div');
        $signaturePage->{'class'} = 'bsignaturedrawcapture_container';
        $signaturePage->add($container);
        $signaturePage->add($hidden);
        $signaturePage->add($image);
        $signaturePage->add($span);
        $signaturePage->show();

        $fileHandling = $this->fileHandling ? 'true' : 'false';
        $action = "engine.php?class={$this->uploaderClass}";

        TScript::create("bsignaturedrawcapture_start('{$this->id}', '{$this->value}', '{$this->penColor}', '{$action}', {$fileHandling})");
    }
}