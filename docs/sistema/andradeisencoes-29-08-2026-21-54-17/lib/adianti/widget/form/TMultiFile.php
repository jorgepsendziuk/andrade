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
 * TMultiFile widget
 *
 * This widget provides a multi-file input field with support for file handling,
 * image galleries, popovers, file size limits, and custom upload services.
 *
 * @version    7.5
 * @package    widget
 * @subpackage form
 * @author     Nataniel Rabaioli
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMultiFile extends TField implements AdiantiWidgetInterface
{
    protected $id;
    protected $height;
    protected $completeAction;
    protected $uploaderClass;
    protected $extensions;
    protected $seed;
    protected $fileHandling;
    protected $imageGallery;
    protected $galleryWidth;
    protected $galleryHeight;
    protected $popover;
    protected $poptitle;
    protected $popcontent;
    protected $limitSize;
    
    /**
     * Constructor method
     *
     * Initializes the TMultiFile widget with a unique identifier, default configurations,
     * and seed for security purposes.
     *
     * @param string $name The name of the input field
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->id = $this->name . '_' . mt_rand(1000000000, 1999999999);
        // $this->height = 25;
        $this->uploaderClass = 'AdiantiUploaderService';
        $this->fileHandling = FALSE;
        
        $ini = AdiantiApplicationConfig::get();
        $this->seed = APPLICATION_NAME . ( !empty($ini['general']['seed']) ? $ini['general']['seed'] : 's8dkld83kf73kf094' );
        $this->imageGallery = false;
        $this->popover = false;
    }
    
    /**
     * Enable image gallery view
     *
     * This method enables the image gallery display for uploaded images.
     *
     * @param int|null $width  The width of the gallery (optional)
     * @param int      $height The height of the gallery (default: 100)
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
     * This method enables a popover when hovering over the file input field.
     *
     * @param string|null $title   The title of the popover (optional)
     * @param string      $content The content of the popover (default: empty string)
     */
    public function enablePopover($title = null, $content = '')
    {
        $this->popover    = TRUE;
        $this->poptitle   = $title;
        $this->popcontent = $content;
    }
    
    /**
     * Define the service class for handling file uploads
     *
     * @param string $service The name of the upload service class
     */
    public function setService($service)
    {
        $this->uploaderClass = $service;
    }
    
    /**
     * Define the allowed file extensions
     *
     * Sets the file types that can be uploaded through this widget.
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
     * Enables internal processing of uploaded files.
     */
    public function enableFileHandling()
    {
        $this->fileHandling = TRUE;
    }
    
    /**
     * Disable file handling
     *
     * Disables internal processing of uploaded files.
     */
    public function disableFileHandling()
    {
        $this->fileHandling = FALSE;
    }
    
    /**
     * Set field size
     *
     * Defines the width of the file input field.
     *
     * @param int|string $width  The width in pixels or percentage
     * @param int|null   $height The height of the field (optional, unused)
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
     * @param int|string $height The height in pixels or percentage
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }
    
    /**
     * Retrieve the posted data
     *
     * Returns the file input values submitted via POST.
     *
     * @return array|null The posted file data or null if not set
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
     * Define upload size limit
     *
     * Sets a maximum file upload size.
     *
     * @param int $limit The maximum size in megabytes (MB)
     */
    public function setLimitUploadSize($limit)
    {
        $this->limitSize = $limit * 1024 * 1024;
    }

    /**
     * Define upload size limit based on PHP configuration
     *
     * Sets the maximum upload file size to the PHP-defined limit.
     */
    public function enablePHPFileUploadLimit()
    {
        $this->limitSize = AdiantiUploaderService::getMaximumFileUploadSize();
    }
    
    /**
     * Set the field value
     *
     * Assigns the provided value to the input field. Handles encoding and processing of file data
     * if file handling is enabled.
     *
     * @param mixed $value The value to set (can be a string, array, or JSON-encoded object)
     */
    public function setValue($value)
    {
        if ($this->fileHandling)
        {
            if (is_array($value))
            {
                $new_value = [];
                
                foreach ($value as $key => $item)
                {
                    if (is_array($item))
                    {
                        $new_value[] = urlencode(json_encode($item));
                    }
                    elseif (is_scalar($item) && substr( (string) $item, 0, 3) !== '%7B')
                    {
                        if (!empty($item))
                        {
                            $new_value[] = urlencode(json_encode(['idFile'=>$key,'fileName'=>$item]));
                        }
                    }
                    else
                    {
                        $value_object = json_decode(urldecode($item));
                        
                        if (!empty($value_object->{'delFile'}) AND $value_object->{'delFile'} == $value_object->{'fileName'})
                        {
                            $value = '';
                        }
                        else
                        {
                            $new_value[] = $item;
                        }
                    }
                }
                $value = $new_value;
            }
            
            parent::setValue($value);
        }
        else
        {            
            parent::setValue($value);
        }
    }
    
    /**
     * Render the widget
     *
     * Displays the multi-file input field and initializes JavaScript behaviors,
     * including file handling, image gallery display, and popover settings.
     *
     * @throws Exception If the form associated with the complete action is not set
     */
    public function show()
    {
        // define the tag properties
        $this->tag->{'id'}        = $this->id;
        $this->tag->{'name'}      = 'file_' . $this->name.'[]';  // tag name
        $this->tag->{'receiver'}  = $this->name;  // tag name
        $this->tag->{'value'}     = $this->value; // tag value
        $this->tag->{'type'}      = 'file';       // input type
        $this->tag->{'multiple'}  = '1';
        
        if ($this->size)
        {
            $size = (strstr((string) $this->size, '%') !== FALSE) ? $this->size : "{$this->size}px";
            $this->setProperty('style', "width:{$size};", FALSE); //aggregate style info
        }
        
        if ($this->height)
        {
            $height = (strstr($this->height, '%') !== FALSE) ? $this->height : "{$this->height}px";
            $this->setProperty('style', "height:{$height}", FALSE); //aggregate style info
        }
        
        $complete_action = "'undefined'";
        
        if (isset($this->completeAction))
        {
            if (!TForm::getFormByName($this->formName) instanceof TForm)
            {
                throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
            }
            $string_action = $this->completeAction->serialize(FALSE);
            
            $complete_action = "function() { __adianti_post_lookup('{$this->formName}', '{$string_action}', '{$this->tag-> id}', 'callback'); }";
        }
        
        $id_div = mt_rand(1000000000, 1999999999);
        
        $div = new TElement('div');
        $div->{'id'}    = 'div_file_'.$id_div;
        
        foreach( (array)$this->value as $val )
        {
            $hdFileName = new THidden($this->name.'[]');
            $hdFileName->setValue( $val );
            
            $div->add( $hdFileName );
        }
                
        $div->add( $this->tag );
        $div->show();
        
        if (empty($this->extensions))
        {
            $action = "engine.php?class={$this->uploaderClass}";
        }
        else
        {
            $hash = md5("{$this->seed}{$this->name}".base64_encode((string) serialize($this->extensions)));
            $action = "engine.php?class={$this->uploaderClass}&name={$this->name}&hash={$hash}&extensions=".base64_encode((string) serialize($this->extensions));
        }
        
        if ($router = AdiantiCoreApplication::getRouter())
        {
	        $action = $router($action, false);
        }

        $fileHandling = $this->fileHandling ? '1' : '0';
        $imageGallery = json_encode(['enabled'=> $this->imageGallery ? '1' : '0', 'width' => $this->galleryWidth, 'height' => $this->galleryHeight]);
        $popover = json_encode(['enabled' => $this->popover ? '1' : '0', 'title' => $this->poptitle, 'content' => base64_encode((string) $this->popcontent)]);
        $limitSize = $this->limitSize ?? 'null';

        TScript::create(" tmultifile_start( '{$this->tag-> id}', '{$div-> id}', '{$action}', {$complete_action}, $fileHandling, '$imageGallery', '$popover', {$limitSize});");

        if(!parent::getEditable())
        {
            TScript::create("tmultifile_disable_field('{$this->formName}', '{$this->name}');");
        }
    }
    
    /**
     * Define the action to be executed when the user leaves the field
     *
     * Sets a callback action to be triggered upon completion.
     *
     * @param TAction $action The action to be executed
     *
     * @throws Exception If the action is not static
     */
    function setCompleteAction(TAction $action)
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
     * Enable the field
     *
     * Allows user interaction with the specified form field.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the field to enable
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tmultifile_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the field
     *
     * Disables user interaction with the specified form field.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the field to disable
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tmultifile_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Clear the field
     *
     * Removes any existing value from the specified form field.
     *
     * @param string $form_name The name of the form
     * @param string $field     The name of the field to clear
     */
    public static function clearField($form_name, $field)
    {
        TScript::create( " tmultifile_clear_field('{$form_name}', '{$field}'); " );
    }
}
