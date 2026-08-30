function tcombo_enable_field(form_name, field) {
    
    if(typeof form_name != 'undefined' && form_name != '') {
        form_name_sel = 'form[name="'+form_name+'"] ';
    }
    else {
        form_name_sel = '';
    }

    var selector = '[name="'+field+'"]';
    if (field.indexOf('[') == -1 && $('#'+field).length >0) {
        var selector = '#'+field;
    }
    
    try {
        if ($(form_name_sel + selector).attr('role') == 'tcombosearch') {
            tmultisearch_enable_field(form_name, field);
        }
        else {
            $(form_name_sel + selector).attr('onclick', null);
            $(form_name_sel + selector).css('pointer-events',   'auto');
        }

        $(form_name_sel + selector).removeClass('tcombo_disabled');
    } catch (e) {
        console.log(e);
    }
}

function tcombo_disable_field(form_name, field) {
    
    if(typeof form_name != 'undefined' && form_name != '') {
        form_name_sel = 'form[name="'+form_name+'"] ';
    }
    else {
        form_name_sel = '';
    }

    var selector = '[name="'+field+'"]';
    if (field.indexOf('[') == -1 && $('#'+field).length >0) {
        var selector = '#'+field
    }
    
    try {
        if ($(form_name_sel + selector).attr('role') == 'tcombosearch') {
            tmultisearch_disable_field(form_name, field);
        }
        else {
            $(form_name_sel + selector).attr('onclick', 'return false');
            $(form_name_sel + selector).attr('tabindex', '-1');
            $(form_name_sel + selector).css('pointer-events', 'none');
        }

        $(form_name_sel + selector).addClass('tcombo_disabled');
    } catch (e) {
        console.log(e);
    }
}

function tcombo_add_option(form_name, field, key, value)
{
    var key = key.replace(/"/g, '');
    
    if(typeof form_name != 'undefined' && form_name != '') {
        form_name = 'form[name="'+form_name+'"] ';
    }
    else {
        form_name = '';
    }

    var selector = 'select[name="'+field+'"]';
    
    if (field.indexOf('[') == -1 && $('#'+field).length >0) {
        var selector = '#'+field
    }
    
    var optgroups =  $(form_name + selector).find('optgroup');
    
    if( optgroups.length > 0 ) {
        $('<option value="'+key+'">'+value+'</option>').appendTo(optgroups.last());
    }
    else {
        $('<option value="'+key+'">'+value+'</option>').appendTo(form_name + selector);
    }
    
}

function tcombo_create_opt_group(form_name, field, label)
{
    if(typeof form_name != 'undefined' && form_name != '') {
        form_name = 'form[name="'+form_name+'"] ';
    }
    else {
        form_name = '';
    }
    
    var selector = '[name="'+field+'"]';
    if (field.indexOf('[') == -1 && $('#'+field).length >0) {
        var selector = '#'+field
    }
    
    $('<optgroup label="'+label+'"></optgroup>').appendTo(form_name + selector);
}

function tcombo_clear(form_name, field, fire_events)
{
    if (typeof fire_events == 'undefined') {
        fire_events = true;
    }
    
    if (typeof form_name != 'undefined' && form_name != '') {
        var form_name = 'form[name="'+form_name+'"] ';
    }
    else {
        var form_name = '';
    }
    
    var selector = '[name="'+field+'"]';
    if (field.indexOf('[') == -1 && $('#'+field).length >0) {
        var selector = '#'+field
    }
    
    var field = $(form_name + selector);
    
    if (field.attr('role') == 'tcombosearch') {
        if (field.find('option:not(:disabled)').length>0) {
            // scoped version of change to avoid indirectly fire events
            field.val('').empty().trigger('change.select2');
        }
    }
    else {
        field.val(false);
        field.html('');
    }
    
    if (fire_events) { 
        if (field.attr('changeaction')) {
            tform_events_hang_exec( field.attr('changeaction') );
        }
    }
}

function tcombo_enable_search(field, placeholder)
{
    $(field).removeAttr('onchange');
    
    const options = {
        // Permite HTML na mensagem de "Nenhum resultado"
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

    select2_prepare_no_results(options, field);

    $(field).select2(
        options
    ).on('change.changeaction', function (e) {
        new Function( $( field ).attr('changeaction'))();
    });
}

function tcombo_set_quick_register_value(element, id)
{
    const val = $(element).val();
    $('#'+id+'_quickregister').val(val);
    $('#'+id+'_btn').attr('data-quick_register_value', val);
}

function select2_prepare_no_results(options, field)
{
    
    if(typeof $(field).attr('noresultsbtnprops') != 'undefined')
    {
        const props = JSON.parse(base64_decode($(field).attr('noresultsbtnprops')));

        options.language = {
            noResults: function() {

                // Obtém a mensagem padrão do Select2 em português
                const defaultMessage = props.noResultsMessage || $.fn.select2.defaults.defaults.language.noResults();
                const btn = $(props.btn);

                if($(field).data('row') != 'undefined')
                {
                    btn.attr('data-row', $(field).data('row'));
                    btn.attr('data-id', $(field).attr('id'));
                    btn.attr('id', $(field).attr('id'));
                }

                const btnHtml = btn.get(0).outerHTML

                // Concatena a mensagem padrão com o botão
                return `
                    <span>${defaultMessage}</span>
                    <div class="no-results-wrapper">
                        ${btnHtml}
                    </div>
                `;
            }
        };
    }

    if(typeof $(field).attr('noresultsquickregisterprops') != 'undefined')
    {
        const props = JSON.parse(base64_decode($(field).attr('noresultsquickregisterprops')));
        
        options.language = {
            noResults: function() {
                // Obtém a mensagem padrão do Select2 em português
                const defaultMessage = props.noResultsMessage || $.fn.select2.defaults.defaults.language.noResults();
                const lastSearchTerm = $(field).data('select2').$dropdown.find('.select2-search__field').val();
                let input = props.input;
                let btn = props.btn;
                
                if(lastSearchTerm)
                {   
                    input = input.replace('value=""', 'value="'+lastSearchTerm+'"');
                    btn = btn.replace('data-quick_register_value=""', 'data-quick_register_value="'+lastSearchTerm+'"');
                }

                const btnJq = $(btn);

                if($(field).data('row') != 'undefined')
                {
                    btnJq.attr('data-row', $(field).data('row'));
                    btnJq.attr('data-id', $(field).attr('id'));
                    btnJq.attr('id', $(field).attr('id'));
                }

                const btnHtmlJq = btnJq.get(0).outerHTML

                // Concatena a mensagem padrão com o botão
                return `
                    <span>${defaultMessage}</span>
                    <div class="no-results-wrapper">
                        ${input}
                        ${btnHtmlJq}
                    </div>
                `;
            }
        };
    }

    if(typeof $(field).attr('noresultsquickregisterprops') != 'undefined')
    {
        $(field).on('select2:open', function() {
            const $search = $(this).data('select2').$dropdown.find('.select2-search__field');
            $search.off('input').on('input', function(e) {
                const searchTerm = $(this).val();
                
                $(field+'_quickregister').val(searchTerm);
                $(field+'_btn').attr('data-quick_register_value', searchTerm);

                if($(field).data('row') != 'undefined')
                {
                    $(field+'_btn').attr('data-row', $(field).data('row'));
                    $(field+'_btn').attr('data-id', $(field).attr('id'));
                    $(field+'_btn').attr('id', $(field).attr('id'));
                }

            });
        });
    }
}
