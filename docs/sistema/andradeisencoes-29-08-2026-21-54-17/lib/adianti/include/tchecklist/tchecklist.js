function tchecklist_row_enable_check(table_id)
{
    // clique na linha
    $('table#'+table_id+' tbody tr').click(function(ev) {
        
        var check   = $(this).find('[type=checkbox]:first');

        if( ($(ev.target).is(':input') && $(ev.target).attr('id') != check.attr('id')) || $(ev.target).closest('.tcheckgroup_label').length > 0)
        {
            return true;
        }

        ev.stopImmediatePropagation();
        ev.stopPropagation();
        ev.preventDefault();

        var current = $(check).is(':checked');
        $(check).prop('checked', !current);

        if (!current) {
            $(this).addClass('selected')
        }
        else {
            $(this).removeClass('selected');
        }
        
        tchecklist_fire_onselect($('table#'+table_id));
    });

    $('table#'+table_id+' tbody tr').each(function(){
        var check = $(this).find('[type=checkbox]:first');

        check.click(function(ev){
            ev.stopPropagation();
        
            var current = $(this).is(':checked');
            var tr = $(this).closest('tr');
            
            if (current) {
                $(tr).addClass('selected')
            }
            else {
                $(tr).removeClass('selected');
            }
            
            tchecklist_fire_onselect($('table#'+table_id));
        });
    });
}

function tchecklist_fire_onselect(table)
{
    var action = table.attr('onselect');
    
    if (action)
    {
        var checklist_name   = table.attr('name');
        
        var checks = [];
        
        table.find('tr.selected').each(function(){

            checks.push($(this).find('input[type="checkbox"]:first'))

        });

        var data = {};
        data[checklist_name] = [];
        checks.each(function (k,v) {
            var input_name = $(v).attr('name');
            input_name = input_name.replace('check_'+checklist_name+'_', '');
            data[checklist_name].push(base64_decode(input_name));
        });
        
        __adianti_post_exec(action, data, null, undefined, '1');
    }
}

function tchecklist_select_all(generator, table_id)
{
    $('table#'+table_id+' tbody tr:visible').each(function(){

        var tr = $(this);
        var check = tr.find('input[type="checkbox"]:first');

        if (!generator.checked && check.is(':checked') ) {
            $(check).prop('checked', false);
            $(tr).removeClass('selected');
        }
        else if (generator.checked && !check.is(':checked') ) {
            $(check).prop('checked', true);
            $(tr).addClass('selected');
        }
    });
    
    tchecklist_fire_onselect($('table#'+table_id));
}

function tchecklist_enable_field(name) {
    try {
        if( $('table.tchecklist[name="'+name+'"]').length > 0) {
            $('table.tchecklist[name="'+name+'"]').parent().find('.tchecklist-disable').remove();
        }
    }
    catch (e) {
        console.log(e);
    }
}

function tchecklist_disable_field(name) {
    try {
        if( $('table.tchecklist[name="'+name+'"]').length > 0) {
            $('table.tchecklist[name="'+name+'"]').parent().prepend('<div class="tchecklist-disable"></div>')
        }
    }
    catch (e) {
        console.log(e);
    }
}
