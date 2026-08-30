loading = true;

Application = {};
Application.translation = {
    'en': {
        'loading': 'Loading',
        'close': 'Close',
        'insert': 'Insert',
        'open_new_tab': 'Open on a new tab',
        'type_message': 'Type a message...',
        'send': 'Send',
        'attention': 'Attention!',
        'tab_warning_message': 'You already have a system tab open in your browser.<br>For better performance and security, please use only one tab at a time.',
        'close_this_tab': 'Close this tab',
        'visible_columns': 'Visible columns',
        'apply': 'Apply',
        'cancel': 'Cancel',
        'reset': 'Reset'
    },
    'pt': {
        'loading': 'Carregando',
        'close': 'Fechar',
        'insert': 'Inserir',
        'open_new_tab': 'Abrir em uma nova aba',
        'type_message': 'Digite uma mensagem...',
        'send': 'Enviar',
        'attention': 'Atenção!',
        'tab_warning_message': 'Você já possui uma aba do sistema aberta em seu navegador.<br>Para melhor performance e segurança, por favor, utilize apenas uma aba por vez.',
        'close_this_tab': 'Fechar esta aba',
        'visible_columns': 'Colunas visíveis',
        'apply': 'Aplicar',
        'cancel': 'Cancelar',
        'reset': 'Redefinir'
    },
    'es': {
        'loading': 'Cargando',
        'close': 'Cerrar',
        'insert': 'Insertar',
        'open_new_tab': 'Abrir en una nueva pestaña',
        'type_message': 'Digite una mensaje...',
        'send': 'Enviar',
        'attention': '¡Atención!',
        'tab_warning_message': 'Ya tienes una pestaña del sistema abierta en tu navegador.<br>Para un mejor rendimiento y seguridad, por favor, utiliza solo una pestaña a la vez.',
        'close_this_tab': 'Cerrar esta pestaña',
        'visible_columns': 'Columnas visibles',
        'apply': 'Aplicar',
        'cancel': 'Cancelar',
        'reset': 'Restablecer'
    }
};

Adianti.onClearDOM = function(){
	/* $(".select2-hidden-accessible").remove(); */
	/* $(".colorpicker-hidden").remove(); */
	$(".pcr-app").remove();
	$(".select2-display-none").remove();
	$(".tooltip.fade").remove();
	$(".select2-drop-mask").remove();
	/* $(".autocomplete-suggestions").remove(); */
	$(".datetimepicker").remove();
	$(".note-popover").remove();
	$(".dtp").remove();
	$("#window-resizer-tooltip").remove();
};


function showLoading() 
{ 
    if(loading)
    {
        __adianti_block_ui(Application.translation[Adianti.language]['loading']);
    }
}

Adianti.onBeforeLoad = function(url) 
{ 
    if (url.indexOf('&show_loading=false') > 0) {
        return true;
    }

    loading = true; 
    setTimeout(function(){showLoading()}, 400);
    if (!url.includes('&static=1') && !url.includes('&auto_scroll=0')) {
        $("html, body").animate({ scrollTop: 0 }, "fast");
    }
};

Adianti.onAfterLoad = function(url, data)
{ 
    loading = false; 
    __adianti_unblock_ui( true );
    
    // Fill page tab title with breadcrumb
    // window.document.title  = $('#div_breadcrumbs').text();
};

// set select2 language
$.fn.select2.defaults.set('language', $.fn.select2.amd.require("select2/i18n/pt"));
