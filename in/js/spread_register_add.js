$(document).on('change', 'input[type=radio][name=registerTypeOptions]', function() {
    if (this.value == 'A') {
        if ($('.agentSettingArea').length == 0) {
            $('.linkSettingArea').after(dividendPreferentialSettingHtml);
        }
    } else if (this.value == 'M') {
        if ($('.agentSettingArea').length != 0) {
            $('.agentSettingArea').remove();
        }
    }
});

$(document).ready(function() {
    
    // input-focus
    $('input.form-control').focus(function(e){
        $(e.target).prev('.fa-pencil-alt').addClass('input-focus');
    });
    $('input.form-control').focusout(function(e){
        $(e.target).prev('.fa-pencil-alt').removeClass('input-focus');
    });
});

$(document).on('click', '#addLinkBtn', function() {
    var csrftoken = csrf;
    var data = {
        'registerType': $('input[name="registerTypeOptions"]:checked').val(),
        'validityPeriod': $('#validityPeriod').val(),
        'note': $('#note').val()
    };

    if (data.registerType == 'A') {
        var agentSetting = {
            'preferential': $('#preferential').val(),
            'dividend': $('#dividend').val()
        }

        data = Object.assign(data, agentSetting);
    }

    $.ajax({
        type: 'POST',
        url: 'spread_register_action.php',
        data: {
            data: data,
            action: 'add',
            csrftoken: csrftoken
        },
        success: function(resp) {
            // $('#result').html(resp);
            // console.log(resp);
            var res = JSON.parse(resp);

            if (res.status == 'success') {
                // alert(res.result);
//'邀請碼已新增，是否前往查看?'
                if (confirm(lang.Invitationcodeadded) == true) {
                    window.location.href = './spread_register.php?t=' + res.type + '&i=' + res.id;
                } else {
                    location.reload();
                }
            } else {
                alert(res.result);
            }
        }
    });
});

function getPostData() {
    var data = {
        'registerType': $('input[name="registerTypeOptions"]:checked').val(),
        'note': $('#note').val()
    };
}