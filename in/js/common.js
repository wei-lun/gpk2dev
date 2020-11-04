//解決bootstrap3升4 nav_tabs觸發問題
$(function() {
	
  $('.nav-tabs a').on('click', function () {
  	$(this).parent("li").siblings().removeClass("active");
  	$(this).parent("li").addClass("active");
	})

});