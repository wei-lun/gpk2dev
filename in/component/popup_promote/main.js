function Popup_promote($popup_promote,$dev=""){
  var popup_promote_html='';
  var pp_link = $popup_promote["link"];
  if($dev=="dev"){pp_link="javascript: void(0)";}
  popup_promote_html +='<div class="modal fade" id="'+$popup_promote["id"]+'" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">\
      <div class="modal-dialog modal-dialog-centered popup-promote '+$popup_promote["style"]+'" role="document">\
        <div class="modal-content" style="width: auto;">\
          <div class="modal-header">\
            <h5 class="modal-title" id="exampleModalLongTitle">'+$popup_promote["title"]+'</h5>\
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>\
            </button>\
          </div>\
          <div class="modal-body p-0">\
            <a class="popup-promote-link" href="'+pp_link+'" target="'+$popup_promote["target"]+'"></a>\
          </div>\
        </div>\
      </div>\
    </div>';

  this.html = popup_promote_html;

  this.show = function($div,$img = $popup_promote["img"]){
    $($div).append(this.html);
    var image = $('<img/>', { 
        src : $img
    }).load(function () {
        $("#"+$popup_promote["id"]+" .popup-promote").css("max-width",this.width);    
    })
    .appendTo($div+" #"+$popup_promote["id"]+" .popup-promote .popup-promote-link");
    if($dev == 'dev'){
      image.error(function() {
        this.src= cdnurl+'common/error.png';
      });
    }
    else{
      image.error(function() {
        $('#'+$popup_promote["id"]).remove();
      });
    }
  };
    
  this.act = function(){    
  //console.log(localStorage["new"]+localStorage["temp_date"]);
    $(window).load(function(){
      $('#'+$popup_promote["id"]).modal('show');
      /*
      if(typeof localStorage["temp_date"] == "undefined"){
            $('#'+$popup_promote["id"]).modal('show');
      }
      else{
            localStorage["new_date"]=new Date();
            if((Date.parse(localStorage["new_date"])-Date.parse(localStorage["temp_date"]))>43200000){
              $('#'+$popup_promote["id"]).modal('show');
            }
      }
      $("#"+$popup_promote["id"]).click(function() {        
        localStorage["temp_date"]=new Date();
      });*/
    });    
  };
}
