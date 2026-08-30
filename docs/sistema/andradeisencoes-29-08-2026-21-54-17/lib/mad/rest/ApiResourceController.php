<?php

namespace Mad\Rest;

use Adianti\Database\TRecord;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Exception;

/**
 * ApiResourceController
 * 
 * Base controller for RESTful API resources that extends Adianti's TRecord.
 * Provides standardized CRUD operations, filtering, pagination and search capabilities.
 * Supports master-detail relationships for nested data structures.
 */
abstract class ApiResourceController
{
    /**
     * The model class name
     * 
     * @var string
     */
    protected $model;

    /**
     * The database connection name
     * 
     * @var string
     */
    protected $database;

    /**
     * The primary key field name
     * 
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Fields that can be filtered
     * 
     * @var array
     */
    protected $searchable = [];

    /**
     * Fields that can be sorted
     * 
     * @var array
     */
    protected $sortable = [];

    /**
     * Fields to include in index response
     * If empty, all fields are included
     * 
     * @var array
     */
    protected $indexFields = [];

    /**
     * Fields to include in show response
     * If empty, all fields are included
     * 
     * @var array
     */
    protected $showFields = [];

    /**
     * Fields to always exclude from responses
     * 
     * @var array
     */
    protected $hiddenFields = [];

    /**
     * Default number of items per page
     * 
     * @var int
     */
    protected $perPage = 15;
    
    /**
     * Detail model class name (for master-detail relationships)
     * 
     * @var string
     */
    protected $detailModel = null;
    
    /**
     * Foreign key field in detail model that references the master model
     * 
     * @var string
     */
    protected $detailForeignKey = null;
    
    /**
     * Name of the property in request/response to hold detail records
     * 
     * @var string
     */
    protected $detailProperty = 'details';
    
    /**
     * Fields to include in detail records response
     * If empty, all fields are included
     * 
     * @var array
     */
    protected $detailIndexFields = [];
    
    /**
     * Required fields for validation before saving
     * Format: field_name => Field Label
     * 
     * @var array
     */
    protected $requiredFields = [];
    
    /**
     * Required fields for detail records validation before saving
     * Format: field_name => Field Label
     * 
     * @var array
     */
    protected $requiredDetailFields = [];
    
    /**
     * Field transformers for main record
     * Format: field_name => function($value, $object){...}
     * 
     * @var array
     */
    protected $transformers = [];
    
    /**
     * Field transformers for detail records
     * Format: field_name => function($value, $object){...}
     * 
     * @var array
     */
    protected $detailTransformers = [];

    /**
     * Create a new resource controller instance
     */
    public function __construct()
    {
        if (!isset($this->model)) {
            throw new Exception('Model class name must be defined in the controller');
        }

        if (!isset($this->database)) {
            throw new Exception('Database connection name must be defined in the controller');
        }
    }

