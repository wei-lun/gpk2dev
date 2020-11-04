//獲取head.js的位址(當作cdn目錄)
var js=document.scripts;
js=js[js.length-1].src.substring(0,js[js.length-1].src.lastIndexOf("/")+1);
//alert(js);
var cdnurl = js;
$(document).ready(function(){
});
function get_component(ui_link){
	$.ajax({
	  url: ui_link,
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
							
			var $position =	["lt","rt","lb","rb"];	
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
};
