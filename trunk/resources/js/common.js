//设置select的值 id select id v定位的值
function form_select_located(id,v)
{
	jQuery('#'+id).val(v);
}
//使用jQuery 日历
function datepicker(id)
{
	jQuery( "#"+id ).datepicker();
}
//删除通用确认
function del_item_confirm( gourl )
{
	if(confirm('你确实要删除嘛？'))
	{
		jQuery.ajax({
		type:'GET',
		dataType:'json',
		url:gourl,
        success:function(json){
			if(json.result == 'succ')
			{
				document.location.href = document.location.href;
			}else
			{
				alert('删除失败!');
			}
		}
		});
	}
}
//返回上次界面
function go(url)
{
	if(typeof(url) == 'undefined' || url == '')
	{
		history.go(-1);
		return;
	}

	document.location.href = url;
}
/*表单验证
调用1  validate_form('form'): 调用验证 form为表单form ID
调用2  validate_form('form',1): 调用验证 form为表单form ID  1 表示提交表单

<input type="text" value="" name="name" id="name" mod="ismobile" msg="不正确">
<input type="text" value="" name="name" id="name" mod="isempty|ismobile" msg="不能为空不正确|必须为手机">
<input type="text" value="" name="name" id="name" mod="isnumeral|ismobile" msg="" len="5-12">
判断是否一样
<input type="text" value="" name="name" id="name" mod='issame'  sameid="house_structure" msg='两次输入密码不一致'>
*/
;(function($){
	var conf = {
		"isemail": {
			msg: '邮箱地址格式不正确',
			reg: /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,5}){1,3})$/
		},
		"ismobile": {
			msg: '手机号码不正确',
			//reg: /^1(3\d{1}|5[389])\d{8}$/
			reg: /^1[35]\d{9}$/
		},
		"isidentity": {
			msg: '证件格式不正确',
			reg: /^(d){5,18}$/
		},
		"isempty": {
			msg: '该字段不能为空',
			reg: /./
		},
		"isnumeral": {
			msg: '必须为数字',
			reg: /[\d]+$/
		},
		"issame": {
			msg: '两次输入不一致',
			reg: null
		},
		"iscurrency": {
			msg: '输入金额格式不对',
			reg: /^\d+(\.\d{2})?$/
		},
		"charcount_15": {
			msg: '不能大于15位',
			reg: /^.{1,15}$/
		},
		"description_limit": {
			msg: '不能大于11位',
			reg: /^[^\|]{1,11}(\|[^\|]{1,11})*(\|[^\|]{1,11})?$/
		},
		"idcard": {
			msg: '身份证件格式不正确!',
			reg: /^[A-Za-z0-9]{18}$|^[A-Za-z0-9]{15}$/
		},
		"charcount_30": {
			msg: '不超过30字!',
			reg: /^.{1,30}$/
		}
	};
	//计算汉字长度
	function str_len(str) {
		var charset = document.charset;
		var len = 0;
		for(var i = 0; i < str.length; i++) {
			//len += str.charCodeAt(i) < 0 || str.charCodeAt(i) > 255 ? (charset == "utf-8" ? 3 : 2) : 1;
			len += str.charCodeAt(i) < 0 || str.charCodeAt(i) > 255 ? 3 : 1;
		}
		return len;
	}
	//验证有没有弹出信息 cmsg 配置信息， msg 自定义信息
	function validate_msg(cmsg,msg)
	{
		if(typeof(msg) != 'undefined' && msg!='')
		{
			return msg;
		}else
		{
			return cmsg;
		}
	}
	//验证是否有isempty  字段，如果没有并且值为空不验证
	function validate_mod(mod,v)
	{
		if(mod.indexOf('isempty')==-1 && v=='')
		{
			return false;
		}else
		{
			return true;
		}
	}
	//验证长度
	function validate_length(len,v)
	{
		var num = str_len(v);
		if(typeof(len) == 'undefined') return true;
		var l = parseInt(len.split('-')[0],10);
		var r = parseInt(len.split('-')[1],10);
		if(num < l || num > r) return false;
		return true;
	}
	//根据不同模式类型做处理 返回验证结果 v 值
	function mod_deal(input,mod,msg,v,len)
	{
		var result = true;
		if(!validate_length(len,v))
		{
			alert(validate_msg('长度应为'+len+'位',msg));
			input.focus();
			return false;
		}
		switch(mod)
		{
			case 'issame':
				var sameid = input.attr('sameid');
				if(v != $('#'+sameid).val())
				{
					alert(validate_msg(conf[mod].msg,msg));
					input.focus();
					result = false;
				}
				break;
			case 'isemptydatepicker':
				if(!conf['isempty'].reg.test(v))
				{
					alert(validate_msg(conf['isempty'].msg,msg));
					result = false;
				}
				break;
			default:
				if(typeof(conf[mod]) != 'undefined')
				{
					if(!conf[mod].reg.test(v))
					{
						alert(validate_msg(conf[mod].msg,msg));
						input.focus();
						result = false;
					}
				}
		}
		return result;
	}
	//处理验证
	function validate_start(obj)
	{
		var mod = obj.attr('mod');
		var value = obj.val();
		var msg = obj.attr('msg');
		var len = obj.attr('len');
		if(!validate_mod(mod,value))
		{
			return true;
		}
		msg = typeof(msg) != 'undefined'?msg:'';
		var mods = mod.split('|');//用竖线分割操作
		var msgs = msg.split('|');//有竖线分割信息
		for(var i=0;i<mods.length;i++)
		{
			if(!mod_deal(obj,mods[i],msgs[i],value,len))
			{
				return false;
			}
		}
		return true;
	}
	//验证
	function validate(objs)
	{
		var result = true;
		objs.each(function(){
			result = validate_start($(this));
			if(!result)
			{
				return false;
			}
		});
		return result;
	}
	//验证  input
	function validate_input(id)
	{
		var objs = $("#"+id+" input[mod]");
		return validate(objs);
	}
	//验证select
	function validate_select(id)
	{
		var objs = $("#"+id+" select[mod]");
		return validate(objs);
	}
	//验证textarea
	function validate_textarea(id)
	{
		var objs = $("#"+id+" textarea[mod]");
		return validate(objs);
	}
	window['validate_form'] = function(id , type)
	{
		var submit = validate_input(id);
		if(submit){submit = validate_select(id);}
		if(submit){submit = validate_textarea(id);}
		if(type==1 && submit)
		{
			$('#'+id).submit();
		}
		return submit;
	}
})(jQuery);

