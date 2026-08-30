<?php
namespace Adianti\Service;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Util\AdiantiStringConversion;

/**
 * File uploader listener
 *
 * This service processes file uploads, verifies allowed extensions, 
 * checks for security risks, and ensures the server's upload limits 
 * are not exceeded.
 *
 * @version    7.5
 * @package    service
 * @author     Nataniel Rabaioli
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiUploaderService
{
    /**
     * Processes the uploaded file, checks for valid extensions, 
     * and moves it to the temporary folder.
     *
     * This method ensures that only allowed file types are uploaded, 
     * verifies the request integrity using a hash, and handles errors.
     *
     * @param array $param Associative array containing the upload parameters:
     *                     - `extensions` (base64-encoded serialized array of allowed extensions)
     *                     - `name` (string) The name of the file being uploaded
     *                     - `hash` (string) A hash for verification
     *
     * @return void Outputs a JSON response with the status and file details.
     */
    function show($param)
    {
        $ini  = AdiantiApplicationConfig::get();
        $seed = APPLICATION_NAME . ( !empty($ini['general']['seed']) ? $ini['general']['seed'] : 's8dkld83kf73kf094' );
        $block_extensions = [
            'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'php9',
            'phps', 'pht', 'phtml', 'phtm', 'phar', 'inc', 'hphp', 'ctp', 'module',
            'pl', 'py', 'rb', 'jsp', 'jspx', 'asp', 'aspx', 'asa', 'cer',
            'sh', 'bash', 'cgi', 'shtml', 'shtm', 'stm',
            'htm', 'htaccess', 'htpasswd', 'phtml5'
	];

        $folder = 'tmp/';
        $response = array();
        if (isset($_FILES['fileName']))
        {
            $file = $_FILES['fileName'];
            
            if( $file['error'] === 0 && $file['size'] > 0 )
            {
                $file['name'] = uniqid().'_'.self::slug($file['name'], '_');
                $path = $folder.$file['name'];
                // check blocked file extension, not using finfo because file.php.2 problem
                foreach ($block_extensions as $block_extension)
                {
                    if (strpos(strtolower($file['name']), ".{$block_extension}") !== false)
                    {
                        $response = array();
                        $response['type'] = 'error';
                        $response['msg']  = AdiantiCoreTranslator::translate('Extension not allowed');
                        echo json_encode($response);
                        return;
                    }
                }
                
                if (!empty($param['extensions']))
                {
                    $name = $param['name'];
                    $extensions = unserialize(base64_decode( $param['extensions'] ));
                    $hash = md5("{$seed}{$name}".base64_encode(serialize($extensions)));
                    
                    if ($hash !== $param['hash'])
                    {
                        $response = array();
                        $response['type'] = 'error';
                        $response['msg']  = AdiantiCoreTranslator::translate('Hash error');
                        echo json_encode($response);
                        return;
                    }
                    
                    // check allowed file extension
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    
                    if (!in_array(strtolower($ext),  $extensions))
                    {
                        $response = array();
                        $response['type'] = 'error';
                        $response['msg']  = AdiantiCoreTranslator::translate('Extension not allowed');
                        echo json_encode($response);
                        return;
                    }
                }
                
                if (is_writable($folder) )
                {
                    if( move_uploaded_file( $file['tmp_name'], $path ) )
                    {
                        $response['type'] = 'success';
                        $response['fileName'] = $file['name'];
                    }
                    else
                    {
                        $response['type'] = 'error';
                        $response['msg'] = '';
                    }
                }
                else
                {
                    $response['type'] = 'error';
                    $response['msg']  = AdiantiCoreTranslator::translate('Permission denied') . ": {$path}";
                }
                echo json_encode($response);
            }
            else
            {
                $response['type'] = 'error';
                $response['msg']  = AdiantiCoreTranslator::translate('Server has received no file') . '. ' . AdiantiCoreTranslator::translate('Check the server limits') .  '. ' . AdiantiCoreTranslator::translate('The current limit is') . ' ' . self::getMaximumFileUploadSizeFormatted();
                echo json_encode($response);
            }
        }
        else
        {
            $response['type'] = 'error';
            $response['msg']  = AdiantiCoreTranslator::translate('Server has received no file') . '. ' . AdiantiCoreTranslator::translate('Check the server limits') .  '. ' . AdiantiCoreTranslator::translate('The current limit is') . ' ' . self::getMaximumFileUploadSizeFormatted();
            echo json_encode($response);
        }
    }
    
    /**
     * Retrieves the maximum file upload size allowed by the server, formatted as a string.
     *
     * This method compares `post_max_size` and `upload_max_filesize` 
     * from the PHP configuration and returns the lower of the two.
     *
     * @return string Formatted string indicating the maximum upload size (e.g., "upload_max_filesize: 8M").
     */
    public static function getMaximumFileUploadSizeFormatted()  
    {  
        $post_max_size = self::convertSizeToBytes(ini_get('post_max_size'));
        $upld_max_size = self::convertSizeToBytes(ini_get('upload_max_filesize'));  
        
        if ($post_max_size < $upld_max_size)
        {
            return 'post_max_size: ' . ini_get('post_max_size');
        }
        
        return 'upload_max_filesize: ' .ini_get('upload_max_filesize');
    }
    
    /**
     * Retrieves the maximum file upload size allowed by the server in bytes.
     *
     * This method determines the smallest value between `post_max_size` 
     * and `upload_max_filesize` to enforce server limitations.
     *
     * @return int The maximum file upload size in bytes.
     */
    public static function getMaximumFileUploadSize()  
    {  
        return min(self::convertSizeToBytes(ini_get('post_max_size')), self::convertSizeToBytes(ini_get('upload_max_filesize')));  
    }  
    
    /**
     * Converts a human-readable file size (e.g., "8M", "512K") into bytes.
     *
     * This method extracts the size suffix (K, M, G, T, P) and converts it 
     * into its equivalent value in bytes.
     *
     * @param string $size The file size string with a suffix (e.g., "8M", "512K").
     *
     * @return int The converted size in bytes.
     */
    public static function convertSizeToBytes($size)
    {
        $suffix = strtoupper(substr($size, -1));
        if (!in_array($suffix,array('P','T','G','M','K'))){
            return (int)$size;  
        } 
        $value = substr($size, 0, -1);
        switch ($suffix) {
            case 'P':
                $value *= 1024;
                // intended
            case 'T':
                $value *= 1024;
                // intended
            case 'G':
                $value *= 1024;
                // intended
            case 'M':
                $value *= 1024;
                // intended
            case 'K':
                $value *= 1024;
                break;
        }
        return (int)$value;
    }

    public static function slug($string)
    {
        $table = array(
            'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
            'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
            'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
            'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
            'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
        );
        $string = strtr($string, $table);
        $string = strtolower($string);
        $string = preg_replace("/[^a-z0-9_.\s-]/", "", $string);
        // Limpa múltiplos hífens ou espaços
        $string = preg_replace("/[\s-]+/", " ", $string);
        // Converte espaços e sublinhados para hífen
        $string = preg_replace("/[\s_]/", "_", $string);
        return $string;
    }
}
