$(document).ready(function () {
	global.autoload=true;
	$('a.gotogame').trigger(
		$.Event('click', {
			ctrlKey: true
		})
	);
	get_gametable(1);
	$('#casinotab li').on("click", function () {
		if (!$(this).attr('id').match('gamesel')) {
			$("#searchitem").val('');
			$('#casinotab .active').removeClass('active');
			$(this).addClass('active');
			if ($(this).attr('id').match('nav-item')) {
				global.listtables = 'gametable';
			} else {
				global.listtables = $(this).attr('attr-table') + 'table';
			}
			$('#casino .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#gametype .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#subcategoryitems .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#allsubcategory').addClass('selected');
			$('#gamessubcategory').hide();
			$('#allgametype').addClass('selected');
			$('#allcasino').addClass('selected');
			$('#gametable').empty();
			get_gametable(1);
			swiper.setTranslate(0);
		}
	});
	$('#gametype span').on("click", function () {
		var id = [];
		//console.log($(this).attr('id'));
		if ($(this).attr('id') == 'allgametype') {
			$("#searchitem").val('');
			$('#gametype .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#subcategoryitems .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#allsubcategory').addClass('selected');
			$(this).addClass('selected');
		} else {
			$('#allgametype').removeClass('selected');
			if ($(this).attr('class').match(/selected/g)) {
				$(this).removeClass('selected');
			} else {
				$(this).addClass('selected');
			}
		}
		$('#gametype .selected').each(function () {
			if ($(this).attr('id') != 'allgametype') {
				id.push($(this).attr('id'));
			}
		});
		if (id.length == 0) {
			$('#allgametype').addClass('selected');
		}
		if (id.length == 1) {
			//console.log(id[0]);
			if (subitem[id[0]]) {
				$('#subcategoryitems').html(subitem[id[0]]);
				$('#gamessubcategory').show();
			}
		} else {
			$('#gamessubcategory').hide();
		}
		//console.log(id.toString());
		$('#gametable').empty();
		get_gametable(1);
		swiper.setTranslate(0);
	});
	$('#subcategoryitems').on("click", 'span', function () {
		var subid = [];
		//console.log($(this).attr('id'));
		if ($(this).attr('id') == 'allsubcategory') {
			$('#subcategoryitems .selected').each(function () {
				$(this).removeClass('selected');
			});
			$(this).addClass('selected');
		} else {
			$('#allsubcategory').removeClass('selected');
			if ($(this).attr('class').match(/selected/g)) {
				$(this).removeClass('selected');
			} else {
				$(this).addClass('selected');
			}
		}
		$('#subcategoryitems .selected').each(function () {
			if ($(this).attr('id') != 'allsubcategory') {
				subid.push($(this).attr('id'));
			}
		});
		if (subid.length == 0) {
			$('#allsubcategory').addClass('selected');
		}
		//console.log(subid.toString());
		$('#gametable').empty();
		get_gametable(1);
		swiper.setTranslate(0);
	});
	$('#casino span').on("click", function () {
		var id = [];
		//console.log($(this).attr('id'));
		if ($(this).attr('id') == 'allcasino') {
			$("#searchitem").val('');
			$('#casino .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#gametype .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#subcategoryitems .selected').each(function () {
				$(this).removeClass('selected');
			});
			$('#allsubcategory').addClass('selected');
			$('#gamessubcategory').hide();
			$('#allgametype').addClass('selected');
			$(this).addClass('selected');
		} else {
			$('#allcasino').removeClass('selected');
			if ($(this).attr('class').match(/selected/g)) {
				$(this).removeClass('selected');
			} else {
				$(this).addClass('selected');
			}
		}
		$('#casino .selected').each(function () {
			if ($(this).attr('id') != 'allcasino') {
				id.push($(this).attr('id'));
			}
		});
		if (id.length == 0) {
			$('#allcasino').addClass('selected');
		}
		//console.log(id.toString());
		$('#gametable').empty();
		get_gametable(1);
		swiper.setTranslate(0);
	});
	$("#searchform").submit(function () {
		setTimeout(function(){ gamesearch();
		swiper.setTranslate(0);
		}, 300);
		return false;
	});
	swiper.on('reachEnd', function () {
	  scroll_load()
	});
});

