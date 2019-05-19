/**
 * 简易提示插件
 *
 * @author xingye (15957674)
 * @version 1.2
 * @param {mixed} options   对象或字符串
 * @param {int} time        小于100是秒数,大于100是毫秒数
 * @return {string} url     显示消失后跳转的url
 *
 * 一.简化用法
 * $.tip('2秒后自动消失');
 * $.tip('0.5秒后消失',500);
 * $.tip('不自动消失',0);	 * 按esc键消失,或者调用$.tip('close');
 * $.tip('2秒后自动转到百度,如果按esc键马上就跳转','http: * www.baidu.com');	 * 时间参数和网址参数顺序可对调,因此不需要写成$.tip('内容',2000,url);
 * 二.复杂用法.详细设置请查看设置参数默认值部分,可任意混合设定
 * $.tip({str:'红色文字',color: '#F00'});
 * $.tip({str:'灰色背景',background: '#ccc'});
 * $.tip({str:'透明度设定',opacity: .5});
 * $.tip({str:'透明度和内间距设置',opacity: .5,padding:'6px 20px'});
 * $.tip({str:'不显示覆盖阴影层',overlay:false});
 * $.tip({str:'显示在上方的中间',place: ['center','top']});
 * $.tip({str:'显示在左上方,偏移20像素',place: ['left','top'], offset[20, 20]});
 * 三.以上的所有用法都是针对全局显示的,其实可以为局部元素显示,并且可为多个元素显示
 * $('#blogDiv,#replyDiv,#photoDiv').tip('载入中,请稍候');	 * 其实这种效果使用loading更方便一些.
 * $('#blogDiv,#replyDiv').tip.close();	 * 只关闭日志与评论部分的提示,photo部分仍然显示
 */
