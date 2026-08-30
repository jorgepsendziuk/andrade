<?php
namespace Adianti\Util;

use Math\Parser;
use Exception;
use Adianti\Core\AdiantiCoreTranslator;

/**
 * Template manipulation
 *
 * Handles template manipulation by replacing placeholders with object properties,
 * evaluating mathematical expressions, and processing variable attributions.
 *
 * @version    7.5
 * @package    util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiTemplateHandler
{
    /**
     * Replaces placeholders within a given string with corresponding object properties.
     * Supports method invocation and property access, with optional type casting.
     *
     * @param string  $content          The string containing placeholders enclosed in `{}`.
     * @param object  $object           The object whose properties will be used for replacement.
     * @param string|null $cast         Optional type casting for replaced values (e.g., 'string', 'int', etc.).
     * @param bool    $replace_methods  Whether to replace method calls (`{method()}`) in the content.
     *
     * @return string The processed string with placeholders replaced by actual values.
     * @throws Exception If attempting to access a non-existent property.
     */
    public static function replace($content, $object, $cast = null, $replace_methods = false)
    {
        if ($replace_methods)
        {
            // replace methods
            $methods = get_class_methods($object);
            if ($methods)
            {
                foreach ($methods as $method)
                {
                    if (stristr($content, "{$method}()") !== FALSE)
                    {
                        $content = str_replace('{'.$method.'()}', $object->$method(), $content);
                    }
                }
            }
        }
        
        if (preg_match_all('/\{(.*?)\}/', $content, $matches) )
        {
            foreach ($matches[0] as $match)
            {
                $property = substr($match, 1, -1);
                
                if (strpos($property, '->') !== FALSE)
                {
                    $parts = explode('->', $property);
                    $container = $object;
                    foreach ($parts as $part)
                    {
                        if (is_object($container))
                        {
                            $result = $container->$part;
                            $container = $result;
                        }
                        else
                        {
                            throw new Exception(AdiantiCoreTranslator::translate('Trying to access a non-existent property (^1)', $property));
                        }
                    }
                    $value = $result;
                }
                else
                {
                    $value    = $object->$property;
                }
                
                if ($cast)
                {
                    settype($value, $cast);
                }
                
                $content  = str_replace($match, (string) $value, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Evaluates a mathematical expression.
     *
     * @param string $expression The mathematical expression to evaluate.
     *
     * @return float|int The result of the evaluated expression.
     */
    public static function evaluateExpression($expression)
    {
        $parser = new Parser;
        $expression = str_replace('+', ' + ', $expression);
        $expression = str_replace('-', ' - ', $expression);
        $expression = str_replace('*', ' * ', $expression);
        $expression = str_replace('/', ' / ', $expression);
        $expression = str_replace('(', ' ( ', $expression);
        $expression = str_replace(')', ' ) ', $expression);
        
        // fix sintax for operator followed by signal
        foreach (['+', '-', '*', '/'] as $operator)
        {
            foreach (['+', '-'] as $signal)
            {
                $expression = str_replace(" {$operator} {$signal} ", " {$operator} {$signal}", $expression);
                $expression = str_replace(" {$operator}  {$signal} ", " {$operator} {$signal}", $expression);
                $expression = str_replace(" {$operator}   {$signal} ", " {$operator} {$signal}", $expression);
            }
        }
        
        return $parser->evaluate($expression);
    }
    
    /**
     * Replaces certain PHP functions (date_format, number_format, evaluate) within a string.
     * Supports evaluating expressions and formatting dates and numbers.
     *
     * @param string $content The content containing function placeholders.
     *
     * @return string The processed content with functions replaced by computed values.
     */
    public static function replaceFunctions($content)
    {
        if ( (strpos($content, 'date_format') === false) AND (strpos($content, 'number_format') === false) AND (strpos($content, 'evaluate') === false) )
        {
            return $content;
        }
        
        preg_match_all('/evaluate\(([-+\/\d\.\s\(\))*]*)\)/', $content, $matches3);
        
        if (count($matches3)>0)
        {
            foreach ($matches3[0] as $key => $value)
            {
                $raw        = $matches3[0][$key];
                $expression = $matches3[1][$key];
                
                $result = self::evaluateExpression($expression);
                $content = str_replace($raw, $result, $content);
            }
        }
        
        $date_masks = [];
        $date_masks[] = '/date_format\(([0-9]{4}-[0-9]{2}-[0-9]{2}),\s*\'([A-z_\/\-0-9\s\:\,\.]*)\'\)/'; // 2018-10-08, mask
        $date_masks[] = '/date_format\(([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}),\s*\'([A-z_\/\-0-9\s\:\.\,]*)\'\)/'; // 2018-10-08 10:12:13, mask
        $date_masks[] = '/date_format\(([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]+),\s*\'([A-z_\/\-0-9\s\:\.\,]*)\'\)/'; // 2018-10-08 10:12:13.17505, mask
        $date_masks[] = '/date_format\((\s*),\s*\'([A-z_\/\-0-9\s\:\.\,]*)\'\)/'; // empty, mask
        
        foreach ($date_masks as $date_mask)
        {
            preg_match_all($date_mask, $content, $matches1);
            
            if (count($matches1)>0)
            {
                foreach ($matches1[0] as $key => $value)
                {
                    $raw    = $matches1[0][$key];
                    $date   = $matches1[1][$key];
                    $mask   = $matches1[2][$key];
                    
                    if (!empty(trim($date)))
                    {
                        $content = str_replace($raw, date_format(date_create($date), $mask), $content);
                    }
                    else
                    {
                        $content = str_replace($raw, '', $content);
                        
                    }
                }
            }
        }
        
        preg_match_all('/number_format\(\s*([\d+\.\d]*)\s*,\s*([0-9])+\s*,\s*\'(\,*\.*)\'\s*,\s*\'(\,*\.*)\'\s*\)/', $content, $matches2);
        
        if (count($matches2)>0)
        {
            foreach ($matches2[0] as $key => $value)
            {
                $raw      = $matches2[0][$key];
                $number   = $matches2[1][$key];
                $decimals = $matches2[2][$key];
                $dec_sep  = $matches2[3][$key];
                $tho_sep  = $matches2[4][$key];
                
                if (is_numeric($number))
                {
                    $content  = str_replace($raw, number_format($number, $decimals, $dec_sep, $tho_sep), $content);
                }
                else
                {
                    $content  = str_replace($raw, '', $content);
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Processes variable attributions within a template.
     * Supports arithmetic operations and variable assignments within `{% set variable = value %}` or `{% set variable += value %}`.
     *
     * @param string $content          The template content containing variable assignments.
     * @param array  &$replacements    Reference array to store variable values.
     */
    public static function processAttribution($content, &$replacements)
    {
        $masks = [];
        $masks[] = '/\{\%\s*set\s*([A-z_]*)\s*\+=\s*([-+\/\d\.\s\(\))*]*) \%\}/';
        $masks[] = '/\{\%\s*set\s*([A-z_]*)\s*=\s*([-+\/\d\.\s\(\))*]*) \%\}/';
        
        foreach ($masks as $mask_key => $mask)
        {
            preg_match_all($mask, $content, $matches1);
            
            if (count($matches1)>0)
            {
                foreach ($matches1[0] as $key => $value)
                {
                    $variable   = $matches1[1][$key];
                    $expression = $matches1[2][$key];
                    
                    if ($mask_key == 0)
                    {
                        if (!isset($replacements['main'][$variable]))
                        {
                            $replacements['main'][$variable] = 0;
                        }
                        $replacements['main'][$variable] += (float) self::evaluateExpression($expression);
                    }
                    else if ($mask_key == 1)
                    {
                        $replacements['main'][$variable] = (float) self::evaluateExpression($expression);
                    }
                }
            }
        }
        
        
        //echo '<pre>';var_dump($replacements);echo '</pre>';
    // {% set total += evaluate( {{price}} * {{quantity}} ) %}
    }
}
