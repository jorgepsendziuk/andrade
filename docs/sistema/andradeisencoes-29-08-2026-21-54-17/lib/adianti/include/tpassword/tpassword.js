function tpassword_start(id) {
    $(`#${id} button`).click(function() {
        var i = $(this).find('i');
        var input = $(this).prev();

        i.toggleClass('fa-eye-slash');
        i.toggleClass('fa-eye');

        if(input.attr('type') == 'text') {
            input.attr('type', 'password');
        } else {
            input.attr('type', 'text');
        }
    });
}

function tpassword_enable_strong_validation(id, options) {
  
    let validationRegex = [];
    let checklistItems = '';
  
    if (typeof options.minLength !== 'undefined' && options.minLength.value > 0) {
        validationRegex.push({ regex: new RegExp(`.{${options.minLength.value},}`) });
        checklistItems += `<li class='tpassword-list-item'><i class='fa fa-check'></i> <i class='fa fa-times'></i> ${options.minLength.message}</li>`;
    }
  
    if (typeof options.requireNumbers !== 'undefined' && options.requireNumbers.value) {
        validationRegex.push({ regex: /[0-9]/ });
        checklistItems += `<li class='tpassword-list-item'><i class='fa fa-check'></i> <i class='fa fa-times'></i> ${options.requireNumbers.message}</li>`;
    }
  
    if (typeof options.requireLowercase !== 'undefined' && options.requireLowercase.value) {
        validationRegex.push({ regex: /[a-z]/ });
        checklistItems += `<li class='tpassword-list-item'><i class='fa fa-check'></i> <i class='fa fa-times'></i> ${options.requireLowercase.message}</li>`;
    }
  
    if (typeof options.requireUppercase !== 'undefined' && options.requireUppercase.value) {
        validationRegex.push({ regex: /[A-Z]/ });
        checklistItems += `<li class='tpassword-list-item'><i class='fa fa-check'></i> <i class='fa fa-times'></i> ${options.requireUppercase.message}</li>`;
    }
  
    if (typeof options.requireSpecialChar !== 'undefined' && options.requireSpecialChar.value) {
        validationRegex.push({ regex: /[^A-Za-z0-9]/ });
        checklistItems += `<li class='tpassword-list-item'><i class='fa fa-check'></i> <i class='fa fa-times'></i> ${options.requireSpecialChar.message}</li>`;
    }
  
    $(`#${id}`).attr('showpopover', 'true');
    var passwordInp = $(`#${id}`)[0]; 
    passwordInp.addEventListener('keyup', function() {
        if($(`#${id}`).attr('showpopover') == 'true') {
            $(`#${id}`).attr('showpopover', 'false');
            $(passwordInp).popover({
                html: true,
                placement: 'top',
                container: 'body',
                content: `
                <ul class='tpassword-checklist' id='${id}-tpassword-checklist'>
                    ${checklistItems}
                </ul>
                `,
                title: options.popoverTitle ?? 'A senha precisa ter:',
            }).on('shown.bs.popover', function () {
                
            }).on('hidden.bs.popover', function () {
                $(`#${id}`).attr('showpopover', 'true');
            }).popover('show');
        }
        
        for (let i = 0; i < validationRegex.length; i++) {
            let isValid = validationRegex[i].regex.test($(`#${id} input`).val());
  
            if(isValid) {
                $($(`#${id}-tpassword-checklist .tpassword-list-item`)[i]).find('.fa-check').show();
                $($(`#${id}-tpassword-checklist .tpassword-list-item`)[i]).find('.fa-times').hide();
            } else {
                $($(`#${id}-tpassword-checklist .tpassword-list-item`)[i]).find('.fa-check').hide();
                $($(`#${id}-tpassword-checklist .tpassword-list-item`)[i]).find('.fa-times').show();
            }
        }
    });
  }