function Float_promote($active_fp,$position="lt",$dev=""){
  var fp_link = $active_fp["link"];
  if($dev != ""){fp_link="javascript: void(0)";}

  var position = {"lt":["left","top"],"rt":["right","top"],"lb":["left","bottom"],"rb":["right","bottom"]};
	this.html='<div id="'+ $active_fp["id"] +'" class="float-promote '+$dev+'" style="'+ position[$position][0] +': 0; '+ position[$position][1] +':0;">\
		<div class="float-promote-close float-promote-close-'+position[$position][0]+'"><i class="fa fa-times" aria-hidden="true"></i></div>\
		<a href="'+ fp_link +'" target="'+ $active_fp["target"] +'"><div data-img = "01"><img onerror="this.src=\''+cdnurl+'common/error.png\'" src="'+ $active_fp["img"] +'"><span>'+ $active_fp["content"] +'</span></div></a>\
		</div>';
	this.show = function($div) {
    if($dev == 'dev'){
      $($div).append(this.html);
    }
    else{
      $($div).append('<div id="'+ $active_fp["id"] +'" class="float-promote '+$dev+'" style="'+ position[$position][0] +': 0; '+ position[$position][1] +':0;">\
    <div class="float-promote-close float-promote-close-'+position[$position][0]+'"><i class="fa fa-times" aria-hidden="true"></i></div>\
    <a href="'+ fp_link +'" target="'+ $active_fp["target"] +'"><div data-img = "01"><img onerror="$(\''+$active_fp["id"]+'\').hide();" src="'+ $active_fp["img"] +'"><span>'+ $active_fp["content"] +'</span></div></a>\
    </div>');
    }    
  };
	this.act = function(){
		$(document).on('click', '#'+$active_fp["id"]+' .float-promote-close', function(){
			$('#'+$active_fp["id"]).hide();
		});
	};
}