<?php

use Mad\Rest\Router;

// Database Manager routes
Router::group([
    'prefix' => '/api/internal/database-manager',
    'middleware' => 'BuilderAdminAuth'
], function () {
    
    // Database operations
    Router::get('/databases', 'BuilderDatabaseManagerController::getDatabases');
    Router::get('/database/tables', 'BuilderDatabaseManagerController::getTables');
    Router::get('/database/structure', 'BuilderDatabaseManagerController::getDatabaseStructure');
    
    // Table operations
    Router::get('/table/structure', 'BuilderDatabaseManagerController::getTableStructure');
    Router::get('/table/data', 'BuilderDatabaseManagerController::getTableData');
    Router::post('/table/filtered-data', 'BuilderDatabaseManagerController::getFilteredTableData');
    
    // Data operations
    Router::post('/query/execute', 'BuilderDatabaseManagerController::executeQuery');
    Router::post('/query/getSavedQueries', 'BuilderDatabaseManagerController::getSavedQueries');
    Router::post('/query/deleteSavedQuery', 'BuilderDatabaseManagerController::deleteSavedQuery');
    Router::post('/query/updateSavedQuery', 'BuilderDatabaseManagerController::updateSavedQuery');
    Router::post('/query/save', 'BuilderDatabaseManagerController::saveQuery');
});

Router::group([
    'prefix' => '/api', 
], function () {
    Router::get('/docs', 'SwaggerController::show');
    Router::post('/auth', 'ApiAuthController::authenticate')->setMiddleware('BasicAuthMiddleware');
});

// Developer routes
