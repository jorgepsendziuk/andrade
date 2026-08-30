<?php
namespace Adianti\Widget\Form;

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Util\TImage;

/**
 * Image uploader with cropping capabilities
 *
 * This class provides an image upload field with cropping functionalities, 
 * supporting file handling, base64 encoding, and webcam capture.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Lucas Tomasi
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TImageCropper extends TField implements AdiantiWidgetInterface
{
    protected $height;
    protected $width;
    protected $value;
    private $extensions;
    private $fileHandling;
    private $base64;
    private $webcam;
    private $uploaderClass;
    private $seed;
    private $title;
    private $buttonText;
    private $cropWidth;
    private $cropHeight;
    private $aspectRatio;
    private $buttonRotate;
    private $buttonDrag;
    private $buttonScale;
    private $buttonReset;
    private $buttonZoom;

    private $imagePlaceholder;
    
    // defaults aspect ratios
    const CROPPER_RATIO_16_9 = 16/9;
    const CROPPER_RATIO_4_3 = 4/3;
    const CROPPER_RATIO_1_1 = 1/1;
    const CROPPER_RATIO_2_3 = 2/3;
    
    /**
     * Class constructor
     *
     * Initializes the image cropper field, setting default values and configurations.
     *
     * @param string $name Field name
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id   = 'timagecropper_' . mt_rand(1000000000, 1999999999);
        $this->tag->{'type'}   = 'hidden';
        $this->tag->{'widget'} = 'timagecropper';
        $this->tag->{'name'} = $name;

        $this->buttonText = 'Ajustar';
        $this->title = 'Ajustar imagem';

        $this->uploaderClass = 'AdiantiUploaderService';
        $ini = AdiantiApplicationConfig::get();
        $this->seed = APPLICATION_NAME . ( !empty($ini['general']['seed']) ? $ini['general']['seed'] : 's8dkld83kf73kf094' );
        
        $this->extensions = ['gif', 'png', 'jpg', 'jpeg'];
        
        $this->cropWidth = null;
        $this->cropHeight = null;
        $this->buttonDrag = true;
        $this->buttonZoom = true;
        $this->aspectRatio = null;
        $this->buttonScale = true;
        $this->buttonReset = true;
        $this->buttonRotate = true;
        $this->fileHandling = false;
        $this->base64 = false;
        $this->webcam = false;
        $this->setSize('100%', 100);

        $this->imagePlaceholder = new TImage('fa:image placeholder');
    }

    /**
     * Set the image placeholder
     *
     * Defines a placeholder image to be displayed when no image is selected.
     *
     * @param TImage $image The placeholder image
     */
    public function setImagePlaceholder(TImage $image)
    {
        $image->{'class'} .= ' placeholder';

        $this->imagePlaceholder = $image;
    }

    /**
     * Set the window title
     *
     * Defines the title of the cropping modal window.
     *
     * @param string $title The window title
     */
    public function setWindowTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Set the crop button label
     *
     * Defines the text displayed on the confirmation button in the cropping modal.
     *
     * @param string $text The button label
     */
    public function setButtonLabel($text)
    {
        $this->buttonText = $text;
    }

    /**
     * Define the aspect ratio for cropping
     *
     * Specifies the aspect ratio to be maintained during cropping.
     *
     * @param float $aspectRatio The desired aspect ratio (e.g., 16/9, 4/3)
    */
    public function setAspectRatio($aspectRatio)
    {
        $this->aspectRatio = $aspectRatio;
    }

    /**
     * Enable base64 encoding
     *
     * Configures the cropper to return the image in base64 format instead of a file.
     */
    public function enableBase64()
    {
        $this->base64 = true;
    }
    
    /**
     * Enable webcam support
     *
     * Allows users to capture an image using their webcam before cropping.
     */
    public function enableWebCam()
    {
        $this->webcam = true;
    }

    /**
     * Enable file handling
     *
     * Enables file handling for the uploaded images, allowing file-based storage.
     */
    public function enableFileHandling()
    {
        $this->fileHandling = true;
    }

    /**
     * Disable drag functionality
     *
     * Prevents users from dragging the image within the cropping area.
     */
    public function disableButtonsDrag()
    {
        $this->buttonDrag = false;
    }

    /**
     * Disable zoom functionality
     *
     * Disables the zoom in and zoom out buttons in the cropping modal.
     */
    public function disableButtonsZoom()
    {
        $this->buttonZoom = false;
    }

    /**
     * Disable scaling functionality
     *
     * Prevents users from scaling the image horizontally or vertically.
     */
    public function disableButtonsScale()
    {
        $this->buttonScale = false;
    }

    /**
     * Disable reset button
     *
     * Removes the reset button from the cropping modal.
     */
    public function disableButtonReset()
    {
        $this->buttonReset = false;
    }

    /**
     * Disable rotation buttons
     *
     * Prevents users from rotating the image.
     */
    public function disableButtonsRotate()
    {
        $this->buttonRotate = false;
    }

    /**
     * Set the initial image value
     *
     * Defines the image to be displayed initially, either as a URL or base64 string.
     * Handles file-based images when file handling is enabled.
     *
     * @param string $value The image URL or base64-encoded image data
     */
    public function setValue($value)
    {
        if ($this->fileHandling && $value)
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

    /**
     * Define allowed file extensions
     *
     * Specifies which file extensions are permitted for image uploads.
     *
     * @param array $extensions An array of allowed file extensions (e.g., ['jpg', 'png'])
     */
    public function setAllowedExtensions($extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * Get the allowed file extensions
     *
     * Returns the list of permitted file extensions for image uploads.
     *
     * @return array The array of allowed file extensions
     */
    public function getAllowedExtensions()
    {
        return $this->extensions;
    }

    /**
     * Define the upload service class
     *
     * Sets the service class responsible for handling file uploads.
     *
     * @param string $service The name of the uploader service class
     */
    public function setService($service)
    {
        $this->uploaderClass = $service;
    }

    /**
     * Set the field dimensions
     *
     * Defines the width and height of the image cropper field.
     *
     * @param string|int $width  The field width (in pixels or percentage)
     * @param string|int|null $height The field height (in pixels or percentage)
     */
    public function setSize($width, $height = NULL)
    {
        $width = (strstr($width, '%') !== FALSE) ? $width : "{$width}px";
        $height = (strstr($height, '%') !== FALSE) ? $height : "{$height}px";

        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Get the field dimensions
     *
     * Returns the width and height of the image cropper field.
     *
     * @return array An array containing the width and height
     */
    public function getSize()
    {
        return [
            str_replace('px', '', $this->width),
            str_replace('px', '', $this->height)
        ];
    }

    /**
     * Set the crop dimensions
     *
     * Defines the width and height for the cropped image.
     *
     * @param int $width  The crop width in pixels
     * @param int $height The crop height in pixels
     */
    public function setCropSize($width, $height)
    {
        $this->cropWidth = $width;
        $this->cropHeight = $height;

        $this->setAspectRatio($this->cropWidth / $this->cropHeight);
    }

    /**
     * Get the component options
     *
     * Returns the configuration options for the cropper, including aspect ratio
     * and enabled buttons.
     *
     * @return string A JSON-encoded string containing the cropper options
     */
    public function getOptions()
    {
        return json_encode([
            'cropWidth' => $this->cropWidth,
            'cropHeight' => $this->cropHeight,
            'aspectRatio' => $this->aspectRatio,
            'enableButtonDrag' => $this->buttonDrag,
            'enableButtonScale' => $this->buttonScale,
            'enableButtonReset' => $this->buttonReset,
            'enableButtonZoom' => $this->buttonZoom,
            'enableButtonRotate' => $this->buttonRotate,
            'labels' =>  [
                'reset'       => AdiantiCoreTranslator::translate('Reset'),
                'scalex'      => AdiantiCoreTranslator::translate('Scale horizontal'),
                'scaley'      => AdiantiCoreTranslator::translate('Scale vertical'),
                'move'        => AdiantiCoreTranslator::translate('Move'),
                'crop'        => AdiantiCoreTranslator::translate('Crop'),
                'zoomin'      => AdiantiCoreTranslator::translate('Zoom in'),
                'zoomout'     => AdiantiCoreTranslator::translate('Zoom out'),
                'rotateright' => AdiantiCoreTranslator::translate('Rotate right'),
                'rotateleft'  => AdiantiCoreTranslator::translate('Rotate left'),
            ]
        ]);
    }
    
    /**
     * Render the component
     *
     * Generates and outputs the HTML structure for the image cropper field,
     * including buttons and image previews.
     */
    public function show()
    {
        $label = new TElement("label");
        $label->{'id'} = 'timagecropper_container_' . $this->name;
        $label->{'class'} = 'label_timagecropper';
        $label->{'style'} = "width: {$this->width}; height: {$this->height};";

        $remover = new TElement('i');
        $remover->{'class'} = 'fa fa-trash-alt';

        $editar = new TElement('i');
        $editar->{'class'} = 'fa fa-pen';
        
        $actions = new THBox('div');
        $actions->{'class'} = 'timagecropper_actions';

        if(! $this->value) {
            $actions->{'style'} = 'display: none';
        }            
        
        $actions->add($editar)->{'action'} = 'edit';
        $actions->add($remover)->{'action'} = 'remove';
        
        $img = new TElement('img');
        $img->{'id'}    = 'timagecropper_' . $this->name;
        $img->{'class'} = 'img_imagecropper rounded timagecropper';
        $img->{'style'} = "max-width: {$this->width}; max-height: {$this->height};margin: auto;";

        $src = '';
        $fileName = '';
        $fileExtension = '';

        if ($this->fileHandling && $this->value)
        {
            $dados_file = json_decode(urldecode($this->value));
            
            if (!empty($dados_file->fileName))
            {
                // Get name and extension img
                $fileName = basename($dados_file->fileName);
                $info = pathinfo($dados_file->fileName);

                if(!empty($info['extension']))
                {
                    $fileExtension = $info['extension'];
                    // Set src img
                    $src = 'download.php?file=' . $dados_file->fileName . '&v=' . uniqid();
                }
            }
        }
        else if ($this->base64 && $this->value)
        {
            $encodedImgString = explode(',', $this->value, 2)[1];
            $decodedImgString = base64_decode($encodedImgString);
            $info = getimagesizefromstring($decodedImgString);
            $ext = explode('/', $info['mime'])[1];
            
            // Get name and extension img
            $fileName = uniqid().".{$ext}";
            $fileExtension = $ext;
            
            // Set src img
            $src = $this->value;
        }
        else if ($this->value)
        {
            // Get name and extension img
            $fileName = empty($this->value) ? '' : basename($this->value);
            $fileExtension = empty($this->value) ? '' : pathinfo($this->value)['extension'];
            
            // Set src img
            $src = $this->value;
        }
        
        if ($src)
        {
            $img->{'src'} = $src;
            $this->imagePlaceholder->{'style'} = 'display: none;';
        }            

        $this->tag->{'value'} = $this->value;

        $file = new TEntry('tfile_timagecropper_' . $this->name);
        $file->{'accept'} =  '.' . implode(',.', $this->extensions);
        $file->{'type'}   = 'file';
        $file->{'class' } = "sr-only";
        $file->{'id' }    = $file->getName();
        
        $hash = md5("{$this->seed}{$this->name}".base64_encode(serialize($this->extensions)));
        $action = "engine.php?class={$this->uploaderClass}&name={$this->name}&hash={$hash}&extensions=".base64_encode(serialize($this->extensions));

        if(parent::getEditable())
        {
            $label->add($file);
        }
        
        $label->add($img);
        $label->add($actions);
        $label->add($this->tag);
        $label->add($this->imagePlaceholder);

        $label->show();

        $options = $this->getOptions();

        $fileHandling = $this->fileHandling ? '1' : '0';
        $base64 = $this->base64 ? '1' : '0';
        $webcam = $this->webcam ? '1' : '0';

        TScript::create("timagecropper_start('{$this->name}', '{$this->title}', '{$this->buttonText}', '{$action}', {$fileHandling}, {$base64}, {$webcam}, {$options}, '{$fileName}', '{$fileExtension}');");
    }
}
