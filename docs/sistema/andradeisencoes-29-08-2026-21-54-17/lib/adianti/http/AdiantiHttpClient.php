<?php
namespace Adianti\Http;

use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

/**
 * Basic HTTP Client request
 *
 * This class provides a static method to perform HTTP requests using cURL.
 * It supports various HTTP methods, including GET, POST, PUT, and DELETE.
 *
 * @version    7.5
 * @package    http
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiHttpClient
{
    /**
     * Executes an HTTP request using cURL.
     *
     * This method supports GET, POST, PUT, and DELETE methods.
     * If a request body is provided, it will be sent as JSON.
     * Throws an exception if the response is not a valid JSON or contains an error message.
     *
     * @param string $url The target URL for the request.
     * @param string $method The HTTP method to use (GET, POST, PUT, DELETE). Default is 'POST'.
     * @param array $params The request parameters to be sent. If used with GET/DELETE, they will be appended as query parameters.
     * @param string|null $authorization Optional authorization token to be included in the request headers.
     *
     * @throws Exception If the cURL request fails or the response is not a valid JSON.
     * @return array The decoded JSON response, or the 'data' field if available.
     */
    public static function request($url, $method = 'POST', $params = [], $authorization = null)
    {
        $ch = curl_init();
        
        if ($method == 'POST' || $method == 'PUT')
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_POST, true);
     
        }
        else if ( ($method == 'GET' || $method == 'DELETE') && $params)
        {
            $url .= '?'.http_build_query($params);
        }
       
        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 10
        );
        
        if (!empty($authorization))
        {
            $defaults[CURLOPT_HTTPHEADER] = ['Authorization: '. $authorization];
        }
        
        curl_setopt_array($ch, $defaults);
        $output = curl_exec ($ch);
        
        if ($output === false)
        {
            throw new Exception( curl_error($ch) );
        }
        
        curl_close ($ch);
        
        $return = (array) json_decode($output);
        
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception(AdiantiCoreTranslator::translate('Return is not a valid JSON. Check the URL') . ' ' . ( AdiantiCoreApplication::getDebugMode() ? $output : '') );
        }
        
        if (!empty($return['status']) && $return['status'] == 'error') {
            throw new Exception(!empty($return['data']) ? $return['data'] : $return['message']);
        }
        
        if (!empty($return['error'])) {
            throw new Exception($return['error']['message']);
        }
        
        if (!empty($return['errors'])) {
            throw new Exception($return['errors']['message']);
        }
        
        if (!empty($return['data']))
        {
            return $return['data'];
        }
        
        return $return;
    }
}
