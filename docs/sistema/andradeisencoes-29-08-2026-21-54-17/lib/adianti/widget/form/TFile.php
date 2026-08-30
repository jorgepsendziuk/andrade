<?php
namespace Adianti\Widget\Form;

use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Adianti\Widget\Form\THidden;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Service\AdiantiUploaderService;
use Exception;

/**
 * FileChooser widget
 *
 * This widget provides a file input field with various features such as
 * image gallery support, popovers, file handling, size restrictions,
 * and more. It integrates with AdiantiUploaderService for file uploads
 * and allows customization of accepted file extensions and display modes.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Nataniel Rabaioli
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TFile extends TField implements AdiantiWidgetInterface
{
    protected $id;
    protected $height;
    protected $completeAction;
    protected $errorAction;
    protected $uploaderClass;
    protected $placeHolder;
    protected $extensions;
    protected $displayMode;
    protected $seed;
    protected $fileHandling;
    protected $imageGallery;
    protected $galleryWidth;
    protected $galleryHeight;
    protected $popover;
    protected $poptitle;
    protected $popcontent;
    protected $limitSize;
    protected $dropZone;
    protected $dropZoneMessage;
    
    /**
     * Constructor method
     *
     * Initializes the file input field with a unique identifier and sets default
     * properties such as uploader service, file handling mode, and popover settings.
     *
     * @param string $name The name of the input field
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = $this->name . '_' . mt_rand(1000000000, 1999999999);
        $this->uploaderClass = 'AdiantiUploaderService';
        $this->fileHandling = FALSE;
        
        $ini = AdiantiApplicationConfig::get();
        $this->seed = APPLICATION_NAME . ( !empty($ini['general']['seed']) ? $ini['general']['seed'] : 's8dkld83kf73kf094' );
        $this->imageGallery = false;
        $this->popover = false;
        $this->popcontent = '';
        $this->tag->{'widget'} = 'tfile';
        $this->dropZone = false;
    }
    
    /**
     * Enable image gallery view
     *
     * When enabled, uploaded images will be displayed in a gallery format.
     * The width and height of the gallery can be customized.
     *
     * @param int|null $width  The width of the image gallery (default: unset)
     * @param int      $height The height of the image gallery (default: 100)
     */
    public function enableImageGallery($width = null, $height = 100)
    {
        $this->imageGallery  = true;
        $this->galleryWidth  = is_null($width) ? 'unset' : $width;
        $this->galleryHeight = is_null($height) ? 'unset' : $height;
    }
    
    /**
     * Enable popover
     *
     * Adds a popover to the file input field, which displays additional information
     * when the user hovers over or clicks the field.
     *
     * @param string|null $title   The title of the popover (default: null)
     * @param string      $content The content of the popover (default: empty string)
     */
    public function enablePopover($title = null, $content = '')
    {
        $this->popover    = TRUE;
        $this->poptitle   = $title;
        $this->popcontent = $content;
    }

    public function enableDropZone($message = false)
    {
        if(!$message)
        {
            $message = AdiantiCoreTranslator::translate('Drag and drop your file here or click to select!');
        }

        $this->dropZone = true;
        $this->dropZoneMessage = $message;
    }


    /**
     * Define upload size limit
     *
     * Sets the maximum file size allowed for uploads, in megabytes.
     *
     * @param int $limit The maximum file size in MB
     */
    public function setLimitUploadSize($limit)
    {
        $this->limitSize = $limit * 1024 * 1024;
    }

    /**
     * Define upload size limit based on PHP configuration
     *
     * Sets the maximum file upload size based on the server's PHP configuration.
     */
    public function enablePHPFileUploadLimit()
    {
        $this->limitSize = AdiantiUploaderService::getMaximumFileUploadSize();
    }
    
    /**
     * Define the display mode
     *
     * Specifies how the uploaded file should be displayed.
     *
     * @param string $mode The display mode (e.g., 'file', 'image')
     */
    public function setDisplayMode($mode)
    {
        $this->displayMode = $mode;
    }
    
    /**
     * Define the service class for response
     *
     * Specifies the service class responsible for handling file uploads.
     *
     * @param string $service The name of the service class
     */
    public function setService($service)
    {
        $this->uploaderClass = $service;
    }
    
    /**
     * Define the allowed extensions
     *
     * Specifies which file types are permitted for upload.
     *
     * @param array $extensions An array of allowed file extensions (e.g., ['jpg', 'png', 'pdf'])
     */
    public function setAllowedExtensions($extensions)
    {
        $this->extensions = $extensions;
        $this->tag->{'accept'} = '.' . implode(',.', $extensions);
    }
    
    /**
     * Enable file handling
     *
     * Activates file handling, which allows the system to manage files
     * in a structured way.
     */
    public function enableFileHandling()
    {
        $this->fileHandling = TRUE;
    }
    
    /**
     * Disable file handling
     *
     * Disables the file handling mechanism, meaning that the uploaded file
     * will not be processed by the system.
     */
    public function disableFileHandling()
    {
        $this->fileHandling = FALSE;
    }
    
    /**
     * Set placeholder element
     *
     * Defines a placeholder element to be displayed within the file input field.
     *
     * @param TElement $widget The placeholder element
     */
    public function setPlaceHolder(TElement $widget)
    {
        $this->placeHolder = $widget;
    }
    
    /**
     * Set field size
     *
     * Defines the width of the file input field.
     *
     * @param int|string $width  The width of the field (can be in pixels or percentage)
     * @param int|null   $height The height of the field (optional)
     */
    public function setSize($width, $height = NULL)
    {
        $this->size   = $width;
    }
    
    /**
     * Set field height
     *
     * Defines the height of the file input field.
     *
     * @param int $height The height in pixels
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }
    
    /**
     * Retrieve the posted file data
     *
     * Gets the value of the file input field from the POST request.
     *
     * @return mixed|null The uploaded file data or null if not set
     */
    public function getPostData()
    {
        $name = str_replace(['[',']'], ['',''], $this->name);
        
        if (isset($_POST[$name]))
        {
            return $_POST[$name];
        }
    }
    
    /**
     * Set field value
     *
     * Defines the value of the file input field. Handles file handling mode
     * and JSON-encoded file metadata.
     *
     * @param mixed $value The value to be set (can be a string, null, or array)
     */
    public function setValue($value)
    {
        if (is_scalar($value))
        {
            if ($this->fileHandling)
            {
                if (strpos( (string) $value, '%7B') === false)
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
        else if (is_null($value) || is_array($value))
        {
            parent::setValue($value);
        }
    }
    
    /**
     * Render the widget
     *
     * Displays the file input field on the screen, configuring necessary
     * attributes and behaviors, including file handling and display modes.
     *
     * @throws Exception If the widget is not inside a valid form
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'id'}       = $this->id;
        $this->tag->{'name'}     = 'file_' . $this->name;  // tag name
        $this->tag->{'receiver'} = $this->name;  // tag name
        $this->tag->{'value'}    = $this->value; // tag value
        $this->tag->{'type'}     = 'file';       // input type
        
        if (!empty($this->size))
        {
            if (strstr((string) $this->size, '%') !== FALSE)
            {
                $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
            }
            else
            {
                $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
            }
        }
        
        if (!empty($this->height))
        {
            $this->setProperty('style', "height:{$this->height}px;", false); //aggregate style info
        }
        
        $hdFileName = new THidden($this->name);
        $hdFileName->setValue( $this->value );
        
        $complete_action = "'undefined'";
        $error_action = "'undefined'";
        
        // verify if the widget is editable
        
        if (isset($this->completeAction) || isset($this->errorAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
        }
        
        if (isset($this->completeAction))
        {
            $string_action = $this->completeAction->serialize(FALSE);
            $complete_action = "function() { __adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback'); tfile_update_download_link('{$this->name}') }";
        }
        
        if (isset($this->errorAction))
        {
            $string_action = $this->errorAction->serialize(FALSE);
            $error_action = "function() { __adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->id}', 'callback'); }";
        }
        
        $div = new TElement('div');
        $div->{'style'} = "display:inline;width:100%;";
        $div->{'id'} = 'div_file_'.mt_rand(1000000000, 1999999999);
        $div->{'class'} = 'div_file';
        
        $div->add( $hdFileName );
        if ($this->placeHolder)
        {
            $div->add( $this->tag );
            $div->add( $this->placeHolder );
            $this->tag->{'style'} = 'display:none';
        }
        elseif($this->dropZone)
        {
            $dropZoneContainer = new TElement('div');
            $dropZoneContainer->id = "tfile_dropzone_{$this->id}";
            $dropZoneContainer->class = "tfile_dropzone";
            
            $dropZoneContainer->add('<i class="fas fa-upload tfile_dropzone_icon"></i>');
            $dropZoneContainer->add("<p class='tfile_dropzone_message'> {$this->dropZoneMessage} </p>");
            
            if($this->extensions)
            {
                $extMessage = AdiantiCoreTranslator::translate('Allowed extensions');
                $extMessage .= ': .' . implode(',.', $this->extensions);
                $dropZoneContainer->add("<small> {$extMessage} </small>");
            }
            
            $dropZoneContainerInfo = new TElement('div');
            $dropZoneContainerInfo->id = "tfile_dropzone_info_{$this->id}";
            $dropZoneContainerInfo->class = "tfile_dropzone_info";

            $this->tag->{'style'} = 'display:none';

            $div->add($dropZoneContainer);
            $div->add($dropZoneContainerInfo);
            $div->add( $this->tag );
        }
        else
        {
            $div->add( $this->tag );
        }
        
        if ($this->displayMode == 'file' && $this->value && file_exists($this->value))
        {
            $icon = TElement::tag('i', null, ['class' => 'fa fa-download']);
            $link = new TElement('a');
            $link->{'id'}     = 'view_'.$this->name;
            $link->{'href'}   = 'download.php?file='.$this->value;
            $link->{'target'} = 'download';
            $link->{'style'}  = 'padding: 4px; display: block';
            $link->add($icon);
            $link->add($this->value);
            $div->add( $link );
        }
        
        $div->show();
        
        if (empty($this->extensions))
        {
            $action = "engine.php?class={$this->uploaderClass}";
        }
        else
        {
            $hash = md5("{$this->seed}{$this->name}".base64_encode(serialize($this->extensions)));
            $action = "engine.php?class={$this->uploaderClass}&name={$this->name}&hash={$hash}&extensions=".base64_encode(serialize($this->extensions));
        }
        
        if ($router = AdiantiCoreApplication::getRouter())
        {
	        $action = $router($action, false);
        }

        $fileHandling = $this->fileHandling ? '1' : '0';
        $imageGallery = json_encode(['enabled'=> $this->imageGallery ? '1' : '0', 'width' => $this->galleryWidth, 'height' => $this->galleryHeight]);
        $popover = json_encode(['enabled' => $this->popover ? '1' : '0', 'title' => $this->poptitle, 'content' => base64_encode($this->popcontent)]);
        $limitSize = $this->limitSize ?? 'null';
        $dropZone = $this->dropZone ? 'true' : 'false';

        TScript::create(" tfile_start( '{$this->tag-> id}', '{$div-> id}', '{$action}', {$complete_action}, {$error_action}, $fileHandling, '$imageGallery', '$popover', {$limitSize}, $dropZone);");

        if (!parent::getEditable())
        {
            TScript::create("tfile_disable_field('{$this->formName}', '{$this->name}');");
        }
    }
    
    /**
     * Define the action to be executed when upload is completed
     *
     * Sets an action that will be triggered upon successful file upload.
     *
     * @param TAction $action The action to be executed
     *
     * @throws Exception If the action is not static
     */
    public function setCompleteAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->completeAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Define the action to be executed when an error occurs
     *
     * Sets an action that will be triggered if an error occurs during upload.
     *
     * @param TAction $action The action to be executed
     *
     * @throws Exception If the action is not static
     */
    public function setErrorAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->errorAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Enable the field
     *
     * Enables the file input field, making it accessible for user interaction.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the field
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tfile_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the field
     *
     * Disables the file input field, preventing user interaction.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the field
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tfile_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clear the field
     *
     * Resets the file input field, removing any selected file.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the field
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tfile_clear_field('{$form_name}', '{$field}'); " );
    }
}
