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
    $(document).ready(function(){
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