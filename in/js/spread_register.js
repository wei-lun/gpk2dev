$(document).ready(function() {
    var clipboard = new ClipboardJS('#copyLinkCode');

    clipboard.on('success', function(e) {
        alert(lang.copyLinkCodesuccess);//'复制成功'

        e.clearSelection();
    });

    clipboard.on('error', function(e) {
        alert(lang.copyLinkCodefaild);//'复制失败'
    });
});

$(document).on('change', 'input[type=radio][name=registerTypeOptions]', function() {
    var csrftoken = csrf;
    var type = this.value;
    if(type=='A'){
      $('#registration_gcash_alert').show()
    }
    else{
      $('#registration_gcash_alert').hide()
    }

    $.ajax({
        type: 'POST',
        url: 'spread_register_action.php',
        data: {
            type: type,
            action: 'typeData',
            csrftoken: csrftoken
        },
        success: function(resp) {
            var tableContent = '';
            var res = JSON.parse(resp);

            $('#linkDataTableBody').empty();

            if (res.status == 'success') {
                $.each(res.result, function(k, v) {
                    tableContent += `
              <tr class="dataCol row `+v.expired+`" id="` + v.htmlIdPrefix + `_` + v.link_code + `">
                <td scope="row" class="col">` + v.link_code + `</td>
                <td class="col">` + v.end_date + `</td>
                <td class="col"><div>` + lang.registered + ` (` + v.register_count + `)</div></td>
              </tr>
              `;
                });
            } else {
                tableContent = `
            <tr class="row no_data">
              <td colspan="3" class="col no_data_style"><p>` + res.result + `</p></td>
            </tr>
            `;
            }

            $('#linkDataTableBody').append(tableContent);
        }
    });
});

$(document).on('click', '.dataCol', function() {
    var id = this.id.split('_');

    if (id[1] != '') {
        if ($('#' + id[1] + '_MenuModal').length == 0) {
            $('.linkDataArea').after(combineMenuHtml(id));
        }

        $('#' + id[1] + '_MenuModal').modal('show');
    }
});

$(document).on('click', '.editLink', function() {
    var csrftoken = csrf;
    var code = this.value;

    $('#' + code + '_MenuModal').modal('hide');

    $.ajax({
        type: 'POST',
        url: 'spread_register_action.php',
        data: {
            code: code,
            action: 'detail',
            csrftoken: csrftoken
        },
        success: function(resp) {
            var res = JSON.parse(resp);

            if ($('#' + res.result.link_code + '_Modal').length != 0) {
                $('#' + res.result.link_code + '_Modal').remove();
            }

            if (res.status == 'success') {
                var status = res.result.status
                $('.linkDataArea').after(combineDetailHtml(res.result, res.status));
                if(!status){
                  $(function () {
                    $('[data-toggle="popover"]').popover();
                      $('.popover-dismiss').popover({
                      trigger: 'focus'
                    });
                  });	
                }
                $('#' + res.result.link_code + '_Modal').modal('show');
            } else {
                alert(res.result);
            }
        }
    });
});

$(document).on('click', '.shareLink', function() {
    var csrftoken = csrf;
    var code = this.value;

    $('#' + code + '_MenuModal').modal('hide');

    $.ajax({
        type: 'POST',
        url: 'spread_register_action.php',
        data: {
            code: code,
            action: 'copy',
            csrftoken: csrftoken
        },
        success: function(resp) {
            var res = JSON.parse(resp);

            if ($('#' + res.result.link_code + '_Modal').length != 0) {
                $('#' + res.result.link_code + '_Modal').remove();
            }

            $('.linkDataArea').after(combineCopyDataHtml(res.result, res.status));

            if ($('#qrcode').length != 0) {
                $('#qrcode').qrcode({
                    width: 200,
                    height: 200,
                    text: res.result.link
                });
            }

            $('#' + res.result.link_code + '_Modal').modal('show');
        }
    });
});

$(document).on('click', '.saveLinkData', function() {
    var csrftoken = csrf;
    var linkCode = this.value;
    var validityPeriod = $('#validityPeriod_' + linkCode).val();
    var note = $('#note_' + linkCode).val();

    $.ajax({
        type: 'POST',
        url: 'spread_register_action.php',
        data: {
            linkCode: linkCode,
            validityPeriod: validityPeriod,
            note: note,
            action: 'edit',
            csrftoken: csrftoken
        },
        success: function(resp) {
            var res = JSON.parse(resp);

            if (res.status == 'success') {
                alert(res.result);
                location.reload();
            } else {
                alert(res.result);
            }
        }
    });
});

