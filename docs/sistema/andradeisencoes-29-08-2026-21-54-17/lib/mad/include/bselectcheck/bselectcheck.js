function bselectcheck_start(field, placeholder) {
    // Flag global para rastrear estados
    let internalSelectionChange = false;
    const options = {
        allowClear: true,
        multiple: true,
        closeOnSelect: false,
        placeholder: placeholder,
        
        templateSelection: function(data, container) {
            if (data.id === '') {
                return data.text;
            }
            return data.text;
        },
        templateResult: function(data) {
            if (data.loading) return data.text;

            // Cria o container com checkbox antes do texto
            var $container = $(
                '<div class="select2-result-item">' +
                    (data.element.tagName === 'OPTGROUP' ? '' : '<input type="checkbox" class="select2-result-item__checkbox">') +
                    '<span class="select2-result-item__text">' + data.text + '</span>' +
                '</div>'
            );
            
            // Adiciona o data-option-value ao checkbox se não for um optgroup
            if (data.element.tagName !== 'OPTGROUP' && data.id) {
                $container.find('input[type="checkbox"]').attr('data-option-value', data.id);
            }
            
            // Marca o checkbox se o item estiver selecionado
            var isSelected = $(data.element).is(':selected');
            $container.find('input[type="checkbox"]').prop('checked', isSelected);
            
            return $container;
        }
    };

    $(field).removeAttr('onchange');

    // Inicializa o select2 sem manipuladores extras
    const select2element = $(field).select2(options);
    
    // IMPORTANTE: Armazenamos uma referência para o elemento original
    select2element.data('original-element', field);
    
    // Manipulador unificado para mudanças
    select2element.on('change.internalupdate', function(e) {
        // Atualizamos o resumo visual
        bselectcheck_update_summary(this, placeholder);
        
        // Se não foi nossa lógica que disparou a mudança, sincronizamos os checkboxes
        if (!internalSelectionChange) {
            setTimeout(function() {
                bselectcheck_sync_checkboxes(select2element);
            }, 10);
        }
    });
    
    // Executamos ação de change apenas após fechamento do dropdown
    select2element.on('select2:close', function() {
        if ($(field).attr('changeaction')) {
            new Function($(field).attr('changeaction'))();
        }
    });

    // NOVA função de sincronização mais robusta
    function bselectcheck_sync_checkboxes(select2elem) {
        const dropdown = select2elem.data('select2').$dropdown;
        
        if (dropdown && dropdown.length) {
            // Abordagem direta: atualiza todos os checkboxes visíveis
            dropdown.find('.select2-results__option:visible').each(function() {
                const data = $(this).data('data');
                if (data && data.id) {
                    // Obtém estado direto do option no select original
                    const isSelected = select2elem.find('option[value="' + data.id + '"]').prop('selected');
                    
                    // Atualiza checkbox e atributo aria-selected
                    const checkbox = $(this).find('.select2-result-item__checkbox');
                    if (checkbox.length) {
                        checkbox.prop('checked', isSelected);
                        $(this).attr('aria-selected', isSelected ? 'true' : 'false');
                    }
                }
            });
            
            // Abordagem alternativa (mais robusta): atualiza pela estrutura de dados do Select2
            const selectData = select2elem.data('select2');
            if (selectData && selectData.data && selectData.data()) {
                const items = selectData.data();
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    if (item && item.id) {
                        const isSelected = select2elem.find('option[value="' + item.id + '"]').prop('selected');
                        const $option = dropdown.find(`.select2-results__option[aria-selected][id$="-${item.id}"]`);
                        
                        if ($option.length) {
                            $option.find('.select2-result-item__checkbox').prop('checked', isSelected);
                            $option.attr('aria-selected', isSelected ? 'true' : 'false');
                        }
                    }
                }
            }
        }
    }
    
    // Interceptamos o momento logo após a abertura
    select2element.on('select2:open', function() {        
        // Força sincronização após pequeno delay para garantir que os itens estão renderizados
        setTimeout(function() {
            bselectcheck_sync_checkboxes(select2element);
        }, 50);
    });
    
    // Após mudanças de seleção/desseleção, sincronizamos
    select2element.on('select2:select select2:unselect', function() {
        // Força sincronização após pequeno delay
        setTimeout(function() {
            bselectcheck_sync_checkboxes(select2element);
        }, 10);
    });
    
    // Função para forçar refresh dos estados dos checkboxes
    const refreshCheckboxStates = function() {
        const dropdown = select2element.data('select2').$dropdown;
        if (dropdown.is(':visible')) {
            dropdown.find('.select2-results__option').each(function() {
                const optionId = $(this).data('data')?.id;
                if (optionId) {
                    const isOptionSelected = select2element.find('option[value="' + optionId + '"]').prop('selected');
                    const checkbox = $(this).find('input[type="checkbox"]');
                    const currentCheckboxState = checkbox.prop('checked');
                    
                    // Só atualiza se houver diferença para evitar loops
                    if (currentCheckboxState !== isOptionSelected) {
                        checkbox.prop('checked', isOptionSelected);
                        $(this).attr('aria-selected', isOptionSelected ? 'true' : 'false');
                    }
                }
            });
        }
    };
    
    // Atualiza os estados dos checkboxes após a renderização
    select2element.on('select2:rendered', function() {
        refreshCheckboxStates();
    });
    
    // Adiciona manipulador para garantir atualização regular dos checkboxes
    select2element.on('mouseup', '.select2-results__option', function() {
        const optionId = $(this).data('data')?.id;
        setTimeout(function() {
            refreshCheckboxStates();
        }, 50);
    });
    
    // Manipulador simplificado para unselecting
    select2element.on('select2:unselecting', function(e) {
        // Evitamos comportamento padrão apenas para o tempo mínimo necessário
        $(this).data('unselecting', true);
        setTimeout(() => $(this).removeData('unselecting'), 10);
    }).on('select2:opening', function(e) {
        if ($(this).data('unselecting')) {
            $(this).removeData('unselecting');
            e.preventDefault();
        }
    });
    
    select2element.on('select2:clearing', function(e) {
        var $container = $(this).next('.select2-container');            
        setTimeout(function(){
            $container.find('.select2-selection__rendered')
            .html('<li class="select2-selection__placeholder">' + placeholder + '</li>');
        });
    });

    // COMPLETAMENTE NOVA implementação de clique nos itens do dropdown
    $(document).off('click', '.select2-results__option, .select2-result-item__checkbox').on('click', '.select2-results__option, .select2-result-item__checkbox', function(e) {
        // Identifica se o clique foi no checkbox ou no item
        const clickedOnCheckbox = $(e.target).hasClass('select2-result-item__checkbox');
        
        // Encontra o item do dropdown, independente de onde foi o clique
        const $option = clickedOnCheckbox ? $(this).closest('.select2-results__option') : $(this);
        
        // Impedimos comportamento padrão de ambos os elementos
        e.preventDefault();
        e.stopPropagation();
        
        // Se não conseguirmos obter dados, abortamos
        if (!$option.data('data')) {
            return false;
        }
        
        // Obtém ID e elemento select
        const optionId = $option.data('data').id;
        const selectElement = $option.closest('.select2-results').siblings('.select2-search--dropdown').closest('.select2-container').siblings('select');
        
        if (!selectElement.length) {
            return false;
        }
        
        // Obtém estado atual diretamente do elemento option no select
        const currentState = selectElement.find(`option[value="${optionId}"]`).prop('selected');
        
        // Indicamos que esta é uma alteração interna
        internalSelectionChange = true;
        
        try {
            // Invertemos manualmente o estado do option
            const newState = !currentState;
            
            // Alteramos no select original
            selectElement.find(`option[value="${optionId}"]`).prop('selected', newState);
            
            // Atualizamos o checkbox visualmente
            $option.find('.select2-result-item__checkbox').prop('checked', newState);
            $option.attr('aria-selected', newState ? 'true' : 'false');
            
            // Notificamos o Select2 sobre a mudança
            selectElement.trigger('change');
            
            // Forçamos atualização do resumo
            bselectcheck_update_summary(selectElement, placeholder);
        } finally {
            // Mesmo se der erro, resetamos a flag
            internalSelectionChange = false;
        }
        
        return false;
    });
    
    // SIMPLIFICADO: Abertura do select2 em um clique
    $(document).off('click', '.select2-selection').on('click', '.select2-selection', function(e) {
        const $select = $(this).closest('.select2-container').siblings('select');
        
        if ($select.length) {
            // Pequeno atraso para evitar conflitos
            setTimeout(function() {
                $select.select2('open');
            }, 10);
        }
    });
    
    // Inicializações adicionais
    const $container = $(field).next('.select2-container');
    if (!$container.find('.select2-selection__arrow').length) {
        $container.find('.select2-selection').append('<span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span>');
    }
    
    // Classes CSS
    select2element.data('select2').$dropdown.addClass('bselectcheck');
    select2element.data('select2').$container.addClass('bselectcheck-container');
    
    // Estado inicial
    bselectcheck_update_summary(field, placeholder);
    
    // Redesenhado: botões e funcionalidades do dropdown
    select2element.on('select2:open', function() {
        const dropdown = select2element.data('select2').$dropdown;
        const selectWidth = $(this).next('.select2-container').outerWidth();
        
        dropdown.css('width', selectWidth + 'px');
                
        dropdown.find('.bselectcheck-custom-dropdown-header').remove();
        
        var searchInput = $('<input type="text" class="bselectcheck-custom-search-input">');
        
        var buttonsContainer = $('<div class="bselectcheck-buttons-container"></div>');
        var selectAllBtn = $('<span  class="bselectcheck-btn btn btn-default btn-sm pull-left"> <i class="far fa-check-square" style=";padding-right:4px"></i> </button>');
        var deselectAllBtn = $('<span  class="bselectcheck-btn btn btn-default btn-sm pull-left"><i class="far fa-square" style=";padding-right:4px"></i></button>');
        var invertSelectionBtn = $('<span  class="bselectcheck-btn btn btn-default btn-sm pull-left"><i class="fas fa-retweet" style=";padding-right:4px"></i></button>');
        
        buttonsContainer.append(selectAllBtn, deselectAllBtn, invertSelectionBtn);
        
        var headerDiv = $('<div class="bselectcheck-custom-dropdown-header"></div>')
            .append(searchInput)
            .append(buttonsContainer);
            
        dropdown.prepend(headerDiv);
        
        // Redesenhado: botões e funcionalidades do dropdown para sincronização correta
        selectAllBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
        
            // Marcamos que esta é uma alteração interna
            internalSelectionChange = true;
            
            try {
                // Seleciona todas as opções no select original
                select2element.find('option').prop('selected', true);
                
                // Força atualização da interface visual diretamente
                const dropdown = select2element.data('select2').$dropdown;
                dropdown.find('.select2-results__option:visible').each(function() {
                    $(this).attr('aria-selected', 'true');
                    $(this).find('.select2-result-item__checkbox').prop('checked', true);
                });
                
                // Força a atualização do Select2
                select2element.trigger('change');
                
                // Atualiza o summary customizado
                bselectcheck_update_summary(select2element, placeholder);
                
                // Segunda verificação de sincronização após delay
                setTimeout(function() {
                    bselectcheck_sync_checkboxes(select2element);
                }, 50);
            } finally {
                internalSelectionChange = false;
            }
        });
        
        // Desmarcar Todos - implementação robusta
        deselectAllBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
        
            // Marcamos que esta é uma alteração interna
            internalSelectionChange = true;
            
            try {
                // Desmarca todas as opções do select original
                select2element.find('option').prop('selected', false);
                
                // Força atualização da interface visual diretamente
                const dropdown = select2element.data('select2').$dropdown;
                dropdown.find('.select2-results__option:visible').each(function() {
                    $(this).attr('aria-selected', 'false');
                    $(this).find('.select2-result-item__checkbox').prop('checked', false);
                });
                
                // Força a atualização do Select2
                select2element.trigger('change');
                
                // Atualiza o summary customizado
                bselectcheck_update_summary(select2element, placeholder);
                
                // Segunda verificação de sincronização após delay
                setTimeout(function() {
                    bselectcheck_sync_checkboxes(select2element);
                }, 50);
            } finally {
                internalSelectionChange = false;
            }
        });
        
        // RADICAL FIX: Botão de inversão completamente reescrito
        invertSelectionBtn.off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Evita que o Select2 processe eventos extras
            internalSelectionChange = true;
            
            try {
                // NOVA ABORDAGEM: Usando o atributo data-option-value nos checkboxes
                const dropdown = select2element.data('select2').$dropdown;
                
                if (!dropdown || dropdown.length === 0) {
                    return;
                }
                
                // Encontre todos os checkboxes visíveis com data-option-value
                const $checkboxes = dropdown.find('.select2-results__option:visible .select2-result-item__checkbox[data-option-value]');
                
                if ($checkboxes.length === 0) {
                    return;
                }
                
                // Para cada checkbox, inverta o estado do option correspondente
                $checkboxes.each(function(index) {
                    // Obter o valor do option associado ao checkbox
                    const optionValue = $(this).attr('data-option-value');
                    
                    if (!optionValue) return; // Pula se não tiver valor
                    
                    // Encontrar o option correspondente
                    const $option = select2element.find(`option[value="${optionValue}"]`);
                    
                    if ($option.length === 0) {
                        return;
                    }
                    
                    // Obter estado atual e invertê-lo
                    const currentState = $option.prop('selected');
                    const newState = !currentState;
                    
                    // Alterar o estado do option
                    $option.prop('selected', newState);
                    
                    // Atualizar o checkbox visualmente
                    $(this).prop('checked', newState);
                    $(this).closest('.select2-results__option').attr('aria-selected', newState ? 'true' : 'false');
                });
                
                // Notificar o Select2 sobre as alterações
                select2element.trigger('change');
                
                // Atualizar o resumo visual
                bselectcheck_update_summary(select2element, placeholder);
                
                // Sincronizar os checkboxes visualmente após um delay
                setTimeout(function() {
                    bselectcheck_sync_checkboxes(select2element);
                }, 100);
                
            } catch (error) {
                console.error('ERRO DURANTE INVERSÃO:', error);
            } finally {
                internalSelectionChange = false;
            }
        });
        
        // Garante a atualização visual após renderização dos itens
        setTimeout(function() {
            bselectcheck_sync_checkboxes(select2element);
        }, 100);
        
        // Filtragem de itens por texto
        searchInput.on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            dropdown.find('.select2-results__options li').each(function() {
                if (!$(this).hasClass('select2-results__message')) {
                    var text = $(this).text().toLowerCase();
                    if (text.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                }
            });
        });
    
        setTimeout(function() {
            searchInput.focus();
        }, 0);
    });

    // Melhoria para manipulação de seleção/desseleção
    select2element.on('select2:select select2:unselect', function(e) {
        var isSelected = e.params.data.selected;

        if(typeof e.params.originalEvent != 'undefined') {
            var $clicked = $(e.params.originalEvent.currentTarget);
            $clicked.find('input[type="checkbox"]').prop('checked', isSelected);
        }
        
        // Verifica estado real da opção no select
        const realState = select2element.find('option[value="' + e.params.data.id + '"]').prop('selected');
        
        // Garante que todos os checkboxes refletem o estado real atual
        setTimeout(function() {

            const dropdown = select2element.data('select2').$dropdown;
            dropdown.find('.select2-results__option').each(function() {
                const optionId = $(this).data('data')?.id;
                if (optionId) {
                    const isOptionSelected = select2element.find('option[value="' + optionId + '"]').prop('selected');
                    $(this).find('input[type="checkbox"]').prop('checked', isOptionSelected);
                    $(this).attr('aria-selected', isOptionSelected ? 'true' : 'false');
                }
            });
        }, 10);
    });

    select2element.on('select2:clearing', function(e) {
        var $container = $(this).next('.select2-container');            
        setTimeout(function(){
            $container.find('.select2-selection__rendered')
            .html('<li class="select2-selection__placeholder">' + placeholder + '</li>');
        });
    });
}

