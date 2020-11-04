$(document).ready(function() {
    $('#captcha').click(function() {
        $.post('in/captcha/captchabase64.php', function(captchabase64data) {
            var img_cpatcha_html = "<img src=" + captchabase64data + " id='captchaImg' alt='Verification code' height='20' width='58'>";
            $('#captchaShow').html(img_cpatcha_html);
        });
    });

    // input-focus
    $('input.form-control').focus(function(e){
        $(e.target).prev('.fa-pencil-alt').addClass('input-focus');
    });
    $('input.form-control').focusout(function(e){
        $(e.target).prev('.fa-pencil-alt').removeClass('input-focus');
    });
});

$(document).on('blur', '#account, #password, #checkPassword, #captcha', function() {
    if ($(this).val() != '') {
        $(this).css('border-color', '');
    }
});
$(document).on('change', '#registration_gcash_check', function(){
    if ($(this).prop('checked') == true) {
        $('#registration_gcash_check_div').css('border-color', '');
    }
});
$(document).on('click', '#submitBtn', function() {
    var checkCol = ['account', 'password', 'checkPassword', 'captcha'];
    var data = {};
    var csrftoken = csrf;

    $.each(checkCol, function(k, v) {
        if ($('#' + v).val() == '') {
            $('#' + v).css('border-color', 'red');
        } else {
            data[v] = $('#' + v).val();
        }
    });
    var registrationGcashCheck=false;
    switch ($('#registration_gcash_check').prop('checked')) {
        case true:
            registrationGcashCheck=true
            break;
        case false:
            $('#registration_gcash_check_div').css('border-color', 'red');
            break;
        default:
            registrationGcashCheck=true
            break;
    }

//'請再次確認開戶資訊，點擊「確定」新增'
    if (Object.keys(data).length == checkCol.length&&registrationGcashCheck) {
        if (confirm(lang.ConfirmCreateAccountAgain) == true) {
            data['type'] = $('input[name="registerTypeOptions"]:checked').val();

            if (data['type'] == 'A') {
                data['preferential'] = $('#preferential').val();
                data['dividend'] = $('#dividend').val();
            }

            $.ajax({
                type: 'POST',
                url: 'register_agenthelp_action.php',
                data: {
                    data: data,
                    action: 'add',
                    csrftoken: csrftoken
                },
                success: function(resp) {
                    var res = JSON.parse(resp);
//'已完成開戶，是否前往查看?'
                    if (res.status == 'success') {
                        if (confirm(lang.CreateAccountSuccess) == true) {
                            window.location.href = './member_management.php?a=' + res.acc;
                        } else {
                            $.each(checkCol, function(k, v) {
                                if ($('#' + v).val() != '') {
                                    $('#' + v).val('');
                                }
                            });
                        }
                    } else {
                        alert(res.result);
                    }
                }
            });
        }
    } else {
        alert(lang.ConfirmAllFields);//'請確認所有必填欄位階已正確輸入'
    }
});

$(document).on('click', '#showPw', function() {
    var type = $('#password').attr('type');

    if (type == 'password') {
        $('#password').attr('type', 'text');
        $('#showPwIcon').removeClass('glyphicon glyphicon-eye-open').addClass('glyphicon glyphicon-eye-close');
    } else {
        $('#password').attr('type', 'password');
        $('#showPwIcon').removeClass('glyphicon glyphicon-eye-close').addClass('glyphicon glyphicon-eye-open');
    }
});

$(document).on('change', 'input[type=radio][name=registerTypeOptions]', function() {
    if (this.value == 'A') {
        if ($('.agentSettingArea').length == 0) {
            $('.linkSettingArea').after(dividendPreferentialSettingHtml);
        }
    } else if (this.value == 'M') {
        if ($('.agentSettingArea').length != 0) {
            //$('.agentSettingArea').next().remove();
            $('.agentSettingArea').remove();
            $('.agentSettingArea').remove();
        }
    }
});