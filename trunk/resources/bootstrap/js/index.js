$(document).ready(function(){
    $(".left li.menu_header a").click(function () {
        $(this).parent().find("ul.submenu").slideToggle(300);
        if($(this).parent().hasClass('active'))
        {
        	$(this).parent().removeClass("active");
        }
        else
        {
        	$(this).parent().addClass("active");
        }
    });
    
    $(".submenu a").click(function () {
        $(".submenu").find("li").removeClass("active");
        $(this).parent().addClass("active");
    });
    
    $('#change_weixin_house').change(function(){
    	var weixin_house_id = $(this).val();
    	if(weixin_house_id!='')
    	{
    		window.location.href='/?site=gongzhong&ctl=account&act=change_house&weixin_house_id=' + weixin_house_id;
    	}
    });


});