function bselectcheck_update_summary(element, placeholder) {
    const $element = $(element);
    const selectedItems = $element.select2('data');
    const count = selectedItems?.length;
    const $container = $element.next('.select2-container');
    const $rendered = $container.find('.select2-selection__rendered');

    // Não manipula diretamente o clear button, deixa o Select2 gerenciar isso
    if (count === 0) {
        $rendered.find('li:not(.select2-search--inline)').remove();
        $rendered.find('.select2-selection__choice, .select2-selection__clear').remove();
        $rendered.prepend(`<li class="select2-selection__placeholder">${placeholder}</li>`);
    } else {
        const { visibleText, remainingCount } = bselectcheck_calculate_visible_items($container, selectedItems);
        let summaryText = visibleText;
        
        if (remainingCount > 0) {
            summaryText += ` (+${remainingCount} itens)`;
        }
        
        $rendered.find('li:not(.select2-search--inline)').remove();
        $rendered.prepend(`<span style="width: 100%;max-width: 100%;"class="select2-selection__choice">${summaryText}</span>`);
    }
}

function bselectcheck_calculate_visible_items($container, selectedItems) {
    const $measure = $('<span>').css({
        'font-size': $container.css('font-size'),
        'font-family': $container.css('font-family'),
        'visibility': 'hidden',
        'position': 'absolute',
        'white-space': 'nowrap'
    }).appendTo('body');

    const availableWidth = $container.width() - 80;
    let visibleItems = [];
    let remainingCount = 0;

    // Filtra itens inválidos antes de começar
    const validItems = selectedItems?.filter(item => item && item.text?.trim());

    for (let i = 0; i < validItems?.length??0; i++) {
        const currentText = visibleItems.length === 0 
            ? validItems[i].text.trim()
            : visibleItems.join(", ") + ", " + validItems[i].text.trim();
        
        // Só adiciona o texto de "& +X itens" se houver itens restantes
        const testText = i < validItems.length - 1 
            ? currentText + ` (+${validItems.length - i - 1} itens)`
            : currentText;
        
        $measure.text(testText);
        
        if (availableWidth < $measure.width()) {
            remainingCount = validItems.length - i;
            break;
        }
        
        visibleItems.push(validItems[i].text.trim());
    }

    $measure.remove();

    return {
        visibleText: visibleItems.length > 0 ? visibleItems.join(", ") : "",
        remainingCount: remainingCount
    };
}