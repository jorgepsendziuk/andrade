<?php
namespace Adianti\Util;

/**
 * String manipulation
 *
 * Provides various string manipulation utilities, such as conversions between 
 * camel case and underscore notation, removing accents, ensuring encoding formats, 
 * and generating slugs.
 *
 * @version    7.5
 * @package    util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiStringConversion
{
    /**
     * Converts an underscore-separated string into camel case format.
     *
     * @param string $string The input string in underscore notation.
     * @param bool $spaces If true, spaces are added between words instead of joining them.
     *
     * @return string The converted string in camel case format.
     */
    public static function camelCaseFromUnderscore($string, $spaces = FALSE)
    {
        $words = explode('_', mb_strtolower($string));

        $return = '';
        foreach ($words as $word)
        {
            $return .= ucfirst(trim($word));
            if ($spaces)
            {
                $return .= ' ';
            }
        }

        return $return;
    }

    /**
     * Converts a camel case string into an underscore-separated format.
     *
     * @param string $string The input string in camel case notation.
     * @param bool $spaces If true, spaces are replaced by underscores.
     *
     * @return string The converted string in underscore notation.
     */
    public static function underscoreFromCamelCase($string, $spaces = FALSE)
    {
        $output = mb_strtolower(preg_replace('/([a-z])([A-Z])/', '$'.'1_$'.'2', $string));
        if ($spaces)
        {
            $output = str_replace(' ', '_', trim($output));
        }
        
        return $output;
    }
    
    /**
     * Removes accents from a given string, converting accented characters
     * to their closest ASCII equivalents.
     *
     * @param string $str The input string containing accented characters.
     *
     * @return string The processed string with accents removed.
     */
    public static function removeAccent($str)
    {
      $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
      $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
      return str_replace($a, $b, $str);
    }
    
    /**
     * Ensures that a given string is properly encoded in UTF-8 format.
     *
     * @param string $content The input string to be checked and converted.
     *
     * @return string The UTF-8 encoded string.
     */
    public static function assureUnicode($content)
    {
        if (extension_loaded('mbstring') && extension_loaded('iconv'))
        {
            $enc_in = mb_detect_encoding( (string) $content, ['UTF-8', 'ISO-8859-1', 'ASCII'], true);
            if ($enc_in !== 'UTF-8')
            {
                $converted = iconv($enc_in, "UTF-8//TRANSLIT//IGNORE", $content);
                if ($converted === false)
                {
                    return $content;
                }
                
                return $converted;
            }
        }
        else
        {
            if (self::utf8_encode(self::utf8_decode($content)) !== $content ) // NOT UTF
            {
                return self::utf8_encode($content);
            }
        }
        
        return $content;
    }

    /**
     * Ensures that a given string is properly encoded in ISO-8859-1 format.
     *
     * @param string $content The input string to be checked and converted.
     *
     * @return string The ISO-8859-1 encoded string.
     */
    public static function assureIso($content)
    {
        if (extension_loaded('mbstring') && extension_loaded('iconv'))
        {
            $enc_in = mb_detect_encoding( (string) $content, ['UTF-8', 'ASCII'], true);
            if ($enc_in !== 'ISO-8859-1')
            {
                $converted = iconv($enc_in, "ISO-8859-1//TRANSLIT//IGNORE", (string) $content);
                if ($converted === false)
                {
                    return $content;
                }
                
                return $converted;
            }
        }
        else
        {
            // se UTF8
            if (self::utf8_encode(self::utf8_decode($content)) == $content )
            {
                $content = self::utf8_decode($content);
            }
        }
        
        return $content;
    }

    /**
     * Generates a URL-friendly slug from a given string.
     *
     * @param string $content The input string to be converted into a slug.
     * @param string $separator The character to use as a separator in the slug (default: '-').
     *
     * @return string The generated slug.
     */
    public static function slug($content, $separator = '-')
    {
        $content = self::assureUnicode($content);
        
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

        $content = strtr($content, $table);

        $content = mb_strtolower($content);
        //Strip any unwanted characters
        $content = preg_replace("/[^a-z0-9_\s-]/", "", $content);
        //Clean multiple dashes or whitespaces
        $content = preg_replace("/[\s-]+/", " ", $content);
        //Convert whitespaces and underscore to dash
        $content = preg_replace("/[\s_]/", $separator, trim($content));
        //Remove non visible chars
        $content = preg_replace('/[[:^print:]]/', "", $content);
        
        return $content;
    }
    
    /**
     * Replaces text between two specified delimiters within a string.
     *
     * @param string $str The input string.
     * @param string $needle_start The starting delimiter.
     * @param string $needle_end The ending delimiter.
     * @param string $replacement The replacement string.
     * @param bool $include_limits If true, the delimiters are also replaced; otherwise, they are preserved.
     *
     * @return string The modified string with the specified section replaced.
     */
    public static function replaceBetween($str, $needle_start, $needle_end, $replacement, $include_limits = true)
    {
        $pos = strpos($str, $needle_start);
        if ($pos === false)
        {
            return $str;
        }
        $start = $pos + ($include_limits ? strlen($needle_start) : 0);

        $pos = strpos($str, $needle_end, $start);
        if ($pos === false)
        {
            return $str;
        }
        $end = ($include_limits ? $pos : $pos + strlen($needle_end));

        return substr_replace($str, $replacement, $start, $end - $start);
    }
    
    /**
     * Extracts text between two specified delimiters within a string.
     *
     * @param string $str The input string.
     * @param string $needle_start The starting delimiter.
     * @param string $needle_end The ending delimiter.
     * @param bool $include_limits If true, includes the delimiters in the extracted text.
     *
     * @return string The extracted text, or an empty string if the delimiters are not found.
     */
    public static function getBetween($str, $needle_start, $needle_end, $include_limits = true)
    {
        $pos = strpos($str, $needle_start);
        if ($pos === false)
        {
            return '';
        }
        
        $start = $pos + ($include_limits ? strlen($needle_start) : 0);

        $pos = strpos($str, $needle_end, $start);
        if ($pos === false)
        {
            return '';
        }
        
        $end = ($include_limits ? $pos : $pos + strlen($needle_end));

        return substr($str, $start, $end - $start);
    }

    /**
     * Converts a string from ISO-8859-1 to UTF-8 encoding.
     *
     * @param string $s The input string in ISO-8859-1 encoding.
     *
     * @return string The UTF-8 encoded string.
     */
    protected static function utf8_encode($s)
    {
        $s = (string) $s;

        if (\PHP_VERSION_ID < 80200) 
        {
            return utf8_encode($s);
        }

        if (function_exists('mb_convert_encoding')) 
        {
            return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        }

        if (class_exists('UConverter')) 
        {
            return \UConverter::transcode($s, 'UTF8', 'ISO-8859-1');
        }

        if (function_exists('iconv')) 
        {
            return iconv('ISO-8859-1', 'UTF-8', $s);
        }

        /*
         * Fallback to the pure PHP implementation from Symfony Polyfill for PHP 7.2
         *
         * @see https://github.com/symfony/polyfill-php72/blob/v1.26.0/Php72.php
         */
        $s .= $s;
        $len = \strlen($s);

        for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
            switch (true) {
                case $s[$i] < "\x80": $s[$j] = $s[$i];
                    break;
                case $s[$i] < "\xC0": $s[$j] = "\xC2";
                    $s[++$j] = $s[$i];
                    break;
                default: $s[$j] = "\xC3";
                    $s[++$j] = \chr(\ord($s[$i]) - 64);
                    break;
            }
        }

        return substr($s, 0, $j);
    }

    /**
     * Converts a string from UTF-8 to ISO-8859-1 encoding.
     *
     * @param string $s The input string in UTF-8 encoding.
     *
     * @return string The ISO-8859-1 encoded string.
     */
    public static function utf8_decode($s)
    {
        $s = (string) $s;

        if (\PHP_VERSION_ID < 80200)
        {
            return utf8_decode($s);
        }
        
        if (function_exists('mb_convert_encoding'))
        {
            return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        }
        
        if (class_exists('UConverter'))
        {
            return \UConverter::transcode( $s, 'ISO-8859-1', 'UTF8');
        }
        
        if (function_exists('iconv'))
        {
            return iconv('UTF-8', 'ISO-8859-1', $s);
        }

        /*
         * Fallback to the pure PHP implementation from Symfony Polyfill for PHP 7.2
         *
         * @see https://github.com/symfony/polyfill-php72/blob/v1.26.0/Php72.php
         */

        $len = \strlen($s);

        for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
            switch ($s[$i] & "\xF0") {
                case "\xC0":
                case "\xD0":
                    $c = (\ord($s[$i] & "\x1F") << 6) | \ord($s[++$i] & "\x3F");
                    $s[$j] = $c < 256 ? \chr($c) : '?';
                    break;

                case "\xF0":
                    ++$i;
                    // no break

                case "\xE0":
                    $s[$j] = '?';
                    $i += 2;
                    break;

                default:
                    $s[$j] = $s[$i];
            }
        }

        return substr($s, 0, $j);
    
    }
}
