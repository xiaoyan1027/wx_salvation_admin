$(document).ready(function(){
//Table list hover
	$(".table_list tr").hover(
		function () {
		$(this).addClass("hover");
		},
		function () {
		$(this).removeClass("hover");	
	});
//Button add class
	$(":button,:submit,:reset").addClass("btn");
//Input add class
	$("input:text").addClass("text")
	.focus(function(){
		$(this).addClass("text_focus")
	})
	.blur(function(){
		$(this).removeClass("text_focus")
	})

})