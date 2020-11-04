//獲取head.js的位址(當作cdn目錄)
var js=document.scripts;
js=js[js.length-1].src.substring(0,js[js.length-1].src.lastIndexOf("/")+1);
//alert(js);
var cdnurl = js;

/*-------------客製化head--------------*/
//  head.js?mode=animate 於網址後帶參數
//  使用getParam(jspath, 'mode') 作判斷
/*------------------------------------*/

//當前js文件路徑
function getJsPath(jsname) {
    var js = document.scripts;
    var jsPath = "";
    for (var i = js.length; i > 0; i--) {
        if (js[i - 1].src.indexOf(jsname) > -1) {
            return js[i - 1].src;
        }
    }
    return jsPath;
}
//js文件後面之參數
function getParam(jspath, parm) {
 if(jspath.indexOf("?")==-1)
  {return "";}
  else{ 
    var urlparse = jspath.split("\?");
    var parms = urlparse[1].split("&");
    var values = {};
    for(var i = 0; i < parms.length; i++) {
        var pr = parms[i].split("=");
        if (pr[0] == parm)
        return pr[1];
    }
  }
}

var jspath = getJsPath('head.js');

/*---------------預設載入之CSS JS--------------*/

/* Parse, validate, manipulate, and display dates in JavaScript. */
document.write("<script src='"+cdnurl+"moment/moment-with-locales.min.js'></script>");
document.write("<script src='"+cdnurl+"moment/moment-timezone-with-data.min.js'></script>");

/*<!-- reset.css -->*/
document.write("<link type='text/css' rel='stylesheet' href='"+cdnurl+"css/reset.css'>");

/*<!-- bootstrap 4.0.0 and jquery -->*/
document.write("<link rel='stylesheet' href='"+cdnurl+"bootstrap/css/bootstrap.min.css'>");
document.write("<script src='"+cdnurl+"jquery/jquery.min.js'></script>");
document.write("<script src='"+cdnurl+"js/common.js'></script>");
document.write("<script src='"+cdnurl+"bootstrap/js/bootstrap.min.js'></script>");

/*<!-- jquery.crypt.js -->*/
document.write("<script src='"+cdnurl+"jquery.crypt.js'></script>");

/* font icon */
document.write("<link rel='stylesheet' href='"+cdnurl+"fonticon/css/icons.min.css'>");

/*<!-- marquee -->*/
document.write("<script src='"+cdnurl+"jquery.marquee.min.js'></script>");
document.write("<script src='"+cdnurl+"jquery.pause.js'></script> ");
/*<!-- CSS共用修正 -->*/
document.write("<link rel='stylesheet' href='"+cdnurl+"css/add.css'>");


/*--------wow.js + animate.css -----------*/
if(getParam(jspath, 'mode')=="animate"){
	document.write("<link rel='stylesheet' href='"+cdnurl+"css/animate.css'>");
	document.write("<script src='"+cdnurl+"js/wow.js'></script> ");
}