function scroll_load(){
	if(global.page < global.maxpage){
		get_gametable(global.page+1)
	}
}

function view_generate(page) {
	var html = '';
	if (this) {
		$.each(this, function (key, value) {
			var gameurl = '';
			html += '<div class="gamebox gametype-'+value.casinolabel+'">';
			if (value.bsdemo == '0') {
				if (ss == 1) {
					gameurl = 'href="gamelobby_action.php" target="gpk_gamewindow" onclick="gotogame(\'' + value.casinoid + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.gamename + '\',\'' + value.token + '\')"';
					//html += '<a class="gotogame" href="gamelobby_action.php" target="gpk_gamewindow"><button value="' + value.g2gamelabel + '" onclick="gotogame(\'' + value.casinoname + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.gamename + '\',\'' + value.token + '\')">' + value.g2gamelabel + '</button></a></div> </div>';
				} else {
					gameurl = ' onclick="loginpage(\'' + value.token + '\')"';
					//html += '<button class="gotogame" value="' + value.g2gamelabel + '" onclick="loginpage(\'' + value.token + '\')">' + value.g2gamelabel + '</button></div> </div>';
				}
			} else {
				gameurl = 'data-toggle="modal" data-target="#' + value.bsdemo + '"';

				//html += '<button type="button" class="gotogame" data-toggle="modal" data-target="#' + value.bsdemo + '">' + value.g2gamelabel + '</button></div> </div>';
				//html += '<div class="gameitem-img"><img src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'"> </div>';
			}
			html += '<div class="item bg-'+ value.casinoid.toLowerCase() +'"><img class="casino_logo" src="' + value.cdnurl + '/landscapem/' + value.casinoid.toLowerCase() + '.png"><img class="games_logo" src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'">';
			html += '<a class="link" '+gameurl+'><div class="link-area"></div></a>';
			html += '<div class="gameitem-info">';
			/*if (value.gamename_mark == 'H5') {
				html += '<span class="fave-mobile"><span></span></span>';
				<span class="gamename_desc">' + value.gamename + '</span>
				<span class="label label-' + value.casinoname.toLowerCase() + '">' + value.casinolabel + '</span>
			}*/
			if (value.myfavfunc == 'addmyfav') {
				html += '<div class="fav" value="' + value.myfavlabel + '" onclick="' + value.myfavfunc + '(\'' + value.casinoid + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.myfavtoken + '\')"></div>';
			} else {
				html += '<div class="fav heart-full" value="' + value.myfavlabel + '" onclick="' + value.myfavfunc + '(\'' + value.casinoid + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.gametype + '\',\'' + value.gamename + '\')"></div>';
			}
			html += ' </div> </div> </div>';
		});
		html='<div id="gamepage-'+page+'" class="gamepage swiper-slide">'+html+'</div>';
		//var item_count= Object.keys(this).length;
		$('#gametable').append(html);
		global.autoload = true;
		//var item_count= $('#gametable').children().length;
		var item_count= $('#gamepage-'+page).children().length;
		//console.log($('.gamebox').height())
		//console.log(page)
		$(window).resize(function() {
			if(item_count%2 != 0){
				item_count=item_count+1;
			}
			var pagewidth = item_count/2*($('.gamebox').height()+11);
			$('.gamebox').width($('.gamebox').height());
			$('#gamepage-'+page).width(pagewidth);
			var tablewidth =0;
			swiper.update()
		}).resize();
		//console.log(global.autoload);
		swiper.update()
		if(page == 1){
			scroll_load()
		}
	}
}