$(document).on('click', '.delLink', function() {
    var csrftoken = csrf;
    var linkCode = this.value;
//'确定删除邀请码?'
    if (confirm(lang.suretodeleteinvitationcode) == true) {

        $.ajax({
            type: 'POST',
            url: 'spread_register_action.php',
            data: {
                linkCode: linkCode,
                action: 'delete',
                csrftoken: csrftoken
            },
            success: function(resp) {
                var res = JSON.parse(resp);

                if (res.status == 'success') {
                    $('#' + linkCode + '_MenuModal').modal('hide');

                    $('#' + linkCode + '_MenuModal').on('hidden.bs.modal', function(e) {
                        if ($('#' + linkCode + '_MenuModal').length != 0) {
                            $('#' + linkCode + '_MenuModal').remove();
                        }

                        if ($('#' + linkCode + '_Modal').length != 0) {
                            $('#' + linkCode + '_Modal').remove();
                        }
                    });

                    $('#' + linkCode).remove();
                    alert(res.result);
                    location.reload();
                } else {
                    alert(res.result);
                }
            }
        });
    }
});

function combineMenuHtml(id) {
    var isDisabled = '';
    var str = '';

    if (id[0] == 'd') {
        isDisabled = 'disabled';
        str = '('+lang.expired+')';//已过期
    }

    var html = `
  <div class="modal fade MenuModal" id="` + id[1] + `_MenuModal" tabindex="-1" role="dialog" aria-labelledby="vLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content modal_contentstyle">
        <div class="modal-header">
         <h6 class="modal-title" id="` + id[1] + `_MenuModalLabel">` + lang.selectoperation + `</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body register_btn">
          <button type="button" class=" editLink" value="` + id[1] + `">` + lang.edit + ` / ` + lang.check + `</button>
          <button type="button" class=" shareLink" value="` + id[1] + `" ` + isDisabled + `>` + lang.shareinvitationcode + str + `</button>
          <button type="button" class=" delLink" value="` + id[1] + `">` + lang.Deleteinvitationcode + `</button>
        </div>
       <!--  <div class="modal-footer"></div> -->
      </div>
    </div>
  </div>
  `;

    return html;
}

function combineDetailHtml(data, status) {
    var feedbackInfoHtml = '';
    var type = (data.register_type == 'A') ? lang.agent : lang.member;//'代理''会员'
    // var linkStatus = (data.status == 1) ? '邀请码可用' : '邀请码失效';
    var descriptionHtml = combineInputHtml('note_' + data.link_code, data.description);
    var validityPeriodHtml = combineSelectHtml(data.validity_period, data.link_code);

    if (data.register_type == 'A') {
        var feedbackInfo = JSON.parse(data.feedbackinfo);

        feedbackInfoHtml = `
    <tr class="row">
      <td class="col-4">` + lang.bonus + `</td>
      <td class="col-8">` + feedbackInfo.preferential + `%</td>
    </tr>
    <tr class="row">
      <td class="col-4">` + lang.commission + `</td>
      <td class="col-8">` + feedbackInfo.dividend + `%</td>
    </tr>
    `;
    }

    if (status == 'success') {
      data.status= data.status||detailStatusAlert
        var modalBody = `
    <div class="col-12">
    <table class="table spread_registertable">
      <thead></thead>
      <tbody>
        <tr class="row">
          <td class="col-4">` + lang.accounttype + `</td>
          <td class="col-8">` + type + `</td>
        </tr>
        ` + feedbackInfoHtml + `
      </tbody>
    </table>
    <table class="table spread_registertable">
      <thead></thead>
      <tbody>
        <tr class="row">
          <td class="col-4">` + lang.views + `</td>
          <td class="col-8">` + data.visits_count + lang.people+` </td>
        </tr>
        <tr class="row">
          <td class="col-4">` + lang.registrationamount + `</td>
          <td class="col-8">` + data.register_count + lang.people+` </td>
        </tr>
        <tr class="row">
          <td class="col-4">` + lang.status + `</td>
          <td class="col-8">` + data.status + `</td>
        </tr>
      </tbody>
    </table>
    <table class="table spread_registertable">
      <thead></thead>
      <tbody>
        <tr class="row">
          <td class="col-4">` + lang.description + `</td>
          <td class="col-8">` + descriptionHtml + `</td>
        </tr>
        <tr class="row">
          <td class="col-4">` + lang.ValidityPeriod + `</td>
          <td class="col-8">` + validityPeriodHtml + `</td>
        </tr>
        <tr class="row">
          <td class="col-4">` + lang.GenerationTime + `</td>
          <td class="col-8">` + data.start_date + `</td>
        </tr>
        <tr class="row">
          <td class="col-4">` + lang.EndTime + `</td>
          <td class="col-8">` + data.end_date + `</td>
        </tr>
      </tbody>
    </table>
    </div>
    `;
    } else {
        var modalBody = `<p>` + data.msg + `</p>`
    }

    var html = `
  <div class="modal fade" id="` + data.link_code + `_Modal" tabindex="-1" role="dialog" aria-labelledby="` + data.link_code + `_ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content modal_contentstyle">
        <div class="modal-header">
          <h6 class="modal-title" id="` + data.link_code + `_ModalLabel">` + data.link_code + lang.detail+`</h6> 
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          ` + modalBody + `
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">` + lang.close + `</button>
          <button type="button" class="btn btn-primary saveLinkData" value="` + data.link_code + `">` + lang.save + `</button>
        </div>
      </div>
    </div>
  </div>
  `;

    return html;
}

