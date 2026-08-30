<?php

use Mad\Rest\Response;

class SwaggerController
{
    private $apiData;
    
    public function __construct()
    {        
        // Carrega rotas através da classe Router
        $routeProvider = new \Mad\Rest\RouteServiceProvider();
        $routeProvider->boot();
        
        // Obtém as rotas registradas
        $routerRoutes = \Mad\Rest\Router::getRoutes();
        
        // Converter objetos Route para o formato esperado pelo SwaggerController
        $routes = $this->convertRoutes($routerRoutes);
        
        

        // Group routes by controller
        $controllerRoutes = [];
        $controllerInfo = [];

        foreach ($routes as $route) {
            $controllerClass = $route['controller'];

            // Skip BuilderDatabaseManagerController routes
            if ( preg_match('/BuilderDatabaseManagerController|SwaggerController/i', $controllerClass) ) {
                continue;
            }
            
            if (!isset($controllerRoutes[$controllerClass])) {
                $controllerRoutes[$controllerClass] = [];
                $controllerInfo[$controllerClass] = $this->getControllerInfo($controllerClass);
            }
            
            $controllerRoutes[$controllerClass][] = $route;
        }


        // Prepare data for the view
        $this->apiData = [
            'info' => [
                'title' => 'API Docs',
                'version' => '',
                'description' => ''
            ],
            'controllers' => $controllerInfo,
            'paths' => [],
        ];

        // Process routes and build API paths
        foreach ($routes as $route) {
            $path = $route['path'];
            $method = strtolower($route['method']);
            $controllerClass = $route['controller'];

            if(!isset($controllerInfo[$controllerClass])){
                continue;
            }
            
            $action = $route['action'];
            
            if (!isset($this->apiData['paths'][$path])) {
                $this->apiData['paths'][$path] = [];
            }
            
            $controller = $controllerInfo[$controllerClass];
            
            $pathParams = [];
            
            // Extract path parameters
            preg_match_all('/{([^}]+)}/', $path, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $param) {
                    $pathParams[$param] = [
                        'name' => $param,
                        'in' => 'path',
                        'required' => true,
                        'description' => 'ID of the resource'
                    ];
                }
            }
            
            // Determine request body schema based on controller and action
            $requestBody = null;
            $responseSchema = null;
            
            if ($action === 'store' || $action === 'update') {
                // Para store e update, obter campos diretamente do modelo
                $modelFields = [];
                $modelClass = $controller['model'] ?? null;
                $requiredFields = $controller['requiredFields'] ?? [];
                
                if ($modelClass && class_exists($modelClass)) {
                    try {
                        // Instanciar o modelo para acessar seus atributos
                        $model = new $modelClass();
                        $reflection = new ReflectionClass($model);
                        
                        // Buscar atributos adicionados via addAttribute
                        $parent = $reflection->getParentClass(); // TRecord
                        if ($parent) {
                            // Buscar todas as chamadas para addAttribute no construtor
                            $constructor = $reflection->getMethod('__construct');
                            $filename = $reflection->getFileName();
                            $startLine = $constructor->getStartLine();
                            $endLine = $constructor->getEndLine();
                            
                            // Ler o conteúdo do construtor
                            $source = file_get_contents($filename);
                            $lines = explode("\n", $source);
                            $constructorLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
                            $constructorCode = implode("\n", $constructorLines);
                            
                            // Extrair chamadas addAttribute
                            preg_match_all("/parent::addAttribute\(['\"](.*?)['\"]/", $constructorCode, $matches);
                            if (!empty($matches[1])) {
                                foreach ($matches[1] as $attribute) {
                                    $modelFields[$attribute] = ['type' => 'string'];
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Fallback para fields do controlador se houver erro
                        $modelFields = $controller['showFields'] ?? [];
                    }
                } else {
                    // Fallback para fields do controlador se a classe não existir
                    $modelFields = $controller['showFields'] ?? [];
                }
                
                // Processar detalhes se existirem
                if ($controller['detailModel']) {
                    $detailModelFields = [];
                    $detailModelClass = $controller['detailModel'] ?? null;
                    $requiredDetailFields = $controller['requiredDetailFields'] ?? [];

                    if ($detailModelClass && class_exists($detailModelClass)) {
                        try {
                            $detailModel = new $detailModelClass();
                            $detailReflection = new ReflectionClass($detailModel);
                            
                            // Mesmo processo para o modelo de detalhes
                            $detailParent = $detailReflection->getParentClass();
                            if ($detailParent) {
                                $detailConstructor = $detailReflection->getMethod('__construct');
                                $detailFilename = $detailReflection->getFileName();
                                $detailStartLine = $detailConstructor->getStartLine();
                                $detailEndLine = $detailConstructor->getEndLine();
                                
                                $detailSource = file_get_contents($detailFilename);
                                $detailLines = explode("\n", $detailSource);
                                $detailConstructorLines = array_slice($detailLines, $detailStartLine - 1, $detailEndLine - $detailStartLine + 1);
                                $detailConstructorCode = implode("\n", $detailConstructorLines);
                                
                                preg_match_all("/parent::addAttribute\(['\"](.*?)['\"]/", $detailConstructorCode, $detailMatches);
                                if (!empty($detailMatches[1])) {
                                    foreach ($detailMatches[1] as $detailAttribute) {
                                        $detailModelFields[$detailAttribute] = ['type' => 'string'];
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $detailModelFields = $controller['detailIndexFields'] ?? [];
                        }
                    } else {
                        $detailModelFields = $controller['detailIndexFields'] ?? [];
                    }
                    
                }

                $requestBody = [
                    'main' => $this->generateInputSchema($modelFields, $requiredFields)
                ];
                
                // Adicionar detalhes como um campo separado no requestBody
                if ($controller['detailModel'] && $controller['detailProperty']) {
                    // Criar esquema para detalhes definindo corretamente o tipo como objeto com propriedades
                    $detailProperties = [];
                    foreach ($detailModelFields as $field => $options) {
                        $detailProperties[$field] = [
                            'type' => 'string',
                            'description' => $field
                        ];
                        
                        if (in_array($field, $requiredDetailFields) || isset($requiredDetailFields[$field])) {
                            $detailProperties[$field]['required'] = true;
                        }
                    }
                    
                    $requestBody['details'] = [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => $detailProperties
                        ]
                    ];
                }
            }
            
            // Determine response schema
            if ($action === 'index') {
                $responseSchema = [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => $this->generateInputSchema($controller['indexFields'] ?? [])
                            ]
                        ],
                        'meta' => [
                            'type' => 'object',
                            'properties' => [
                                'total' => ['type' => 'integer'],
                                'per_page' => ['type' => 'integer'],
                                'current_page' => ['type' => 'integer'],
                                'last_page' => ['type' => 'integer'],
                            ]
                        ]
                    ]
                ];
            } else if ($action === 'store' || $action === 'update') {
                // Para store e update, obter campos diretamente do modelo
                $modelFields = [];
                $modelClass = $controller['model'] ?? null;
                
                if ($modelClass && class_exists($modelClass)) {
                    try {
                        // Instanciar o modelo para acessar seus atributos
                        $model = new $modelClass();
                        $reflection = new ReflectionClass($model);
                        $attributes = [];
                        
                        // Buscar atributos adicionados via addAttribute
                        $parent = $reflection->getParentClass(); // TRecord
                        if ($parent) {
                            $addAttributeMethod = $parent->getMethod('addAttribute');
                            
                            // Buscar todas as chamadas para addAttribute no construtor
                            $constructor = $reflection->getMethod('__construct');
                            $filename = $reflection->getFileName();
                            $startLine = $constructor->getStartLine();
                            $endLine = $constructor->getEndLine();
                            
                            // Ler o conteúdo do construtor
                            $source = file_get_contents($filename);
                            $lines = explode("\n", $source);
                            $constructorLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
                            $constructorCode = implode("\n", $constructorLines);
                            
                            // Extrair chamadas addAttribute
                            preg_match_all("/parent::addAttribute\(['\"](.*?)['\"]/", $constructorCode, $matches);
                            if (!empty($matches[1])) {
                                foreach ($matches[1] as $attribute) {
                                    $modelFields[$attribute] = ['type' => 'string'];
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Fallback para fields do controlador se houver erro
                        $modelFields = $controller['showFields'] ?? [];
                    }
                } else {
                    // Fallback para fields do controlador se a classe não existir
                    $modelFields = $controller['showFields'] ?? [];
                }
                
                $responseSchema = [
                    'type' => 'object',
                    'properties' => $this->generateInputSchema($modelFields)
                ];
                
                if ($controller['detailModel'] && $controller['detailProperty']) {
                    // Tentar fazer o mesmo para o modelo de detalhes
                    $detailModelFields = [];
                    $detailModelClass = $controller['detailModel'] ?? null;
                    
                    if ($detailModelClass && class_exists($detailModelClass)) {
                        try {
                            $detailModel = new $detailModelClass();
                            $detailReflection = new ReflectionClass($detailModel);
                            
                            // Mesmo processo para o modelo de detalhes
                            $detailParent = $detailReflection->getParentClass();
                            if ($detailParent) {
                                $detailConstructor = $detailReflection->getMethod('__construct');
                                $detailFilename = $detailReflection->getFileName();
                                $detailStartLine = $detailConstructor->getStartLine();
                                $detailEndLine = $detailConstructor->getEndLine();
                                
                                $detailSource = file_get_contents($detailFilename);
                                $detailLines = explode("\n", $detailSource);
                                $detailConstructorLines = array_slice($detailLines, $detailStartLine - 1, $detailEndLine - $detailStartLine + 1);
                                $detailConstructorCode = implode("\n", $detailConstructorLines);
                                
                                preg_match_all("/parent::addAttribute\(['\"](.*?)['\"]/", $detailConstructorCode, $detailMatches);
                                if (!empty($detailMatches[1])) {
                                    foreach ($detailMatches[1] as $detailAttribute) {
                                        $detailModelFields[$detailAttribute] = ['type' => 'string'];
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $detailModelFields = $controller['detailIndexFields'] ?? [];
                        }
                    } else {
                        $detailModelFields = $controller['detailIndexFields'] ?? [];
                    }
                    
                    $responseSchema['properties'][$controller['detailProperty']] = [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => $this->generateInputSchema($detailModelFields)
                        ]
                    ];
                }
            } 
            else if ($action === 'show') {
                $responseSchema = [
                    'type' => 'object',
                    'properties' => $this->generateInputSchema($controller['showFields'] ?? [])
                ];
                
                if ($controller['detailModel'] && $controller['detailProperty']) {
                    $responseSchema['properties'][$controller['detailProperty']] = [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => $this->generateInputSchema($controller['detailIndexFields'] ?? [])
                        ]
                    ];
                }
            } else {
                $responseSchema = null;
            }
            
            // Try to parse PHPDoc from the controller method
            $phpDocInfo = $this->parsePHPDocFromMethod($controllerClass, $action);
            
            
            // Use PHPDoc information if available, otherwise fall back to defaults
            $summary = ucfirst($action) . ' ' . ($controller['model'] ?? 'resource');
            $description = $this->getOperationDescription($action, $controller);
            $responses = [
                '200' => [
                    'description' => 'Successful operation',
                    'schema' => $responseSchema
                ],
                '400' => [
                    'description' => 'Bad request'
                ],
                '404' => [
                    'description' => 'Resource not found'
                ]
            ];
            
            // Override with PHPDoc information if available
            if ($phpDocInfo) {
                if (!empty($phpDocInfo['summary'])) {
                    $summary = $phpDocInfo['summary'];
                }
                if (!empty($phpDocInfo['description'])) {
                    $description = $phpDocInfo['description'];
                }
                
                // Extract request schema from @param PHPDoc if available
                if (!empty($phpDocInfo['params'])) {

                    

                    foreach ($phpDocInfo['params'] as $param) {
                        if (isset($param['jsonSchema']) && !$requestBody) {
                            $requestBody = $param['jsonSchema'];
                            break; // Use first parameter with JSON schema
                        }
                    }

                    
                }
                
                // Extract response schemas from @return PHPDoc if available
                if (!empty($phpDocInfo['return']['jsonSchemas'])) {
                    foreach ($phpDocInfo['return']['jsonSchemas'] as $httpCode => $schemaInfo) {
                        $responses[$httpCode] = [
                            'description' => ucfirst($schemaInfo['type']) . ' response',
                            'schema' => $schemaInfo['schema']
                        ];
                    }
                }
                
                // Fallback: Extract request parameters from PHPDoc example if available (legacy support)
                if (!empty($phpDocInfo['requestExample']) && !$requestBody) {
                    $requestBody = [
                        'type' => 'object',
                        'properties' => []
                    ];
                    
                    foreach ($phpDocInfo['requestExample'] as $key => $value) {
                        $requestBody['properties'][$key] = [
                            'type' => is_numeric($value) ? 'integer' : 'string',
                            'description' => ucfirst($key),
                            'example' => $value
                        ];
                    }
                }
                
                // Fallback: Enhance response schema with PHPDoc example if available (legacy support)
                if (!empty($phpDocInfo['responseExample']) && !isset($responses['200']['schema'])) {
                    $responses['200']['example'] = $phpDocInfo['responseExample'];
                    
                    // If no response schema was generated, create one from the example
                    if (!$responseSchema && $phpDocInfo['responseExample']) {
                        $responseSchema = $this->generateSchemaFromExample($phpDocInfo['responseExample']);
                        $responses['200']['schema'] = $responseSchema;
                    }
                }
                
                // Add error responses from @throws tags
                if (!empty($phpDocInfo['throws'])) {
                    foreach ($phpDocInfo['throws'] as $throw) {
                        $responses['500'] = [
                            'description' => 'Server Error: ' . $throw['description']
                        ];
                    }
                }
            }
            
            $operation = [
                'summary' => $summary,
                'description' => $description,
                'tags' => [$controllerClass],
                'parameters' => array_values($pathParams),
                'requestBody' => $requestBody,
                'responses' => $responses
            ];
            
            // Add operation to the correct HTTP method
            $this->apiData['paths'][$path][$method] = $operation;
        }

        $this->apiData = json_encode($this->apiData, JSON_PRETTY_PRINT);
    }

    /**
     * Parse routes from API content
     */
    private function parseRoutes($apiContent)
    {
        $routes = [];
        
        // Match group definitions
        preg_match_all('/Router::group\(\[\s*\'prefix\'\s*=>\s*[\'"]([^\'"]+)[\'"](?:,\s*\'middleware\'\s*=>\s*(\[[^\]]*\]))?\s*\],\s*function\s*\(\)\s*{(.*?)\}\);/s', $apiContent, $groups, PREG_SET_ORDER);
        
        foreach ($groups as $group) {
            $groupPrefix = $group[1];
            $groupMiddleware = isset($group[2]) ? eval("return {$group[2]};") : [];
            $groupContent = $group[3];
            
            // Process nested groups
            preg_match_all('/Router::group\(\[\s*\'prefix\'\s*=>\s*[\'"]([^\'"]+)[\'"](?:,\s*\'middleware\'\s*=>\s*(\[[^\]]*\]))?\s*\],\s*function\s*\(\)\s*{(.*?)\}\);/s', $groupContent, $nestedGroups, PREG_SET_ORDER);
            
            foreach ($nestedGroups as $nestedGroup) {
                $nestedPrefix = $nestedGroup[1];
                $nestedMiddleware = isset($nestedGroup[2]) ? eval("return {$nestedGroup[2]};") : [];
                $nestedContent = $nestedGroup[3];
                
                $combinedPrefix = rtrim($groupPrefix, '/') . '/' . ltrim($nestedPrefix, '/');
                $combinedMiddleware = array_merge($groupMiddleware, $nestedMiddleware);
                
                // Extract routes in nested group
                $routes = array_merge($routes, $this->extractRoutesFromContent($nestedContent, $combinedPrefix, $combinedMiddleware));
            }
            
            // Extract routes in main group (excluding nested groups)
            $mainGroupContent = preg_replace('/Router::group\(\[\s*\'prefix\'\s*=>\s*[\'"][^\'"]+[\'"](?:,\s*\'middleware\'\s*=>\s*\[[^\]]*\])?\s*\],\s*function\s*\(\)\s*{.*?\}\);/s', '', $groupContent);
            $routes = array_merge($routes, $this->extractRoutesFromContent($mainGroupContent, $groupPrefix, $groupMiddleware));
        }
        
        // Extract top-level routes (not in any group)
        $topLevelContent = preg_replace('/Router::group\(\[\s*\'prefix\'\s*=>\s*[\'"][^\'"]+[\'"](?:,\s*\'middleware\'\s*=>\s*\[[^\]]*\])?\s*\],\s*function\s*\(\)\s*{.*?\}\);/s', '', $apiContent);
        $routes = array_merge($routes, $this->extractRoutesFromContent($topLevelContent, '', []));
        
        return $routes;
    }

    /**
     * Extract routes from content
     */
    private function extractRoutesFromContent($content, $prefix = '', $middleware = [])
    {
        $routes = [];
        $httpMethods = ['get', 'post', 'put', 'delete', 'patch', 'options'];
        
        foreach ($httpMethods as $method) {
            $pattern = '/Router::' . $method . '\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)[\'"]\);/';
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $path = $match[1];
                $controller = $match[2];
                
                // Combine prefix with path
                $fullPath = rtrim($prefix, '/') . '/' . ltrim($path, '/');
                $fullPath = '/' . ltrim($fullPath, '/');  // Ensure it starts with /
                
                // Extract controller and method
                list($controllerClass, $controllerMethod) = explode('::', $controller);
                
                $routes[] = [
                    'method' => strtoupper($method),
                    'path' => $fullPath,
                    'controller' => $controllerClass,
                    'action' => $controllerMethod,
                    'middleware' => $middleware
                ];
            }
        }
        
        return $routes;
    }

    /**
     * Get controller information
     */
    private function getControllerInfo($controllerClass)
    {
        // If controller doesn't exist or it's SwaggerController itself, return basic info
        if (!class_exists($controllerClass) || $controllerClass === __CLASS__ || $controllerClass === 'SwaggerController') {
            return [
                'model' => null,
                'database' => null,
                'primaryKey' => null,
                'fields' => [],
                'detailModel' => null,
                'detailForeignKey' => null,
                'requiredFields' => [],
                'requiredDetailFields' => [],
            ];
        }
        
        $reflection = new ReflectionClass($controllerClass);
        $controller = null;
        
        try {
            // Check if the controller has a constructor that might cause infinite recursion
            if ($reflection->hasMethod('__construct')) {
                // Skip instantiation for controllers that might cause recursive loops
                $constructorCode = file_get_contents($reflection->getFileName());
                if (strpos($constructorCode, 'SwaggerController') !== false) {
                    // Skip instantiation if the controller references SwaggerController
                    throw new Exception('Skipping instantiation to avoid potential recursion');
                }
            }
            $controller = new $controllerClass();
        } catch (Exception $e) {
            // If we can't instantiate, try to extract info from reflection
        }
        
        $info = [
            'model' => null,
            'database' => null,
            'primaryKey' => null,
            'fields' => [],
            'detailModel' => null,
            'detailForeignKey' => null,
            'requiredFields' => [],
            'requiredDetailFields' => [],
        ];
        
        // Try to get properties via reflection
        if ($controller) {
            // Directly accessible properties
            $info['model'] = $this->getProtectedProperty($controller, 'model');
            $info['database'] = $this->getProtectedProperty($controller, 'database');
            $info['primaryKey'] = $this->getProtectedProperty($controller, 'primaryKey');
            $info['perPage'] = $this->getProtectedProperty($controller, 'perPage');
            $info['sortable'] = $this->getProtectedProperty($controller, 'sortable');
            $info['filterable'] = $this->getProtectedProperty($controller, 'filterable');
            $info['showFields'] = $this->getProtectedProperty($controller, 'showFields');
            $info['indexFields'] = $this->getProtectedProperty($controller, 'indexFields');
            $info['requiredFields'] = $this->getProtectedProperty($controller, 'requiredFields');
            
            // Master-detail properties
            $info['detailModel'] = $this->getProtectedProperty($controller, 'detailModel');
            $info['detailForeignKey'] = $this->getProtectedProperty($controller, 'detailForeignKey');
            $info['detailProperty'] = $this->getProtectedProperty($controller, 'detailProperty');
            $info['requiredDetailFields'] = $this->getProtectedProperty($controller, 'requiredDetailFields');
            $info['detailIndexFields'] = $this->getProtectedProperty($controller, 'detailIndexFields');
        } else {
            // Try to extract from reflection if instantiation failed
            foreach ($reflection->getProperties(ReflectionProperty::IS_PROTECTED) as $property) {
                $property->setAccessible(true);
                $defaultValue = $property->getDefaultValue();
                $info[$property->getName()] = $defaultValue;
            }
        }
        
        return $info;
    }

    /**
     * Parse PHPDoc comments from controller methods to extract parameters and response information
     * 
     * @param string $controllerClass The controller class name
     * @param string $methodName The method name to parse
     * @return array Parsed PHPDoc information including parameters, return type, and examples
     */
    private function parsePHPDocFromMethod($controllerClass, $methodName)
    {
        if (!class_exists($controllerClass)) {
            return null;
        }
        
        try {
            $reflection = new ReflectionClass($controllerClass);
            
            if (!$reflection->hasMethod($methodName)) {
                return null;
            }
            
            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();
            
            if (!$docComment) {
                return null;
            }
            
            $parsed = $this->parsePHPDocComment($docComment);
            
           
            
            return $parsed;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Parse PHPDoc comment string and extract structured information
     * 
     * @param string $docComment The PHPDoc comment string
     * @return array Parsed information including description, params, return, throws, example
     */
    private function parsePHPDocComment($docComment)
    {
        $parsed = [
            'description' => '',
            'summary' => '',
            'params' => [],
            'return' => null,
            'throws' => [],
            'example' => null,
            'since' => null,
            'author' => null
        ];
        
        // Remove /** and */ and clean up
        $content = preg_replace('/^\/\*\*|\*\/$/', '', $docComment);
        $lines = explode("\n", $content);
        
        $currentSection = 'description';
        $descriptionLines = [];
        $currentParamContent = '';
        $currentReturnContent = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\*\s?/', '', $line); // Remove leading * and space
            
            if (empty($line)) {
                continue;
            }
            
            // Check for PHPDoc tags
            if (preg_match('/^@(\w+)\s+(.*)$/', $line, $matches)) {
                // Process previous section before starting new one
                if ($currentSection === 'param' && !empty($currentParamContent)) {
                    $parsed['params'][] = $this->parseParamTag($currentParamContent);
                    $currentParamContent = '';
                } elseif ($currentSection === 'return' && !empty($currentReturnContent)) {
                    $parsed['return'] = $this->parseReturnTag($currentReturnContent);
                    $currentReturnContent = '';
                }
                
                $tag = $matches[1];
                $content = $matches[2];
                
                switch ($tag) {
                    case 'param':
                        $currentSection = 'param';
                        $currentParamContent = $content;
                        break;
                    case 'return':
                        $currentSection = 'return';
                        $currentReturnContent = $content;
                        break;
                    case 'throws':
                        $parsed['throws'][] = $this->parseThrowsTag($content);
                        break;
                    case 'example':
                        $currentSection = 'example';
                        break;
                    case 'since':
                        $parsed['since'] = trim($content);
                        break;
                    case 'author':
                        $parsed['author'] = trim($content);
                        break;
                }
            } else {
                // Handle content based on current section
                if ($currentSection === 'description') {
                    $descriptionLines[] = $line;
                } elseif ($currentSection === 'param') {
                    $currentParamContent .= "\n" . $line;
                } elseif ($currentSection === 'return') {
                    $currentReturnContent .= "\n" . $line;
                } elseif ($currentSection === 'example') {
                    if ($parsed['example'] === null) {
                        $parsed['example'] = '';
                    }
                    $parsed['example'] .= $line . "\n";
                }
            }
        }
        
        // Process any remaining section content
        if ($currentSection === 'param' && !empty($currentParamContent)) {
            $parsed['params'][] = $this->parseParamTag($currentParamContent);
        } elseif ($currentSection === 'return' && !empty($currentReturnContent)) {
            $parsed['return'] = $this->parseReturnTag($currentReturnContent);
        }
        
        // Process description lines
        if (!empty($descriptionLines)) {
            $parsed['summary'] = $descriptionLines[0];
            $parsed['description'] = implode(' ', $descriptionLines);
        }
        
        // Parse example for request parameters and response
        if ($parsed['example']) {
            $exampleData = $this->parseExampleContent($parsed['example']);
            $parsed['requestExample'] = $exampleData['request'] ?? null;
            $parsed['responseExample'] = $exampleData['response'] ?? null;
        }
        
        return $parsed;
    }
    
    /**
     * Parse @param tag content
     */
    private function parseParamTag($content)
    {
        // Format: @param type $name description (multi-line support)
        if (preg_match('/^(\S+)\s+\$?(\w+)\s*(.*)$/s', $content, $matches)) {
            $param = [
                'type' => $matches[1],
                'name' => $matches[2],
                'description' => trim($matches[3])
            ];
            
            // Debug: Show the full description captured            
            // Try to extract JSON structure from description
            $jsonSchema = $this->extractJsonSchemaFromDescription($matches[3]);
            if ($jsonSchema) {
                $param['jsonSchema'] = $jsonSchema;
            } else {
            }
            
            return $param;
        }
        
        return [
            'type' => 'mixed',
            'name' => 'unknown',
            'description' => $content
        ];
    }
    
    /**
     * Parse @return tag content
     */
    private function parseReturnTag($content)
    {
        // Format: @return type description (multi-line support)
        if (preg_match('/^(\S+)\s*(.*)$/s', $content, $matches)) {
            $return = [
                'type' => $matches[1],
                'description' => trim($matches[2])
            ];
            
            
            // Try to extract JSON structure from description
            $jsonSchemas = $this->extractJsonSchemasFromReturnDescription($matches[2]);
            if ($jsonSchemas) {
                $return['jsonSchemas'] = $jsonSchemas;
            }
            
            return $return;
        }
        
        return [
            'type' => 'mixed',
            'description' => $content
        ];
    }
    
    /**
     * Extract JSON schema from @param description
     */
    private function extractJsonSchemaFromDescription($description)
    {
        // Look for JSON structure pattern in description
        // Pattern: Expected JSON structure: { ... }
        
        if (preg_match('/Expected JSON structure:\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/s', $description, $matches)) {
            return $this->parseJsonStructureFromText($matches[1]);
        }
    
        return null;
    }
    
    /**
     * Extract JSON schemas from @return description (handles multiple response types)
     */
    private function extractJsonSchemasFromReturnDescription($description)
    {
        $schemas = [];
        
        // Look for Success (HTTP XXX): { ... } patterns
        if (preg_match_all('/(Success|Error)\s*\(HTTP\s*(\d+)\):\s*\{([^}]+(?:\{[^}]*\}[^}]*)*)\}/s', $description, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = strtolower($match[1]); // success or error
                $httpCode = $match[2];
                $jsonContent = $match[3];
                
                $schema = $this->parseJsonStructureFromText($jsonContent, 'return');
                if ($schema) {
                    $schemas[$httpCode] = [
                        'type' => $type,
                        'httpCode' => $httpCode,
                        'schema' => $schema
                    ];
                }
            }
        }
        
        return !empty($schemas) ? $schemas : null;
    }
    
    /**
     * Parse JSON structure from text description
     */
    private function parseJsonStructureFromText($jsonText, $type = 'param')
    {   
        $keySchema = 'main';
        if($type == 'return')
        {
            $keySchema = 'properties';
        }
        
        // Parse the hierarchical structure
        $result = $this->parseNestedJsonStructure($jsonText);
        
        if ($result) {
            return [
                'type' => 'object',
                $keySchema => $result
            ];
        }
        
        return null;
    }
    
    /**
     * Parse nested JSON structure with proper hierarchy
     */
    private function parseNestedJsonStructure($jsonText)
    {
        $lines = explode("\n", $jsonText);
        $properties = [];
        $currentLevel = 0;
        $stack = [&$properties]; // Stack to track nested levels
        $levelStack = [0]; // Track indentation levels
        
        foreach ($lines as $line) {
            $originalLine = $line;
            $line = trim($line);
            if (empty($line)) continue;
            
            // Calculate indentation level
            $indentLevel = strlen($originalLine) - strlen(ltrim($originalLine));
            
            // Adjust stack based on indentation
            while (count($levelStack) > 1 && $indentLevel <= end($levelStack)) {
                array_pop($stack);
                array_pop($levelStack);
            }
            
            $currentContainer = &$stack[count($stack) - 1];
            
            // Parse property line: "fieldName": type (required) - description
            if (preg_match('/["\']([^"\'
]+)["\']\s*:\s*(\w+)(?:\s*\(([^)]+)\))?(?:\s*-\s*(.*))?/', $line, $matches)) {
                $fieldName = $matches[1];
                $fieldType = $this->mapPhpTypeToJsonType($matches[2]);
                $constraints = isset($matches[3]) ? $matches[3] : '';
                $description = isset($matches[4]) ? trim($matches[4]) : '';
                
                $property = [
                    'type' => $fieldType,
                    'description' => $description
                ];
                
                // Check if field is required
                if (strpos($constraints, 'required') !== false) {
                    $property['required'] = true;
                }
                
                // Extract examples from description
                if (preg_match('/\(e\.g\.,\s*["\']([^"\'
]+)["\']\)/', $description, $exampleMatch)) {
                    $property['example'] = $exampleMatch[1];
                }
                
                $currentContainer[$fieldName] = $property;
            }
            // Handle nested objects: "fieldName": {
            else if (preg_match('/["\']([^"\'
]+)["\']\s*:\s*\{/', $line, $matches)) {
                $fieldName = $matches[1];
                $currentContainer[$fieldName] = [
                    'type' => 'object',
                    'properties' => []
                ];
                
                // Add this nested object to the stack
                $stack[] = &$currentContainer[$fieldName]['properties'];
                $levelStack[] = $indentLevel;
            }
            // Handle closing braces
            else if (preg_match('/^\s*\}\s*$/', $line)) {
                // Pop from stack when we encounter closing brace
                if (count($stack) > 1) {
                    array_pop($stack);
                    array_pop($levelStack);
                }
            }
        }
        
        return !empty($properties) ? $properties : null;
    }
    
    /**
     * Map PHP types to JSON schema types
     */
    private function mapPhpTypeToJsonType($phpType)
    {
        $typeMap = [
            'string' => 'string',
            'int' => 'integer',
            'integer' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object'
        ];
        
        return $typeMap[strtolower($phpType)] ?? 'string';
    }
    
    /**
     * Parse @throws tag content
     */
    private function parseThrowsTag($content)
    {
        // Format: @throws ExceptionClass description
        if (preg_match('/^(\S+)\s*(.*)$/', $content, $matches)) {
            return [
                'exception' => $matches[1],
                'description' => trim($matches[2])
            ];
        }
        
        return [
            'exception' => 'Exception',
            'description' => $content
        ];
    }
    
    /**
     * Parse example content to extract request parameters and response structure
     */
    private function parseExampleContent($example)
    {
        $result = ['request' => null, 'response' => null];
        
        // Look for request parameters pattern - updated to match actual format
        // Pattern: // Request parameters: {"login": "user", "password": "pass"}
        if (preg_match('/\/\/\s*Request parameters:\s*({[^}]+})/', $example, $matches)) {
            try {
                $result['request'] = json_decode($matches[1], true);
            } catch (Exception $e) {
                // If JSON decode fails, try to extract manually
                $result['request'] = $this->extractJsonFromString($matches[1]);
            }
        }
        
        // Look for response pattern - updated to match actual format
        // The response spans multiple lines with // comments
        // Pattern: // Success response:\n // {\n //   "status": "success",\n //   ...\n // }
        if (preg_match('/\/\/\s*Success response:\s*\n((?:\s*\/\/.*\n?)+)/', $example, $matches)) {
            // Extract JSON from commented lines
            $responseLines = $matches[1];
            // Remove // from each line and reconstruct JSON
            $jsonLines = [];
            $lines = explode("\n", $responseLines);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^\/\/\s*(.*)$/', $line, $lineMatch)) {
                    $jsonLines[] = $lineMatch[1];
                }
            }
            
            $jsonString = implode("\n", $jsonLines);
            
            try {
                $result['response'] = json_decode($jsonString, true);
            } catch (Exception $e) {
                // If JSON decode fails, try to extract manually
                $result['response'] = $this->extractJsonFromString($jsonString);
            }
        } else {
            // Try alternative pattern for single-line or compact response
            if (preg_match('/\/\/\s*({.*?})/', $example, $matches)) {
                try {
                    $result['response'] = json_decode($matches[1], true);
                } catch (Exception $e) {
                    // Silent fallback
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Extract JSON-like structure from string when json_decode fails
     */
    private function extractJsonFromString($jsonString)
    {
        // Simple extraction for basic JSON structures
        $result = [];
        
        // Extract key-value pairs
        if (preg_match_all('/"([^"]+)":\s*"([^"]+)"/', $jsonString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result[$match[1]] = $match[2];
            }
        }
        
        return $result;
    }
    
    /**
     * Generate JSON schema from example data
     * 
     * @param array $example The example data to generate schema from
     * @return array Generated JSON schema
     */
    private function generateSchemaFromExample($example)
    {
        if (!is_array($example)) {
            return [
                'type' => gettype($example),
                'example' => $example
            ];
        }
        
        $schema = [
            'type' => 'object',
            'properties' => []
        ];
        
        foreach ($example as $key => $value) {
            if (is_array($value)) {
                if (isset($value[0])) {
                    // Array of items
                    $schema['properties'][$key] = [
                        'type' => 'array',
                        'items' => $this->generateSchemaFromExample($value[0])
                    ];
                } else {
                    // Object
                    $schema['properties'][$key] = $this->generateSchemaFromExample($value);
                }
            } else {
                $type = 'string';
                if (is_int($value)) {
                    $type = 'integer';
                } elseif (is_float($value)) {
                    $type = 'number';
                } elseif (is_bool($value)) {
                    $type = 'boolean';
                }
                
                $schema['properties'][$key] = [
                    'type' => $type,
                    'example' => $value
                ];
            }
        }
        
        return $schema;
    }
    
    /**
     * Get protected property value
     */
    private function getProtectedProperty($object, $propertyName)
    {
        if (!$object) return null;
        
        try {
            $reflection = new ReflectionClass($object);
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            return $property->getValue($object);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Converte objetos Route do Router para o formato esperado pelo SwaggerController
     * 
     * @param array $routerRoutes Array de objetos \Mad\Rest\Route
     * @return array Array de rotas no formato esperado pelo SwaggerController
     */
    private function convertRoutes(array $routerRoutes)
    {
        $routes = [];
        
        foreach ($routerRoutes as $route) {
            $action = $route->getAction();
            $controllerClass = null;
            $actionMethod = null;
            
            // Se a action for uma string no formato Controller::method
            if (is_string($action) && strpos($action, '::') !== false) {
                list($controllerClass, $actionMethod) = explode('::', $action);
            }
            
            $routes[] = [
                'path' => '/'.$route->getUrl(),
                'method' => $route->getMethod(),
                'controller' => $controllerClass,
                'action' => $actionMethod,
                'middleware' => $route->getMiddleware() ?: []
            ];
        }
        
        return $routes;
    }

    /**
     * Generate input schema for fields
     */
    private function generateInputSchema($fields = [], $required = [])
    {
        $schema = [];
        
        if (empty($fields)) {
            return null;
        }
        
        foreach ($fields as $field => $label) {
            if (is_numeric($field)) {
                // Handle array format ['{field1}', '{field2}']
                $field = trim($label, '{}');
                $label = $field;
            }
            
            $schema[$field] = [
                'type' => 'string',
                'description' => $label
            ];
            
            if (in_array($field, $required) || isset($required[$field])) {
                $schema[$field]['required'] = true;
            }
        }
        
        return $schema;
    }

    /**
     * Get operation description based on action and controller
     */
    private function getOperationDescription($action, $controller)
    {
        $model = $controller['model'] ?? 'resource';
        
        switch ($action) {
            case 'index':
                return "List all {$model}s with pagination support";
            case 'show':
                return "Get a specific {$model} by ID";
            case 'store':
                return "Create a new {$model}";
            case 'update':
                return "Update an existing {$model}";
            case 'destroy':
                return "Delete a {$model}";
            default:
                return "Perform {$action} operation on {$model}";
        }
    }

    /**
     * Get the generated API data
     */
    public function getApiData()
    {
        return $this->apiData;
    }

    public function show()
    {
        $html = file_get_contents('app/resources/swagger.html');
        
        // Garantir que apiData seja convertido para JSON antes de inserir no template
        $html = str_replace('{$apiData}', $this->apiData, $html);
        
        return (new Response())->html($html);
    }
}