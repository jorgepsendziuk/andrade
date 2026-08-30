<?php

use Mad\Rest\Request;
use Mad\Rest\Response;
use Mad\Rest\JSONResponse;

/**
 * BuilderDatabaseManagerController
 * 
 * Controller to handle database management API requests
 */
class BuilderDatabaseManagerController
{
    /**
     * Get a list of all databases
     * 
     * @return JSONResponse Response data
     */
    public static function getDatabases()
    {
        $databases = array_keys(BuilderDatabaseManagerService::getDatabases());
        
        $response = new Response();
        return $response->json([
            'type' => 'DATABASE_LIST',
            'data' => $databases
        ], 200);
    }
    
    /**
     * Get a list of tables for a specific database
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function getTables(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $response = new Response();
        
        if (!$databaseName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name is required'
            ], 400);
        }
        
        $tables = BuilderDatabaseManagerService::getTables($databaseName);
        
        return $response->json([
            'type' => 'TABLE_LIST',
            'data' => [
                'databaseName' => $databaseName,
                'tables' => $tables
            ]
        ], 200);
    }
    
    /**
     * Get the structure of a specific table
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function getTableStructure(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableName = $request->get('tableName') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$tableName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and table name are required'
            ], 400);
        }
        
        $structure = BuilderDatabaseManagerService::getTableStructure($databaseName, $tableName);
        
        if (!$structure) {
            return $response->json([
                'type' => 'ERROR',
                'message' => "Table structure for {$tableName} in {$databaseName} not found"
            ], 404);
        }
        
        return $response->json([
            'type' => 'TABLE_STRUCTURE',
            'data' => [
                'databaseName' => $databaseName,
                'tableName' => $tableName,
                'structure' => $structure
            ]
        ], 200);
    }
    
    /**
     * Get the complete structure of a database
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function getDatabaseStructure(?Request $request = null)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $response = new Response();
        
        if (!$databaseName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name is required'
            ], 400);
        }
        
        $structure = BuilderDatabaseManagerService::getDatabaseStructure($databaseName);
        
        return $response->json([
            'type' => 'DATABASE_STRUCTURE',
            'data' => [
                'databaseName' => $databaseName,
                'structure' => $structure
            ]
        ], 200);
    }
    
    /**
     * Get table data with pagination
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function getTableData(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableName = $request->get('tableName') ?? null;
        $limit = $request->get('limit') !== null ? (int)$request->get('limit') : 500;
        $offset = $request->get('offset') !== null ? (int)$request->get('offset') : 0;
        $response = new Response();

        if (!$databaseName || !$tableName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and table name are required'
            ], 400);
        }

        $data = BuilderDatabaseManagerService::getTableData($databaseName, $tableName, $limit, $offset);
        
        return $response->json([
            'type' => 'TABLE_DATA',
            'data' => [
                'databaseName' => $databaseName,
                'tableName' => $tableName,
                'records' => $data['records'],
                'columns' => $data['columns'],
                'foreignKeys' => $data['foreignKeys'],
                'totalCount' => $data['totalCount'],
                'limit' => $limit,
                'offset' => $offset
            ]
        ], 200);
    }
    
    /**
     * Get filtered table data
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function getFilteredTableData(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableName = $request->get('tableName') ?? null;
        $filters = $request->get('filters') ?? [];
        $limit = $request->get('limit') !== null ? (int)$request->get('limit') : 500;
        $offset = $request->get('offset') !== null ? (int)$request->get('offset') : 0;
        $response = new Response();
        
        if (!$databaseName || !$tableName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and table name are required'
            ], 400);
        }
        
        $data = BuilderDatabaseManagerService::getFilteredTableData($databaseName, $tableName, $filters, $limit, $offset);
        
        return $response->json([
            'type' => 'FILTERED_DATA',
            'data' => [
                'databaseName' => $databaseName,
                'tableName' => $tableName,
                'records' => $data['records'],
                'columns' => $data['columns'],
                'totalCount' => $data['totalCount'],
                'filters' => $filters
            ]
        ], 200);
    }
    
    /**
     * Execute a SQL query
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function executeQuery(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $query = $request->get('query') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$query) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and query are required'
            ], 400);
        }
        
        try{
            $results = BuilderDatabaseManagerService::executeQuery($databaseName, $query);
        }
        catch(Exception $e)
        {
            return $response->json([
                'type' => 'ERROR',
                'message' => $e->getMessage()
            ], 500);
        }
        
        return $response->json([
            'type' => 'QUERY_RESULTS',
            'data' => [
                'databaseName' => $databaseName,
                'query' => $query,
                'results' => $results
            ]
        ], 200);
    }

    /**
     * Save a SQL query
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function saveQuery(Request $request)
    {
        $databaseName = $request->get('database') ?? null;
        $query = $request->get('query') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$query) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and query are required'
            ], 400);
        }
        
        $id = BuilderDatabaseManagerService::saveQuery($databaseName, $query, $request->get('name'));
        
        return $response->json([
            'type' => 'QUERY_SAVED',
            'data' => [
                'databaseName' => $databaseName,
                'query' => $query,
                'id' => $id,
                'name' => $request->get('name')
            ]
        ], 200);
    }

    /**
     * Save a SQL query
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function updateSavedQuery(Request $request)
    {
        $databaseName = $request->get('database') ?? null;
        $query = $request->get('query') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$query) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and query are required'
            ], 400);
        }
        
        BuilderDatabaseManagerService::updateSavedQuery($databaseName, $request->get('id'), $request->get('name'), $query);
        
        return $response->json([
            'type' => 'QUERY_UPDATED',
            'data' => [
                'databaseName' => $databaseName,
                'name' => $request->get('name')
            ]
        ], 200);
    }

    /**
     * Get saved queries
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function getSavedQueries(Request $request)
    {
        $databaseName = $request->get('database') ?? null;
        $response = new Response();
        
        if (!$databaseName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name is required'
            ], 400);
        }
        
        $result = BuilderDatabaseManagerService::getSavedQueries($databaseName);
        
        return $response->json([
            'type' => 'SAVED_QUERIES',
            'data' => [
                'databaseName' => $databaseName,
                'queries' => $result,
                'name' => $request->get('name')
            ]
        ], 200);
    }

    /**
     * Delete a saved SQL query
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function deleteSavedQuery(Request $request)
    {
        $databaseName = $request->get('database') ?? null;
        $queryId = $request->get('id') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$queryId) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and query id required'
            ], 400);
        }
        
        BuilderDatabaseManagerService::deleteSavedQuery($databaseName, $queryId);
        
        return $response->json([
            'type' => 'QUERY_DELETED',
            'data' => [
                'databaseName' => $databaseName,
                'queryId' => $queryId
            ]
        ], 200);
    }
    
    
    /**
     * Apply structure changes to a table
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function applyStructureChanges(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableName = $request->get('tableName') ?? null;
        $changes = $request->get('changes') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$tableName || !$changes) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name, table name, and changes are required'
            ], 400);
        }
        
        $result = BuilderDatabaseManagerService::applyStructureChanges($databaseName, $tableName, $changes);
        
        return $response->json([
            'type' => 'STRUCTURE_CHANGED',
            'data' => $result
        ], 200);
    }
    
    /**
     * Create a new table
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function createTable(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableDefinition = $request->get('tableDefinition') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$tableDefinition) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and table definition are required'
            ], 400);
        }
        
        $result = BuilderDatabaseManagerService::createTable($databaseName, $tableDefinition);
        
        return $response->json([
            'type' => 'TABLE_CREATED',
            'data' => $result
        ], 200);
    }
    
    /**
     * Delete a table
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function deleteTable(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableName = $request->get('tableName') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$tableName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and table name are required'
            ], 400);
        }
        
        $result = BuilderDatabaseManagerService::deleteTable($databaseName, $tableName);
        
        return $response->json([
            'type' => 'TABLE_DELETED',
            'data' => $result
        ], 200);
    }
    
    /**
     * Export table data to a file
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function exportData(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableName = $request->get('tableName') ?? null;
        $format = $request->get('format') ?? 'csv';
        $response = new Response();
        
        if (!$databaseName || !$tableName) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name and table name are required'
            ], 400);
        }
        
        $result = BuilderDatabaseManagerService::exportData($databaseName, $tableName, $format);
        
        return $response->json([
            'type' => 'EXPORT_READY',
            'data' => $result
        ], 200);
    }
    
    /**
     * Import data to a table
     * 
     * @param Request $request Request object
     * @return JSONResponse Response data
     */
    public static function importData(Request $request)
    {
        $databaseName = $request->get('databaseName') ?? null;
        $tableName = $request->get('tableName') ?? null;
        $data = $request->get('data') ?? null;
        $response = new Response();
        
        if (!$databaseName || !$tableName || !$data) {
            return $response->json([
                'type' => 'ERROR',
                'message' => 'Database name, table name, and data are required'
            ], 400);
        }
        
        $result = BuilderDatabaseManagerService::importData($databaseName, $tableName, $data);
        
        return $response->json([
            'type' => 'IMPORT_FINISHED',
            'data' => $result
        ], 200);
    }
}