function combineCopyDataHtml(data, status) {
    var descriptionHtml = ``;
    var type = (data.register_type == 'A') ? lang.agent : lang.member;//'代理' : '会员'

    if (data.description != '') {
        descriptionHtml = `
    <p class="font-weight-bold">` + data.description + `</p>
    `;
    }

    if (status == 'success') {
        var modalBody = descriptionHtml + `
    <div class="text-center" id="qrcode"></div>
    <div class="input-group input_register">
      <input type="text" class="form-control register_qr" aria-label="linkCode" aria-describedby="showLinkCode" id="copyText_` + data.link_code + `" value="` + data.link + `">
      <div class="input-group-append input_register">
        <button class="btn btn-primary" type="button" data-clipboard-target="#copyText_` + data.link_code + `" value="` + data.link + `" id="copyLinkCode">` + lang.copy + `</button>
      </div>
    </div>
    <table class="showcopy table">
    <tr scope="row"><td scope="col-4">` + lang.invitationcode + `：</td><td scope="col-8" class="linkCode">` + data.link_code + `</td></tr>
    <tr scope="row"><td scope="col-4">` + lang.accounttype + `：</td><td scope="col-8"> ` + type + `</td></tr>
    <tr scope="row"><td scope="col-4">` + lang.ValidityPeriod + `：</td><td scope="col-8"> ` + data.end_date + `</td></tr>
    </table>
    
    `;
    } else {
        var modalBody = `<p>` + data.msg + `</p>`
    }

    var html = `
  <div class="modal fade" id="` + data.link_code + `_Modal" tabindex="-1" role="dialog" aria-labelledby="` + data.link_code + `_ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content modal_contentstyle">
        <div class="modal-header">
          <h6 class="modal-title" id="` + data.link_code + `_ModalLabel">` + data.link_code + lang.detail+`</h6> 
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          ` + modalBody + `
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">` + lang.close + `</button>
        </div>
      </div>
    </div>
  </div>
  `;

    return html;
}

function getValidityPeriod() {
    var validityPeriod = {
        '1': lang.oneday,//'一天'
        '2': lang.twodays,//'两天'
        '3': lang.threedays,//'三天'
        '10': lang.onemonth,//'一个月'
        '20': lang.twomonths,//'两个月'
        '30': lang.threemonths,//'三个月'
        '1000': lang.Permanent//'永久有效'
    };

    return validityPeriod;
}

function combineInputHtml(id, value) {
    var html = `
  <input class="form-control" type="text" id="` + id + `" placeholder="` + lang.notset + `" value="` + value + `">
  `;

    return html;
}

function combineSelectHtml(date, id) {
    var options = '';
    var validityPeriod = getValidityPeriod();

    $.each(validityPeriod, function(k, v) {
        var isChecked = (k == date) ? 'selected' : '';
        options += `<option value="` + k + `" ` + isChecked + `>` + v + `</option>`;
    });

    var html = `
  <select class="form-control" id="validityPeriod_` + id + `">
    ` + options + `
  </select>
  `;

    return html;
}