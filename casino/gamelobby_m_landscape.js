// 此為橫向手機專用~~~~~~~~by Orange
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

function alertforbsdemo(){
	alert(lang.busnissdemoalert)
}

function view_generate(data, id, tid) {
	if (data) {
		var html = '';
		var count = Object.keys(data).length;
		if (count >= 1) {
			$.each(data, function (key, value) {
				//html += '<div class="game-box"><div class="item"><div class="gameitem-content"><div class="game-button">';
				var gameurl = '';
				html += '<div class="gamebox gametype-'+value.casinolabel+'">';
				if (value.bsdemo == '0') {
					if (ss == 1) {
						gameurl = ' href="gamelobby_action.php" target="gpk_gamewindow" onclick="gotogame(\'' + value.casinoid + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.gamename + '\',\'' + value.token + '\')"';
						//html += '<a class="gotogame" href="gamelobby_action.php" target="gpk_gamewindow"><button onclick="gotogame(\'' + value.casinoname + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.gamename + '\',\'' + value.token + '\')">前往游戏</button></a></div> </div>';
					} else {
						gameurl = 'href="login2page.php?t=' + value.token + '" target="gpk_gamewindow"';
						//html += '<a class="gotogame" href="login2page.php?t=' + value.token + '" target="gpk_gamewindow"><button>前往游戏</button></div> </div></a>';
					}
				} else {//不能進去的遊戲
					gameurl = 'data-toggle="modal" data-target="#' + value.bsdemo + '" onclick="alertforbsdemo()"';
					//html += '<button type="button" data-toggle="modal" data-target="#' + value.bsdemo + '">前往游戏</button></div> </div>';
				}
				html += '<div class="item bg-'+ value.casinoid.toLowerCase() +'"><img class="casino_logo" src="' + value.cdnurl + '/landscapem/' + value.casinoid.toLowerCase() + '.png"><img class="games_logo" src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'">';
				//html += '<div class="game-box gametype-'+value.casinolabel+'"><a class="item" '+gameurl+'><img src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'"></a></div>';
				html += '<a class="link" '+gameurl+'><div class="link-area"></div></a>';
				/*
				html += '<div class="gameitem-img"><img src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'"> </div>';
				html += '<div class="gameitem-info"><div class="info-lbl"><span class="gamename_desc">' + value.gamename + '</span></div>';
				html += '</span></div> </div> </div> </div></div>';*/
				html += ' </div> </div>';
			});
			//console.log(html)
			if (id) {
				htmls = '';
				$('#index-gamebox-inner').append(html);
				//$('#' + tid).show();
				$(window).resize(function() {
                  var boxwidth =$('#index-gamebox-content').height()*0.45;
                  $('.gamebox').width(boxwidth);
                  var item_count=$('#index-gamebox-inner').children().length;
                  if(item_count%2 != 0){
					item_count=item_count+1;
				  }
                  var innerwidth = item_count/2*(boxwidth+10);
                  if(innerwidth > 0){
                    $('#index-gamebox-inner').width(innerwidth).show();
                  }
                }).resize();

			} else {
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
	} else if (global.stype == 'm') {
		// console.log($(sdata).attr('data-cid'));
		mainct = global.mainct;
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
		//console.log(data)
		if (data.gameitems) {
			if (mainct == 'hot') {
				$.each(data.gameitems, function (key, value) {
					var htmldata = data.gameitems.key;
					view_generate(value, 'gametable_' + key, 'index_mhot_' + key);
				})
				$('#gametable').html('');
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
	var v_width = $('#casino').width();
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
$(document).ready(function () {
	getViewRecords();
});
// 取得瀏覽紀錄
function getViewRecords() {
	$.post("gamelobby_action.php?a=getReviewGames",
		function(data) {
				if (data) {
				let reviews = JSON.parse(data).reviews;
				let sort =  JSON.parse(data).sort;
				let counts = 0;
				let viewHtml = '';
				if (sort.length > 0) {
					counts = sort.length;
					for (let i = sort.length-1; i >= 0  ; i--) {
						let game;
						for (let j = 1; j <= sort.length; j++) {
							if (sort[i] == reviews[j].gamecode){
								game = reviews[j];
								break;
							}
						}
						if (game) {
							let gotoGameBtnHtml = genReviewGameHtml(game, ss);
							let myFavGameBtnHtml = genMyFavGameHtml(game, game.myfavfunc);
							viewHtml +=
							'<li class="browse_gamebox gametype-'+game.casinolabel+'">'+
							'<div class="item bg-'+game.casinolabel+'">'+
							'<img class="casino_logo" src="' + game.cdnurl + '/landscapem/' + game.casinoid.toLowerCase() + '.png">'+
							'<img class="games_logo" src="'+ game.gameimgurl +'" onerror="this.src=\'' + game.cdnurl + '.png\'">'+
							gotoGameBtnHtml +
							'<div class="gameitem-info">'+
							'</div>'+
							'</li>';
						}
					}
				}
				$("#browse_content_list").html(viewHtml);

				// 瀏覽列
				let status = $('.browse_list_li li').length > 0;
				reviewList(status);

			}
		}
	);
}

// 組成近期瀏覽 HTML
function genReviewGameHtml(item, login) {
	let reviewHtml = '';

	if (item.bsdemo == '0') {
		if (login) {
			reviewHtml =
				'<a class="gotogame" href="gamelobby_action.php" target="gpk_gamewindow">' +
				'<button class="go_game" value="'+ item.g2gamelabel +'" ' +
				'onclick="gotogame(\'' + item.casinoid + '\' ,\''+ item.gamecode + '\',\''+ item.gameplatform +
				'\' ,\'' + item.gamename + '\', \'' + item.token + '\')">'+ item.g2gamelabel +'</button></a>';
		} else {
			reviewHtml =
				'<button class="go_game" value="'+ item.g2gamelabel +'" onclick="loginpage(\'' + item.token + '\')">'+ item.g2gamelabel +'</button>';
		}
	} else {
		reviewHtml =
			'<button type="button" class="go_game" data-toggle="modal" data-target="#' + item.bsdemo + '">' + item.g2gamelabel + '</button>';
	}

	return reviewHtml;
}


// 組成加到最愛 HTML
function genMyFavGameHtml(item, myFav) {
	let myFavHtml = '';
	if (myFav == 'addmyfav') {
		myFavHtml = '<button class="add_like fav" onclick="' + item.myfavfunc +'(\''+ item.casinoid +'\', \'' + item.gamecode +'\', \''+ item.gameplatform +'\', \''+ item.myfavtoken +'\')"></button>';
	} else {
		myFavHtml = '<button class="add_like fav heart-full" onclick="'+ item.myfavfunc +'(\''+ item.casinoid +'\', \''+ item.gamecode +'\', \''+ item.gameplatform +'\', \''+ item.gametype +'\', \''+ item.gamename +'\')"></button>';
	}

	return myFavHtml;
}



// 瀏覽列功能
function reviewList(status) {
	//按鈕
	$('.browse_bt_a').attr('attr-open',status);

	//展開狀況
	$('.browse_content').attr('attr-open',status);

	//重複點擊
	var clickstatus = false;
	$('.browse_bt_a').click(function(){
		if( $('.browse_list_li li').length > 0 ){
			if( clickstatus == false){
				status = false;
				$('.browse_bt_a').attr('attr-open',status);
				$('.browse_content').attr('attr-open',status);

				clickstatus = true;
			}else if( clickstatus == true ){
				status = true;
				$('.browse_bt_a').attr('attr-open',status);
				$('.browse_content').attr('attr-open',status);

				clickstatus = false;
			}
		}else{
			alert("无浏览纪录");
		}
		return false
	});

	$('.browse_close').click(function(){
		status = false;
		$('.browse_bt_a').attr('attr-open',status);
		$('.browse_content').attr('attr-open',status);
		clickstatus = true;
		return false
	});
}