    /**
     * Get a paginated list of resources
     * 
     * @param Request $request
     * @return string JSON response
     */
    public function index(Request $request)
    {
        try {
            TTransaction::open($this->database);
            $criteria = $this->buildCriteria($request);
            $repository = new TRepository($this->model);
            
            $count = $repository->count($criteria);
            
            $this->addPagination($request, $criteria);
            $collection = $repository->load($criteria);
            
            $data = [];
            if ($collection) {
                foreach ($collection as $object) {
                    
                    $indexData = $this->prepareItemForResponse($object, 'index');

                    // Carregar os detalhes para cada item, se aplicável
                    if ($this->hasDetails()) {
                        $indexData[$this->detailProperty] = $this->loadDetails($object);
                    }
                    $data[] = $indexData;
                }
            }
            
            $response = [
                'data' => $data,
                'meta' => [
                    'total' => $count,
                    'per_page' => $this->perPage,
                    'current_page' => $request->get('page', 1),
                    'last_page' => ceil($count / $this->perPage)
                ]
            ];
            
            TTransaction::close();
            
            return (new Response())->json($response);
        }
        catch (Exception $e) {
            TTransaction::rollback();
            return (new Response())->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Determine if this controller has detail handling enabled
     * 
     * @return bool
     */
    protected function hasDetails(): bool
    {
        return !empty($this->detailModel) && !empty($this->detailForeignKey);
    }
    
    /**
     * Get a single resource
     * 
     * @param Request $request
     * @return string JSON response
     */
    public function show(Request $request)
    {
        try {
            TTransaction::open($this->database);
            
            $id = $request->get($this->primaryKey);

            if (!$id) {
                throw new Exception("{$this->primaryKey} parameter is required");
            }
            
            $object = new $this->model($id);
            if (!isset($object->{$this->primaryKey})) {
                return (new Response())->json(['error' => 'Record not found'], 404);
            }
            
            $response = $this->prepareItemForResponse($object, 'show');
            
            // Include details if available
            if ($this->hasDetails()) {
                $response[$this->detailProperty] = $this->loadDetails($object);
            }
            
            TTransaction::close();
            
            return (new Response())->json($response);
        }
        catch (Exception $e) {
            TTransaction::rollback();
            return (new Response())->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new resource
     * 
     * @param Request $request
     * @return string JSON response
     */
    public function store(Request $request)
    {
        try {
            TTransaction::open($this->database);
            
            $data = $request->getParams();
            $object = new $this->model();
            
            // Process master data (excluding details)
            $masterData = $data;
            $details = null;
            
            // Extract details if they exist
            if ($this->hasDetails() && isset($data[$this->detailProperty])) {
                $details = $data[$this->detailProperty];
                unset($masterData[$this->detailProperty]);
            }
            
            // Validate required fields
            $this->validateRequiredFields($masterData);
            
            $object->fromArray((array) $masterData);
            
            if (method_exists($this, 'beforeStore')) {
                $this->beforeStore($object, $masterData);
            }
            
            // Save the master record
            $object->store();
            
            if (method_exists($this, 'afterStore')) {
                $this->afterStore($object, $masterData);
            }
            
            // Process details if they exist
            if ($this->hasDetails() && $details) {
                // Validate detail required fields
                if (!empty($this->requiredDetailFields)) {
                    foreach ($details as $index => $detailData) {
                        $detailData[$this->detailForeignKey] = $object->{$this->primaryKey};

                        $this->validateRequiredFields($detailData, $this->requiredDetailFields, "Detail record #" . ($index + 1) . ": ");
                    }
                }
                
                $this->saveDetails($object, $details);
            }
            
            // Prepare response
            $response = $this->prepareItemForResponse($object, 'show');
            
            // Include details in the response if necessary
            if ($this->hasDetails()) {
                $response[$this->detailProperty] = $this->loadDetails($object);
            }
            
            TTransaction::close();
            
            return (new Response())->json($response);
        }
        catch (Exception $e) {
            TTransaction::rollback();
            return (new Response())->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a resource
     * 
     * @param Request $request
     * @return string JSON response
     */
    public function update(Request $request)
    {
        try {
            TTransaction::open($this->database);
            
            $id = $request->get($this->primaryKey);
            if (!$id) {
                throw new Exception("{$this->primaryKey} parameter is required");
            }
            
            $object = new $this->model($id);
            if (!isset($object->{$this->primaryKey})) {
                return (new Response())->json(['error' => 'Resource not found'], 404);
            }

            $data = $request->getParams();
            
            // Process master data (excluding details)
            $masterData = $data;
            $details = null;
            
            // Extract details if they exist
            if ($this->hasDetails() && isset($data[$this->detailProperty])) {
                $details = $data[$this->detailProperty];
                unset($masterData[$this->detailProperty]);
            }
            
            // Validate required fields
            $this->validateRequiredFields($masterData);
            
            $object->fromArray((array) $masterData);
            
            // Save the master record
            $object->store();
            
            // Process details if they exist
            if ($this->hasDetails() && $details) {
                // Validate detail required fields
                if (!empty($this->requiredDetailFields)) {
                    foreach ($details as $index => $detailData) {
                        $detailData[$this->detailForeignKey] = $object->{$this->primaryKey};

                        $this->validateRequiredFields($detailData, $this->requiredDetailFields, "Detail record #" . ($index + 1) . ": ");
                    }
                }
                
                $this->saveDetails($object, $details);
            }
            
            // Prepare response
            $response = $this->prepareItemForResponse($object, 'show');
            
            // Include details in the response if necessary
            if ($this->hasDetails()) {
                $response[$this->detailProperty] = $this->loadDetails($object);
            }
            
            TTransaction::close();
            
            return (new Response())->json($response);
        }
        catch (Exception $e) {
            TTransaction::rollback();
            return (new Response())->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a resource
     * 
     * @param Request $request
     * @return string JSON response
     */
    public function destroy(Request $request)
    {
        try {
            TTransaction::open($this->database);
            
            $id = $request->get($this->primaryKey);
            if (!$id) {
                throw new Exception("{$this->primaryKey} parameter is required");
            }
            
            $object = new $this->model($id);
            if (!$object) {
                return (new Response())->json(['error' => 'Resource not found'], 404);
            }
            
            $object->delete();
            
            TTransaction::close();
            
            return (new Response())->json(['message' => 'Resource deleted successfully']);
        }
        catch (Exception $e) {
            TTransaction::rollback();
            return (new Response())->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add a transformer for a field in the main record
     * 
     * @param string $field Field name
     * @param callable $transformer Function that receives ($value, $object)
     * @return self
     */
    public function addTransformer(string $field, callable $transformer)
    {
        $field = str_replace(['{', '}' ], ['',''], $field);
        $this->transformers[$field] = $transformer;
        return $this;
    }
    
    /**
     * Add a transformer for a field in detail records
     * 
     * @param string $field Field name
     * @param callable $transformer Function that receives ($value, $object)
     * @return self
     */
    public function addDetailTransformer(string $field, callable $transformer)
    {
        $this->detailTransformers[$field] = $transformer;
        return $this;
    }

    /**
     * Apply transformers to a data array
     * 
     * @param array $data Data array
     * @param TRecord $object Original record object
     * @param array $transformers Transformers to apply
     * @return array Transformed data
     */
    protected function applyTransformers(array $data, TRecord $object, array $transformers): array
    {
        if (empty($transformers)) {
            return $data;
        }
        
        foreach ($transformers as $field => $transformer) {
            if (isset($data[$field]) && is_callable($transformer)) {
                $data[$field] = $transformer($data[$field], $object);
            }
        }
        
        return $data;
    }
    
    /**
     * Prepare a record for response based on defined fields
     * 
     * @param TRecord $object The record to prepare
     * @param string $context The context ('index' or 'show')
     * @return array Filtered data
     */
    protected function prepareItemForResponse(TRecord $object, string $context = 'index'): array
    {
        // Get fields to be included based on context
        $fieldsToInclude = $context === 'index' ? $this->indexFields : $this->showFields;
        
        // If no specific fields are defined for this context, include all except hidden
        if (empty($fieldsToInclude)) {
            $data = $object->toArray();
            // Filter out hidden fields
            if (!empty($this->hiddenFields)) {
                foreach ($this->hiddenFields as $field) {
                    if (isset($data[$field])) {
                        unset($data[$field]);
                    }
                }
            }
            
            // Apply transformers to the data
            return $this->applyTransformers($data, $object, $this->transformers);
        }

        // Process fields with custom mappings and relationships
        
        $filteredData = [];
        foreach ($fieldsToInclude as $key => $value) {
            if (in_array($value, $this->hiddenFields)) {
                continue;
            }
            
            // Handle custom field mapping (key => value format)
            if (is_string($key)) {
                $field = $value;
                $outputKey = $key;
            } else {
                $field = $value;
                $outputKey = $field;
            }
            
            // Generate output key by removing curly braces and converting -> to _
            $outputKey = str_replace('->', '_', trim($outputKey, '{}'));
            
            $renderedValue = $object->render($field);
            
            if (!in_array($field, $this->hiddenFields)) {
                $filteredData[$outputKey] = $renderedValue;
            }
        }
        
        // Always include primary key if not already included
        if (!isset($filteredData[$this->primaryKey])) {
            $filteredData[$this->primaryKey] = $object->{$this->primaryKey};
        }
        
        // Apply transformers to the data
        return $this->applyTransformers($filteredData, $object, $this->transformers);
    }

    /**
     * Build criteria from request parameters
     * 
     * @param Request $request
     * @return TCriteria
     */
    protected function buildCriteria(Request $request)
    {
        $criteria = new TCriteria();
        
        // Get JSON data from request body
        $params = $request->getParams() ?? [];
        
        // Parse JSON if it's a string
        if (is_string($params)) {
            $params = json_decode($params, true);
        }
        
        // Handle sorting
        $sort = $request->get('sort');
        $direction = strtolower($request->get('direction', 'asc'));
        
        // Override with values from JSON if provided
        if (isset($params['sort'])) {
            $sort = $params['sort'];
        }
        if (isset($params['direction'])) {
            $direction = strtolower($params['direction']);
        }
        
        if ($sort && in_array($sort, $this->sortable)) {
            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = 'asc';
            }
            $criteria->setProperty('order', "{$sort} {$direction}");
        }
        
        // Handle filters
        if (!empty($params['filters']) && is_array($params['filters'])) {
            $this->processFilters($criteria, $params['filters']);
        }
         
        return $criteria;
    }

    protected function addPagination(Request $request, TCriteria $criteria)
    {
        $params = $request->getParams() ?? [];
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', $this->perPage);
        
        // Override with values from JSON if provided
        if (isset($params['page'])) {
            $page = $params['page'];
        }
        if (isset($params['per_page'])) {
            $perPage = $params['per_page'];
        }
        
        // Set pagination properties
        $criteria->setProperty('limit', $perPage);
        $criteria->setProperty('offset', ($page - 1) * $perPage);
    }
    
    /**
     * Process filters from JSON structure
     * 
     * @param TCriteria $criteria
     * @param array $filters
     */
    protected function processFilters(TCriteria $criteria, array $filters)
    {
        foreach ($filters as $field => $conditions) {
            if (!in_array($field, $this->searchable)) {
                continue;
            }
            
            foreach ($conditions as $operator => $value) {
                if ((empty($value) && $value !== '0' && $value !== 0) || $value === null) {
                    continue;
                }
                
                switch ($operator) {
                    case '=':
                    case 'eq':
                        $criteria->add(new TFilter($field, '=', $value));
                        break;
                    case 'like':
                        $criteria->add(new TFilter($field, 'like', "%{$value}%"));
                        break;
                    case 'like_start':
                        $criteria->add(new TFilter($field, 'like', "{$value}%"));
                        break;
                    case 'like_end':
                        $criteria->add(new TFilter($field, 'like', "%{$value}"));
                        break;
                    case 'ilike':
                        $criteria->add(new TFilter($field, 'ilike', "%{$value}%"));
                        break;
                    case 'ilike_start':
                        $criteria->add(new TFilter($field, 'ilike', "{$value}%"));
                        break;
                    case 'ilike_end':
                        $criteria->add(new TFilter($field, 'ilike', "%{$value}"));
                        break;
                    case 'in':
                        $values = is_array($value) ? $value : [$value];
                        $criteria->add(new TFilter($field, 'in', $values));
                        break;
                    case 'not in':
                        $values = is_array($value) ? $value : [$value];
                        $criteria->add(new TFilter($field, 'not in', $values));
                        break;
                    case '>':
                    case 'gt':
                        $criteria->add(new TFilter($field, '>', $value));
                        break;
                    case '>=':
                    case 'gte':
                        $criteria->add(new TFilter($field, '>=', $value));
                        break;
                    case '<':
                    case 'lt':
                        $criteria->add(new TFilter($field, '<', $value));
                        break;
                    case '<=':
                    case 'lte':
                        $criteria->add(new TFilter($field, '<=', $value));
                        break;
                    case '!=':
                    case 'not':
                        $criteria->add(new TFilter($field, '!=', $value));
                        break;
                    case 'between':
                        if (is_array($value) && count($value) === 2) {
                            $criteria->add(new TFilter($field, 'between', $value[0], $value[1]));
                        }
                        break;
                    case 'is null':
                    case 'is_null':
                        if ($value === true) {
                            $criteria->add(new TFilter($field, 'is', null));
                        }
                        break;
                    case 'is not null':
                    case 'is_not_null':
                        if ($value === true) {
                            $criteria->add(new TFilter($field, 'is not', null));
                        }
                        break;
                }
            }
        }
    }
    
    /**
     * Saves detail records
     * 
     * @param TRecord $master Master record
     * @param array $details Array of detail records
     */
    protected function saveDetails($master, array $details)
    {
        if (!$this->hasDetails()) {
            return;
        }
        
        $masterPrimaryKey = $master->{$this->primaryKey};
        $detailClass = $this->detailModel;
        $foreignKey = $this->detailForeignKey;
        
        $storedDetails = [];
        foreach ($details as $detailData) {
            $detail = new $detailClass();
            $detail->$foreignKey = $masterPrimaryKey;
            $detail->fromArray((array) $detailData);
            
            if (method_exists($this, 'beforeStoreDetail')) {
                $this->beforeStoreDetail($master, $detail, $detailData);
            }

            $detail->store();

            if (method_exists($this, 'afterStoreDetail')) {
                $this->afterStoreDetail($master, $detail, $detailData);
            }

            $storedDetails[] = $detail;
        }
        
        // Hook for additional calculations after saving details
        if (method_exists($this, 'afterStoreDetails')) {
            $this->afterStoreDetails($master, $storedDetails);
        }
    }
    
    /**
     * Prepare a detail record for response
     * 
     * @param TRecord $detail Detail record
     * @return array Prepared detail record data
     */
    protected function prepareDetailForResponse(TRecord $detail): array
    {
        // If no specific fields are defined, include all fields
        if (empty($this->detailIndexFields)) {
            $data = $detail->toArray();
            
            // Apply transformers to the data
            return $this->applyTransformers($data, $detail, $this->detailTransformers);
        }
        
        // Process only the specified fields
        $filteredData = [];
    foreach ($this->detailIndexFields as $key => $field) {
            // Handle custom field mapping (key => value format)
            if (is_string($key)) {
                $fieldName = $field;
                $outputKey = $key;
            } else {
                $fieldName = $field;
                $outputKey = $field;
            }

            // Generate output key by removing curly braces and converting -> to _
            $outputKey = str_replace('->', '_', trim($outputKey, '{}'));
            
            // Use render for relationship fields
            $filteredData[$outputKey] = $detail->render($fieldName);   
        }
        
        // Always include primary key if not already included and it exists in the detail model
        if (!isset($filteredData[$this->primaryKey]) && property_exists($detail, $this->primaryKey)) {
            $filteredData[$this->primaryKey] = $detail->{$this->primaryKey};
        }
        
        // Apply transformers to the filtered data
        return $this->applyTransformers($filteredData, $detail, $this->detailTransformers);
    }
    
    /**
     * Loads detail records
     * 
     * @param TRecord $master Master record
     * @return array Array of detail records
     */
    protected function loadDetails($master)
    {
        if (!$this->hasDetails()) {
            return [];
        }
        
        $detailClass = $this->detailModel;
        $foreignKey = $this->detailForeignKey;
        $masterPrimaryKey = $master->{$this->primaryKey};
        
        $repository = new TRepository($detailClass);
        $criteria = new TCriteria();
        $criteria->add(new TFilter($foreignKey, '=', $masterPrimaryKey));
        
        $details = [];
        foreach ($repository->load($criteria) as $detail) {
            $details[] = $this->prepareDetailForResponse($detail);
        }
        
        return $details;
    }
    
    /**
     * Validates that all required fields are present and not empty
     * 
     * @param array $data Data to validate
     * @param array|null $requiredFieldsToCheck Optional custom required fields to check against
     * @param string $errorPrefix Optional prefix for error messages
     * @throws Exception If a required field is missing or empty
     */
    protected function validateRequiredFields(array $data, array $requiredFieldsToCheck = [], string $errorPrefix = '')
    {
        $fields = !empty($requiredFieldsToCheck) ? $requiredFieldsToCheck : $this->requiredFields;
        $validator = new TRequiredValidator();
        
        if (!empty($fields)) {
            foreach ($fields as $field => $label) {
                $value = $data[$field] ?? null;
                try {
                    $validator->validate($errorPrefix . $label, $value);
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            }
        }
    }
    
    /**
     * Deletes detail records
     * 
     * @param TRecord $master Master record
     */
    protected function deleteDetails($master)
    {
        if (!$this->hasDetails()) {
            return;
        }
        
        $detailClass = $this->detailModel;
        $foreignKey = $this->detailForeignKey;
        $masterPrimaryKey = $master->{$this->primaryKey};
        
        $repository = new TRepository($detailClass);
        $criteria = new TCriteria();
        $criteria->add(new TFilter($foreignKey, '=', $masterPrimaryKey));
        
        $repository->delete($criteria);
    }
} 