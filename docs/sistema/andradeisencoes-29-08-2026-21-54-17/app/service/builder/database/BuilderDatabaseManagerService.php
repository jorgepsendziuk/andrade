<?php


// use Adianti\Database\TTransaction;
// use \BuilderDatabaseSystemService;
// use SystemPreference;

/**
 * BuilderDatabaseManagerService
 * 
 * Service class to handle database management operations for the Builder Admin interface
 */
class BuilderDatabaseManagerService
{
    /**
     * Get a list of all databases
     * 
     * @return array List of database names
     */
    public static function getDatabases()
    {
        return BuilderDatabaseSystemService::listDatabases();
        
    }
    
    /**
     * Get a list of tables for a specific database
     * 
     * @param string $databaseName The database name
     * @return array List of table names
     */
    public static function getTables(string $databaseName)
    {
        return BuilderDatabaseSystemService::listTables($databaseName);
    }
    
    /**
     * Get the structure of a specific table
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @return array|null Table structure or null if not found
     */
    public static function getTableStructure(string $databaseName, string $tableName)
    {
        // Mock table structures based on the database and table names
        $structures = self::getMockTableStructures();
        
        if (isset($structures[$databaseName]) && 
            isset($structures[$databaseName][$tableName])) {
            return $structures[$databaseName][$tableName];
        }
        
        return null;
    }
    
    /**
     * Get the complete structure of a database
     * 
     * @param string $databaseName The database name
     * @return array Database structure with all tables
     */
    public static function getDatabaseStructure(string $databaseName)
    {
        $structures = self::getMockTableStructures();

        BuilderDatabaseSystemService::listDatabases();
        
        if (!isset($structures[$databaseName])) {
            return [];
        }
        
        return $structures[$databaseName];
    }
    
    /**
     * Get table data with pagination
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Table data with records, columns and count
     */
    public static function getTableData(string $databaseName, string $tableName, int $limit = 500, int $offset = 0)
    {
        try {
            $tableInfos = \BuilderDatabaseService::getTableInfos($databaseName, $tableName);
        }
        catch (Exception $e) {
            $tableInfos = false;
        }

        TTransaction::openFake($databaseName);
        $conn = TTransaction::get();

        $result = $conn->query('SELECT * FROM ' . $tableName . ' LIMIT ' . $limit . ' OFFSET ' . $offset);

        $records = $result->fetchAll(\PDO::FETCH_CLASS);
        $columns = [];
        if($records)
        {
            $record = $records[0];
            $columns = array_keys(get_object_vars($record));
        }
        
        $foreignKeys = [];
        if($tableInfos)
        {
            foreach($tableInfos['foreignKeys'] as $fkName => $entityFk)
            {
                $sql = $entityFk['valuesSql'];

                $ids = [];
                foreach ($records as $object) {
                    $key = $object->{$fkName};
                    if ($key) { // Verificar se não é vazio/nulo
                        $ids[$key] = $key; 
                    }
                }
                
                // Se não houver IDs para pesquisar, pular esta chave estrangeira
                if (empty($ids)) {
                    $foreignKeys[$fkName] = [
                        'targetTable' => $entityFk['targetTable'],
                        'targetColumn' => $entityFk['targetColumn'],
                        'displayColumn' => $entityFk['displayColumn'],
                        'values' => []
                    ];
                    continue;
                }
                
                // Técnica para usar bind parameters na cláusula IN
                // Criar parâmetros nomeados dinâmicos para cada ID (ex: :id0, :id1, :id2, etc.)
                $placeholders = [];
                $params = [];
                $i = 0;
                
                foreach ($ids as $id) {
                    $paramName = ":id$i";
                    $placeholders[] = $paramName;
                    $params[$paramName] = $id;
                    $i++;
                }
                
                // Criar a parte IN da consulta com os placeholders
                $inClause = implode(',', $placeholders);
                
                // Modificar o SQL original substituindo :ids pelo conjunto de placeholders
                $sqlWithParams = str_replace(':ids', $inClause, $sql);
                
                // Preparar a consulta
                $stmt = $conn->prepare($sqlWithParams);
                
                // Vincular todos os parâmetros
                foreach ($params as $param => $value) {
                    $stmt->bindValue($param, $value, \PDO::PARAM_INT);
                }
                
                // Executar a consulta
                $stmt->execute();
                
                // Obter os valores retornados pela consulta
                $values = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                $foreignKeys[$fkName] = [
                    'targetTable' => $entityFk['targetTable'],
                    'targetColumn' => $entityFk['targetColumn'],
                    'displayColumn' => $entityFk['displayColumn'],
                    'values' => $values
                ];
            } 
        }
        TTransaction::close();
        
        return [
            'records' => $records,
            'columns' => $columns,
            'foreignKeys' => $foreignKeys,
            'totalCount' => count($records),
            'limit' => $limit,
            'offset' => $offset 
        ];
    }
    
