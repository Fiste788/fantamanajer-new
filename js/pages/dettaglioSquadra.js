$(document).ready(function() { 
	$("#qui").toggle(
	function(event){
		$("#mostraDati").css("display","none");
		$("#datiNascosti").fadeIn(function () {
			if(jQuery.browser.msie)
				$(this).removeAttr("filter");
		});
		$("#qui").attr("title","Nascondi menu");
	},
	function(event){
		$("#datiNascosti").css("display","none");
		$("#mostraDati").fadeIn(function () {
			if(jQuery.browser.msie)
				$(this).removeAttr("filter");
		});
		$("#qui").attr("title","Mostra menu");
	});
});
$(document).ready(function() { 
	$(".fancybox").fancybox({
		'zoomSpeedIn': 500,
		'zoomSpeedOut' : 500,
		'imageScale' : true,
		'zoomOpacity' : true,
		'overlayShow' : true,
		'overlayOpacity' : 0.7,
		'centerOnScroll' : true,
		'padding' : 0,
		'hideOnContentClick' : true
	})
});