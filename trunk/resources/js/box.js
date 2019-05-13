/**
 * box插件
 * 注意页面头必须定义w3c标准,否则定位不准确
 */
(function($) {
    var $this, $box, box, $p;
    //主函数
    $.box = function (options)
    {
    	//插件主参数
        $p = $.extend({}, $.box.defaults, options);
    	
    	//创建覆盖层
    	if($p.overlay && $('div.overlay').length < 1) $.box.overlay();
    	
    	//加入box
    	createBox();
    	
    	//加入标题栏与关闭按钮
    	title();
    	
    	//加入按钮组
    	button();
    	
    	//显示内容
    	if($p.str) {
    		box.prepend($p.str);
    		cb();
    	}else if($p.url) {
    		if($p.post) {
    		    $.post($p.url, $p.post, cb);
    		}else{
    		    $.get($p.url, cb);
    		}
    	}
    }
    
    //公开方法: 改变全局默认设置
    $.box.set = function (options){
        $.box.defaults = $.extend({}, $.box.defaults, options);
    }
    
    //关闭tip
    $.box.close = function (id)
    {
		if(!id) id = 'jqBox';
	    if($('div.jqBox').length < 2) {
	        $(window).unbind('resize');
	        $('div.overlay').remove();
	    }
		//$p.close($('#'+id));
		$('#'+id).remove();
    }
    
    //创建覆盖层
    $.box.overlay = function (options)
	{
		//设置参数的默认值
		var p = $.extend({
			zIndex: 99,	//z轴
			opacity: .6,	//透明度
			action: ''	//可以是close,loading
		},options || {});
		
		//定位信息
		var ps = size($('body'));
		
		//覆盖层
		var ol = $('<div class="overlay"></div>');
		ol.css({
			left: 0,
			top: 0,
			display: 'none',
			zIndex: 99,
			position: 'absolute',
			textAlign: 'left',
			opacity: p.opacity
		});
		$('body').append(ol);
		ol.css({
		    width: ps.W,
		    height: ps.H,
		    opacity: p.opacity,
		    background: '#000'
		}).show();
		return this;
	}
	
    //创建容器
    var createBox = function()
    {
        if($('#'+$p.id).length) $('#'+$p.id).remove();
    	$('body').append('<div id="' + $p.id + '" class="jqBox"><div class="box"></div></div>');
    	$box  = $('#'+$p.id);
    	box = $box.find('div.box');
    	box.css({height: $p.height - 40, overflowY: 'auto', overflowX: 'hidden', padding: '6px 3px'});
    	
    	
    	//box的调整
    	var zIndex = 99 + $('div.jqBox').length;
    	$box.css({position:'absolute',visibility:'hidden',overflow:'hidden', 
    	           width:$p.width,zIndex:zIndex,fontSize:'12px',color:'#444',top: 0,lineHeight: '22px',
    		      background: $p.background,border:'2px solid #abc'});
    	if($p.height) $box.height($p.height);
    	$box.find('select').css({display:'none'});
    	if($p.style) {
    		$box.attr('style',box.attr('style') + ';' + $p.style)
    	}
    	return $box;
    }
    
    var title = function ()
    {
        $box.prepend('<div class="boxTitle">'+$p.title+'</div>');
    	var title = $box.find('.boxTitle');
    	title.css({left:0, top:0, background:'#69f url('+$p.titleBg+')', height:24,
    	          width:box.innerWidth() - 4, color:'#fff',
    	          fontSize:'14px', fontWeight:'bold', padding: '4px 0 0 4px'});
    	if($p.titleStyle){
    		title.attr('style',title.attr('style') + ';' + $p.titleStyle)
    	}
    	if($p.closeBtn) {
    		title.append('<a class="jqBoxClose" href="javascript:;" title="esc键关闭">\
    		              <img src="'+$p.closeImg+'" alt="X"/></a>');
    
    		$('a.jqBoxClose').css({position:'absolute',right: '5px',top:'5px',color:'#A00'}).click(function (){
    			var id = $(this).parents('.jqBox').attr('id');
    		    $.box.close(id);
    		}).find('img').css('border','none');
    	}
    	$(document).keydown(function (e){
    		if (e.keyCode == 27) {
    		    $.box.close($p.id);
    		}
    	});	
    }
    //生成按钮组
    var button = function ()
    {
        if($p.buttons.length) {
    		box.append('<div class="boxBtn" style="margin-top: 8px;text-align:'+$p.buttonAlign+';"></div>');
    	}
    	for(var k = 0; k < $p.buttons.length; k++) {
    		box.find('.boxBtn').append('<button style="margin:0 5px;height:26px;padding:0 6px;">'+$p.buttons[k].text+'</button>')
    			.find('button:last').click($p.buttons[k].handler);
    	}
    	if($p.buttonClass) box.find('.boxBtn button').addClass($p.buttonClass);
    	
    	$(window).resize(function (){
    	    posi($box);
    	});    
    }
    
    //ajax载入内容,执行回调函数
	var cb = function(data) {
		box.append(data);
		box.find('select').css('display','none');
		setTimeout(epilogue, 0);
	}
    		
    //执行善后工作,自动置中. 显示box,ie6下select的处理
	var epilogue = function (){
		posi();
		$box.css('visibility','visible');//先隐藏再显示,不然在IE中定位时窗口会从左边闪到中间,用透明度来隐藏比display好,不影响定位
		$box.find('select').show();
		if($box.drag && $p.drag) {
		    //拖拽效果
    		$box.drag({handler: '.boxTitle'});
		}
		$p.callback.call($box, $box);
	}
    	
    //定位
    var posi = function ()
    {
        //box定位
		var ps = size($('body'));
		t = ps.h > $box.outerHeight() ? (ps.h - $box.outerHeight()) / 2 + ps.top : ps.h/2;
		l = (ps.w - $box.outerWidth()) / 2 + ps.left;
		$box.css({left:l, top:t});
    }
	
    //获取宽高(html头必须定义w3c标准)
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
		var x = document.documentElement.scrollLeft;
		var y = document.documentElement.scrollTop;
		if(self.pageXOffset) {	//兼容chrome
			x=self.pageXOffset;
			y=self.pageYOffset;
		}
		//整体正文宽高
		var W = $(document).width();
		var H = $(document).height();
		return {w:w, h:h, W:W, H:H, left:x, top:y};
	}

	/**
     * box插件的confirm版本
     * @param string str	提示文本
     * @param fn yes	按下yes的回调函数,执行完关闭box,除非回调函数返回false
     * @param fn no		按下no的回调函数,默认为关闭box
     * @example		$.confirm('确定删除?', function (){//删除操作});
     */
    $.confirm = function (str, yes, no)
    {
    	var options = {
    	    id: 'confirm',
    	    title: '系统信息',
    		str: '<span style="color: #A00;">' + str + '</span>',
    		width: 250,
    		height: 100,
    		closeBtn: false,
    		show: function (){
    			$('.jqBox .boxBtn button:last').focus()
    		},
    		buttons:[
    			{text:'确定', handler:function (){
    				if(yes.call() != false) {
    				    $.box.close('confirm');
    				}
    			}},
    			{text:'取消', handler:function (){
    			    if(no) no.call();
    			    $.box.close('confirm');
    			}}
    		]
    	};
    	$.box(options);
    	//自动调整高度
    	var box = $('#confirm .box');
    	box[0].scrollTop = 999; //滚动到最底下
    	var h = box[0].scrollTop;
    	$('#confirm').height(100 + h);
    	box.height(box.height() + h);
    	//焦点到按钮
    	setTimeout(function (){
    	    box.find('.boxBtn>button:first').focus();
    	}, 100);
    }
    /**
     * box插件的alert版本
     * @param string str	提示文本
     * @param fn yes	按下yes的回调函数
     * @param fn no		按下no的回调函数,默认为关闭box
     * @example		$.alert('很遗憾的说,你的智商低于10');
     */
    $.alert = function (str, yes)
    {
    	if(!yes) var yes = function (){};
    	var options = {
    	    id: 'alert',
    	    title: '系统信息',
    		str:str,
    		str: '<span style="color: #A00;">' + str + '</span>',
    		height: 100,
    		width: 250,
    		closeBtn: false,
    		show: function (){
    			$('.jqBox .boxBtn button:first').focus();
    		},
    		buttons:[{text:'确定', handler:function (){
    			if(yes.call() != false) $.box.close('alert');
    		}}]
    	};
    	$.box(options);
    	//自动调整高度
    	var box = $('#alert .box');
    	box[0].scrollTop = 999; //滚动到最底下
    	var h = box[0].scrollTop;
    	$('#alert').height(100 + h);
    	box.height(box.height() + h);
    	//焦点到按钮
    	setTimeout(function (){
    	    box.find('.boxBtn>button:first').focus();
    	}, 100);
    }
    
    /**
     * 拖拽插件，无依赖，为box提供拖拽功能，如果缺少本插件box也不出错，只是无拖拽功能
     * 例: $('div').drag({handler:'h2'});	//将div中的h2做为拖拽句柄
     */
    $.fn.drag = function (options)
    {
    	var p = $.extend({
    		handler: ''		//可拖拽点，默认为本身
    	},options || {});
    	function int(v) {
    		v = parseInt(v);
    		return isNaN(v) ? 0 : v; 
    	}
    	this.each(function (i, e){
    		e = $(e);
    		var mx,my,x,y,left,top;	//mx,my是鼠标点击位置。x,y是元素原始坐标,left,top是元素的css属性
    		var mt = int(e.css('margin-top'));
    		var ml = int(e.css('margin-left'));
    		e.css({position:'absolute'});
    		if(p.handler) {
    			var handler = e.find(p.handler);
    		}else{
    			var handler = e;
    		}
    		handler.css('cursor','pointer');
    		handler.mousedown(function (ev){
    			ev = ev || window.event;
    			var of = e.offset();
    			mx = of.left;	//元素左上角x坐标
    			my = of.top;
    			x = ev.pageX;	//鼠标x坐标
    			y = ev.pageY;
    			
    			left = int(e.css('left'));
    			top = int(e.css('top'));
    			$(document).mousemove(function (ec){
    				ec = ec || window.event;
    				e.css({left:mx + ec.pageX - x - ml, top:my + ec.pageY - y - mt});
    			});
    		});
    		$(document).mouseup(function (){
    			$(document).unbind('mousemove');
    		});
    	});
    	return this;
    }

    //默认值
    $.box.defaults = {
        id: 'jqBox',
		width: 600,
		height: 400,			//设为0则自动调整高度,不出现滚动条
		overlay: true,			//是否使用覆盖阴影
		background: '#fff',
		overflow: 'auto',       //或者auto
		title: '&nbsp;',				//如果为空,不显示标题栏
		drag: true,				//是否可拖拽，需要drag插件，如果没有也不会出错只是不能拖拽
		url: '',				//ajax载入url
		post: null,               //如果设置了post,就用post传输数据,值是一个键值对
		callback:function(box){},    //载入后执行,只有设置了url参数才有效,在函数中box是box的jqeury封装
		close:function(box){},	  //关闭时执行的函数
		str: '',				//非ajax方式时显示的内容,与url参数不共存
		closeBtn: true,			//是否需要右上角关闭图标
		closeImg: '../resources/images/del.gif',	//关闭图标图片
		titleBg: '/js/apps/table/images/titleBg.gif',        //title背景图
		buttons: [],			//按钮组,每个按钮是{text:'确定',handler:fn}形式
		buttonAlign: 'center',		//按钮置中(或left,right)
		buttonClass: '',		//按钮的class
		style: '',				//主容器的css设置
		titleStyle:''			//标题栏css设置
    };
})(jQuery);