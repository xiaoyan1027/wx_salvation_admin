function gallery_list_html()
{
	var html ='<div id="gallery_manager_id" style="display:none;">\
	<div class="PopUpBg"></div>\
	<iframe class="PopUpCover"></iframe>\
	<div class="popup_imglist"> <a href="javascript:void(0)" class="close" id="gallery_close_id">[关闭]</a>\
		<ul class="img_select clearfix" id="gallery_select_list_id"></ul>\
		<ul class="img_list clearfix" id="list_id"></ul>\
		<div class="tc p5" id="page_id"></div>\
		<div class="tc p5">\
			<input name="save_button" id="save_button"  type="button" value="保存">\
			<input name="quxiao_button" id="quxiao_button" type="button" value="取消">\
		</div>\
	</div>\
	</div>';
	return html;
}

function select_gallery(obj, ttype,e)
{
	if(!jQuery("#gallery_manager_id").length)
	{
		jQuery("body").append(gallery_list_html());
		jQuery("#gallery_close_id,#quxiao_button").click(function(){
			jQuery("#gallery_manager_id").remove();
		});

		jQuery("#quxiao_button").click(function(){
			jQuery("input[name='pic_url']").attr('checked',false);
		});

		jQuery("#save_button").click(function(){
			if(jQuery("input[name='pic_url']:checked").length<1)
			{
				alert('请选择图片!');
				return ;
			}
			var pic_url = [];
			jQuery("input[name='pic_url']:checked").each(function()
			{
				pic_url.push(jQuery(this).val());
			});
			 gallery_save( pic_url , obj, ttype,e);
			 jQuery("#gallery_manager_id").remove();
		});
	}
	
	jQuery("#gallery_manager_id").show();
	//PopUp
	var wheight=$(window).height();
	var wwidth=$(document).width()-30;
	var popHeight=wheight-$(this).height();
	var popWidth=wwidth-$(this).width();
	$(".PopUpCover").height(wheight).width(wwidth);
	$(".PopUpBg").height($(document).height());
	if(!!e){
	    var popHeight = $(".popup_imglist").height();
    	var y = e.pageY;
    	if(y + popHeight > $(document).height()){
    	    y = y > popHeight ? y - popHeight : 10 ;
    	}
    	$(".popup_imglist").css({
    	  top:  y + "px"    
    	});
    }
	gallery_tree_change(0);
}

function gallery_tree_change(fid)
{
	var obj = fid;
	if(fid != 0)
	{
		fid = jQuery(obj).val();
	}
	var gallery_id = 'gallery_select_'+fid;
	var gallery_li_id = 'gallery_select_li_'+fid;
	//if(fid == '-1') return;
	del_select_li(fid,obj);
	var url = "index.php?site=public&ctl=gallery_tree&act=parent_tree&fid="+fid;
	jQuery.ajax({
	  type:"GET",
	  url:url,
	  dataType:"json",
	  success: function(json){		  
		  if(json && json.length>0)
		  {
			var html = '<li id="'+gallery_li_id+'">';
			html += '<select name="'+gallery_id+'" id="'+gallery_id+'" onchange="gallery_tree_change(this)">';
			html += '<option value="-1">选择目录</option>';
			for(var i = 0; i<json.length; i++)
			{
				html += '<option value="'+json[i].id+'">'+json[i].name+'</option>';
			}
			html += '</select>';
			html += '</li>';
			jQuery("#gallery_select_list_id").append(html);
			
		  }
		  start_list(1 , fid);
	   } 
	});
}
/*
删除 目录后面的select 
*/
function del_select_li(fid,obj)
{
	var $li = '';
	 if(fid == 0)
	 {
		$li = jQuery("#gallery_select_li_0");
	 }else
	 {
		$li = jQuery(obj).parent();
	 }
	jQuery($li).nextAll().remove();
	jQuery("#list_id").html('');
	jQuery("#page_id").html('');
}
/**合成中间**/
function make_content_list(obj){

	var html = '<li><img src="'+obj.pic_url+'">\
				<label>\
					<input name="pic_url" type="checkbox" value="'+obj.pic_url+'" class="vm"> '+obj.name+'</label>\
			</li>'
	return html;
}

/**合成底部html**/
function make_content_footer(obj , tree_id){
	var shouye='start_list(1)';
	var moye='start_list('+obj.total_page+')';
	var html = '';
	if(obj.total_page>1){
		var page_sum=5;
		var page=Math.floor(page_sum/2);
		
		var begin=obj.page-page;
		var end=obj.page+page;
		begin=begin<1?1:begin;
		
		var temp=end-begin;
		if(temp<(page_sum-1)){
			temp=page_sum-temp-1;
			end=end+temp;
		}

		if(end>obj.total_page){
			temp=end-obj.total_page;
			begin=begin-temp;
			end=obj.total_page;
			begin=begin<1?1:begin;
		}
		if(obj.page>1){
				html +='<a href="javascript:start_list('+(obj.page-1)+','+tree_id+');" class="prev" title="上一页">上一页</a>';
		}else{
			   html +='<a class="prev" title="上一页">上一页</a>';
		}

		for(var c=begin;c<=end;c++){
			if(c==obj.page){
				html += c; 
			}else{
				html +=' <a href="javascript:start_list('+c+','+tree_id+');">'+c+'</a> ';
			}
		}

		if(obj.page<obj.total_page){
			html += '<a href="javascript:start_list('+(obj.page+1)+','+tree_id+')" class="next" title="下一页">下一页</a>';
		}else{
			html += '<a class="next" title="下一页">下一页</a>';
		}
	}
	return html;
}
/**翻页**/
function load_html_start(infos , tree_id){
	var total_item=parseInt(infos.size,10);
	var length= parseInt(infos.count ,10);
	var total_page=Math.ceil(length/total_item);
	page= parseInt(infos.page,10);

	var message_obj=new Object();
	message_obj.page=page;
	message_obj.total_page=total_page;
	var buf=[];
	for(var i=0;i<infos.list.length;i++){
		buf.push(make_content_list(infos.list[i]));
	}

	jQuery("#list_id").html(buf.join(""));

	jQuery("#page_id").html(make_content_footer(message_obj , tree_id));
}

function start_list(page ,  tree_id){
	var size = 9;
	function Lfill0(num){
			return (num>9?num:"0"+num);
	}
	 var url ='?site=public&ctl=gallery_pic&act=list_pic';
	 url += '&p='+page;
	 url += '&size='+size;
	 url += '&tree_id='+tree_id;
	 url += '&random='+Math.random();
	 jQuery.ajax({
	  type:"GET",
	  url:url,
	  dataType:"json",
	   success: function(json){
		  if(json.result == 'succ')
		  {
			  load_html_start(json.infos , tree_id);
		  }
	   } 
	});
}