function get_gametable(page) {
	global.page = page || global.page;
	//console.log(global.page);
	var casino = [];
	$('#casino .selected').each(function () {
		casino.push($(this).attr('id'));
	});
	var gametype = [];
	$('#gametype .selected').each(function () {
		gametype.push($(this).attr('id'));
	});
	var subcate = [];
	$('#subcategoryitems .selected').each(function () {
		subcate.push($(this).attr('id'));
	});
	var searchitem = $("#searchitem").val();
	var searchct = $("#searchct").val();

	var url = 'gamelobby_action.php?a=' + global.listtables + '&num=' + global.maxiconnum + '&start=' + global.page;
	$.post(url, {
		'casino': casino,
		'search': searchitem,
		'searchct': searchct,
		'gametype': gametype,
		'subtype': subcate
	}, function (data) {
		global.maxpage = data.total;
		//console.log(data)
		if (data.gameitems) {
			var htmldata = data.gameitems;
			view_generate.apply(htmldata,[page]);
		} else {
			var loggerhtml = '<div class="mt-4 mx-auto p-2"><mark class="alert alert-info">' + data.logger + '</mark></div>';
			$('#gametable').html(loggerhtml);
			$('#gametable').width('100%');
			swiper.update();
		}
		if(searchct == 'game' || searchct == ''){
			$('#gamesel').show();
		}else{
			$('#gamesel').hide();
			$('#collapsemenu').removeClass("show");
		}
		update_page(data.total, global.page, 'gametablepage');
	}, 'json');

}
// 分頁 ref： http://fanli7.net/a/bianchengyuyan/PHP/20120624/175310.html
function update_page(total_page, current_page, father) {
	total_page = parseInt(total_page);
	current_page = parseInt(current_page);
	global.page = current_page;
	//不包next 和 prev 必須为奇數
	var pager_length = 5;
	var pager = new Array(pager_length);
	var header_length = 2;
	var tailer_length = 2;
	//header_length + tailer_length 必須为偶數
	// main_length 必須为奇數
	var main_length = pager_length - header_length - tailer_length;

	var a_tag = 'li';
	var a_class = 'page_btn';
	var a_id = 'page';
	var a_name = 'pagebtn';
	var a_onclick = 'get_gametable';

	var disable_class = 'page_btn btn-default disable';
	var select_class = 'page_btn btn-default select';
	var i;
	var code = '';
	if (total_page < current_page) {
		//alert('總頁數不能小於當前頁數'+total_page+' '+current_page);
		//return false;
		code += fill_tag(a_tag, select_class, a_id, a_name, a_onclick, 1, 1);
		var prev = fill_tag(a_tag, disable_class, a_id, a_name, a_onclick, current_page, '«');
		var next = fill_tag(a_tag, disable_class, a_id, a_name, a_onclick, current_page, '»');
		code = prev + code + next;
	} else {
		//判斷總頁數是不是小於分頁長度，若小於分頁長度則直接顯示
		if (total_page < pager_length) {
			for (i = 0; i < total_page; i++) {

				code += (i + 1 != current_page) ? fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i + 1, i + 1) : fill_tag(a_tag, select_class, a_id, a_name, a_onclick, i + 1, i + 1);
			}
		}
		//如果總頁數大於分頁長度, 則進行分頁及部份頁數隱藏處理
		else {
			//先計算中心偏移量，即當前頁面頁數的位置
			var offset = (pager_length - 1) / 2;
			//分三種情況，第一種當前頁面在中心的左邊頁數，左邊沒有...
			if (current_page <= offset + 1) {
				var tailer = '';
				//前header_length + main_length 個直接輸出之後加一個...然後輸出倒數的    tailer_length 個
				for (i = 0; i < header_length + main_length; i++)
					code += (i + 1 != current_page) ? fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i + 1, i + 1) : fill_tag(a_tag, select_class, a_id, a_name, a_onclick, i + 1, i + 1);
				code += fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i + 1, '...');
				for (i = total_page; i > total_page - tailer_length; i--)
					tailer = fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i, i) + tailer;

				code += tailer;
			} else if (current_page >= total_page - offset) {
				//第二種情況當前頁面在在中心的右邊頁數，右邊沒有...
				var header = '';
				//後tailer_length + main_length 個直接輸出之前加一個...然後拼接 最前面的 header_length 個
				for (i = total_page; i >= total_page - main_length - 1; i--)
					code = ((current_page != i) ? fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i, i) : fill_tag(a_tag, select_class, a_id, a_name, a_onclick, i, i)) + code;
				code = fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i - 1, '...') + code;
				for (i = 0; i < header_length; i++)
					header += fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i + 1, i + 1);
				code = header + code;
			} else {
				//最後一種情況，兩邊都有...
				var header = '';
				var tailer = '';
				//首先處理頭部
				for (i = 0; i < header_length; i++)
					header += fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i + 1, i + 1);
				header += fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i - 1, '...');
				//處理尾巴
				for (i = total_page; i > total_page - tailer_length; i--)
					tailer = fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i, i) + tailer;
				tailer = fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i + 1, '...') + tailer;
				//處理中間
				//計算main的中心點
				var offset_m = (main_length - 1) / 2;
				var partA = '';
				var partB = '';
				var j;
				var counter = (parseInt(current_page) + parseInt(offset_m));
				for (i = j = current_page; i <= counter; i++, j--) {
					partA = ((i == j) ? '' : fill_tag(a_tag, a_class, a_id, a_name, a_onclick, j, j)) + partA;
					partB += (i == j) ? fill_tag(a_tag, select_class, a_id, a_name, a_onclick, i, i) : fill_tag(a_tag, a_class, a_id, a_name, a_onclick, i, i);
				}
				code = header + partA + partB + tailer;
			}
		}
		var prev = (current_page == 1) ? fill_tag(a_tag, disable_class, a_id, a_name, a_onclick, current_page, '«') : fill_tag(a_tag, a_class, a_name, a_name, a_onclick, current_page - 1, '«');
		var next = (current_page == total_page) ? fill_tag(a_tag, disable_class, a_id, a_name, a_onclick, current_page, '»') : fill_tag(a_tag, a_class, a_name, a_name, a_onclick, current_page + 1, '»');
		code = prev + code + next;
	}

	$('#' + father).html(code);
}