//统计图
var chart;
function stat_tu(tu_id, tu_title, tu_data)
{
	chart = new Highcharts.Chart({
		chart: {
			renderTo: tu_id,
			plotBackgroundColor: null,
			plotBorderWidth: null,
			plotShadow: false
		},
		title: {
			text: tu_title
		},
		tooltip: {
			formatter: function() {
				return '<b>'+ this.point.name +'</b>: '+ this.y +' ';
			}
		},
		plotOptions: {
			pie: {
				allowPointSelect: true,
				cursor: 'pointer',
				dataLabels: {
					enabled: true,
					color: '#000000',
					connectorColor: '#000000',
					formatter: function() {
						return '<b>'+ this.point.name +'</b>: '+ this.y +' ';
					}
				}
			}
		},
	    series: [{
			type: 'pie',
			name: tu_title,
			data: tu_data
		}]
	});
}

var fnDialog=(function(){
   //tip提示
   function _tip(data)
   {
        var message=data.message;
        var type=data.type || "success";
        var tipHtml = '<div class="callout callout-'+type+'" style="position:absolute;display:none;left:50%;top:50%;">'+message+'</div>';
        var panel=$(tipHtml);
        $("body").append(panel);
        panel.css({
            "margin-left":panel.width()/2*-1+"px",
            "margin-top":panel.height()/2*-1+"px",
        });
        panel.fadeIn();
        setTimeout(function(){
            panel.fadeOut();
        },2000);
   }
   //alert弹窗
   function _alert(data)
   {
        var message=data.message;
        var callback=data.callback;
        var type=data.type || "success";
        var alertHtml = ''+
        '<div class="modal modal-'+type+'">'+
          '<div class="modal-dialog" style="position:absolute;left:50%;top:50%;width:350px;">'+
            '<div class="modal-content">'+
              '<div class="modal-header">'+
                '<button type="button" class="close J_close" data-dismiss="modal" aria-label="Close">'+
                  '<span aria-hidden="true">×</span></button>'+
                '<h4 class="modal-title">温馨提示</h4>'+
              '</div>'+
              '<div class="modal-body">'+
                '<p>'+message+'</p>'+
              '</div>'+
              '<div class="modal-footer">'+
                '<button type="button" class="btn btn-outline J_ok">确认</button>'+
              '</div>'+
            '</div>'+
          '</div>'+
        '</div>';
        var panel=$(alertHtml);
        $("body").append(panel);
        panel.show();
        var dialog = $(".modal-dialog",panel);
        dialog.css({
            "margin-left":dialog.width()/2*-1+"px",
            "margin-top":dialog.height()/2*-1+"px",
        });
        $(".J_close,.J_ok",panel).click(function(){
            panel.remove();
        });
        $(".J_ok",panel).click(function(){
            if(callback) callback();
        });
   }
   
   //confirm弹窗
   function _confirm(data)
   {
        var title=data.title || "温馨提示";
        var message=data.message;
        var callback=data.callback;
        var type=data.type || "success";
        var ok_text=data.ok_text || "确认";
        var confirmHtml ='<div class="modal modal-'+type+'">'+
          '<div class="modal-dialog" style="position:absolute;left:50%;top:50%;width:400px;">'+
            '<div class="modal-content">'+
              '<div class="modal-header">'+
                '<button type="button" class="close J_close" data-dismiss="modal" aria-label="Close">'+
                  '<span aria-hidden="true">×</span></button>'+
                '<h4 class="modal-title">'+title+'</h4>'+
              '</div>'+
              '<div class="modal-body">'+
                '<p>'+message+'</p>'+
              '</div>'+
              '<div class="modal-footer">'+
                '<button type="button" class="btn btn-outline pull-left J_cancel" data-dismiss="modal">取消</button>'+
                '<button type="button" class="btn btn-outline J_ok">'+ok_text+'</button>'+
              '</div>'+
            '</div>'+
          '</div>'+
        '</div>';
        
        var panel=$(confirmHtml);
        $("body").append(panel);
        panel.show();
        var dialog = $(".modal-dialog",panel);
        dialog.css({
            "margin-left":dialog.width()/2*-1+"px",
            "margin-top":dialog.height()/2*-1+"px",
        });
        
        $(".J_ok,.J_cancel",panel).click(function(){
            var is_ok=false;
            if($(this).is('.J_ok'))
            {
                is_ok=true;
            }
            if(callback) callback(is_ok,panel);
        });
        
        $(".J_close,.J_ok,.J_cancel",panel).click(function(){
            panel.remove();
        });
   }
   return {
        tip:_tip,
        alert:_alert,
        confirm:_confirm
   }
})();
//undefined