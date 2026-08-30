<?php

require_once 'init.php';

new TSession;

// Check if user is logged in and is admin
if (!TSession::getValue('logged')) {
    // User is not logged in, redirect to login page
    header('Location: index.php?class=LoginForm');
    exit;
}

if (TSession::getValue('login') !== 'admin') {
    // User is logged in but not admin, show access denied message
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Acesso Negado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
            color: #333;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        h1 {
            color: #e74c3c;
        }
        .back-button {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>Acesso Negado</h1>
        <p>Você não tem permissão para acessar esta página. Apenas o usuário administrador pode acessar o Gerenciador de Banco de Dados.</p>
        <a href='index.php' class='back-button'>Voltar para o Início</a>
    </div>
</body>
</html>";
    exit;
}

$ini = AdiantiApplicationConfig::get();
$token = $ini['general']['token'];


// User is logged in and is admin, show database manager interface
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Banco de Dados</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            display: flex;
            flex-direction: column;
            margin: 0 auto;
            height: 100vh;
        }
        .content {
            display: flex;
            flex-direction: row;
            flex: 1;
        }
        .iframe-container {
            flex: 1;
            overflow: hidden;
            height: 100%;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .log-panel {
            width: 400px;
            margin-left: 20px;
            background-color: #333;
            color: #0f0;
            border-radius: 5px;
            padding: 10px;
            font-family: monospace;
            font-size: 14px;
            overflow-y: auto;
            max-height: 100%;
        }
        .log-entry {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #555;
        }
        .log-entry-time {
            color: #999;
            font-size: 12px;
        }
        .log-entry-direction {
            color: #ff9800;
            font-weight: bold;
        }
        .log-entry-content {
            white-space: pre-wrap;
            word-break: break-all;
        }
        #toggleLogPanel {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: #4a6da7;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- <button id="toggleLogPanel">Toggle Log Panel</button> -->
    <div class="container">
        <div class="content">
            <div class="iframe-container">
                <iframe id="dbManager" src="https://manager.madbuilder.com.br/db/?token=<?php echo $token; ?>" allowfullscreen></iframe>
                <!-- <iframe id="dbManager" src="http://localhost:5173" allowfullscreen></iframe> -->
            </div>
            <!-- <div class="log-panel" id="logPanel">
                <h3>Log de Comunicação</h3>
                <div id="logEntries"></div>
            </div> -->
        </div>
    </div>

    <script>
  
        // Log entries to the UI panel
        function logMessage(direction, message) {
            return false;

            const logEntries = document.getElementById('logEntries');
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            
            const time = new Date().toLocaleTimeString();
            const formattedMessage = typeof message === 'object' ? JSON.stringify(message, null, 2) : message;
            
            entry.innerHTML = `
                <div class="log-entry-time">${time}</div>
                <div class="log-entry-direction">${direction}</div>
                <pre class="log-entry-content">${formattedMessage}</pre>
            `;
            
            logEntries.appendChild(entry);
            
            const logPanel = document.getElementById('logPanel');
            logPanel.scrollTop = logPanel.scrollHeight;
        }

        // Handle messages from the iframe
        window.addEventListener('message', async (event) => {
            // Process messages from the iframe
            try {
                const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                
                // Log received message
                logMessage('RECEIVED FROM IFRAME', data);
                
                // Process the message based on type
                let response;
                
                switch (data.type) {
                    case 'READY':
                    case 'REQUEST_DATABASES':
                        // Get databases list
                        response = await fetchFromApi('api/internal/database-manager/databases');
                        break;
                        
                    case 'REQUEST_TABLES':
                        // Get tables for a database
                        response = await fetchFromApi(`api/internal/database-manager/database/tables?databaseName=${data.data.databaseName}`);
                        break;
                        
                    case 'REQUEST_TABLE_STRUCTURE':
                        // Get structure for a table
                        response = await fetchFromApi(`api/internal/database-manager/table/structure?databaseName=${data.data.databaseName}&tableName=${data.data.tableName}`);
                        break;
                        
                    case 'REQUEST_DATABASE_STRUCTURE':
                        // Get complete database structure
                        response = await fetchFromApi(`api/internal/database-manager/database/structure?databaseName=${data.data.databaseName}`);
                        break;
                        
                    case 'REQUEST_TABLE_DATA':
                        // Get table data
                        const limit = data.data.limit || 500;
                        const offset = data.data.offset || 0;
                        response = await fetchFromApi(`api/internal/database-manager/table/data?databaseName=${data.data.databaseName}&tableName=${data.data.tableName}&limit=${limit}&offset=${offset}`);
                        break;
                        
                    case 'REQUEST_FILTERED_DATA':
                        // Get filtered data - uses POST because of filter object
                        response = await fetchFromApi('api/internal/database-manager/table/filtered-data', 'POST', data.data);
                        break;
                        
                    case 'EXECUTE_QUERY':
                        // Execute SQL query
                        response = await fetchFromApi('api/internal/database-manager/query/execute', 'POST', data.data);
                        break;

                    case 'SAVE_QUERY':
                        // Save SQL query
                        response = await fetchFromApi('api/internal/database-manager/query/save', 'POST', data.data);
                        break;

                    case 'REQUEST_SAVED_QUERIES':
                        // Get saved queries
                        response = await fetchFromApi('api/internal/database-manager/query/getSavedQueries', 'POST', data.data);
                        break;

                    case 'DELETE_SAVED_QUERY':
                        // Delete saved query
                        response = await fetchFromApi('api/internal/database-manager/query/deleteSavedQuery', 'POST', data.data);
                        break;

                    case 'UPDATE_SAVED_QUERY':
                        // Update saved query
                        response = await fetchFromApi('api/internal/database-manager/query/updateSavedQuery', 'POST', data.data);
                        break;  
                        
                    case 'APPLY_STRUCTURE_CHANGES':
                        // Apply structure changes
                        response = await fetchFromApi('api/internal/database-manager/table/structure/update', 'POST', data.data);
                        break;
                        
                    case 'CREATE_TABLE':
                        // Create a new table
                        response = await fetchFromApi('api/internal/database-manager/table/create', 'POST', data.data);
                        break;
                        
                    case 'DELETE_TABLE':
                        // Delete a table
                        response = await fetchFromApi('api/internal/database-manager/table/delete', 'POST', data.data);
                        break;
                        
                    case 'EXPORT_DATA':
                        // Export data
                        response = await fetchFromApi('api/internal/database-manager/data/export', 'POST', data.data);
                        break;
                        
                    case 'IMPORT_DATA':
                        // Import data
                        response = await fetchFromApi('api/internal/database-manager/data/import', 'POST', data.data);
                        break;
                        
                    default:
                        logMessage('WARNING', `Tipo de mensagem desconhecido: ${data.type}`);
                        response = {
                            type: 'UNKNOWN_MESSAGE_TYPE',
                            message: `Tipo de mensagem desconhecido: ${data.type}`,
                            originalType: data.type
                        };
                }
                
                // Send response back to iframe
                if (response) {
                    const iframe = document.getElementById('dbManager');
                    logMessage('SENDING TO IFRAME', response);
                    iframe.contentWindow.postMessage(
                        JSON.stringify(response),
                        '*'
                    );
                }
                
            } catch (error) {
                console.error('Error processing message:', error);
                logMessage('ERROR', `Error processing message: ${error.message}`);
            }
        });

        // Helper function to fetch data from API
        async function fetchFromApi(endpoint, method = 'GET', data = null) {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include' // Include cookies for session authentication
                };
                
                if (data && (method === 'POST' || method === 'PUT')) {
                    options.body = JSON.stringify(data);
                }
                
                logMessage('API REQUEST', { endpoint, method, data });
                
                const url = endpoint;
                const response = await fetch(url, options);
                
                if (!response.ok) {
                    throw new Error(`API error: ${response.status} ${response.statusText}`);
                }
                
                const result = await response.json();
                logMessage('API RESPONSE', result);
                
                return result;
            } catch (error) {
                logMessage('API ERROR', error.message);
                return {
                    type: 'ERROR',
                    message: error.message
                };
            }
        }

        // When page loads, wait for the iframe to initialize
        window.addEventListener('load', () => {
            logMessage('INFO', 'Page loaded, waiting for iframe to initialize...');
        });
    </script>
</body>
</html>
