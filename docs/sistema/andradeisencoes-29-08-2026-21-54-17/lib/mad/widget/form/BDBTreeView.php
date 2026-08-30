<?php
namespace Mad\Widget\Form;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TConnection;
use Adianti\Database\TCriteria;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Mad\Widget\Form\BTreeView;

/**
 * @version    4.0
 * @package    widget
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 Mad Solutions Ltd. (http://www.madbuilder.com.br)
 */

class BDBTreeView extends BTreeView
{
    private $model;
    private $key;
    private $key_value;
    private $groups;
    private $ordercolumn;
    private $criteria;
    
    /**
     * BDBTreeView constructor.
     *
     * Initializes the tree view with database information, model, keys, and optional grouping.
     *
     * @param string     $name        The name of the tree view component.
     * @param string     $database    The database connection name.
     * @param string     $model       The model class name.
     * @param string     $key         The primary key field name.
     * @param string     $key_value   The display value field name.
     * @param array      $groups      Optional group mappings as an associative array.
     * @param string|null $ordercolumn The column used for ordering the results.
     * @param TCriteria|null $criteria Optional criteria for filtering the model data.
     */
    public function __construct($name, $database, $model, $key, $key_value, $groups = [], $ordercolumn = NULL, ?TCriteria $criteria = NULL)
    {
        parent::__construct($name);
        
        $this->database = $database;
        $this->model = $model;
        $this->key = $key;
        $this->key_value = $key_value;
        $this->groups = $groups ?? [];
        $this->criteria = $criteria;
        $this->ordercolumn = $ordercolumn;
    }
    
    /**
     * Adds a new group to the tree view.
     *
     * @param string $groupKey   The field name to group by.
     * @param string $groupValue The display value for the group.
     */
    public function addGroup($groupKey, $groupValue)
    {
        $this->groups[] = [$groupKey => $groupValue];
    }
    
    /**
     * Sets the groups for the tree view.
     *
     * @param array $groups An associative array defining the grouping structure.
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }
    
    /**
     * Retrieves the defined groups for the tree view.
     *
     * @return array The array of defined groups.
     */
    public function getGroups()
    {
        return $this->groups;
    }
    
    /**
     * Organizes data into hierarchical groups based on the provided grouping structure.
     *
     * @param array $items  The existing items in the tree.
     * @param object $object The object being processed.
     * @param array $groups The grouping structure.
     *
     * @return array The updated tree structure with the grouped data.
     */
    private function makeGroups($items, $object, $groups)
    {
        $key = (isset($object->{$this->key})) ? $object->{$this->key} : $object->render($this->key);
        $value = (isset($object->{$this->key_value})) ? $object->{$this->key_value} : $object->render($this->key_value);
        
        $arrayItem = $this->recursiveGroup($object, [], $groups, $key, $value);
        
        return array_replace_recursive($items, $arrayItem);
    }
    
    /**
     * Recursively groups data into a nested tree structure.
     *
     * @param object $object    The object being processed.
     * @param array  $arrayItem The current tree structure.
     * @param array  $groups    The remaining grouping fields.
     * @param string $k         The key identifier for the current object.
     * @param string $v         The display value for the current object.
     *
     * @return array The updated tree structure with grouped data.
     */
    public function recursiveGroup($object, $arrayItem, $groups, $k, $v)
    {
        if (empty($groups))
        {
            $arrayItem["btreekey_{$k}"] = [$v, $object];
            return $arrayItem;
        }
        
        $groupkey = key($groups);
        $groupvalue = $groups[$groupkey];
        unset($groups[$groupkey]);
        
        $gk = (isset($object->{$groupkey})) ? $object->{$groupkey} : $object->render($groupkey);
        $gv = (isset($object->{$groupvalue})) ? $object->{$groupvalue} : $object->render($groupvalue);
        
        $arrayItem['btreekey_' . $gk ] = [
            'label' => $gv,
            'object' => $object,
            'items' => $this->recursiveGroup($object, $arrayItem, $groups, $k, $v)
        ];
        
        return $arrayItem;
    }
    
    /**
     * Retrieves items from the database model and organizes them into the tree structure.
     *
     * @throws Exception If the required parameters (database, model, key, key_value) are missing.
     * @return array The structured tree items retrieved from the model.
     */
    public function getItemsFromModel()
    {
        $items = [];
        
        if (empty($this->database))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'database', __CLASS__));
        }
        
        if (empty($this->model))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'model', __CLASS__));
        }
        
        if (empty($this->key))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'key', __CLASS__));
        }
        
        if (empty($this->key_value))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', 'value', __CLASS__));
        }
        
        $cur_conn = serialize(TTransaction::getDatabaseInfo());
        $new_conn = serialize(TConnection::getDatabaseInfo($this->database));
        
        $open_transaction = ($cur_conn !== $new_conn);
        
        if ($open_transaction)
        {
            TTransaction::openFake($this->database);
        }
        
        // creates repository
        $repository = new TRepository($this->model);
        if (is_null($this->criteria))
        {
            $this->criteria = new TCriteria;
        }
        
        $this->criteria->setProperty('order', isset($this->ordercolumn) ? $this->ordercolumn : $this->key);
        
        // load all objects
        $collection = $repository->load($this->criteria, FALSE);
        
        // add objects to the options
        if ($collection)
        {
            foreach ($collection as $object)
            {
                $items = $this->makeGroups($items, $object, $this->groups);
            }
            
            self::sort($items);
        }
        
        if ($open_transaction)
        {
            TTransaction::close();
        }
        
        return $items;
    }
    
    /**
     * Reloads the tree view data from the database model and updates the form field.
     *
     * @param string      $formname   The name of the form containing the tree view.
     * @param string      $name       The name of the tree view component.
     * @param string      $database   The database connection name.
     * @param string      $model      The model class name.
     * @param string      $key        The primary key field name.
     * @param string      $key_value  The display value field name.
     * @param array       $groups     Optional group mappings.
     * @param string|null $ordercolumn The column used for ordering the results.
     * @param TCriteria|null $criteria Optional criteria for filtering the data.
     * @param array       $options    Additional options for rendering the tree.
     */
    public static function reloadFromModel($formname, $name, $database, $model, $key, $key_value, $groups = [], $ordercolumn = NULL, ?TCriteria $criteria = NULL, $options = [])
    {
        $field = new self($name, $database, $model, $key, $key_value, $groups, $ordercolumn, $criteria, $options);
        $items = $field->getItemsFromModel();
        
        self::reload($formname, $name, $items, $options);
    }
    
    /**
     * Displays the tree view by setting the retrieved model data.
     */
    public function show()
    {
        $this->setItems($this->getItemsFromModel());
       
        parent::show();
    }
}