function fill_tag(a_tag, a_class, a_id, a_name, a_onclick, a_currentpage, a_html) {
	a_class = (a_class == '') ? '' : ' class="' + a_class + '"';
	a_id = (a_id == '') ? '' : ' id="' + a_id + '"';
	a_name = (a_name == '') ? '' : ' name="' + a_name + '"';
	a_onclick = (!a_onclick) ? '' : ' onclick="' + a_onclick + '(' + a_currentpage + ');"';
	a_currentpage = (!a_currentpage) ? '' : ' onclick="' + a_onclick + '(' + a_currentpage + ');"';
	var code = '<' + a_tag + a_class + a_id + a_name + a_onclick + ' >' + a_html + '</' + a_tag + '>';
	return code;
}

// 新增/移除我的最愛
function addmyfav(casinoname, gameid, gameplatform, token) {
	if (ss == 1) {
		$.post('gamelobby_action.php?a=addmyfav', {
				casinoname: casinoname, // 實際是 casinoid
				gameid: gameid,
				gameplatform: gameplatform
			},
			function (result) {
				//get_gametable(global.page);
				getRecommendGame();
				getViewRecords();
				alert(result.logger);
				window.location.reload();
			}, 'json');
	} else {
		loginpage(token);
	}
}

function delmyfav(casinoname, gameid, gameplatform, gametype, gamename) {
	if (confirm(lang.favdelconfirm.replace('%s',gamename))) {
		$.post('gamelobby_action.php?a=delmyfav', {
				casinoname: casinoname, // 實際是 casinoid
				gameid: gameid,
				gametype: gametype,
				gameplatform: gameplatform
			},
			function (result) {
				//get_gametable(global.page);
				getRecommendGame();
				getViewRecords();
				alert(result.logger);
				window.location.reload();
			}, 'json');
	}
}

