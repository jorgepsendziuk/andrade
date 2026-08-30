<?php
namespace Adianti\Control;

use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;
use Mad\Core\BuilderApplication;
use ReflectionMethod;

/**
 * Structure to encapsulate an action
 *
 * Represents an encapsulated action that can be executed dynamically.
 * Supports defining parameters, enabling/disabling, hiding/showing, and serializing actions into URLs.
 *
 * @version    7.5
 * @package    control
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TAction
{
    protected $disabled = false;
    protected $hidden = false;
    protected $action;
    protected $param;
    protected $properties;
    
    /**
     * Class constructor.
     *
     * Initializes an action with an optional set of parameters. 
     * It also verifies action permissions using BuilderApplication settings.
     *
     * @param callable $action The callback to be executed (array with class/method or function name).
     * @param array|null $parameters Optional parameters for the action.
     *
     * @throws Exception If the provided action is invalid or does not exist.
     */
    public function __construct($action, $parameters = null)
    {
        $this->action = $action;
        
        if (is_object($this->action[0]))
        {
            $this->action[0] = get_class($this->action[0]);
        }
        
        if (!$this->validate($action))
        {
            $action_string = $this->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Method ^1 must receive a parameter of type ^2', __METHOD__, 'Callback'). ' <br> '.
                                AdiantiCoreTranslator::translate('Check if the action (^1) exists', $action_string));
        }
        
        if (!empty($parameters))
        {
            // does not override the action
            unset($parameters['class']);
            unset($parameters['method']);
            
            $this->param = $parameters;
        }

        if($verifyActionPermissionCallback = BuilderApplication::getVerifyActionPermission())
        {
            if(!$verifyActionPermissionCallback($this))
            {
                if(BuilderApplication::isHideAction())
                {
                    $this->hide();
                }

                $this->disable();
            }
        }
    }
    
    /**
     * Disables the action, preventing it from being executed.
     */
    public function disable()
    {
        $this->disabled = true;
    }
    
    /**
     * Enables the action, allowing it to be executed.
     */
    public function enable()
    {
        $this->disabled = false;
    }

    /**
     * Checks if the action is disabled.
     *
     * @return bool TRUE if the action is disabled, FALSE otherwise.
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * Hides the action, preventing it from being displayed.
     */
    public function hide()
    {
        $this->hidden = true;
    }

    /**
     * Unhides the action, making it visible again.
     */
    public function unHide()
    {
        $this->hidden = false;
    }

    /**
     * Checks if the action is hidden.
     *
     * @return bool TRUE if the action is hidden, FALSE otherwise.
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * Creates a clone of the current action with additional parameters.
     *
     * @param array $parameters Additional parameters to set on the cloned action.
     *
     * @return TAction A new action instance with the specified parameters.
     */
    public function cloneWithParameters($parameters = [])
    {
        $clone = clone $this;
        
        if ($parameters)
        {
            foreach ($parameters as $key => $value)
            {
                $clone->setParameter($key, $value);
            }
        }
        
        return $clone;
    }
    
    /**
     * Retrieves the list of dynamic field parameters used in the action.
     * Parameters are enclosed in `{}` and replaced dynamically.
     *
     * @return array List of dynamic field parameters.
     */
    public function getFieldParameters()
    {
        $field_parameters = [];
        
        if ($this->param)
        {
            foreach ($this->param as $parameter)
            {
                if (substr($parameter,0,1) == '{' && substr($parameter,-1) == '}')
                {
                    $field_parameters[] = substr($parameter,1,-1);
                }
            }
        }
        
        return $field_parameters;
    }
    
    /**
     * Returns the action as a string representation.
     *
     * @return string The action in the format 'Class::Method' or function name.
     */
    public function toString()
    {
        $action_string = '';
        if (is_string($this->action))
        {
            $action_string = $this->action;
        }
        else if (is_array($this->action))
        {
            if (is_object($this->action[0]))
            {
                $action_string = get_class($this->action[0]) . '::' . $this->action[1];
            }
            else
            {
                $action_string = $this->action[0] . '::' . $this->action[1];
            }
        }
        return $action_string;
    }
    
    /**
     * Adds or updates a parameter in the action.
     *
     * @param string $param The name of the parameter.
     *
     * @param mixed $value The value to be assigned to the parameter.
     */
    public function setParameter($param, $value)
    {
        $this->param[$param] = $value;
    }
    
        /**
     * Sets multiple parameters for the action.
     *
     * Removes special parameters such as 'class', 'method', and 'static' to prevent conflicts.
     *
     * @param array $parameters Associative array of parameters to set.
     */
    public function setParameters($parameters)
    {
        // does not override the action
        unset($parameters['class']);
        unset($parameters['method']);
        unset($parameters['static']);
        
        $this->param = $parameters;
    }
    
    /**
     * Retrieves the value of a specific parameter.
     *
     * @param string $param The name of the parameter.
     *
     * @return mixed|null The value of the parameter, or NULL if not set.
     */
    public function getParameter($param)
    {
        if (isset($this->param[$param]))
        {
            return $this->param[$param];
        }
        return NULL;
    }
    
    /**
     * Retrieves all parameters of the action.
     *
     * @return array|null Associative array of parameters, or NULL if none are set.
     */
    public function getParameters()
    {
        return $this->param;
    }
    
    /**
     * Retrieves the callback associated with this action.
     *
     * @return callable The callback function or method reference.
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Sets a property in the action.
     *
     * @param string $property The property name.
     * @param mixed $value     The value to assign to the property.
     */
    public function setProperty($property, $value)
    {
        $this->properties[$property] = $value;
    }
    
    /**
     * Retrieves the value of a specific property.
     *
     * @param string $property The property name.
     *
     * @return mixed|null The value of the property, or NULL if not set.
     */
    public function getProperty($property)
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Adds a forced parameter to the action, ensuring it is always included.
     *
     * @param string $param The name of the parameter.
     * @param mixed $value  The value to be assigned.
     */
    public function setForcedParameter($param, $value)
    {
        $this->param[$param] = $value;
        $this->param["bforcedparam_{$param}"] = $value;
    }
    
    /**
     * Prepares an action for execution with a given object.
     *
     * Replaces dynamic placeholders in parameters with actual values from the object.
     *
     * @param object $object The data object used for parameter substitution.
     *
     * @return TAction A new action instance with resolved parameters.
     * @throws Exception If trying to access a non-existent property in the object.
     */
    public function prepare($object)
    {
        $parameters = $this->param;
        $action     = clone $this;
        
        if ($parameters)
        {
            if (isset($parameters['*']))
            {
                unset($parameters['*']);
                unset($action->param['*']);
                
                foreach ($object as $attribute => $value)
                {
                    if (is_scalar($value))
                    {
                        $parameters[$attribute] = $value;
                    }
                }
            }
            
            foreach ($parameters as $parameter => $value)
            {
                // replace {attribute}s
                $action->setParameter($parameter, $this->replace($value, $object) );
            }
        }
        
        return $action;
    }
    
    /**
     * Replaces placeholders in a string with values from an object.
     *
     * @param string $content The content containing placeholders in the format {attribute}.
     * @param object $object The object providing the replacement values.
     *
     * @return string The content with replaced values.
     * @throws Exception If trying to access a non-existent property in the object.
     */
    private function replace($content, $object)
    {
        if (preg_match_all('/\{(.*?)\}/', (string) $content, $matches) )
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
                    $content = $result;
                }
                else
                {
                    $value = isset($object->$property) ? $object->$property : null;
                    $content  = str_replace($match, (string) $value, $content);
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Serializes the action into a URL format.
     *
     * Generates a formatted URL with action and parameters, suitable for execution.
     *
     * @param bool $format_action If TRUE, formats as a document or JavaScript action; if FALSE, returns a raw query string.
     *
     * @return string The serialized action URL.
     */
    public function serialize($format_action = TRUE)
    {
        // check if the callback is a method of an object
        if (is_array($this->action))
        {
            // get the class name
            $url['class'] = is_object($this->action[0]) ? get_class($this->action[0]) : $this->action[0];
            // get the method name
            $url['method'] = $this->action[1];
            
            if (isset($_GET['register_state']) AND $_GET['register_state'] == 'false' AND empty($this->param['register_state']))
            {
                $url['register_state'] = 'false';
            }
            
            if (isset($_GET['target_container']) AND !empty($_GET['target_container']) AND empty($this->param['target_container']) AND ($_GET['target_container'] !== 'adianti_div_content'))
            {
                $url['target_container'] = $_GET['target_container'];
            }
            
            if ($this->isStatic())
            {
                $url['static'] = '1';
            }
        }
        // otherwise the callback is a function
        else if (is_string($this->action))
        {
            // get the function name
            $url['method'] = $this->action;
        }
        
        if(!empty($_REQUEST))
        {
            foreach($_REQUEST as $key => $value)
            {
                if(substr($key, 0, 13) == 'bforcedparam_')
                {
                    $pieces = explode('bforcedparam_', $key);
                    
                    if(!empty($this->param[$pieces[1]]))
                    {
                        continue;
                    }
                    
                    $this->setForcedParameter($pieces[1], $value);
                }
            }
        }

        // check if there are parameters
        if ($this->param)
        {
            $url = array_merge($url, $this->param);
        }
            
        if($this->disabled)
        {
            return '#';
        }

        if ($format_action)
        {
            if ($router = AdiantiCoreApplication::getRouter())
            {
                return $router(http_build_query($url));
            }
            else
            {
                return 'index.php?'.http_build_query($url);
            }
        }
        else
        {
            if ($router = AdiantiCoreApplication::getRouter())
            {
                return $router(http_build_query($url), FALSE);
            }
            else
            {
                return http_build_query($url);
            }
        }
    }
    
    /**
     * Validates if the specified action is a callable method of a valid class.
     *
     * @return bool TRUE if the action is valid, FALSE otherwise.
     */
    public function validate()
    {
        $class = is_string($this->action[0]) ? $this->action[0] : get_class($this->action[0]);
        
        if (class_exists($class))
        {
            $method = $this->action[1];
            
            if (method_exists($class, $method))
            {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    /**
     * Determines if the action refers to a static method.
     *
     * @return bool TRUE if the action is static, FALSE otherwise.
     */
    public function isStatic()
    {
        if (is_array($this->action))
        {
            $class = is_string($this->action[0]) ? $this->action[0] : get_class($this->action[0]);
            
            if (class_exists($class))
            {
                $method = $this->action[1];
                
                if (method_exists($class, $method))
                {
                    $rm = new ReflectionMethod( $class, $method );
                    return $rm->isStatic() || (isset($this->param['static']) && $this->param['static'] == '1');
                }
            }
        }
        return FALSE;
    }
}