    /**
     * Get filtered table data
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param array $filters Filters to apply
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array Filtered table data
     */
    public static function getFilteredTableData(string $databaseName, string $tableName, array $filters, int $limit = 500, int $offset = 0)
    {
        
        TTransaction::openFake($databaseName);
        $conn = TTransaction::get();
        $dbInfo = TTransaction::getDatabaseInfo();
        
        // Build the SQL query with WHERE clause if filters are provided
        $sql = 'SELECT * FROM ' . $tableName;
        
        // Prepare WHERE clause with parameters
        $whereClause = '';
        $parameters = [];
        
        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $column => $operatorValue) {
                $value = $operatorValue['value'];
                $operator = $operatorValue['operator'];
                if ($value !== '' && $value !== null) {

                    if (in_array($dbInfo['type'], ['pgsql', 'ibase', 'fbird']) && $operator === 'ilike') {
                        // PostgreSQL e Firebird suportam ILIKE nativamente
                        $operator = str_replace('like', 'ilike', $operator);
                    } elseif (in_array($dbInfo['type'], ['mysql', 'sqlite', 'oracle', 'mssql']) && $operator === 'ilike') {
                        // MySQL, SQLite, Oracle e SQL Server não suportam ILIKE
                        $operator = str_replace('ilike', 'like', $operator);
                    }

                    if($operator === 'like' || $operator === 'ilike') {
                        $value = "%" . $value . "%";
                    }
                    $conditions[] = "$column $operator :$column";
                    $parameters[":$column"] = $value;
                }
            }
            
