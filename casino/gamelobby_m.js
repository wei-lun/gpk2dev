// 使用時需要加入以下語法在要加入選單的版塊裡
// 	page: 起始頁
// 	stype: 主類另選單模式，c：依娛樂城分類，m ：依6大分類
// 					設為 c 時只會讀取 casinoid，設為 m 時只會讀取 mainct
// 	casinoid: 頁面預設娛樂城
// 	mainct: 頁面預設主分類
// 	maxiconnum: 每頁最大讀取數量
// 	rnd: 是否隨機輸出list，1：隨機輸出，0：依排序輸出
//
// <!-- 首頁gamelobby顯示區域 -->
// <script type="text/javascript">
// var global = {
// 	page: 1,
// 	stype: 'c',
// 	casinoid: '',
// 	mainct: '',
// 	maxiconnum: 6,
// 	rnd: '1'
// }
// </script>
//   <?php require_once dirname(dirname(dirname(__DIR__))).'/casino/casino_config.php';
// 	 home_gamelist(); ?>
// <!-- 首頁結束 -->
var myWindow;
$(document).ready(function () {

	$('a.gotogame').trigger(
		$.Event('click', {
			ctrlKey: true
		})
	);
	$('#casino li').mouseenter(function () {
		global.casinoid = $(this).attr('data-cid');
		//console.log(global.casinoid);
		$('#casino li').removeClass('on');
		$(this).addClass('on');
		get_gametable();
	});
	$('#mainct a').mouseenter(function () {
		global.mainct = $(this).attr('data-mct');
		// console.log(global.mainct);
		// get_gametable();
	});
	get_gametable();
	slidecheck();
});

function view_generate(data, id, tid) {
	if (data) {
		var html = '';
		var count = Object.keys(data).length;
		//分類icon
		//console.log(idname);
		//如果id有帶進來
		if( id != undefined ){
			var idname = id.slice(0,9);
			//如果id不等於 gametable
			if( idname != 'gametable' ){
				var iconclass = id.toLowerCase();
				var noicon = '';
			}else{
				var iconclass = '';
				var noicon = 'no';
			}
		}else{
			var iconclass = '';
			var noicon = 'no';
		}
		if (count >= 1) {
			$.each(data, function (key, value) {
				html += '<div class="col-4 col-sm-3 col-md-2"><div class="gameitem"><div class="gameitem-content"><div class="game-button">';
				if (value.bsdemo == '0') {
					if (ss == 1) {
						html += '<a class="gotogame" href="gamelobby_action.php" target="gpk_gamewindow"><button onclick="gotogame(\'' + value.casinoid + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.gamename + '\',\'' + value.token + '\')">'+lang.togame+'</button></a></div> </div>';
					} else {
						html += '<a class="gotogame" href="login2page.php?t=' + value.token + '" target="gpk_gamewindow"><button>'+lang.togame+'</button></div> </div></a>';
					}
				} else {
					html += '<button type="button" class="gotogame" data-toggle="modal" data-target="#' + value.bsdemo + '">'+lang.togame+'</button></div> </div>';
				}
				html += '<div class="gameitem-img"><img src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'"> </div>';
				html += '<div class="gameitem-info"><div class="info-lbl"><span class="gamename_desc">' + value.gamename + '</span></div><div class="infommobile-lb2 '+noicon+'"><span class="label label-gpk2">' + value.casinoname + '</span><span class="'+iconclass+'"></span></div>';
				html += '</span></div> </div> </div> </div></div>';
			});
			if (id && global.generate != 'notclassified') {
				htmls = '';
				$('#' + id + ' .row').html(html);
				$('#' + tid).show();
			}else if(global.generate == 'notclassified'){
				$('#gametable').append(html);
			}else {
				$('#gametable').html(html);
			}
		} else {
			if (id) {
				$('#' + tid).hide();
			}
		}
	}
}

function get_gametable() {
	var casino = '';
	var mainct = '';
	if (global.stype == 'c') {
		casino = global.casinoid;
		generate = (!!global.generate)? global.generate:false;
	} else if (global.stype == 'm') {
		// console.log($(sdata).attr('data-cid'));
		mainct = global.mainct;
		generate = (!!global.generate)? global.generate:global.mainct;
	}
	var gametype = '';
	$('#gametype .selected').each(function () {
		gametype = $(sdata).attr('id');
	});
	var subcate = '';
	$('#subcategoryitems .selected').each(function () {
		subcate = $(sdata).attr('id');
	});

	var url = 'gamelobby_action.php?a=gametable_mini&num=' + global.maxiconnum + '&start=' + global.page + '&rnd=' + global.rnd;
	$.post(url, {
		'cid': casino,
		'mct': mainct,
		'ct': gametype,
		'subct': subcate
	}, function (data) {
		if (data.gameitems) {
			if (generate == 'hot') {
				var idlength = Object.keys(data.gameitems).length;
				console.log(idlength);
				//console.log(data.gameitems);
				$.each(data.gameitems, function (key, value) {
					var htmldata = data.gameitems.key;
					view_generate(value, 'gametable_' + key, 'index_mhot_' + key);
				})
				$('#gametable').html('');
			}else if(generate == 'notclassified'){
				//分類長度判斷
				var idlength = Object.keys(data.gameitems).length;
				//只有1個分類
				if( idlength == '1' ){
					$.each(data.gameitems, function (key, value) {
						var htmldata = data.gameitems.key;
						//view_generate(value);
						view_generate(value);
					})
				}else{
					$.each(data.gameitems, function (key, value) {
						var htmldata = data.gameitems.key;
						//view_generate(value);
						view_generate(value, 'gameicon_' + key,key);
					})
				}
				$('#index_m_hotgame').hide();
				//分類顯示
				//$('#index_m_notclassified').show();
				//view_generate(htmldata);
			} else {
				var htmldata = data.gameitems;
				view_generate(htmldata);
			}
		} else {
			var loggerhtml = '<div class="row mx-auto pb-2"><mark class="bg-info mx-auto p-2 rounded">' + data.logger + '</mark></div>';
			$('#gametable').html(loggerhtml);
		}
	}, 'json');

}

