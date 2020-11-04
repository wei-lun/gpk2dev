$(document).ready(function () {
	get_exposure_gametable();
});

function exposure_view_generate() {
	var html = '';
	if (this) {
		$.each(this, function (key, value) {
			html += '<div class="swiper-slide"><div class="gameitem"><div class="hot">热门</div><div class="gameitem-content"><div class="game-button">';
			if (value.bsdemo == '0') {
				if (ss == 1) {
					html += '<a class="gotogame" href="gamelobby_action.php'+ '?a=gotogame&login=1&mobile=1&casinoname=' + value.casinoname + '&gamecode=' + value.gamecode + '&gameplatform=' + value.gameplatform +'" target="_blank"><button value="' + value.g2gamelabel + '" onclick="gotogame()">' + value.g2gamelabel + '</button></a></div> </div>';
				} else {
					html += '<button class="gotogame" value="' + value.g2gamelabel + '" onclick="loginpage(\'' + value.token + '\')">' + value.g2gamelabel + '</button></div> </div>';
				}
				html += '<div class="gameitem-img"><img src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'"> </div>';
			} else {
				html += '<button type="button" class="gotogame" data-toggle="modal" data-target="#' + value.bsdemo + '">' + value.g2gamelabel + '</button></div> </div>';
				html += '<div class="gameitem-img"><img src="' + value.gameimgurl + '" onerror="this.src=\'' + value.cdnurl + '.png\'"> </div>';
			}
			html += '<div class="gameitem-info"><div class="info-lbl"><span class="gamename_desc">' + value.gamename + '</span></div>';
			html += '<div class="info-lb2"><span class="label label-' + value.casinoname.toLowerCase() + '">' + value.casinolabel + '</span><span class="info-lb2-icon">';
			if (value.gamename_mark == 'H5') {
				html += '<span></span>';
			}
			if (value.myfavfunc == 'addmyfav') {
				html += '<div class="fave-heart"> <span value="' + value.myfavlabel + '" onclick="' + value.myfavfunc + '(\'' + value.casinoname + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.myfavtoken + '\')"></span>';
			} else {
				html += '<div class="fave-heart-full"> <span value="' + value.myfavlabel + '" onclick="' + value.myfavfunc + '(\'' + value.casinoname + '\',\'' + value.gamecode + '\',\'' + value.gameplatform + '\',\'' + value.gametype + '\',\'' + value.gamename + '\')"></span>';
			}
			html += '</span></div> </div> </div> </div></div>';
		});

		$('#game-exposure').html(html);
		game_exposure_swiper.update()
	}
}

function get_exposure_gametable() {
	var url = 'gamelobby_action.php?a=hotgamestable&num=10&start=1';
	$.post(url, {}, function (data) {
		if (data.gameitems) {
			var htmldata = data.gameitems;
			exposure_view_generate.apply(htmldata);
		} else {
			var loggerhtml = '<div class="row mx-auto pb-2"><mark class="bg-info mx-auto p-2 rounded">' + data.logger + '</mark></div>';
			$('#gametable').html(loggerhtml);
		}
	}, 'json');
}