            if (!empty($conditions)) {
                $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            }
        }
        
        $sql .= $whereClause . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        
        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        foreach ($parameters as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();

        $records = $stmt->fetchAll(\PDO::FETCH_CLASS);
        $columns = [];
        if($records)
        {
            $record = $records[0];
            $columns = array_keys(get_object_vars($record));
        }
        
        // Count total records that match the filter
        $countSql = 'SELECT COUNT(*) as total FROM ' . $tableName . $whereClause;
        $countStmt = $conn->prepare($countSql);
        foreach ($parameters as $param => $value) {
            $countStmt->bindValue($param, $value);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];

        TTransaction::close();

        return [
            'records' => $records,
            'columns' => $columns,
            'totalCount' => $totalCount,
            'filters' => $filters
        ];
    }
    
    /**
     * Execute a SQL query
     * 
     * @param string $databaseName The database name
     * @param string $query The SQL query to execute
     * @return array Query results
     */
    public static function executeQuery(string $databaseName, string $query)
    {
        $startTime = microtime(true);
        TTransaction::openFake($databaseName);
        $conn = TTransaction::get();

        // Prepare and execute the statement
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $records = $stmt->fetchAll(\PDO::FETCH_CLASS);
        $columns = [];
        if($records)
        {
            $record = $records[0];
            $columns = array_keys(get_object_vars($record));
        }
        
        TTransaction::close();

        return [
            'columns' => $columns,
            'rows' => $records,
            'affectedRows' => count($records),
            'executionTime' => microtime(true) - $startTime
        ];
         
    }

    /**
     * Save a SQL query
     * 
     * @param string $databaseName The database name
     * @param string $query The SQL query to save
     * @param string $name The name of the query
     * @return array Query results
     */
    public static function saveQuery(string $databaseName, string $query, string $name)
    {
        TTransaction::openFake('permission');
        
        $userId = TSession::getValue('userid');

        $preference = SystemPreference::find("{$userId}_{$databaseName}_queries");
        if($preference)
        {
            $userQueries = json_decode($preference->preference, true);
        }
        else
        {
            $preference = new SystemPreference();
            $preference->id = "{$userId}_{$databaseName}_queries";
            $userQueries = [];
        }

        $id = uniqid();
        $userQueries[] = [
            'id' => $id,
            'name' => $name,
            'sql' => $query,
            'database' => $databaseName
        ];

        $preference->preference = json_encode($userQueries);
        $preference->store();

        TTransaction::close();

        return $id;
    }

    /**
     * Save a SQL query
     * 
     * @param string $databaseName The database name
     * @param string $queryId The ID of the query to delete
     * @return array Query results
     */
    public static function deleteSavedQuery(string $databaseName, string $queryId)
    {
        TTransaction::openFake('permission');
        
        $userId = TSession::getValue('userid');

        $preference = SystemPreference::find("{$userId}_{$databaseName}_queries");
        if($preference)
        {
            $userQueries = json_decode($preference->preference, true);
        }
        else
        {
            $preference = new SystemPreference();
            $preference->id = "{$userId}_{$databaseName}_queries";
            $userQueries = [];
        }

        $userQueries = array_filter($userQueries, fn($query) => $query['id'] !== $queryId);

        $preference->preference = json_encode($userQueries);
        $preference->store();

        TTransaction::close();

        return true;
    }

    /**
     * Get saved queries
     * 
     * @param string $databaseName The database name
     * @return array Query results
     */
    public static function getSavedQueries(string $databaseName)
    {
        TTransaction::openFake('permission');
        
        $userId = TSession::getValue('userid');

        $userQueries = SystemPreference::getPreference("{$userId}_{$databaseName}_queries");
        if($userQueries)
        {
            $userQueries = json_decode($userQueries, true);
        }
        else
        {
            $userQueries = [];
        }

        TTransaction::close();

        return $userQueries;
    }

    /**
     * Update saved query
     * 
     * @param string $databaseName The database name
     * @return array Query results
     */
    public static function updateSavedQuery(string $databaseName, string $queryId, string $name, string $query)
    {
        TTransaction::openFake('permission');
        $conn = TTransaction::get();
        
        $userId = TSession::getValue('userid');

        $userQueries = SystemPreference::getPreference("{$userId}_{$databaseName}_queries");
        if($userQueries)
        {
            $userQueries = json_decode($userQueries, true);
        }
        else
        {
            $userQueries = [];
        }

        $userQueries = array_map(function($item) use ($queryId, $name, $query) {
            if($item['id'] === $queryId)
            {
                return [
                    'id' => $queryId,
                    'name' => $name,
                    'sql' => $query
                ];
            }
            return $item;
        }, $userQueries);

        $userQueries = json_encode($userQueries);
        SystemPreference::setPreference("{$userId}_{$databaseName}_queries", $userQueries);

        TTransaction::close();

        return [
            'success' => true,
            'data' => $userQueries
        ];
    }
    
    /**
     * Apply structure changes to a table
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param array $changes Changes to apply
     * @return array Operation result
     */
    public static function applyStructureChanges(string $databaseName, string $tableName, array $changes)
    {
        // In a real implementation, this would modify the actual database structure
        // For now, we'll just return success
        return [
            'databaseName' => $databaseName,
            'tableName' => $tableName,
            'success' => true,
            'message' => 'Estrutura da tabela atualizada com sucesso'
        ];
    }
    
    /**
     * Create a new table
     * 
     * @param string $databaseName The database name
     * @param array $tableDefinition Table definition
     * @return array Operation result
     */
    public static function createTable(string $databaseName, array $tableDefinition)
    {
        // In a real implementation, this would create a new table in the database
        // For now, we'll just return success
        return [
            'databaseName' => $databaseName,
            'tableName' => $tableDefinition['name'],
            'success' => true,
            'message' => 'Tabela criada com sucesso'
        ];
    }
    
    /**
     * Delete a table
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @return array Operation result
     */
    public static function deleteTable(string $databaseName, string $tableName)
    {
        // In a real implementation, this would delete the table from the database
        // For now, we'll just return success
        return [
            'databaseName' => $databaseName,
            'tableName' => $tableName,
            'success' => true,
            'message' => 'Tabela excluída com sucesso'
        ];
    }
    
    /**
     * Export table data to a file
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param string $format Export format (csv, json, sql)
     * @return array Operation result
     */
    public static function exportData(string $databaseName, string $tableName, string $format)
    {
        // In a real implementation, this would generate a file with the exported data
        // For now, we'll just return success
        return [
            'databaseName' => $databaseName,
            'tableName' => $tableName,
            'format' => $format,
            'downloadUrl' => '#', // This would be a real URL in production
            'success' => true,
            'message' => 'Dados exportados com sucesso'
        ];
    }
    
    /**
     * Import data to a table
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param array $data Data to import
     * @return array Operation result
     */
    public static function importData(string $databaseName, string $tableName, array $data)
    {
        // In a real implementation, this would import the data into the table
        // For now, we'll just return success
        return [
            'databaseName' => $databaseName,
            'tableName' => $tableName,
            'success' => true,
            'rowsAffected' => 15,
            'message' => 'Dados importados com sucesso'
        ];
    }
    
    /**
     * Apply filters to data
     * 
     * @param array $records Records to filter
     * @param array $filters Filters to apply
     * @return array Filtered records
     */
    private static function applyFilters(array $records, array $filters)
    {
        if (empty($records) || empty($filters)) {
            return $records;
        }
        
        return array_filter($records, function($record) use ($filters) {
            foreach ($filters as $key => $filter) {
                $filterValue = strtolower($filter);
                $recordValue = isset($record[$key]) ? strtolower((string)$record[$key]) : '';
                
                if (strpos($recordValue, $filterValue) === false) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * Generate mock records for a table
     * 
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param array $structure Table structure
     * @param int $limit Number of records to generate
     * @return array Generated records
     */
    private static function generateMockRecords(string $databaseName, string $tableName, array $structure, int $limit)
    {
        $records = [];
        
        for ($i = 0; $i < $limit; $i++) {
            $record = [];
            
            foreach ($structure['columns'] as $column) {
                $columnName = $column['name'];
                $columnType = $column['type'];
                
                $record[$columnName] = self::generateMockValue($columnName, $columnType, $i);
            }
            
            $records[] = $record;
        }
        
        return $records;
    }
    
    /**
     * Generate a mock value for a specific column
     * 
     * @param string $columnName Column name
     * @param string $columnType Column type
     * @param int $index Record index
     * @return mixed Generated value
     */
    private static function generateMockValue(string $columnName, string $columnType, int $index)
    {
        $upperType = strtoupper($columnType);
        
        if (strpos($upperType, 'INTEGER') !== false || strpos($upperType, 'SERIAL') !== false) {
            return $index + 1;
        } else if (strpos($upperType, 'VARCHAR') !== false || strpos($upperType, 'CHAR') !== false) {
            if (strpos($columnName, 'nome') !== false || strpos($columnName, 'name') !== false) {
                return "Nome " . ($index + 1);
            } else if (strpos($columnName, 'email') !== false) {
                return "usuario" . ($index + 1) . "@exemplo.com";
            } else if (strpos($columnName, 'telefone') !== false || strpos($columnName, 'phone') !== false) {
                return "(11) 9" . rand(1000, 9999) . "-" . rand(1000, 9999);
            } else if (strpos($columnName, 'status') !== false) {
                $statuses = ['Ativo', 'Inativo', 'Pendente', 'Concluído', 'Cancelado'];
                return $statuses[array_rand($statuses)];
            } else {
                return "Valor " . ($index + 1);
            }
        } else if (strpos($upperType, 'TEXT') !== false) {
            return "Este é um texto de exemplo para o registro " . ($index + 1) . ". Contém múltiplas frases para simular um conteúdo mais longo que pode ser armazenado em um campo do tipo TEXT.";
        } else if (strpos($upperType, 'DECIMAL') !== false || strpos($upperType, 'NUMERIC') !== false || strpos($upperType, 'FLOAT') !== false || strpos($upperType, 'DOUBLE') !== false) {
            return round(mt_rand(100, 100000) / 100, 2);
        } else if (strpos($upperType, 'BOOLEAN') !== false) {
            return (bool)mt_rand(0, 1);
        } else if (strpos($upperType, 'DATE') !== false || strpos($upperType, 'TIME') !== false || strpos($upperType, 'TIMESTAMP') !== false) {
            $date = new \DateTime();
            $date->modify('-' . mt_rand(0, 365) . ' days');
            return $date->format('Y-m-d H:i:s');
        } else {
            return "Valor " . ($index + 1);
        }
    }
    
    /**
     * Get mock table structures
     * 
     * @return array Mock table structures
     */
    private static function getMockTableStructures()
    {
        return [
            'ecommerce' => [
                'produtos' => [
                    'name' => 'produtos',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'nome', 'type' => 'VARCHAR(255)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'preco', 'type' => 'DECIMAL(10,2)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'estoque', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => true],
                        ['name' => 'categoria_id', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => true]
                    ],
                    'indexes' => [
                        ['name' => 'idx_categoria', 'columns' => ['categoria_id']],
                        ['name' => 'idx_nome', 'columns' => ['nome']]
                    ]
                ],
                'categorias' => [
                    'name' => 'categorias',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'nome', 'type' => 'VARCHAR(100)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'descricao', 'type' => 'TEXT', 'isPrimary' => false, 'allowNull' => true]
                    ],
                    'indexes' => [
                        ['name' => 'idx_nome_cat', 'columns' => ['nome']]
                    ]
                ],
                'clientes' => [
                    'name' => 'clientes',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'nome', 'type' => 'VARCHAR(150)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'email', 'type' => 'VARCHAR(150)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'telefone', 'type' => 'VARCHAR(20)', 'isPrimary' => false, 'allowNull' => true],
                        ['name' => 'endereco', 'type' => 'TEXT', 'isPrimary' => false, 'allowNull' => true]
                    ],
                    'indexes' => [
                        ['name' => 'idx_email', 'columns' => ['email'], 'unique' => true]
                    ]
                ],
                'pedidos' => [
                    'name' => 'pedidos',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'cliente_id', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'data', 'type' => 'DATETIME', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'status', 'type' => 'VARCHAR(50)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'valor_total', 'type' => 'DECIMAL(10,2)', 'isPrimary' => false, 'allowNull' => false]
                    ],
                    'indexes' => [
                        ['name' => 'idx_cliente', 'columns' => ['cliente_id']],
                        ['name' => 'idx_data', 'columns' => ['data']]
                    ]
                ],
                'itens_pedido' => [
                    'name' => 'itens_pedido',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'pedido_id', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'produto_id', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'quantidade', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'preco_unitario', 'type' => 'DECIMAL(10,2)', 'isPrimary' => false, 'allowNull' => false]
                    ],
                    'indexes' => [
                        ['name' => 'idx_pedido', 'columns' => ['pedido_id']],
                        ['name' => 'idx_produto', 'columns' => ['produto_id']]
                    ]
                ]
            ],
            'blog' => [
                'posts' => [
                    'name' => 'posts',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'titulo', 'type' => 'VARCHAR(200)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'conteudo', 'type' => 'TEXT', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'data_publicacao', 'type' => 'DATETIME', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'autor_id', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'categoria_id', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => true]
                    ],
                    'indexes' => [
                        ['name' => 'idx_autor', 'columns' => ['autor_id']],
                        ['name' => 'idx_data', 'columns' => ['data_publicacao']]
                    ]
                ],
                'comentarios' => [
                    'name' => 'comentarios',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'post_id', 'type' => 'INTEGER', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'autor', 'type' => 'VARCHAR(100)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'conteudo', 'type' => 'TEXT', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'data', 'type' => 'DATETIME', 'isPrimary' => false, 'allowNull' => false]
                    ],
                    'indexes' => [
                        ['name' => 'idx_post', 'columns' => ['post_id']]
                    ]
                ],
                'usuarios' => [
                    'name' => 'usuarios',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'nome', 'type' => 'VARCHAR(100)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'email', 'type' => 'VARCHAR(100)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'senha', 'type' => 'VARCHAR(255)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'tipo', 'type' => 'VARCHAR(20)', 'isPrimary' => false, 'allowNull' => false]
                    ],
                    'indexes' => [
                        ['name' => 'idx_email', 'columns' => ['email'], 'unique' => true]
                    ]
                ],
                'categorias' => [
                    'name' => 'categorias',
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'isPrimary' => true, 'allowNull' => false],
                        ['name' => 'nome', 'type' => 'VARCHAR(50)', 'isPrimary' => false, 'allowNull' => false],
                        ['name' => 'descricao', 'type' => 'TEXT', 'isPrimary' => false, 'allowNull' => true]
                    ],
                    'indexes' => [
                        ['name' => 'idx_nome', 'columns' => ['nome'], 'unique' => true]
                    ]
                ]
            ]
        ];
    }
}