function fill_tag(a_tag, a_class, a_id, a_name, a_onclick, a_currentpage, a_html) {
	a_class = (a_class == '') ? '' : ' class="' + a_class + '"';
	a_id = (a_id == '') ? '' : ' id="' + a_id + '"';
	a_name = (a_name == '') ? '' : ' name="' + a_name + '"';
	a_onclick = (!a_onclick) ? '' : ' onclick="' + a_onclick + '(' + a_current.page + ');"';
	a_current.page = (!a_current.page) ? '' : ' onclick="' + a_onclick + '(' + a_current.page + ');"';
	var code = '<' + a_tag + a_class + a_id + a_name + a_onclick + ' >' + a_html + '</' + a_tag + '>';
	return code;
}

// 進入GAME
function gotogame(casinoname, gamecode, gameplatform, gamename, token) {
	var show_text = lang.gotogame.replace('%s',gamename);
	var url = 'gamelobby_action.php?a=gotogame&closed=1&casinoname=' + casinoname;

	if (ss == 1) {
		if (jQuery.trim(gamecode) == '') {
			var show_error = lang.nogameinfo;
			alert(show_error);
		} else {
			$.get(url,
				function (result) {
					if (!myWindow || myWindow.closed) {
						myWindow = window.open('', 'gpk_gamewindow', 'fullscreen=no,status=yes,resizable=yes,top=0,left=0,height=1024,width=768', false);
						// console.log('no window');
					}
					if (result.logger) {
					 	self.focus();
						global.myWindow.close()
						alert(result.logger)
						$.unblockUI()
						window.location.reload()
					} else {
						var gotogamecodeurl = result.url + '?a=goto_game&casinoname=' + casinoname + '&gamecode=' + gamecode + '&gameplatform=' + gameplatform;
						var wait_text = '<div style="width: 100%;               height: 100vh;          display: flex;          justify-content: center;                align-items: center;            overflow: hidden;">執行中，請勿關閉視窗.<img src="' + global.cdnurl + 'loading_balls.gif"></div>';
						myWindow.postMessage(wait_text, '*');
						myWindow.location = gotogamecodeurl;
						myWindow.focus();
					}
				}, 'json');
		}
	} else {
		loginpage(token);
	}
}

function loginpage(token) {
	var gotogamecodeurl = 'login2page.php?t=' + token;
	if (!myWindow || myWindow.closed) {
		myWindow = window.open('', 'gpk_gamewindow', 'fullscreen=no,status=yes,resizable=yes,top=0,left=0,height=1024,width=768', false);
		// console.log('no window');
	}
	myWindow.location = gotogamecodeurl;
	myWindow.focus();
}
function slidecheck(now){
	var v_width = $('#casino').width();
	if(typeof $('.tabUl').get()[0] == 'undefined'){
		return false;
	}
	var maxwidth = $('.tabUl').get()[0].scrollWidth;
	if(now == null){
		now = $('.tabUl').css("left").slice(0,-2);
	}
	if(v_width>=maxwidth){
		$('#casino-pre').hide();
		$('#casino-next').hide();
	}
	else if(parseInt(now)==0){
		$('#casino-pre').hide();
		$('#casino-next').show();
	}else if(maxwidth+parseInt(now)<=v_width){
		$('#casino-pre').show();
		$('#casino-next').hide();
	}else{
		$('#casino-pre').show();
		$('#casino-next').show();
	}
}
function slide(act){
	var v_width = $('#casino').width()/2;
	var maxwidth = $('.tabUl').get()[0].scrollWidth;
	if(act == 'next'){
		var now = $('.tabUl').css("left").slice(0,-2);
		var num = maxwidth + parseInt(now) - v_width;
		if(num > v_width)
		{
			var newnum = parseInt(now) - v_width;
			$('.tabUl').css("left",newnum + 'px');
		}else{
			var newnum = -maxwidth + v_width;
			$('.tabUl').css("left",newnum + 'px');
		}
		slidecheck(newnum);
	}
	if(act == 'pre'){
		var now = $('.tabUl').css("left").slice(0,-2);
		var num =parseInt(now) + v_width;
		if(num <= 0)
		{
			$('.tabUl').css("left",num+'px');
			slidecheck(num);
		}else{
			$('.tabUl').css("left",'0px');
			slidecheck(0);
		}
	}
	//slidecheck();
}
