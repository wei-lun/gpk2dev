function Aside_promote($aside_promote,$position="left",$dev=""){
  var aside_promote_html='';

  aside_promote_html+='\
  <aside id="'+$aside_promote["id"]+'" class="aside-promote '+$aside_promote["style"]+' '+$dev+'">\
    <ul>\
      <li class="aside-promote-title"><img src="'+cdnurl+'aside_promote/'+$aside_promote["style"]+'/title.png" alt="title"></li>';

  for (var k = 0; k < $aside_promote["content"].length; k++) {
    var ap_link = $aside_promote["content"][k]["link"];
    if($dev=="dev"){ap_link = "javascript: void(0)";}
    aside_promote_html+='\
        <a href="'+ap_link+'" target="'+$aside_promote["content"][k]["target"]+'">\
          <li class="aside-promote-content">\
            <div class="content-title">'+$aside_promote["content"][k]["title"]+'</div>\
            <div class="content-txt">'+$aside_promote["content"][k]["txt"]+'</div>\
          </li>\
        </a>';  
  }
  aside_promote_html+='\
        <li class="aside-promote-foot"><img src="'+cdnurl+'aside_promote/'+$aside_promote["style"]+'/foot.png" alt="foot"></li>';

  if($aside_promote["closeable"]==true || $aside_promote["closeable"]=="true"){
    aside_promote_html+='\
    <li class="aside-promote-close"><img src="'+cdnurl+'aside_promote/'+$aside_promote["style"]+'/close.png" alt="close"></li>\
      </ul>';
  }
  aside_promote_html+='</ul></aside>';

  this.html = aside_promote_html;

  this.show = function($div) {
      $($div).append(this.html);
  };

  this.act = function(){
    $(window).load(function(){
      $(document).on('click', '#'+$aside_promote["id"]+' .aside-promote-close', function(){
        $('#'+$aside_promote["id"]).hide();
      });
      var $win = $(window),
          $ad = $('#'+$aside_promote["id"]).css('opacity', 0).show(),
          _width = $ad.width(),
          _height = $ad.height(), 
          _diffY = (window.innerHeight-_height)/2, _diffX = 10, // 距離右及下方邊距
          _moveSpeed = 800; // 移動的速度

            // 先移動到定點
          $ad.css({
              top: window.innerHeight - _height - _diffY,
              left: $win.width() - _width - _diffX,
              opacity: 1
            });
             
          // 幫網頁加上 scroll 及 resize 事件
          $win.bind('scroll resize', function(){
            var $this = $(this);
            _diffY = (window.innerHeight-_height)/2
              // 控制 #abgne_float_promote 的移動
              if($position=="right"){
                $ad.stop().animate({
                  top: $this.scrollTop() + window.innerHeight - _height - _diffY,
                  left: $this.scrollLeft() + $("html").width() - _width - _diffX
                }, _moveSpeed); 
              }
              else{
                $ad.css("left","auto");
                $ad.stop().animate({
                  top: $this.scrollTop() + window.innerHeight - _height - _diffY,
                  right: $this.scrollLeft() + $("html").width() - _width - _diffX
                }, _moveSpeed); 
              }
          }).scroll();  // 觸發一次 scroll()
    });
  };

}
function Float_promote($active_fp,$position="lt",$dev=""){
  var fp_link = $active_fp["link"];
  if($dev != ""){fp_link="javascript: void(0)";}

  var position = {"lt":["left","top"],"rt":["right","top"],"lb":["left","bottom"],"rb":["right","bottom"]};
  this.html='<div id="'+ $active_fp["id"] +'" class="float-promote '+$dev+'" style="'+ position[$position][0] +': 0; '+ position[$position][1] +':0;">\
    <div class="float-promote-close float-promote-close-'+position[$position][0]+'"><i class="fa fa-times" aria-hidden="true"></i></div>\
    <a href="'+ fp_link +'" target="'+ $active_fp["target"] +'"><div data-img = "01"><img src="'+ $active_fp["img"] +'"><span>'+ $active_fp["content"] +'</span></div></a>\
    </div>';
  this.show = function($div) {
    $($div).append(this.html);
    };
    this.act = function(){
      $(document).on('click', '#'+$active_fp["id"]+' .float-promote-close', function(){
        $('#'+$active_fp["id"]).hide();
      });
    };
}
function highlight_menu($highlight_menu){
  for (var i = 0; i < $highlight_menu.length; i++) {
      $(".navi_"+$highlight_menu[i][0]).addClass($highlight_menu[i][1]+" hot");
  }
} 
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
    .appendTo("#"+$popup_promote["id"]+" .popup-promote .popup-promote-link");
  };
    
  this.act = function(){    
  //console.log(localStorage["new"]+localStorage["temp_date"]);
    $(window).load(function(){
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
      });
    });
  };
}
function Slide_aside($active_sa,$position="left",$dev=""){
  if(typeof $active_sa["title"] == 'undefined')
    $active_sa["title"]="";
  var slide_aside_html="";
    slide_aside_html +='\
    <div id="'+$active_sa["id"]+'" class="head-'+$active_sa["style"]+' pollslider '+$position+' '+$dev+'">\
      <div class="pollSlider-button '+$active_sa["style"]+'">'+$active_sa["title"]+'</div>\
      <ul class="pollSiderContent '+$active_sa["style"]+'">\
      <li class="pollSiderContenthead"></li>';

    for (var j = 0; j < $active_sa["content"].length; j++) {
      var sa_link = $active_sa["content"][j]["link"];
      if($dev=="dev"){sa_link = "javascript: void(0)";}
        slide_aside_html+='<a href="'+sa_link+'" target="'+$active_sa["content"][j]["target"]+'"><li class="pollSiderContentli">';
        if ($active_sa["content"][j]["title"]!="") {
          slide_aside_html+='<div class="poolsidertit">'+$active_sa["content"][j]["title"]+'</div>';
        }
        if ($active_sa["content"][j]["txt"]!="") {
          slide_aside_html+='<div class="poolsidertxt">'+$active_sa["content"][j]["txt"]+'</div>';
        }
        slide_aside_html+='</li></a>';
    }

    slide_aside_html+='</ul>\
    </div>';

  this.html = slide_aside_html;

  this.show = function($div){
    $($div).append(this.html);
    $(document).ready(function () {
          var pollwidth =$("#"+$active_sa["id"]+" .pollSiderContentli").css("width");
          if($dev=="")
          { 
            if($position=="right")         
              $("#"+$active_sa["id"]).css({ "margin-right" : "-"+pollwidth,"display":"flex"});
            else if($position=="left")
              $("#"+$active_sa["id"]).css({ "margin-left" : "-"+pollwidth,"display":"flex"});
          }
    });
  };
  
  this.act = function(){
  $('.pollSlider-button').mouseover(function() {
    var pollhover = $(this).parent().attr('id');
    var slider_width = $("#"+pollhover+" .pollSiderContent").width();
    if($(this).parent().hasClass('right')){
        if ($("#"+pollhover+".pollSlider").css("margin-right") == 0 + "px" && !$(".pollSlider").is(':animated')) {
          $("#"+pollhover).animate({
            "margin-right": '-=' + slider_width
          });
        } else {
          if (!$(".pollSlider").is(':animated'))
          {
            $("#"+pollhover).animate({
              "margin-right": 0
            });
          }
        }
      } else{
        if ($("#"+pollhover+".pollSlider").css("margin-left") == 0 + "px" && !$(".pollSlider").is(':animated')) {
          $("#"+pollhover).animate({
            "margin-left": '-=' + slider_width
          });
        } else {
          if (!$(".pollSlider").is(':animated'))
          {
            $("#"+pollhover).animate({
              "margin-left": 0
            });
          }
        }
      }
    });  
  };
}
//獲取head.js的位址(當作cdn目錄)
var js=document.scripts;
js=js[js.length-1].src.substring(0,js[js.length-1].src.lastIndexOf("/")+1);
//alert(js);
var cdnurl = js;
$(document).ready(function(){
  $.ajax({
    url: 'uisetting_action.php?s=1',
    type: 'GET',
    dataType: 'json',
    success: function(Jdata) {
      //alert('SUCCESS!!!');
      //console.log(Jdata);
      var data = Jdata;
      //var data = Array()
      
      var templ_name = $("body").attr("id");

    var $highlight_menu = [];
    for (var i = 0; i < data["highlight_menu"].length; i++) {
        $highlight_menu.push(data["highlight_menu"][i]);
    }
    if ($highlight_menu.length!=0) {highlight_menu($highlight_menu);}

    if (typeof data[templ_name] != "undefined" && data[templ_name] != null){
      if (data[templ_name]["popup-promote"].length!=0) {
        if(data[templ_name]["popup-promote"][0]['switch']==1){
          var $popup_promote = new Popup_promote(data[templ_name]["popup-promote"][0]);
          $popup_promote.show("body");
          $popup_promote.act();
        }
      }     
      
      var $position = ["left","right"];
      for (var i = 0; i < 2; i++) {
        if(data[templ_name]["aside-promote"][$position[i]].length != 0){
          if(data[templ_name]["aside-promote"][$position[i]][0]["type"]=="float"){
            if(data[templ_name]["aside-promote"][$position[i]][0]['switch']==1){
              var $aside_promote = new Aside_promote(data[templ_name]["aside-promote"][$position[i]][0],$position[i]);
              $aside_promote.show("body");
              $aside_promote.act();
            }
          }
          else if(data[templ_name]["aside-promote"][$position[i]][0]["type"]=="slide"){
            if(data[templ_name]["aside-promote"][$position[i]][0]['switch']==1){
              var $aside_promote = new Slide_aside(data[templ_name]["aside-promote"][$position[i]][0],$position[i]);
              $aside_promote.show("body");
              $aside_promote.act();
            }
          }
        }
      }   
              
      var $position = ["lt","rt","lb","rb"];  
      for (var i = 0; i < 4; i++) {
        if(data[templ_name]["float-promote"][$position[i]].length != 0){
          if(data[templ_name]["float-promote"][$position[i]][0]['switch']==1){
              var $active_fp = new Float_promote(data[templ_name]["float-promote"][$position[i]][0],$position[i]);
              $active_fp.show("body");
              $active_fp.act(); 
          }
        }  
      }

    }
    },    
    error: function() {
      console.log('component_ui.json error');
    }
  });
});
