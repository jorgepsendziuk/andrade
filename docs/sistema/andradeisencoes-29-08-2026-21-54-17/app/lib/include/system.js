window.System = ( function() {

    let formDebuggerEnabled = false;

    const initTwoFactorEmailForm = function()
    {
        $(document).ready(function() {
            const inputs = $('#two-factor-container input');
            const emailCode = $('#email_code');
            const resendLink = $('#resend-link');
            const verifyBtn = $('#btn-two-factor');
            
            inputs.on('input', function() {
                const index = inputs.index(this);
                const value = $(this).val();
                
                $(this).val(value.replace(/[^0-9]/g, ''));
                
                if (value.length === 1 && index < inputs.length - 1) {
                    $(inputs[index + 1]).focus();
                }
                
                updateCode();
                checkComplete();
            });
            
            inputs.on('keydown', function(e) {
                const index = inputs.index(this);
                
                if (e.key === 'Backspace' && $(this).val() === '') {
                    if (index > 0) {
                        e.preventDefault();
                        $(inputs[index - 1]).focus().val('');
                        updateCode();
                        checkComplete();
                    }
                }
            });
            
            inputs.on('paste', function(e) {
                e.preventDefault();

                const pasteData = e.originalEvent.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                
                if (pasteData.length === 6) {
                    inputs.each(function(i) {
                        $(this).val(pasteData[i] || '');
                    });
                    updateCode();
                    checkComplete();
                }
            });
            
            function updateCode() {
                let code = '';
                inputs.each(function() {
                    code += $(this).val();
                });
                emailCode.val(code);
            }
            
            function checkComplete() {
                const complete = Array.from(inputs).every(input => input.value.length === 1);
                verifyBtn.prop('disabled', !complete);
            }
            
            let countdown = 60;
            let canResend = true;
            
            resendLink.on('click', function() {
                if (!canResend) return;
                
                canResend = false;
                resendLink.css({
                    'opacity': '0.5',
                    'cursor': 'default'
                });
                
                resendLink.text(`Aguarde 60s`);
                const timer = setInterval(() => {
                    countdown--;
                    resendLink.text(`Aguarde ${countdown}s`);
                    
                    if (countdown <= 0) {
                        clearInterval(timer);
                        countdown = 60;
                        canResend = true;
                        resendLink.text('Reenviar código');
                        resendLink.css({
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                    }
                }, 1000);
            });
            
            $(inputs[0]).focus();
            checkComplete();
        });
    }

    const initTwoFactorGoogleForm = function()
    {
        $(document).ready(function() {
            const inputs = $('#two-factor-container input');
            const googleCode = $('#google_code');
            const verifyBtn = $('#btn-two-factor');
            
            inputs.on('input', function() {
                const index = inputs.index(this);
                const value = $(this).val();
                
                $(this).val(value.replace(/[^0-9]/g, ''));
                
                if (value.length === 1 && index < inputs.length - 1) {
                    $(inputs[index + 1]).focus();
                }
                
                updateCode();
                checkComplete();
            });
            
            inputs.on('keydown', function(e) {
                const index = inputs.index(this);
                
                if (e.key === 'Backspace' && $(this).val() === '') {
                    if (index > 0) {
                        e.preventDefault();
                        $(inputs[index - 1]).focus().val('');
                        updateCode();
                        checkComplete();
                    }
                }
            });
            
            inputs.on('paste', function(e) {
                e.preventDefault();
                const pasteData = e.originalEvent.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                
                if (pasteData.length === 6) {
                    inputs.each(function(i) {
                        $(this).val(pasteData[i] || '');
                    });
                    updateCode();
                    checkComplete();
                }
            });
            
            function updateCode() {
                let code = '';
                inputs.each(function() {
                    code += $(this).val();
                });
                googleCode.val(code);
            }
            
            function checkComplete() {
                const complete = Array.from(inputs).every(input => input.value.length === 1);
                verifyBtn.prop('disabled', !complete);
            }
            
            $(inputs[0]).focus();
            checkComplete();
        });
    }


    const initSelect2 = function(field, placeholder) {

        const options = {
            escapeMarkup: function(markup) {
                return markup;
            },
            allowClear: true,
            placeholder: placeholder,
            templateResult: function (d) {
                if (/<[a-z][\s\S]*>/i.test(d.text)) {
                    return $("<span>"+d.text+"</span>");
                }
                else {
                    return d.text;
                }
            },
            templateSelection: function (d) {
                if (/<[a-z][\s\S]*>/i.test(d.text)) {
                    return $("<span>"+d.text+"</span>");
                }
                else {
                    return d.text;
                }
            }
        };

        $(field).select2(options);
        $(field).attr('role', 'tcombosearch');
        $(field).attr('widget', 'tcombo');
        $(field).after(`<script type="text/plain"> System.initSelect2("#${$(field).attr('id')}", "${placeholder}"); </script>`);
    }

    const onChangeImportDataDestinationTable = function(element) {

        const table_id = $(element).val();
        const destination_field = $(element).closest('tr').find('select[name="destination_field\[\]"]');        

        if(table_id)
        {
            const columns = System.tables[table_id].columns;
            const items = {};
            for (const key in columns) {
                items[columns[key].name] = columns[key].name;
            }

            this.fillCombo(destination_field, items, '', true);
        }
        else
        {
            this.clearCombo(destination_field);
        }
    }

    const fillCombo = function(selector, data, val, defaultOption){
        
        $(selector).html('');

        if(typeof defaultOption == 'undefined' || typeof defaultOption != 'undefined' && defaultOption == true)
        {
            $('<option value=""></option>').appendTo($(selector));
        }
        
        var selected = ''
        $.each(data, function(key, value){
            selected = ''
            if(val == value){
                selected = 'selected="selected"';
            }
            
            $('<option '+selected+' value="'+key+'">'+value+'</option>').appendTo($(selector));
        });   
    }

    const clearCombo = function(selector){
        $(selector).html(''); 
    }

    const checkMultipleTabs = function(application_name) {
        const channel = new BroadcastChannel('BuilderSystemTab');
        
        // Pergunta se já existe aba ativa
        channel.postMessage('checkTab');
        
        channel.onmessage = (event) => {
            if (event.data === 'checkTab') {
                // Se esta é uma aba ativa, responde
                channel.postMessage(application_name + '_active');
            }
            
            if (event.data === application_name + '_active') {
                // Se receber confirmação de outra aba ativa, mostra aviso
                $('body').empty();
                $('body').html(`<div class="one-tab-mode-warning-container">
                    <div class="one-tab-mode-warning-icon"></div>
                    <h1 class="one-tab-mode-warning-title">${Builder.translate('attention')}</h1>
                    <p class="one-tab-mode-warning-message">
                        ${Builder.translate('tab_warning_message')}
                    </p>
                </div>`);
    
                $('body').addClass('one-tab-mode-body');
                channel.close();
                return false;
            }
        };
        
        return true;
    }

    /**
     * Função para interceptar requisições Ajax do jQuery
     * @param {Function} callback - Função que será chamada quando uma requisição Ajax for completada
     * @returns {Object} - Objeto com métodos para gerenciar as requisições capturadas
     */
    const interceptAjaxRequests = function(callback) {
        // Validar o callback
        if (typeof callback !== 'function') {
            throw new Error('O callback deve ser uma função');
        }
        
        // Ativar os eventos globais Ajax
        $.ajaxSetup({
            global: true
        });
 
        // Evento disparado quando a requisição é completada (sucesso ou erro)
        $(document).ajaxComplete(function(event, jqXHR, ajaxOptions) {

            // Tentativa de converter o corpo da requisição para objeto se for JSON
            let requestBody = ajaxOptions.data;
            try {
                if (typeof ajaxOptions.data === 'string' && ajaxOptions.data.trim().startsWith('{')) {
                    requestBody = JSON.parse(ajaxOptions.data);
                }
            } catch (e) {
                // Manter como string se não puder ser convertido
            }
            
            // Tentativa de converter a resposta para objeto se for JSON
            let responseBody = jqXHR.responseText;
            try {
                if (jqXHR.responseText && jqXHR.getResponseHeader('Content-Type') && 
                    jqXHR.getResponseHeader('Content-Type').includes('application/json')) {
                    responseBody = JSON.parse(jqXHR.responseText);
                }
            } catch (e) {
                // Manter como string se não puder ser convertido
            }
            
            // Criação do objeto no formato desejado
            const requestData = {
                id: jqXHR.getResponseHeader('Mad-Req-Id') || null,
                method: ajaxOptions.type || 'GET',
                url: ajaxOptions.url,
                status: jqXHR.status,
                headers: {
                    ...ajaxOptions.headers,
                    'Content-Type': ajaxOptions.contentType || 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                response: responseBody
            };

            if(typeof responseBody != 'undefined' && responseBody != null)
            {
                requestData.body = requestBody;
            }
            
            // Chamar o callback com os dados capturados
            callback(requestData);

        });
    }

    const initDebugConsole = function() {

        // Salvar a referência original do XMLHttpRequest
        const OriginalXMLHttpRequest = window.XMLHttpRequest;
        // Sobreescrever a classe XMLHttpRequest
        window.XMLHttpRequest = function() {
            // Criar uma instância do XMLHttpRequest original
            const xhr = new OriginalXMLHttpRequest();
            
            let responseTextCache = '';
            let responseCache = null;
            let responseModified = false;
                    
            // Interceptar o evento readystatechange para modificar a resposta quando estiver completa
            xhr.addEventListener('readystatechange', function() {
                if (xhr.readyState === 4 && !responseModified) {
                    try {
                        const originalText = xhr.responseText;
                        let modifiedText = originalText;

                        modifiedText = extractAndExecuteScripts(originalText).htmlWithoutScripts;
                        
                        // Armazenar a resposta modificada
                        responseTextCache = modifiedText;
                        responseModified = true;
                        
                        // Se o responseType for 'json', também prepare o objeto JSON modificado
                        if (xhr.responseType === 'json') {
                            try {
                                responseCache = JSON.parse(modifiedText);
                            } catch(e) {
                                console.error('Erro ao converter resposta modificada para JSON:', e);
                            }
                        }
                    } catch(e) {
                        console.error('Erro ao interceptar a resposta:', e);
                    }
                }
            }, false);
            
            // Substituir a propriedade responseText por um proxy
            const originalResponseText = Object.getOwnPropertyDescriptor(OriginalXMLHttpRequest.prototype, 'responseText');
            
            // Interceptar a propriedade responseText com um getter personalizado
            Object.defineProperty(xhr, 'responseText', {
                get: function() {
                const originalValue = originalResponseText ? originalResponseText.get.call(xhr) : xhr.responseText;
                
                if (responseModified) {
                    return responseTextCache;
                }
                
                return originalValue;
                }
            });
            
            // Interceptar a propriedade response com um getter personalizado
            const originalResponse = Object.getOwnPropertyDescriptor(OriginalXMLHttpRequest.prototype, 'response');
            
            Object.defineProperty(xhr, 'response', {
                get: function() {
                    try {
                    // Se a resposta já foi modificada e temos um cache, retornar do cache
                    if (responseModified) {
                        if (xhr.responseType === 'json' && responseCache) {
                            return responseCache;
                        } else if (xhr.responseType === '' || xhr.responseType === 'text') {
                            return responseTextCache;
                        }
                    }
                    
                    // Caso contrário, retornar a resposta original
                    return originalResponse ? originalResponse.get.call(xhr) : xhr.response;
                    } catch(e) {
                        console.error('Erro ao acessar response:', e);
                        // Em caso de erro, tente acessar diretamente
                        return xhr.response;
                    }
                }
            });
            
            return xhr;
        };

        /**
         * Extrai e executa o conteúdo de tags script dentro de uma string
         * @param {string} htmlString - String contendo código HTML com possíveis tags script
         * @returns {Object} - Objeto com a string HTML sem scripts e array de scripts encontrados
         */
        function extractAndExecuteScripts(htmlString) {
            // Array para armazenar os scripts encontrados
            const extractedScripts = [];
            
            // Cópia da string original para remover os scripts
            let cleanedHtml = htmlString;
            
            // Padrão regex para encontrar tags script com atributo data-log='mad-debug-console-log'
            // Usamos um padrão que busca especificamente scripts com esse atributo
            const scriptRegex = /<script\b[^>]*data-log=['"]mad-debug-console-log['"][^>]*>([\s\S]*?)<\/script>/gi;
            
            // Encontrar todas as ocorrências de tags script com o atributo específico
            let match;
            while ((match = scriptRegex.exec(htmlString)) !== null) {
                // match[0] contém a tag script completa
                // match[1] contém apenas o conteúdo dentro da tag script
                const scriptContent = match[1];
                const fullScriptTag = match[0];
                
                // Adicionando ao array de scripts encontrados
                extractedScripts.push(scriptContent);
                
                // Remover a tag script completa da string limpa
                cleanedHtml = cleanedHtml.replace(fullScriptTag, '');
                
                try {
                    // Executar o script usando eval ou Function (eval é mais simples, mas menos seguro)
                    // A função Function cria um novo escopo, o que pode ser mais seguro em alguns casos
                    const scriptFunction = new Function(scriptContent);
                    scriptFunction();   
                    
                } catch (error) {
                    console.error("Erro ao executar o script:", error);
                }
            }
        
            // Retornar um objeto com a string limpa e os scripts extraídos
            return {
                htmlWithoutScripts: cleanedHtml,
                extractedScripts: extractedScripts
            };
        }

        // Create a wrapper div that will be resizable
        const debugContainer = $('<div>', {
            id: 'debug-console-container',
            class: 'debug-sidebar-bottom'
        });
        
        // Create the iframe
        const debugIframe = $('<iframe>', {
            id: 'debug-console-iframe',
            src: 'https://manager.madbuilder.com.br/console/',
            css: {
                width: '100%',
                height: '100%',
                color: '#f0f0f0',
                fontFamily: '\'Consolas\', \'Monaco\', \'Menlo\', monospace',
                fontSize: '12px',
                lineHeight: 1.4,
                display: 'block',
                boxSizing: 'border-box',
                border: 'none'
            }
        });
        
        // Add iframe to the container
        debugContainer.append(debugIframe);
        
        // Add the container to the DOM
        $('body').append(debugContainer);
        $('body').append(`<style>
            .body-debug-minimized {
                height: auto !important;
            }
            .debug-minimized {
                width: 200px !important;
                height: 20px !important;
                right: 0px !important;
            }
            .debug-fullscreen {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100% !important;
                height: 100% !important;
                max-width: none !important;
                max-height: none !important;
                z-index: 99999 !important;
                border-radius: 0 !important;
                border: none !important;
                transform: none !important;
            }
            .debug-sidebar-bottom {
                position: fixed;
                z-index: 9999;
                bottom: 0px;
                width: 100%;
                height: 400px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
                box-sizing: border-box;
            }

            .debug-sidebar{
                top: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 30%;
                height: 100% !important;
                border-left: 1px solid #444 !important;
                transition: transform 0.3s ease !important;
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3) !important;
                min-width: 475px !important;
            }

            .debug-console-enabled, #adianti_right_panel{
                height: calc(100vh - 400px);
            }
            

        </style>`);

        setTimeout(() => {

        $('body').addClass('debug-console-enabled');

        // Define app prefix for localStorage keys
        const appPrefix = BuilderTemplate && BuilderTemplate.application_name ? 
            BuilderTemplate.application_name + '_' : 'default_';
            
        // Check for saved sidebar state in localStorage and apply it
        const savedSidebarState = localStorage.getItem(appPrefix + 'debug_panel_sidebar');
        
        // Try to get saved dimensions
        let savedDimensions = null;
        try {
            const dimensionsJson = localStorage.getItem(appPrefix + 'debug_panel_dimensions');
            if (dimensionsJson) {
                savedDimensions = JSON.parse(dimensionsJson);
            }
        } catch (e) {
            console.error('Error parsing saved dimensions:', e);
        }
        
        if (savedSidebarState === 'true') {
            // Apply sidebar mode
            $('#debug-console-container').addClass('debug-sidebar');
            
            // Apply custom dimensions if available, otherwise use defaults
            if (savedDimensions) {
                $('#debug-console-container').css('height', savedDimensions.height + 'px');
                $('#debug-console-container').css('width', typeof savedDimensions.width === 'string' && savedDimensions.width.includes('%') ? savedDimensions.width : savedDimensions.width + 'px');
                $('#debug-console-container').css('top', savedDimensions.top + 'px');
                
                // Calculate body dimensions based on the saved dimensions
                $('body').css('width', 'calc(100% - ' + savedDimensions.width + 'px)');
                $('body').css('height', '100vh');

                $('#adianti_right_panel').css('right', savedDimensions.width + 'px');
                $('#adianti_right_panel').css('height', '100vh');
            } else {
                // Default sidebar dimensions
                $('#debug-console-container').css('height', '100vh');
                $('#debug-console-container').css('width', '30%');
                $('#debug-console-container').css('top', '0px');
                
                $('body').css('width', 'calc(100% - 30%)');
                $('body').css('height', '100vh');

                $('#adianti_right_panel').css('right', '30%');
                $('#adianti_right_panel').css('height', '100vh');
            }
            
            // These positioning properties are always applied in sidebar mode
            $('#debug-console-container').css('right', '0px');
            $('#debug-console-container').css('left', 'auto');
        } else if (savedDimensions && !$('#debug-console-container').hasClass('debug-sidebar')) {
            // If not in sidebar mode but we have saved dimensions, apply them
            $('#debug-console-container').css('height', savedDimensions.height + 'px');
            $('#debug-console-container').css('width', savedDimensions.width + 'px');
            
            // Adjust body height based on panel height (for bottom panel mode)
            $('body').css('height', 'calc(100vh - ' + savedDimensions.height + 'px)');
            $('#adianti_right_panel').css('height', 'calc(100vh - ' + savedDimensions.height + 'px)');
        }
        
        // Apply resizable to the container instead of the iframe
        // Implementação melhorada de redimensionamento com eventos de mouse
        (function() {
            // Elementos
            const container = document.getElementById('debug-console-container');
            const iframe = document.getElementById('debug-console-iframe');
            
            // Configurações
            const config = {
                minHeight: 100,
                minWidth: 200,
                handleSize: 5, // Aumentado para facilitar o arrasto
                resizing: false,
                startPos: { x: 0, y: 0 },
                startSize: { width: 0, height: 0 },
                startBody: { width: 0, height: 0 },
                direction: null,
                originalRect: null
            };
        
            // Criação das alças visuais para melhorar a usabilidade
            function createHandles() {
                const handles = {
                    n: document.createElement('div'),
                    e: document.createElement('div')
                };
            
                // Estilo comum
                const commonStyle = {
                    position: 'absolute',
                    backgroundColor: 'transparent',
                    zIndex: 1000
                };
            
                // Alça norte
                Object.assign(handles.n.style, commonStyle, {
                    top: '0',
                    left: '0',
                    right: '0',
                    height: config.handleSize + 'px',
                    cursor: 'n-resize'
                });
                handles.n.className = 'resize-handle resize-handle-n';
                
                // Alça oeste (contrário da leste)
                Object.assign(handles.e.style, commonStyle, {
                    top: '0',
                    left: '0',
                    bottom: '0',
                    width: config.handleSize + 'px',
                    cursor: 'w-resize'
                });
                handles.e.className = 'resize-handle resize-handle-w';
                
                // Adiciona as alças ao container
                container.appendChild(handles.n);
                container.appendChild(handles.e);
                
                // Adiciona eventos às alças
                handles.n.addEventListener('mousedown', (e) => {
                    startResize(e, { north: true, east: false, south: false, west: false });
                });
                
                handles.e.addEventListener('mousedown', (e) => {
                    startResize(e, { north: false, east: false, south: false, west: true });
                });
                
                return handles;
            }
        
            // Inicialização
            function init() {
                // Garante que o container tenha position para funcionar corretamente
                if (getComputedStyle(container).position === 'static') {
                    container.style.position = 'relative';
                }
                
                // Adiciona indicação visual ao passar o mouse
                addResizeStyles();
                
                // Cria as alças visuais
                const handles = createHandles();
            }
        
            // Inicia o processo de redimensionamento
            function startResize(e, direction) {
                // Captura dimensões iniciais
                const rect = container.getBoundingClientRect();
                config.originalRect = {
                    top: rect.top,
                    left: rect.left,
                    width: rect.width,
                    height: rect.height
                };
                
                // Captura dimensões iniciais do body
                const bodyRect = document.body.getBoundingClientRect();
                config.startBody = {
                    width: bodyRect.width,
                    height: bodyRect.height
                };
                
                // Inicia o processo de redimensionamento
                config.resizing = true;
                config.startPos = { x: e.clientX, y: e.clientY };
                config.startSize = {
                    width: rect.width,
                    height: rect.height,
                    top: container.offsetTop,
                    left: container.offsetLeft
                };
                config.direction = direction;
                
                // Adiciona eventos temporários
                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
                
                // Adiciona classe para indicar que está redimensionando
                container.classList.add('resizing');
                
                // Feedback visual - sombra ou borda durante o redimensionamento
                container.style.boxShadow = '0 0 10px rgba(0, 0, 255, 0.3)';
                
                // Previne comportamento padrão
                e.preventDefault();
                e.stopPropagation();
            }
        
            // Processa o movimento do mouse durante o redimensionamento
            function handleMouseMove(e) {
                if (!config.resizing) return;
                
                // Calcula o deslocamento do mouse
                const deltaX = e.clientX - config.startPos.x;
                const deltaY = e.clientY - config.startPos.y;
                
                // Determina a direção dinamicamente com base na posição atual do mouse em relação à posição inicial
                updateDynamicDirection(e);
                
                // Calcula novas dimensões
                let newWidth = config.startSize.width;
                let newHeight = config.startSize.height;
                let newTop = config.startSize.top;
                
                // Aplica o redimensionamento conforme a direção
                if (config.direction.north) {
                    const possibleHeight = config.startSize.height - deltaY;
                    if (possibleHeight >= config.minHeight) {
                        newHeight = possibleHeight;
                        newTop = config.startSize.top + deltaY;
                    }
                }
                
                if (config.direction.south) {
                    newHeight = Math.max(config.startSize.height + deltaY, config.minHeight);
                }
                
                if (config.direction.east) {
                    newWidth = Math.max(config.startSize.width + deltaX, config.minWidth);
                }
                
                if (config.direction.west) {
                    const possibleWidth = config.startSize.width - deltaX;
                    if (possibleWidth >= config.minWidth) {
                        newWidth = possibleWidth;
                        container.style.left = (config.startSize.left + deltaX) + 'px';
                    }
                }
                
                // Aplica as novas dimensões
                container.style.height = newHeight + 'px';
                container.style.width = newWidth + 'px';
                container.style.top = newTop + 'px';
                
                // Redimensiona o iframe
                if (iframe) {
                    iframe.style.width = '100%';
                    iframe.style.height = '100%';
                }
                
                // Ajusta o body conforme a classe do container
                adjustBody(newWidth, newHeight);
               
            }
        
            // Função específica para ajustar o body corretamente
            function adjustBody(newWidth, newHeight) {
                if (container.classList.contains('debug-sidebar')) {
                    // Se for uma barra lateral, ajusta a largura do body
                    const bodyWidth = `calc(100% - ${newWidth}px)`;
                    document.body.style.width = bodyWidth;
                    $('#adianti_right_panel').css('right', `${newWidth}px`);
                } else {
                    // Se for um painel inferior, ajusta a altura do body
                    const bodyHeight = `calc(100vh - ${newHeight}px)`;
                    document.body.style.height = bodyHeight;
                    $('#adianti_right_panel').css('height', bodyHeight);
                }
            }
        
            function updateDynamicDirection(e) {
                const rect = container.getBoundingClientRect();
            
                // Verifica se o mouse está acima ou abaixo do container para mudar direção norte/sul
                if (e.clientY < rect.top - 30) {
                    // Mouse está bem acima, vamos para norte
                    config.direction.north = true;
                    config.direction.south = false;
                } else if (e.clientY > rect.bottom + 30) {
                    // Mouse está bem abaixo, vamos para sul
                    config.direction.north = false;
                    config.direction.south = true;
                }
            
                // Verifica se o mouse está à esquerda ou à direita para mudar direção leste/oeste
                if (e.clientX < rect.left - 30) {
                    config.direction.west = true;
                    config.direction.east = false;
                } else if (e.clientX > rect.right + 30) {
                    config.direction.west = false;
                    config.direction.east = true;
                }
            }
        
            // Finaliza o redimensionamento
            function handleMouseUp(e) {
                if (!config.resizing) return;
                
                config.resizing = false;
                
                // Remove os eventos temporários
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
                
                // Remove classe e estilo de redimensionamento
                container.classList.remove('resizing');
                container.style.boxShadow = '';
                
                // Save the custom dimensions to localStorage
                const dimensions = {
                    width: container.offsetWidth,
                    height: container.offsetHeight,
                    top: container.offsetTop,
                    left: container.offsetLeft
                };
                
                const appPrefix = BuilderTemplate && BuilderTemplate.application_name ? 
                    BuilderTemplate.application_name + '_' : 'default_';
                
                localStorage.setItem(appPrefix + 'debug_panel_dimensions', JSON.stringify(dimensions));
            }
        
            // Adiciona estilos visuais para indicar áreas de redimensionamento
            function addResizeStyles() {
                const style = document.createElement('style');
                style.textContent = `
                    .resize-handle {
                        transition: background-color 0.2s;
                    }
                    
                    .resize-handle:hover {
                        background-color: rgba(0, 120, 255, 0.2);
                    }
                    
                    .resize-handle-n:hover {
                        border-top: 2px solid rgba(0, 120, 255, 0.5);
                    }
                    
                    .resize-handle-w:hover {
                        border-left: 2px solid rgba(0, 120, 255, 0.5);
                    }
                    
                    #debug-console-container.resizing {
                        user-select: none;
                    }
                    
                    #debug-console-container.resizing iframe {
                        pointer-events: none;
                    }
                `;
                document.head.appendChild(style);
            }

            init();
        })();
    }, 100); // Increased timeout for better reliability

        window.addEventListener('message', function(event) {

            if (event.data.type === 'command') {
                if(event.data.function == 'debugToggle')
                {
                    $('#debug-console-container').toggleClass('debug-minimized');
                    $('body').toggleClass('body-debug-minimized');
                    if($('#debug-console-container').hasClass('debug-sidebar'))
                    {
                        $('#debug-console-container').toggleClass('debug-sidebar');
                        $('#debug-console-container').css('height', '400px');
                        $('#debug-console-container').css('width', '100%');
                        $('#debug-console-container').css('top', 'auto');
                        $('#debug-console-container').css('left', 'auto');
                        $('#debug-console-container').css('right', '0px');

                        $('body').css('height', 'calc(100vh - 400px)');
                        $('body').css('width', '100%');

                        $('#adianti_right_panel').css('height', 'calc(100vh - 400px)');
                        $('#adianti_right_panel').css('right', '0px');
                    }
                }
                else if(event.data.function == 'debugToggleFullscreen')
                {
                    $('#debug-console-container').toggleClass('debug-fullscreen');
                }
                else if(event.data.function == 'debugToggleSidebar')
                {

                    if($('#debug-console-container').hasClass('debug-minimized'))
                    {
                        $('#debug-console-container').toggleClass('debug-minimized');
                        $('body').toggleClass('body-debug-minimized');
                    }

                    // Define app prefix for localStorage keys
                    const appPrefix = BuilderTemplate && BuilderTemplate.application_name ? 
                        BuilderTemplate.application_name + '_' : 'default_';
                        
                    if($('#debug-console-container').hasClass('debug-sidebar'))
                    {
                        $('#debug-console-container').css('height', '400px');
                        $('#debug-console-container').css('width', '100%');
                        $('#debug-console-container').css('top', 'auto');
                        $('#debug-console-container').css('left', '0px');

                        $('body').css('height', 'calc(100vh - 400px)');
                        $('body').css('width', '100%');

                        $('#adianti_right_panel').css('height', 'calc(100vh - 400px)');
                        $('#adianti_right_panel').css('right', '0px');
                        
                        // Save state to localStorage - not in sidebar mode
                        localStorage.setItem(appPrefix + 'debug_panel_sidebar', 'false');
                        
                        // Also save the current dimensions
                        const dimensions = {
                            width: '100%', // 100% width is stored as 100 for simplicity
                            height: 400,
                            top: $('#debug-console-container').offset().top,
                            left: 0
                        };
                        localStorage.setItem(appPrefix + 'debug_panel_dimensions', JSON.stringify(dimensions));
                    }
                    else
                    {
                        $('#debug-console-container').css('height', '100vh');
                        $('#debug-console-container').css('width', '30%');
                        $('#debug-console-container').css('top', '0px');
                        $('#debug-console-container').css('right', '0px');
                        $('#debug-console-container').css('left', 'auto');

                        $('body').css('width', 'calc(100% - 30%)');
                        $('body').css('height', '100vh');

                        $('#adianti_right_panel').css('right', '30%');
                        $('#adianti_right_panel').css('height', '100vh');
                        
                        // Save state to localStorage - in sidebar mode
                        localStorage.setItem(appPrefix + 'debug_panel_sidebar', 'true');
                        
                        // Also save the current dimensions
                        const dimensions = {
                            width: $('#debug-console-container').width(),
                            height: $('#debug-console-container').height(),
                            top: 0,
                            left: $('#debug-console-container').offset().left
                        };
                        localStorage.setItem(appPrefix + 'debug_panel_dimensions', JSON.stringify(dimensions));
                    }
                    $('#debug-console-container').toggleClass('debug-sidebar');
                }
                else if(event.data.function == 'hideDebugConsole')
                {
                    $('#debug-console-container').hide();
                    $('body').removeClass('body-debug-minimized');
                    $('body').css('width', '100%');
                    $('body').css('height', '100vh');

                    $('#adianti_right_panel').css('right', '0px');
                    $('#adianti_right_panel').css('height', '100vh');
                }
                else if(event.data.function == 'debugToggleFormDebugger')
                { 
                    if(!formDebuggerEnabled)
                    {
                        if (!$('#form-debugger-styles').length) {
                            $('<style id="form-debugger-styles"></style>')
                                .text(debugStyles)
                                .appendTo('head');
                        }
                
                        addDebugLabels();
                        addPageNameLabels();

                        formDebuggerEnabled = true;
                    }
                    else{
                        formDebuggerEnabled = false;
                        $(`.${DEBUG_LABEL_CLASS}`).remove();
                        $(`.${PAGE_NAME_CLASS}`).remove();
                    }

                    
                }
            }
        }, false);
        
        
        sendToDebugConsole({
            type: 'debugCommand',
            function: 'init',
            params: []
        });
    
        interceptAjaxRequests(function(request) {

            setTimeout(() => {
                request.type = 'http_request';
                System.addDebug(request);
            }, 1);

        });
    }

    const sendToDebugConsole = function(data) {
        // Aguardar o carregamento completo do iframe antes de enviar a mensagem
        $('#debug-console-iframe').on('load', function() {

            // O segundo parâmetro é a origem de destino. '*' permite qualquer origem
            // Em produção, você deve especificar a origem exata por segurança
            this.contentWindow.postMessage(data, '*');
        });
        
        if ( document.getElementById('debug-console-iframe').contentWindow ) {
            document.getElementById('debug-console-iframe').contentWindow.postMessage(data, '*');
        }
    }

    const addDebug = function(params) {
        const message = {
            type: 'debugCommand',
            function: 'addDebug',
            params: params
        };
        
        sendToDebugConsole(message);
    }

   
    const DEBUG_LABEL_CLASS = 'form-debug-label';
    const PAGE_NAME_CLASS = 'form-debug-page-name';
    
    // Estilos CSS para os rótulos de debug
    const debugStyles = `
        .${DEBUG_LABEL_CLASS} {
            border: 1px solid #000000;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            margin-top: 2px;
            border-radius: 3px;
            font-family: monospace;
            position: relative;
            z-index: 9999;
            background-color: #2a2727;
            margin-bottom: 5px;
            margin-top: 5px;
        }
        
        .${PAGE_NAME_CLASS} {
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: white;
            font-size: 12px;
            padding: 3px 8px;
            margin-bottom: 5px;
            font-family: monospace;
            font-weight: bold;
            display: block;
            width: fit-content;
        }
        
        /* Mostrar campos hidden */
        input[type="hidden"] {
            display: block !important;
            background-color: #ffeeee;
            border: 1px dashed #ff6666;
            height: 22px;
            margin: 5px 0;
            padding: 2px;
            opacity: 0.8;
        }
    `;
    
  
    // Função para adicionar rótulos de debug a todos os campos
    const addDebugLabels = function () {
        // Seleciona todos os inputs (incluindo hidden), selects e textareas
        
        $('input[type=hidden]').each(function(){
            $(this).attr('type', 'text');
            $(this).css('background-color', '#828df8')
            $(this).css('color', '#fff');
            $(this).css('border', '1px solid black');
        });

        $('input, select, textarea').each(function() {
            const fieldName = $(this).attr('name') || 'sem nome';
            const fieldId = $(this).attr('id') || 'sem id';
            
            // Verificar se o campo já tem um rótulo de debug
            if (!$(this).next('.' + DEBUG_LABEL_CLASS).length) {
                $('<div></div>')
                    .addClass(DEBUG_LABEL_CLASS)
                    .html(`Name: ${fieldName} <br> ID: ${fieldId}`)
                    .insertAfter(this);
            }
        });
    }
    
    // Função para adicionar títulos para divs com atributo page-name
    const addPageNameLabels = function () {
        $('div[page-name]').each(function() {
           const pageName = $(this).attr('page-name');
            
            // Verificar se já existe um label de page-name dentro do elemento
            if (pageName && !$(this).find('.' + PAGE_NAME_CLASS).length) {
                $('<div></div>')
                    .addClass(PAGE_NAME_CLASS)
                    .text(pageName)
                    .prependTo(this); // Uses prependTo to add as first child
            }
        });
    }

    return {
        addDebug: addDebug,
        initSelect2: initSelect2,
        initTwoFactorEmailForm: initTwoFactorEmailForm,
        initTwoFactorGoogleForm:initTwoFactorGoogleForm,
        onChangeImportDataDestinationTable: onChangeImportDataDestinationTable,
        fillCombo: fillCombo,
        clearCombo: clearCombo,
        checkMultipleTabs: checkMultipleTabs,
        initDebugConsole: initDebugConsole
    };

})();

System.lastRequestId = null;