(function($) {
    var $this, $jq, $p;
    
    //主函数
    $.fn.tip = function(options, time, url) {    
    	if(typeof options != 'object') {
    		var options = {str: options};
    		if(typeof time == 'number') options.time = time;
    		if(typeof time == 'string') options.url = time;
    		if(typeof url == 'number') options.time = url;
    		if(typeof url == 'string') options.url = url;
    		if(options.time < 100) options.time *= 1000;
    	}
    	
    	//插件主参数
        $p = $.extend({}, $.tip.defaults, options);
    	this.each(function (i){
    	    //以单元素的data覆盖主参数
    	    $this = $(this);
    		
    		//创建提示层容器
    		createJqMsg();
    		
    		//定位
    		posi();
    		$jq.show();
    		
    		if($p.time != 0) {
    			//加入定时器队列
    			$.tip.queue[$.tip.serial] = +new Date + $p.time;
    			if(!$.tip.timing) {	//一定要判断,不然会有多个定时器同时转动
    				$.tip.timing = setInterval(function (){
    				    timing();
    				}, 100);
    			}
    		}
    		if($p.url && $p.time > 0) setTimeout("location.href='"+$p.url+"'", $p.time);
    		
    		esc();        //esc退出
    		$(window).resize(posi);   //调整定位
    	});
    	return this;
    };
    
    //简化操作
    $.tip = function (options, time, url){
        $('body').tip(options, time, url);
    }
    
    //公开方法: 改变全局默认设置
    $.tip.set = function (options){
        $.tip.defaults = $.extend({}, $.tip.defaults, options);
    }
    
    //关闭tip
    $.tip.close = function ()
    {
        $('body').tip.close();
    }
    $.fn.tip.close = function ()
    {
		$('div.'+$p.className+'[serial='+$this.attr('serial')+']').remove();
    }
    
    //创建容器
    var createJqMsg = function()
    {
        //给层编号
		if($this.attr('serial')) {
			$.tip.serial = $this.attr('serial');
		}else{
			$this.attr('serial', ++$.tip.serial) ;
		}
		
		$jq = $('div.'+$p.className+'[serial='+$.tip.serial+']');
		//创建容器
		if(!$jq.length) {
			var jqMsg = $('<div/>').attr({className: $p.className, serial: $.tip.serial})
			             .css({position: 'absolute', display: 'none'});
			$('body').append(jqMsg);
			$jq = $('div.'+$p.className+'[serial='+$.tip.serial+']');
		}
	
		var leng = $p.str.replace(/[^\x00-\xff]/g, "**").length;	//字符串真实长度
		if(leng > 40) $jq.width(200);
		$jq.html($p.str).css({
		    padding: $p.padding,
		    zIndex: $p.zIndex,
		    color: $p.color,
		    background: $p.background,
		    border: $p.border,
		    textAlign: $p.textAlign,
		    fontSize: $p.fontSize
		});
    }
    
    //定位
    var posi = function (){
        //开始定位
		var ps = size($this);     //取元素尺寸与偏移信息
	    var top, left; 
	    var jqw = $jq.outerWidth();
	    var jqh = $jq.outerHeight();
		if($p.place[0] == 'left') {
		    left = ps.left + $p.offset[0];
		}else if($p.place[0] == 'right') {
		    left = ps.left + ps.w - jqw + $p.offset[0];
		}else {
		    left = ps.left + (ps.w - jqw) / 2 + $p.offset[0];
		}
		if($p.place[1] == 'top') {
		    top = ps.top + $p.offset[1];
		}else if($p.place[1] == 'bottom') {
		    top = ps.top + ps.h - jqh + $p.offset[1];
		}else{
		    top = ps.top + (ps.h - jqh) / 2 + $p.offset[1];
		}
		//显示
    	$jq.css('top',top).css('left', left);
    }
    
    //获取宽高
    var size = function ($e){
		//局部元素
		if($e.attr('tagName') != 'BODY') {
			var posi = $e.offset();
			posi.w = posi.W = $e.outerWidth();
			posi.h = posi.H = $e.outerHeight();
			return posi;
		}
		//获取浏览器可见部分的定位宽高信息
		var w = document.documentElement.clientWidth;
		var h = document.documentElement.clientHeight;
		var x=document.documentElement.scrollLeft;
		var y=document.documentElement.scrollTop;
		if(self.pageXOffset) {	//兼容chrome
			x=self.pageXOffset;
			y=self.pageYOffset;
		}
		//整体正文宽高
		var W = $(document).width();
		var H = $(document).height();
		return {w:w, h:h, W:W, H:H, left:x, top:y};
	}
	
	//定时器
	var timing = function ()
	{
		var now = +new Date;
		for(k in $.tip.queue) {
			if(now > $.tip.queue[k]) {
				$('div.'+$p.className+'[serial='+k+']').remove();
			}
		}
		if($('div.'+$p.className).length == 0) {
			clearInterval($.tip.timing);	//都消失时停止定时器
			$.tip.timing = null;
		}
	}
	
	//按esc键退出
	var esc = function ()
	{
    	$(document).keydown(function (e){
    	    if(e.keyCode == 27) {
    	        if($p.url) {
        			setTimeout(function (){
        			    location.href = $p.url;
        			}, 100);
        		}
    	    }
    	});
	}
	
	//定时器句柄
	$.tip.timing = null;    
	//定时器队列
	$.tip.queue = {};
	$.tip.serial = 0;
    //默认值
    $.tip.defaults = {
        str:' ',			    //内容
		time:2000,				//延时自动消失,单位为毫秒,设为0不消失,按esc键时才消失(若小于100,则单位为秒)
		url:'',					//提示消失后转向的url
		color: '#a00',			//字体颜色
		background: '#ffc',		//背景
		border: '3px solid #abc',	//边框
		fontSize: '12px',		//字体大小
		opacity: .2,			//背景透明度,如果overlay设为真
		textAlign: 'left',		//对齐方式
		zIndex: 1500,			//z轴
		padding: '10px 50px',	//内间距
		place:['center','middle'],	   // ['left','top']
		offset: [0, 0],                   //以正负数字来偏移
		className: 'jqMsg'       //容器class
    };
})(jQuery);