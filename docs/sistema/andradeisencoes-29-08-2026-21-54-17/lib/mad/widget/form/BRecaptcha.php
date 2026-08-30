<?php

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;

/**
 * Google reCAPTCHA v2 Widget
 * 
 * @version    4.0
 * @package    widget
 * @subpackage form
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

class BRecaptcha extends TElement
{
    protected $id;
    protected $name;
    protected $size;
    private $siteKey;
    
    /**
     * Class Constructor
     * @param $name  widget's name
     */
    public function __construct($name, $siteKey)
    {
        parent::__construct('div');
        
        $this->siteKey  = $siteKey;
        $this->id   = 'grecaptcha_' . mt_rand(1000000000, 1999999999);
        $this->name = $name;
        
        // adds the container to the page
        $this->id = $this->id;
        $this->name = $name;
        $this->class = 'g-recaptcha';
        $this->{"data-sitekey"} = $this->siteKey;

        // Load Google reCAPTCHA API
        $api_url = 'https://www.google.com/recaptcha/api.js?hl=' . strtolower( AdiantiCoreTranslator::getLanguage() );
        
        TScript::create("
            if (typeof grecaptcha === 'undefined') {
                var script = document.createElement('script');
                script.src = '{$api_url}';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        ");
    }    
    
    /**
     * Verify if the reCAPTCHA response is valid
     * @param string $response The reCAPTCHA response from the form
     * @return bool Returns true if verification is successful
     */
    public static function verify($response, $secretKey)
    {
        if (empty($response)) {
            return false;
        }
            
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $resultJson = json_decode($result);
        
        return $resultJson->success ?? false;
    }
}
