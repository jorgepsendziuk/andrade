<?php
namespace Adianti\Database;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TSqlSelect;
use Adianti\Database\TSqlInsert;
use Adianti\Database\TSqlUpdate;
use Adianti\Database\TSqlDelete;
use Adianti\Registry\TSession;

use Math\Parser;
use PDO;
use Exception;
use IteratorAggregate;
use ArrayIterator;
use Traversable;

/**
 * Base class for Active Records
 *
 * Provides an object-oriented interface for interacting with database records,
 * including CRUD operations, relationships, caching, and soft deletion.
 *
 * @version    7.5
 * @package    database
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
abstract class TRecord implements IteratorAggregate
{
    protected $recordObjectIntartnalData;  // array containing the data of the object
    protected $vdata; // array with virtual data (non-persistant properties)
    protected $attributes; // array of attributes
    protected $trashed;
    protected $managePermissionCallbacks = [];
    protected static $boot = [];
    protected static $booted = [];
    protected static $defaultFilters = [];
    
    /**
     * Initializes the Active Record.
     *
     * If an ID is provided, attempts to load the corresponding record from the database.
     * If the object is not found, throws an exception.
     *
     * @param mixed|null $id Optional object ID. If provided, loads the object.
     * @param bool $callObjectLoad Whether to call the `load` method to retrieve the object.
     *
     * @throws Exception If the object with the given ID is not found.
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        static::bootIfNotBoot();

        $this->attributes = array();
        $this->trashed = FALSE;
        
        if ($id) // if the user has informed the $id
        {
            // load the object identified by ID
            if ($callObjectLoad)
            {
                $object = $this->load($id);
            }
            else
            {
                $object = self::load($id);
            }
            
            if ($object)
            {
                $this->fromArray($object->toArray());
            }
            else
            {
                throw new Exception(AdiantiCoreTranslator::translate('Object ^1 not found in ^2', $id, constant(get_class($this).'::TABLENAME')));
            }
        }

        static::bootedIfNotBooted();
    }

    /**
     * Ensures the boot method is called only once per class.
     *
     * This method checks if the class has already been booted and calls the boot method
     * if it hasn't been called yet. This is part of the initialization process for Active Record classes.
     *
     * @return void
     */
    protected static function bootIfNotBoot()
    {
        $class = static::class;
        
        if (!isset(static::$boot[$class]))
        {
            static::$boot[$class] = true;
            static::boot();
        }
    }

    /**
     * Ensures the booted method is called only once per class.
     *
     * This method checks if the class has already been marked as booted and initializes
     * the default filters array and calls the booted method if it hasn't been called yet.
     * This completes the initialization process for Active Record classes.
     *
     * @return void
     */
    protected static function bootedIfNotBooted()
    {
        $class = static::class;
        
        if (!isset(static::$booted[$class]))
        {
            static::$booted[$class] = true;
            static::$defaultFilters[$class] = [];
            static::booted();
        }
    }
    
    /**
     * Boot method that can be overridden by child classes.
     *
     * This method is called only once per class during the initialization process.
     * Child classes can override this method to perform custom initialization logic.
     *
     * @return void
     */
    protected static function boot()
    {
        // Method that child classes can override
        // This is called only once per class
    }

    /**
     * Booted method that can be overridden by child classes.
     *
     * This method is called only once per class after the boot process is complete.
     * Child classes can override this method to perform custom post-initialization logic.
     *
     * @return void
     */
    protected static function booted()
    {
        // Method that child classes can override
        // This is called only once per class
    }
    
    /**
     * Adds a global scope filter to the Active Record class.
     *
     * Global scopes are filters that are automatically applied to all queries
     * for this Active Record class unless explicitly removed.
     *
     * @param string $name The name identifier for the global scope.
     * @param TFilter $filter The filter object to be applied as a global scope.
     *
     * @return void
     */
    public static function addGlobalScope($name, TFilter $filter)
    {
        $class = static::class;
        static::$defaultFilters[$class][$name] = $filter;
    }
    
    /**
     * Retrieves all global scope filters for the Active Record class.
     *
     * This method returns an array of all global scope filters that have been
     * registered for this Active Record class. It ensures the class is properly
     * initialized before returning the filters.
     *
     * @return array An associative array of global scope filters indexed by their names.
     */
    public static function getGlobalScopes()
    {
        $class = static::class;
        
        // Ensure the class has been initialized
        static::bootedIfNotBooted();
        
        return static::$defaultFilters[$class] ?? [];
    }

    /**
     * Creates a repository instance without applying specified global scopes.
     *
     * This method allows you to temporarily disable global scopes for a query.
     * If no scopes are specified, all global scopes will be disabled.
     *
     * @param array|string|null $scopes Optional. The names of specific scopes to disable.
     *                                  If null, all global scopes will be disabled.
     *                                  Can be a string for a single scope or an array for multiple scopes.
     *
     * @return TRepository A repository instance with the specified global scopes disabled.
     */
    public static function withoutGlobalScopes($scopes = null)
    {
        $repository = new TRepository(get_called_class());
        return $repository->withoutGlobalScopes($scopes);
    }
    
    /**
     * Adds a permission management callback.
     *
     * The callback function will be used to verify if the user has permission to manage the record.
     *
     * @param callable $callback The callback function to be added.
     *
     * @return void
     */
    public function addManagePermission(callable $callback)
    {
        $this->managePermissionCallbacks[] = $callback;
    }

    /**
     * Checks if the current user can manage the given object.
     *
     * This method executes all registered permission callbacks to determine if the user
     * has permission to manage the specified object.
     *
     * @param mixed $object The object to be managed.
     * @param mixed|null $id Optional ID of the object. If provided, the object will be loaded.
     * @param mixed|null $hook Optional hook to specify the context of the permission check.
     *
     * @return void
     */
    private function canManage($object, $id = null, $hook = null)
    {
        if (!empty($this->managePermissionCallbacks))
        {
            if ($id)
            {
                $this->load($id);
            }

            foreach($this->managePermissionCallbacks as $managePermissionCallback)
            {
                call_user_func($managePermissionCallback, $object, $hook);   
            }
        }
    }
    
    /**
     * Returns an iterator for the record's internal data.
     *
     * @return Traversable An iterator for traversing the record's attributes.
     */
    public function getIterator () : Traversable
    {
        return new ArrayIterator( $this->recordObjectIntartnalData );
    }
    
    /**
     * Creates a new Active Record instance and stores it in the database.
     *
     * @param array $data Associative array of attributes to be assigned to the new record.
     *
     * @return static The newly created Active Record instance.
     */
    public static function create($data)
    {
        $object = new static;
        $object->fromArray($data);
        $object->store();
        return $object;
    }
    
    /**
     * Handles the cloning of an Active Record.
     *
     * Ensures that the cloned object does not retain the original object's primary key.
     */
    public function __clone()
    {
        $pk = $this->getPrimaryKey();
        unset($this->$pk);
    }
    
    /**
     * Handles static method calls dynamically.
     *
     * This method enables calling repository methods directly on the Active Record class,
     * as well as handling transactional method calls.
     *
     * @param string $method The name of the method being called.
     * @param array $parameters The parameters passed to the method.
     *
     * @return mixed The result of the method call.
     * @throws Exception If the method is not found.
     */
    public static function __callStatic($method, $parameters)
    {
        $class_name = get_called_class();
        if (substr($method,-13) == 'InTransaction')
        {
            $method = substr($method,0,-13);
            if (method_exists($class_name, $method))
            {
                $database = array_shift($parameters);
                $open = TTransaction::isOpen($database) ? false : true;
                
                if($open)
                {
                    TTransaction::open($database);
                }
                
                $content = forward_static_call_array( array($class_name, $method), $parameters);
                
                if($open)
                {
                    TTransaction::close();
                }
                
                return $content;
            }
            else
            {
                throw new Exception(AdiantiCoreTranslator::translate('Method ^1 not found', $class_name.'::'.$method.'()'));
            }
        }
        else if (method_exists('TRepository', $method))
        {
            $class = get_called_class(); // get the Active Record class name
            $repository = new TRepository( $class ); // create the repository
            return call_user_func_array( array($repository, $method), $parameters );
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('Method ^1 not found', $class_name.'::'.$method.'()'));
        }
    }
    
    /**
     * Retrieves a property value dynamically.
     *
     * Supports virtual properties and chained object access using "->".
     *
     * @param string $property The property name.
     *
     * @return mixed The property value.
     * @throws Exception If the property does not exist.
     */
    public function __get($property)
    {
        // check if exists a method called get_<property>
        if (method_exists($this, 'get_'.$property))
        {
            // execute the method get_<property>
            return call_user_func(array($this, 'get_'.$property));
        }
        else
        {
            if (strpos($property, '->') !== FALSE)
            {
                $parts = explode('->', $property);
                $container = $this;
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
                return $result;
            }
            else
            {
                // returns the property value
                if (isset($this->recordObjectIntartnalData[$property]))
                {
                    return $this->recordObjectIntartnalData[$property];
                }
                else if (isset($this->vdata[$property]))
                {
                    return $this->vdata[$property];
                }
            }
        }
    }
    
    /**
     * Assigns a value to a property dynamically.
     *
     * Supports scalar and object properties while maintaining internal data integrity.
     *
     * @param string $property The property name.
     * @param mixed $value The value to be assigned.
     *
     * @return void
     */
    public function __set($property, $value)
    {
        // if ($property == 'data')
        // {
        //     throw new Exception(AdiantiCoreTranslator::translate('Reserved property name (^1) in class ^2', $property, get_class($this)));
        // }
        
        // check if exists a method called set_<property>
        if (method_exists($this, 'set_'.$property))
        {
            // executed the method called set_<property>
            call_user_func(array($this, 'set_'.$property), $value);
        }
        else
        {
            if ($value === NULL)
            {
                $this->recordObjectIntartnalData[$property] = NULL;
            }
            else if (is_scalar($value))
            {
                // assign the property's value
                $this->recordObjectIntartnalData[$property] = $value;
                unset($this->vdata[$property]);
            }
            else
            {
                // other non-scalar properties that won't be persisted
                $this->vdata[$property] = $value;
                unset($this->recordObjectIntartnalData[$property]);
            }
        }
    }
    
    /**
     * Checks if a property is set.
     *
     * @param string $property The property name.
     *
     * @return bool True if the property is set, false otherwise.
     */
    public function __isset($property)
    {
        return isset($this->recordObjectIntartnalData[$property]) or
               isset($this->vdata[$property]) or
               method_exists($this, 'get_'.$property);
    }
    
    /**
     * Unsets a property dynamically.
     *
     * @param string $property The property name.
     *
     * @return void
     */
    public function __unset($property)
    {
        unset($this->recordObjectIntartnalData[$property]);
        unset($this->vdata[$property]);
    }
    
    /**
     * Retrieves the cache control mechanism for the Active Record.
     *
     * @return mixed The cache control instance or false if caching is disabled.
     */
    public function getCacheControl()
    {
        $class = get_class($this);
        $cache_name = "{$class}::CACHECONTROL";
        
        if ( defined( $cache_name ) )
        {
            $cache_control = constant($cache_name);
            $implements = class_implements($cache_control);
            
            if (in_array('Adianti\Registry\AdiantiRegistryInterface', $implements))
            {
                if ($cache_control::enabled())
                {
                    return $cache_control;
                }
            }
        }
        
        return FALSE;
    }
    
    /**
     * Retrieves the database table name associated with the Active Record.
     *
     * @return string The table name.
     */
    public function getEntity()
    {
        // get the Active Record class name
        $class = get_class($this);
        // return the TABLENAME Active Record class constant
        return constant("{$class}::TABLENAME");
    }
    
    /**
     * Retrieves the primary key name for the Active Record.
     *
     * @return string The primary key column name.
     */
    public function getPrimaryKey()
    {
        // get the Active Record class name
        $class = get_class($this);
        // returns the PRIMARY KEY Active Record class constant
        return constant("{$class}::PRIMARYKEY");
    }
    
    /**
     * Retrieves the column name that stores the creation timestamp.
     *
     * @return string|null The name of the created_at column, or null if not defined.
     */
    public function getCreatedAtColumn()
    {
        // get the Active Record class name
        $class = get_class($this);
        
        if (defined("{$class}::CREATEDAT"))
        {
            // returns the CREATEDAT Active Record class constant
            return constant("{$class}::CREATEDAT");
        }
    }
    
    /**
     * Retrieves the column name that stores the last update timestamp.
     *
     * @return string|null The name of the updated_at column, or null if not defined.
     */
    public function getUpdatedAtColumn()
    {
        // get the Active Record class name
        $class = get_class($this);
        
        if (defined("{$class}::UPDATEDAT"))
        {
            // returns the UPDATEDAT Active Record class constant
            return constant("{$class}::UPDATEDAT");
        }
    }
    
    /**
     * Retrieves the column name that stores the soft-delete timestamp.
     *
     * @return string|null The name of the deleted_at column, or null if not defined.
     */
    public static function getDeletedAtColumn()
    {
        // get the Active Record class name
        $class = get_called_class();
        if(defined("{$class}::DELETEDAT"))
        {
            // returns the DELETEDAT Active Record class constant
            return constant("{$class}::DELETEDAT");
        }

        return NULL;
    }
    
    /**
     * Retrieves the column name that stores the user who created the record.
     *
     * @return string|null The name of the created_by column, or null if not defined.
     */
    public function getCreatedByColumn()
    {
        // get the Active Record class name
        $class = get_class($this);
        
        if (defined("{$class}::CREATED_BY"))
        {
            // returns the CREATED_BY Active Record class constant
            return constant("{$class}::CREATED_BY");
        }
    }
    
    /**
     * Retrieves the column name that stores the user who last updated the record.
     *
     * @return string|null The name of the updated_by column, or null if not defined.
     */
    public function getUpdatedByColumn()
    {
        // get the Active Record class name
        $class = get_class($this);
        
        if (defined("{$class}::UPDATED_BY"))
        {
            // returns the UPDATED_BY Active Record class constant
            return constant("{$class}::UPDATED_BY");
        }
    }
    
    /**
     * Retrieves the column name that stores the user who deleted the record.
     *
     * @return string|null The name of the deleted_by column, or null if not defined.
     */
    public static function getDeletedByColumn()
    {
        // get the Active Record class name
        $class = get_called_class();
        if(defined("{$class}::DELETED_BY"))
        {
            // returns the DELETED_BY Active Record class constant
            return constant("{$class}::DELETED_BY");
        }

        return NULL;
    }

    /**
     * Retrieves the column name that stores the ID of the user who deleted the record.
     *
     * @return string|null The name of the deleted_by_user_id column, or null if not defined.
     */
    public static function getDeletedByUserIdColumn()
    {
        // get the Active Record class name
        $class = get_called_class();
        if(defined("{$class}::DELETED_BY_USER_ID"))
        {
            // returns the DELETED_BY_USER_ID Active Record class constant
            return constant("{$class}::DELETED_BY_USER_ID");
        }

        return NULL;
    }

    /**
     * Retrieves the column name that stores the ID of the user who last updated the record.
     *
     * @return string|null The name of the updated_by_user_id column, or null if not defined.
     */
    public function getUpdatedByUserIdColumn()
    {
        // get the Active Record class name
        $class = get_called_class();
        if(defined("{$class}::UPDATED_BY_USER_ID"))
        {
            // returns the UPDATED_BY_USER_ID Active Record class constant
            return constant("{$class}::UPDATED_BY_USER_ID");
        }

        return NULL;
    }

    /**
     * Retrieves the column name that stores the ID of the user who created the record.
     *
     * @return string|null The name of the created_by_user_id column, or null if not defined.
     */
    public function getCreatedByUserIdColumn()
    {
        // get the Active Record class name
        $class = get_called_class();
        if(defined("{$class}::CREATED_BY_USER_ID"))
        {
            // returns the CREATEDBYUSERID Active Record class constant
            return constant("{$class}::CREATED_BY_USER_ID");
        }

        return NULL;
    }

    /**
     * Retrieves the column name that stores the unit ID associated with the record creation.
     *
     * @return string|null The name of the created_by_unit_id column, or null if not defined.
     */
    public function getCreatedByUnitIdColumn()
    {
        // get the Active Record class name
        $class = get_called_class();
        if(defined("{$class}::CREATED_BY_UNIT_ID"))
        {
            // returns the CREATED_BY_UNIT_ID Active Record class constant
            return constant("{$class}::CREATED_BY_UNIT_ID");
        }

        return NULL;
    }
    
    /**
     * Retrieves the name of the sequence used for generating primary key values.
     *
     * If a sequence is explicitly defined for the class, it returns that sequence.
     * Otherwise, it generates a sequence name based on the table and primary key.
     *
     * @return string The name of the sequence.
     */
    private function getSequenceName()
    {
        $conn = TTransaction::get();
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        // get the Active Record class name
        $class = get_class($this);
        
        if (defined("{$class}::SEQUENCE"))
        {
            return constant("{$class}::SEQUENCE");
        }
        else if (in_array($driver, array('oci', 'oci8')))
        {
            return $this->getEntity().'_seq';
        }
        else
        {
            return $this->getEntity().'_'. $this->getPrimaryKey().'_seq';
        }
    }
    
    /**
     * Merges the attributes of another Active Record into this instance.
     *
     * @param TRecord $object The Active Record instance to merge from.
     *
     * @return void
     */
    public function mergeObject(TRecord $object)
    {
        $data = $object->toArray();
        foreach ($data as $key => $value)
        {
            $this->recordObjectIntartnalData[$key] = $value;
        }
    }
    
    /**
     * Populates the Active Record attributes from an associative array.
     *
     * @param array $data An associative array of attribute values.
     *
     * @return void
     */
    public function fromArray($data)
    {
        if (count($this->attributes) > 0)
        {
            $pk = $this->getPrimaryKey();
            foreach ($data as $key => $value)
            {
                // set just attributes defined by the addAttribute()
                if ((in_array($key, $this->attributes) AND is_string($key)) OR ($key === $pk))
                {
                    $this->recordObjectIntartnalData[$key] = $data[$key];
                }
            }
        }
        else
        {
            foreach ($data as $key => $value)
            {
                $this->recordObjectIntartnalData[$key] = $data[$key];
            }
        }
    }
    
    /**
     * Converts the Active Record into an associative array.
     *
     * @param array|null $filter_attributes Optional list of attributes to include.
     *
     * @return array The record data as an associative array.
     */
    public function toArray( $filter_attributes = null )
    {
        $attributes = $filter_attributes ? $filter_attributes : $this->attributes;
        
        $data = array();
        if (count($attributes) > 0)
        {
            $pk = $this->getPrimaryKey();
            if (!empty($this->recordObjectIntartnalData))
            {
                foreach ($this->recordObjectIntartnalData as $key => $value)
                {
                    if ((in_array($key, $attributes) AND is_string($key)) OR ($key === $pk))
                    {
                        $data[$key] = $this->recordObjectIntartnalData[$key];
                    }
                }
            }
        }
        else
        {
            $data = $this->recordObjectIntartnalData;
        }
        return $data;
    }
    
    /**
     * Retrieves virtual (non-persistent) properties of the object.
     *
     * @return array An array of virtual data.
     */
    public function getVirtualData()
    {
        return $this->vdata;
    }
    
    /**
     * Converts the Active Record into a JSON string.
     *
     * @return string The JSON representation of the record.
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Replaces placeholders in a string with corresponding object properties.
     *
     * @param string $pattern The string pattern with placeholders inside `{}`.
     * @param string|null $cast Optional type casting for the replaced values.
     *
     * @return string The formatted string with variables replaced.
     */
    public function render($pattern, $cast = null)
    {
        $content = $pattern;
        if (preg_match_all('/\{(.*?)\}/', (string) $pattern, $matches) )
        {
            foreach ($matches[0] as $match)
            {
                $property = substr($match, 1, -1);
                if (substr($property, 0, 1) == '$')
                {
                    $property = substr($property, 1);
                }
                $value = $this->$property;
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
     * Evaluates a mathematical expression containing object properties.
     *
     * @param string $pattern The expression pattern with placeholders.
     *
     * @return float|int The result of the mathematical evaluation.
     */
    public function evaluate($pattern)
    {
        $content = $this->render($pattern, 'float');
        $content = str_replace('+', ' + ', $content);
        $content = str_replace('-', ' - ', $content);
        $content = str_replace('*', ' * ', $content);
        $content = str_replace('/', ' / ', $content);
        $content = str_replace('(', ' ( ', $content);
        $content = str_replace(')', ' ) ', $content);
        
        // fix sintax for operator followed by signal
        foreach (['+', '-', '*', '/'] as $operator)
        {
            foreach (['+', '-'] as $signal)
            {
                $content = str_replace(" {$operator} {$signal} ", " {$operator} {$signal}", $content);
                $content = str_replace(" {$operator}  {$signal} ", " {$operator} {$signal}", $content);
                $content = str_replace(" {$operator}   {$signal} ", " {$operator} {$signal}", $content);
            }
        }
        
        $parser = new Parser;
        $content = $parser->evaluate(substr($content,1));
        return $content;
    }
    
    /**
     * Registers a new persisted attribute for the Active Record.
     *
     * @param string $attribute The name of the attribute.
     *
     * @return void
     */
    public function addAttribute($attribute)
    {
        // if ($attribute == 'data')
        // {
        //     throw new Exception(AdiantiCoreTranslator::translate('Reserved property name (^1) in class ^2', $attribute, get_class($this)));
        // }
        
        $this->attributes[] = $attribute;
    }
    
    /**
     * Retrieves the list of attributes that are persisted in the database.
     *
     * @return array The list of persisted attributes.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    /**
     * Retrieves the list of persisted attributes.
     *
     * @return string A comma-separated list of attributes, or `*` if no attributes are registered.
     */
    public function getAttributeList()
    {
        if (count($this->attributes) > 0)
        {
            $attributes = $this->attributes;
            array_unshift($attributes, $this->getPrimaryKey());
            return implode(', ', array_unique($attributes));
        }
        
        return '*';
    }
    
    /**
     * Stores the object in the database.
     *
     * If the object has no ID, it is inserted. Otherwise, it is updated.
     * Also handles UUIDs, serial IDs, timestamps, and user tracking.
     *
     * @return int The number of affected rows.
     * @throws Exception If there is no active database transaction.
     */
    public function store()
    {
        $conn = TTransaction::get();
        
        if (!$conn)
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
        
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        // get the Active Record class name
        $class = get_class($this);
        
        // check if the object has an ID or exists in the database
        $pk = $this->getPrimaryKey();
        $createdat = $this->getCreatedAtColumn();
        $createdby = $this->getCreatedByColumn();
        $updatedat = $this->getUpdatedAtColumn();
        $updatedby = $this->getUpdatedByColumn();
        $updatedbyuserid = $this->getUpdatedByUserIdColumn();
        $createdbyuserid = $this->getCreatedByUserIdColumn();
        $createdbyunitid = $this->getCreatedByUnitIdColumn();
        
        if (method_exists($this, 'onBeforeStore'))
        {
            $virtual_object = (object) $this->recordObjectIntartnalData;
            $this->onBeforeStore( $virtual_object );
            $this->recordObjectIntartnalData = (array) $virtual_object;
        }
        
        $this->canManage($this, $this->$pk, 'onBeforeStore');
        
        if (empty($this->recordObjectIntartnalData[$pk]) or (!self::exists($this->$pk)))
        {
            // increments the ID
            if (empty($this->recordObjectIntartnalData[$pk]))
            {
                if ((defined("{$class}::IDPOLICY")) AND (constant("{$class}::IDPOLICY") == 'serial'))
                {
                    unset($this->$pk);
                }
                else if ((defined("{$class}::IDPOLICY")) AND (constant("{$class}::IDPOLICY") == 'uuid'))
                {
                    $this->$pk = implode('-', [
                                     bin2hex(random_bytes(4)),
                                     bin2hex(random_bytes(2)),
                                     bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
                                     bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
                                     bin2hex(random_bytes(6))
                                 ]);
                }
                else
                {
                    $this->$pk = $this->getLastID() +1;
                }
            }
            // creates an INSERT instruction
            $sql = new TSqlInsert;
            $sql->setEntity($this->getEntity());
            // iterate the object data
            foreach ($this->recordObjectIntartnalData as $key => $value)
            {
                // check if the field is a calculated one
                if ( !method_exists($this, 'get_' . $key) OR (count($this->attributes) > 0) )
                {
                    if (count($this->attributes) > 0)
                    {
                        // set just attributes defined by the addAttribute()
                        if ((in_array($key, $this->attributes) AND is_string($key)) OR ($key === $pk))
                        {
                            // pass the object data to the SQL
                            $sql->setRowData($key, $this->recordObjectIntartnalData[$key]);
                        }
                    }
                    else
                    {
                        // pass the object data to the SQL
                        $sql->setRowData($key, $this->recordObjectIntartnalData[$key]);
                    }
                }
            }
            
            if (!empty($createdat))
            {
                $info = TTransaction::getDatabaseInfo();
                $date_mask = (in_array($info['type'], ['sqlsrv', 'dblib', 'mssql'])) ? 'Ymd H:i:s' : 'Y-m-d H:i:s';
                $sql->setRowData($createdat, date($date_mask));
            }
            
            if (!empty($createdby))
            {
                $sql->setRowData($createdby, TSession::getValue('login'));
            }
            if (!empty($createdbyuserid))
            {
                $sql->setRowData($createdbyuserid, TSession::getValue('userid'));
            }
            if (!empty($createdbyunitid))
            {
                $sql->setRowData($createdbyunitid, TSession::getValue('userunitid'));
            }
        }
        else
        {
            // creates an UPDATE instruction
            $sql = new TSqlUpdate;
            $sql->setEntity($this->getEntity());
            // creates a select criteria based on the ID
            $criteria = new TCriteria;
            $criteria->add(new TFilter($pk, '=', $this->$pk));
            $sql->setCriteria($criteria);
            // interate the object data
            foreach ($this->recordObjectIntartnalData as $key => $value)
            {
                if ($key !== $pk) // there's no need to change the ID value
                {
                    // check if the field is a calculated one
                    if ( !method_exists($this, 'get_' . $key) OR (count($this->attributes) > 0) )
                    {
                        if (count($this->attributes) > 0)
                        {
                            // set just attributes defined by the addAttribute()
                            if ((in_array($key, $this->attributes) AND is_string($key)) OR ($key === $pk))
                            {
                                // pass the object data to the SQL
                                $sql->setRowData($key, $this->recordObjectIntartnalData[$key]);
                            }
                        }
                        else
                        {
                            // pass the object data to the SQL
                            $sql->setRowData($key, $this->recordObjectIntartnalData[$key]);
                        }
                    }
                }
            }
            
            if (!empty($createdat))
            {
                $sql->unsetRowData($createdat);
            }
            
            if (!empty($updatedat))
            {
                $info = TTransaction::getDatabaseInfo();
                $date_mask = (in_array($info['type'], ['sqlsrv', 'dblib', 'mssql'])) ? 'Ymd H:i:s' : 'Y-m-d H:i:s';
                $sql->setRowData($updatedat, date($date_mask));
            }
            
            if (!empty($updatedby))
            {
                $sql->setRowData($updatedby, TSession::getValue('login'));
            }

            if (!empty($updatedbyuserid))
            {
                $sql->setRowData($updatedbyuserid, TSession::getValue('userid'));
            }
        }
        
        // register the operation in the LOG file
        TTransaction::log($sql->getInstruction());
        
        $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
        if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
        {
            $command = $sql->getInstruction( TRUE );
            
            if ($driver == 'firebird')
            {
                $command = str_replace('{{primary_key}}', $pk, $command);
            }
            else if ($driver == 'sqlsrv')
            {
                $command .= ";SELECT SCOPE_IDENTITY() as 'last_inserted_id'";
            }
            
            $result = $conn-> prepare ( $command , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $result-> execute ( $sql->getPreparedVars() );
        }
        else
        {
            $command = $sql->getInstruction();
            
            if ($driver == 'firebird')
            {
                $command = str_replace('{{primary_key}}', $pk, $command);
            }
            else if ($driver == 'sqlsrv')
            {
                $command .= ";SELECT SCOPE_IDENTITY() as 'last_inserted_id'";
            }
            
            // execute the query
            $result = $conn-> query($command);
        }
        TTransaction::logExecutionTime();
        
        if ((defined("{$class}::IDPOLICY")) AND (constant("{$class}::IDPOLICY") == 'serial'))
        {
            if ( ($sql instanceof TSqlInsert) AND empty($this->recordObjectIntartnalData[$pk]) )
            {
                if ($driver == 'firebird')
                {
                    $this->$pk = $result-> fetchColumn();
                }
                else if ($driver == 'sqlsrv')
                {
                    $result->nextRowset();
                    $this->$pk = $result-> fetchColumn();
                }
                else if (in_array($driver, array('oci', 'oci8')))
                {
                    $result_id = $conn-> query('SELECT ' . $this->getSequenceName() . ".currval FROM dual");
                    $this->$pk = $result_id-> fetchColumn();
                }
                else
                {
                    $this->$pk = $conn->lastInsertId( $this->getSequenceName() );
                }
            }
        }
        
        if ( $cache = $this->getCacheControl() )
        {
            $record_key = $class . '['. $this->$pk . ']';
            if ($cache::setValue( $record_key, $this->toArray() ))
            {
                TTransaction::log($record_key . ' stored in cache');
            }
        }
        
        if (method_exists($this, 'onAfterStore'))
        {
            $this->onAfterStore( (object) $this->toArray() );
        }
        
        // return the result of the exec() method
        return $result;
    }
    
    /**
     * Checks if a record with the given ID exists in the database.
     *
     * @param mixed $id The object ID.
     *
     * @return bool True if the record exists, false otherwise.
     * @throws Exception If there is no active transaction.
     */
    public function exists($id)
    {
        if (empty($id))
        {
            return FALSE;
        }
        
        $pk = $this->getPrimaryKey();  // discover the primary key name
        
        // creates a SELECT instruction
        $sql = new TSqlSelect;
        $sql->setEntity($this->getEntity());
        $sql->addColumn($this->getAttributeList());
        
        // creates a select criteria based on the ID
        $criteria = new TCriteria;
        $criteria->add(new TFilter($pk, '=', $id));

        $deletedat = self::getDeletedAtColumn();
        if (!$this->trashed && $deletedat)
        {
            $criteria->add(new TFilter($deletedat, 'IS', NULL));
        }

        // define the select criteria
        $sql->setCriteria($criteria);
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
            {
                $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $result-> execute ( $criteria->getPreparedVars() );
            }
            else
            {
                $result = $conn-> query($sql->getInstruction());
            }
            
            TTransaction::logExecutionTime();

            // if there's a result
            if ($result)
            {
                // returns the data as an object of this class
                $object = $result-> fetchObject();
            }
            
            return is_object($object);
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Reloads the current Active Record instance from the database.
     *
     * @return static|null The refreshed Active Record object or null if not found.
     * @throws Exception If there is no active transaction.
     */
    public function reload()
    {
        // discover the primary key name 
        $pk = $this->getPrimaryKey();
        
        return $this->load($this->$pk);
    }
    
    /**
     * Loads an Active Record object from the database.
     *
     * @param mixed $id The object ID.
     *
     * @return static|null The Active Record object or null if not found.
     * @throws Exception If there is no active transaction.
     */
    public function load($id)
    {
        $class = get_class($this);     // get the Active Record class name
        $pk = $this->getPrimaryKey();  // discover the primary key name
        
        if (method_exists($this, 'onBeforeLoad'))
        {
            $this->onBeforeLoad( $id );
        }
        
        if ( $cache = $this->getCacheControl() )
        {
            $record_key = $class . '['. $id . ']';
            if ($fetched_data = $cache::getValue( $record_key ))
            {
                $fetched_object = (object) $fetched_data;
                $loaded_object  = clone $this;
                
                if (method_exists($this, 'onAfterLoad'))
                {
                    $this->onAfterLoad( $fetched_object );
                    $loaded_object->fromArray( (array) $fetched_object);
                }
                else
                {
                    $loaded_object->fromArray($fetched_data);
                }
                
                $this->canManage($loaded_object, null, 'onAfterLoad');
                
                TTransaction::log($record_key . ' loaded from cache');
                return $loaded_object;
            }
        }
        
        // creates a SELECT instruction
        $sql = new TSqlSelect;
        $sql->setEntity($this->getEntity());
        // use *, once this is called before addAttribute()s
        $sql->addColumn($this->getAttributeList());
        
        // creates a select criteria based on the ID
        $criteria = new TCriteria;
        $criteria->add(new TFilter($pk, '=', $id));

        $deletedat = self::getDeletedAtColumn();
        if (!$this->trashed && $deletedat)
        {
            $criteria->add(new TFilter($deletedat, 'IS', NULL));
        }

        // define the select criteria
        $sql->setCriteria($criteria);
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
            {
                $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $result-> execute ( $criteria->getPreparedVars() );
            }
            else
            {
                // execute the query
                $result = $conn-> query($sql->getInstruction());
            }

            TTransaction::logExecutionTime();
            
            // if there's a result
            if ($result)
            {
                $activeClass = get_class($this);
                $fetched_object = $result-> fetchObject();
                if ($fetched_object)
                {   
                    if (method_exists($this, 'onAfterLoad'))
                    {
                        $this->onAfterLoad($fetched_object);
                    }
                    $object = new $activeClass;
                    $object->fromArray( (array) $fetched_object );
                    
                    $this->canManage($object, null, 'onAfterLoad');
                }
                else
                {
                    $object = NULL;
                }
                
                if ($object)
                {
                    if ( $cache = $this->getCacheControl() )
                    {
                        $record_key = $class . '['. $id . ']';
                        if ($cache::setValue( $record_key, $object->toArray() ))
                        {
                            TTransaction::log($record_key . ' stored in cache');
                        }
                    }
                }
            }
            
            return $object;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Loads a soft-deleted record from the database.
     *
     * This method allows retrieving a record that has been marked as deleted
     * using soft deletion (i.e., has a non-null `deleted_at` column).
     *
     * @param mixed $id The object ID to be loaded.
     *
     * @return static|null The loaded Active Record instance or null if not found.
     */
    public function loadTrashed($id)
    {
        $this->trashed = TRUE;
        return $this->load($id);
    }
    
    /**
     * Deletes an Active Record object from the database.
     *
     * Supports soft deletion if a `deleted_at` column is defined.
     *
     * @param mixed|null $id Optional object ID. If not provided, the current object's ID is used.
     *
     * @return mixed The result of the delete operation.
     * @throws Exception If there is no active transaction.
     */
    public function delete($id = NULL)
    {
        $class = get_class($this);
        
        if (method_exists($this, 'onBeforeDelete'))
        {
            $this->onBeforeDelete( (object) $this->toArray() );
        }
        
        $this->canManage($this, null, 'onBeforeDelete');
        
        // discover the primary key name
        $pk = $this->getPrimaryKey();
        // if the user has not passed the ID, take the object ID
        $id = $id ? $id : $this->$pk;

        $deletedat = self::getDeletedAtColumn();
        $deletedby = self::getDeletedByColumn();
        $deletedbyuserid = self::getDeletedByUserIdColumn();

        if ($deletedat)
        {
            // creates a Update instruction
            $sql = new TSqlUpdate;
            $sql->setEntity($this->getEntity());

            $info = TTransaction::getDatabaseInfo();
            $date_mask = (in_array($info['type'], ['sqlsrv', 'dblib', 'mssql'])) ? 'Ymd H:i:s' : 'Y-m-d H:i:s';
            $sql->setRowData($deletedat, date($date_mask));
            if($deletedby)
            {
                $sql->setRowData($deletedby, TSession::getValue('login'));
            }
            
            if($deletedbyuserid)
            {
                $sql->setRowData($deletedbyuserid, TSession::getValue('userid'));
            }
        }
        else
        {
            // creates a DELETE instruction
            $sql = new TSqlDelete;
            $sql->setEntity($this->getEntity());
        }

        // creates a select criteria
        $criteria = new TCriteria;
        $criteria->add(new TFilter($pk, '=', $id));
        // assign the criteria to the delete instruction
        $sql->setCriteria($criteria);
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            if (isset($dbinfo['prep']) AND $dbinfo['prep'] == '1') // prepared ON
            {
                $result = $conn-> prepare ( $sql->getInstruction( TRUE ) , array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                if ($sql instanceof TSqlUpdate)
                {
                    $result-> execute ($sql->getPreparedVars());
                }
                else
                {
                    $result-> execute ($criteria->getPreparedVars());
                }
            }
            else
            {
                // execute the query
                $result = $conn-> query($sql->getInstruction());
            }
            
            TTransaction::logExecutionTime();

            if ( $cache = $this->getCacheControl() )
            {
                $record_key = $class . '['. $id . ']';
                if ($cache::delValue( $record_key ))
                {
                    TTransaction::log($record_key . ' deleted from cache');
                }
            }
            
            if (method_exists($this, 'onAfterDelete'))
            {
                $this->onAfterDelete( (object) $this->toArray() );
            }
            
            unset($this->recordObjectIntartnalData);
            
            // return the result of the exec() method
            return $result;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    /**
     * Restores a soft-deleted record.
     *
     * @return static The restored Active Record instance.
     * @throws Exception If soft deletion is not enabled for this entity.
     */
    public function restore()
    {
        $deletedat = self::getDeletedAtColumn();
        
        if ($deletedat)
        {
            $pk = $this->getPrimaryKey();
            $this->withTrashed()->where($pk, '=', $this->$pk)->set($deletedat, null)->update();
            
            return $this;
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('Softdelete is not active') . ' : '. $this->getEntity());
        }
    }

    /**
     * Retrieves the first object ID from the database.
     *
     * @return int|null The first object ID, or null if no records exist.
     * @throws Exception If there is no active database transaction.
     */
    public function getFirstID()
    {
        $pk = $this->getPrimaryKey();
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            // instancia instrução de SELECT
            $sql = new TSqlSelect;
            $sql->addColumn("min({$pk}) as {$pk}");
            $sql->setEntity($this->getEntity());
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            $result= $conn->Query($sql->getInstruction());
            TTransaction::logExecutionTime();
            // retorna os dados do banco
            $row = $result-> fetch();
            return $row[0];
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Retrieves the last object ID from the database.
     *
     * @return int|null The last object ID, or null if no records exist.
     * @throws Exception If there is no active database transaction.
     */
    public function getLastID()
    {
        $pk = $this->getPrimaryKey();
        
        // get the connection of the active transaction
        if ($conn = TTransaction::get())
        {
            // instancia instrução de SELECT
            $sql = new TSqlSelect;
            $sql->addColumn("max({$pk}) as {$pk}");
            $sql->setEntity($this->getEntity());
            // register the operation in the LOG file
            TTransaction::log($sql->getInstruction());
            $result= $conn->Query($sql->getInstruction());
            TTransaction::logExecutionTime();
            // retorna os dados do banco
            $row = $result-> fetch();
            return $row[0];
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
    
    /**
     * Retrieves multiple Active Record objects based on a criteria.
     *
     * @param TCriteria|null $criteria Optional filtering criteria.
     * @param bool $callObjectLoad Whether to invoke the load method on each object.
     * @param bool $withTrashed Whether to include soft-deleted records.
     *
     * @return array The retrieved objects.
     */
    public static function getObjects($criteria = NULL, $callObjectLoad = TRUE, $withTrashed = FALSE)
    {
        // get the Active Record class name
        $class = get_called_class();
        
        // create the repository
        $repository = new TRepository($class, $withTrashed);
        
        if (!$criteria)
        {
            $criteria = new TCriteria;
        }
        
        return $repository->load( $criteria, $callObjectLoad );
    }
    
    /**
     * Counts the number of records that match the given criteria.
     *
     * @param TCriteria|null $criteria Optional filtering criteria.
     * @param bool $withTrashed Whether to include soft-deleted records in the count.
     * 
     * @return int The count of matching records.
     */
    public static function countObjects($criteria = NULL, $withTrashed = FALSE)
    {
        // get the Active Record class name
        $class = get_called_class();
        
        // create the repository
        $repository = new TRepository($class, $withTrashed);
        if (!$criteria)
        {
            $criteria = new TCriteria;
        }
        
        return $repository->count( $criteria );
    }
    
    /**
     * Loads related records in a composition relationship.
     *
     * @param string $composite_class The class name of the related records.
     * @param string $foreign_key The foreign key column linking the records.
     * @param mixed|null $id The primary key of the parent object.
     * @param string|null $order The order clause for the related records.
     *
     * @return array An array of related records.
     */
    public function loadComposite($composite_class, $foreign_key, $id = NULL, $order = NULL)
    {
        $pk = $this->getPrimaryKey(); // discover the primary key name
        $id = $id ? $id : $this->$pk; // if the user has not passed the ID, take the object ID
        $criteria = TCriteria::create( [$foreign_key => $id ], ['order' => $order] );
        $repository = new TRepository($composite_class);
        return $repository->load($criteria);
    }
    
    /**
     * Shortcut for loading related records in a composition relationship.
     *
     * @param string $composite_class The class name of the related records.
     * @param string|null $foreign_key The foreign key column (optional).
     * @param string|null $primary_key The primary key of the parent object (optional).
     * @param string|null $order The order clause (optional).
     *
     * @return array An array of related records.
     */
    public function hasMany($composite_class, $foreign_key = NULL, $primary_key = NULL, $order = NULL)
    {
        $foreign_key = isset($foreign_key) ? $foreign_key : $this->underscoreFromCamelCase(get_class($this)) . '_id';
        $primary_key = $primary_key ? $primary_key : $this->getPrimaryKey();
        return $this->loadComposite($composite_class, $foreign_key, $this->$primary_key, $order);
    }
    
    /**
     * Creates a repository query to filter related records.
     *
     * This method is useful for retrieving related records based on a relationship
     * without immediately loading them into memory.
     *
     * @param string $composite_class The class name of the related records.
     * @param string|null $foreign_key The foreign key column in the related records (optional).
     * @param string|null $primary_key The primary key of the parent record (optional).
     * @param string|null $order The order clause for sorting the results (optional).
     *
     * @return TRepository The repository instance with the applied filter.
     */
    public function filterMany($composite_class, $foreign_key = NULL, $primary_key = NULL, $order = NULL)
    {
        $foreign_key = isset($foreign_key) ? $foreign_key : $this->underscoreFromCamelCase(get_class($this)) . '_id';
        $primary_key = $primary_key ? $primary_key : $this->getPrimaryKey();
        
        $criteria = TCriteria::create( [$foreign_key => $this->$primary_key ], ['order' => $order] );
        $repository = new TRepository($composite_class);
        $repository->setCriteria($criteria);
        return $repository;
    }
    
    /**
     * Deletes related records in a composition relationship.
     *
     * @param string $composite_class The class name of the related records.
     * @param string $foreign_key The foreign key column linking the records.
     * @param mixed $id The primary key of the parent object.
     * @param bool $callObjectLoad Whether to call the `load` method before deletion.
     *
     * @return mixed The result of the delete operation.
     */
    public function deleteComposite($composite_class, $foreign_key, $id, $callObjectLoad = FALSE)
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter($foreign_key, '=', $id));
        
        $repository = new TRepository($composite_class);
        return $repository->delete($criteria, $callObjectLoad);
    }
    
    /**
     * Saves related records in a composition relationship.
     *
     * @param string $composite_class The class name of the related records.
     * @param string $foreign_key The foreign key column linking the records.
     * @param mixed $id The primary key of the parent object.
     * @param array $objects The array of related objects to be saved.
     * @param bool $callObjectLoad Whether to call the `load` method before saving.
     *
     * @return void
     */
    public function saveComposite($composite_class, $foreign_key, $id, $objects, $callObjectLoad = FALSE)
    {
        $this->deleteComposite($composite_class, $foreign_key, $id, $callObjectLoad);
        
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $object-> $foreign_key  = $id;
                $object->store();
            }
        }
    }
    
    /**
     * Loads related records in an aggregation relationship.
     *
     * Aggregation differs from composition in that the related objects can exist independently
     * of the parent object. This method retrieves related objects by joining through an intermediate table.
     *
     * @param string $aggregate_class The class name of the related objects.
     * @param string $join_class The class name of the join table.
     * @param string $foreign_key_parent The foreign key column linking the parent object in the join table.
     * @param string $foreign_key_child The foreign key column linking the child objects in the join table.
     * @param mixed|null $id The primary key of the parent object (optional).
     *
     * @return array An array of related objects.
     */
    public function loadAggregate($aggregate_class, $join_class, $foreign_key_parent, $foreign_key_child, $id = NULL)
    {
        // discover the primary key name
        $pk = $this->getPrimaryKey();
        // if the user has not passed the ID, take the object ID
        $id = $id ? $id : $this->$pk;
        
        $criteria   = new TCriteria;
        $criteria->add(new TFilter($foreign_key_parent, '=', $id));
        
        $repository = new TRepository($join_class);
        $objects = $repository->load($criteria);
        
        $aggregates = array();
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $aggregates[] = new $aggregate_class($object-> $foreign_key_child);
            }
        }
        return $aggregates;
    }
    
    /**
     * Loads related records in a many-to-many aggregation relationship.
     *
     * This is a shortcut for `loadAggregate` that assumes a conventional join table name
     * and foreign key column names if they are not explicitly provided.
     *
     * @param string $aggregate_class The class name of the related objects.
     * @param string|null $join_class The class name of the join table (optional).
     * @param string|null $foreign_key_parent The foreign key column in the join table for the parent object (optional).
     * @param string|null $foreign_key_child The foreign key column in the join table for the child objects (optional).
     *
     * @return array An array of related objects.
     */
    public function belongsToMany($aggregate_class, $join_class = NULL, $foreign_key_parent = NULL, $foreign_key_child = NULL)
    {
        $class = get_class($this);
        $join_class = isset($join_class) ? $join_class : $class.$aggregate_class;
        $foreign_key_parent = isset($foreign_key_parent) ? $foreign_key_parent : $this->underscoreFromCamelCase($class) . '_id';
        $foreign_key_child  = isset($foreign_key_child)  ? $foreign_key_child  : $this->underscoreFromCamelCase($aggregate_class) . '_id';
        
        return $this->loadAggregate($aggregate_class, $join_class, $foreign_key_parent, $foreign_key_child);
    }
    
    /**
     * Saves related records in an aggregation relationship.
     *
     * This method ensures that only the specified related records are linked to the
     * parent object by first deleting any existing relationships and then inserting new ones.
     *
     * @param string $join_class The class name of the join table.
     * @param string $foreign_key_parent The foreign key column linking the parent object in the join table.
     * @param string $foreign_key_child The foreign key column linking the child objects in the join table.
     * @param mixed $id The primary key of the parent object.
     * @param array $objects An array of related Active Record objects to be linked.
     *
     * @return void
     */
    public function saveAggregate($join_class, $foreign_key_parent, $foreign_key_child, $id, $objects)
    {
        $this->deleteComposite($join_class, $foreign_key_parent, $id);
        
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $join = new $join_class;
                $join-> $foreign_key_parent = $id;
                $join-> $foreign_key_child  = $object->id;
                $join->store();
            }
        }
    }
    
    /**
     * Retrieves the first record in the database.
     *
     * @param bool $withTrashed Whether to include soft-deleted records.
     *
     * @return static|null The first record or null if none found.
     */
    public static function first($withTrashed = FALSE)
    {
        $object = new static;
        $id = $object->getFirstID();

        return self::find($id, $withTrashed);
    }
    
    /**
     * Retrieves the first record matching the given criteria or creates a new instance.
     *
     * If no record is found, this method returns a new instance with the given attributes.
     * The new instance is not persisted to the database.
     *
     * @param array|null $filters Optional associative array of filter conditions.
     *
     * @return static An existing record matching the filters, or a new instance.
     */
    public static function firstOrNew($filters = NULL)
    {
        $criteria = TCriteria::create($filters);
        $criteria->setProperty('limit', 1);
        $objects = self::getObjects( $criteria );
        
        if (isset($objects[0]))
        {
            return $objects[0];
        }
        else
        {
            $created = new static;
            if (is_array($filters))
            {
                $created->fromArray($filters);
            }
            return $created;
        }
    }
    
    /**
     * Retrieves the first record matching the given criteria or creates and persists a new instance.
     *
     * If no record is found, a new instance is created with the given attributes and stored in the database.
     *
     * @param array|null $filters Optional associative array of filter conditions.
     *
     * @return static An existing record matching the filters, or a newly created and persisted instance.
     */
    public static function firstOrCreate($filters = NULL)
    {
        $obj = self::firstOrNew($filters);
        $obj->store();
        return $obj;
    }
    
    /**
     * Retrieves the last record in the database.
     *
     * @param bool $withTrashed Whether to include soft-deleted records.
     *
     * @return static|null The last record or null if none found.
     */
    public static function last($withTrashed = FALSE)
    {
        $object = new static;
        $id = $object->getLastID();

        return self::find($id, $withTrashed);
    }
    
    /**
     * Finds and retrieves an Active Record instance by its ID.
     *
     * @param mixed $id The object ID.
     * @param bool $withTrashed Whether to include soft-deleted records.
     *
     * @return static|null The found Active Record instance or null if not found.
     */
    public static function find($id, $withTrashed = FALSE)
    {
        $classname = get_called_class();
        $ar = new $classname;
        
        if ($withTrashed)
        {
            return $ar->loadTrashed($id);
        }
        else
        {
            return $ar->load($id);
        }
    }
    
    /**
     * Retrieves all records from the database.
     *
     * @param bool $indexed Whether to index the array by the primary key.
     * @param bool $withTrashed Whether to include soft-deleted records.
     *
     * @return array An array of Active Record objects.
     */
    public static function all($indexed = false, $withTrashed = FALSE)
    {
        $objects = self::getObjects(NULL, FALSE, $withTrashed);
        if ($indexed)
        {
            $list = [];
            foreach ($objects as $object)
            {
                $pk = $object->getPrimaryKey();
                $list[ $object->$pk ] = $object;
            }
            return $list;
        }
        else
        {
            return $objects;
        }
    }
    
    /**
     * Saves the Active Record instance.
     *
     * @return void
     */
    public function save()
    {
        $this->store();
    }
    
    /**
     * Creates an indexed array from the database.
     *
     * @param string $indexColumn The column used for array keys.
     * @param string $valueColumn The column used for array values.
     * @param TCriteria|null $criteria Optional filtering criteria.
     * @param bool $withTrashed Whether to include soft-deleted records.
     *
     * @return array An indexed array where the key is from `$indexColumn` and the value is from `$valueColumn`.
     */
    public static function getIndexedArray($indexColumn, $valueColumn, $criteria = NULL, $withTrashed = FALSE)
    {
        $sort_array = false;
        
        if (empty($criteria))
        {
            $criteria = new TCriteria;
            $sort_array = true;
        }
        
        $indexedArray = array();
        $class = get_called_class(); // get the Active Record class name
        $repository = new TRepository($class, $withTrashed); // create the repository
        $objects = $repository->load($criteria, FALSE);
        if ($objects)
        {
            foreach ($objects as $object)
            {
                $key = (isset($object->$indexColumn)) ? $object->$indexColumn : $object->render($indexColumn);
                $val = (isset($object->$valueColumn)) ? $object->$valueColumn : $object->render($valueColumn);
                
                $indexedArray[ $key ] = $val;
            }
        }
        
        if ($sort_array)
        {
            asort($indexedArray);
        }
        return $indexedArray;
    }
    
    /**
     * Creates a repository query with filters.
     *
     * @return TRepository The repository instance.
     */
    public static function select()
    {
        $repository = new TRepository( get_called_class() ); // create the repository
        return $repository->select( func_get_args() );
    }
    
    /**
     * Groups the repository query results by a specified column.
     *
     * @param string $group The column to group by.
     *
     * @return TRepository The repository instance.
     */
    public static function groupBy($group)
    {
        $repository = new TRepository( get_called_class() ); // create the repository
        return $repository->groupBy($group);
    }
    
    /**
     * Filters the repository query results by a condition.
     *
     * @param string $variable The column to filter.
     * @param string $operator The comparison operator (>, <, =, !=, <=, >=, IN, NOT IN, LIKE, IS NULL, IS NOT NULL).
     * @param mixed $value The value to compare against.
     * @param string $logicOperator The logical operator (TExpression::AND_OPERATOR, TExpression::OR_OPERATOR).
     *
     * @return TRepository The repository instance.
     */
    public static function where($variable, $operator, $value, $logicOperator = TExpression::AND_OPERATOR)
    {
        $repository = new TRepository( get_called_class() ); // create the repository
        return $repository->where($variable, $operator, $value, $logicOperator);
    }
    
    /**
     * Adds an OR condition to the repository query filter.
     *
     * @param string $variable The column to filter.
     * @param string $operator The comparison operator.
     * @param mixed $value The value to compare against.
     *
     * @return TRepository The repository instance.
     */
    public static function orWhere($variable, $operator, $value)
    {
        $repository = new TRepository( get_called_class() ); // create the repository
        return $repository->orWhere($variable, $operator, $value);
    }
    
    /**
     * Orders the repository query results by a specified column.
     *
     * @param string $order The column to order by.
     * @param string $direction The sorting direction (asc or desc).
     *
     * @return TRepository The repository instance.
     */
    public static function orderBy($order, $direction = 'asc')
    {
        $repository = new TRepository( get_called_class() ); // create the repository
        return $repository->orderBy( $order, $direction );
    }
    
    /**
     * Limits the number of results returned in the query.
     *
     * @param int $limit The maximum number of results.
     *
     * @return TRepository The repository instance.
     */
    public static function take($limit)
    {
        $repository = new TRepository( get_called_class() ); // create the repository
        return $repository->take($limit);
    }
    
    /**
     * Skips a number of results in the query.
     *
     * @param int $offset The number of records to skip.
     *
     * @return TRepository The repository instance.
     */
    public static function skip($offset)
    {
        $repository = new TRepository( get_called_class() ); // create the repository
        return $repository->skip($offset);
    }

    /**
     * Includes soft-deleted records in the query.
     *
     * @return TRepository The repository instance.
     */
    public static function withTrashed()
    {
        return new TRepository(get_called_class(), TRUE);
    }

    /**
     * Converts a camelCase string to an underscore_case string.
     *
     * @param string $string The camelCase string.
     *
     * @return string The converted underscore_case string.
     */
    private function underscoreFromCamelCase($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$'.'1_$'.'2', $string)); 
    }
}