// 進入GAME
function gotogame(casinoname, gamecode, gameplatform, gamename, token) {
	var show_text = lang.gotogame.replace('%s',gamename);
	var url = 'gamelobby_action.php?a=gotogame&closed=1&casinoname=' + casinoname;

	// 儲存瀏覽紀錄
	let gameItem = {
		'casino': casinoname, // 實際是 casinoid
		'code': gamecode,
		'platform': gameplatform,
		'name': gamename,
		'token': token
	};

	$.ajax({
		url: 'gamelobby_action.php?a=setReviewGame',
		data: gameItem,
		type: 'POST',
		async: false,
		dataType: 'json'
	}).done(function (data) {
		if (ss == 1) {
			var wait_text = '<div style="width: 100%;	height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden;">'+lang.processing+'<img src="' + global.cdnurl + 'loading_balls.gif"></div>';
			$.blockUI({
				message: wait_text,
				css: {
					border: 'none',
					height: '100%',
					'cursor': 'auto',
					'width': '100%',
					'top': '0',
					'left': '0'
				}
			});

			if (jQuery.trim(gamecode) == '') {
				var show_error = lang.nogameinfo;
				alert(show_error);
			} else {
				$.get(url,
					function (result) {
						if (!global.myWindow || global.myWindow.closed) {
							global.myWindow = window.open('', 'gpk_gamewindow', 'fullscreen=no,status=yes,resizable=yes,top=0,left=0,height=1024,width=768', false);
							// console.log('now window');
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
							global.myWindow.postMessage(wait_text, '*');
							setTimeout(global.myWindow.location.replace(gotogamecodeurl));
							global.myWindow.focus();
							setTimeout('$.unblockUI();', 3000);
						}
				}, 'json');
			}
		} else {
			loginpage(token);
		}
	}).fail(function (e) {
		alert(alert(lang.ConnestionTimeOut));
		$.unblockUI();
	});
}

function gamesearch() {
	var searchitem = $("#searchitem").val();
	var searchct = $("#searchct").val();
	var url = 'gamelobby_action.php?a=searchgame&start=1';
	$.post(url, {
		'search': searchitem,
		'searchct': searchct
	}, function (data) {
		if (!data.logger) {
			var htmldata = data.gameitems;
			$('#gametable').empty();
			global.page=1;
			global.maxpage=data.total;
			view_generate.apply(htmldata,[global.page]);
		} else {
			html += '<center><mark class="bg-info">' + data.logger + '</mark></center>';
		}
		update_page(data.total, global.page, 'gametablepage');
	}, 'json');
}

function loginpage(token) {
	var gotogamecodeurl = 'login2page.php?t=' + token;
	var wait_text = '<div style="width: 100%;	height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden;">'+lang.processing+'<img src="' + global.cdnurl + 'loading_balls.gif"></div>';
	$.blockUI({
		message: wait_text,
		css: {
			border: 'none',
			height: '100%',
			'cursor': 'auto',
			'width': '100%',
			'top': '0',
			'left': '0'
		}
	});
	//global.myWindow = window.open('', '_self', 'fullscreen=no,status=yes,resizable=yes,top=0,left=0,height=1024,width=768', false);
	//global.myWindow.document.write(wait_text);
	//global.myWindow.moveTo(0,0);
	//global.myWindow.resizeTo(screen.availWidth, screen.availHeight);
	//console.log(gotogamecodeurl);
	//global.myWindow = window.open(gotogamecodeurl, '_self', 'fullscreen=no,status=yes,resizable=yes,top=0,left=0,height=1024,width=768', false);
	global.myWindow = window.open(gotogamecodeurl, 'gpk_gamewindow', 'fullscreen=no,status=yes,resizable=yes,top=0,left=0,height=1024,width=768', false);
	global.myWindow.focus();
	setTimeout('$.unblockUI();', 3000);
}

function mct_getgamelist(mct){
	global.listtables = 'hotgamestable';
	$("#casinotab li").removeClass("active");
	$("#hotgames").addClass("active");
	global.page = 1;

	if(mct=='game'){
		$('#searchct').val('');
	}else{
		$('#searchct').val(mct);
	}
	get_gametable(1);
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
							myFavGameBtnHtml +
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
