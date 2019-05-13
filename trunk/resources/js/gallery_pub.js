//上传处理
function uploadStart(file) {
	try {
		var tree_id = jQuery('#tree_id').val();
		if(tree_id == '' || tree_id == 0)
		{
			alert("请选择目录！");
			return false;
		}
		var name = jQuery('#name').val();
		swfu.setPostParams({'tree_id':tree_id,'name':name,'cookie':document.cookie});
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("图片上传中...");
		progress.toggleCancel(true, this);
	}
	catch (ex) {}
	
	return true;
}

//上传成功
function uploadSuccess(file, serverData) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		progress.setStatus("上传完成.");
		progress.toggleCancel(false);
		
		var data = jQuery.parseJSON(serverData);
		if(data.result == 'succ')
		{
			showUploadPic(data.data);
		}
		
	} catch (ex) {
		this.debug(ex);
	}
}

//显示上传图片
function showUploadPic(data)
{
	try {
		var show_html = '<li>';
		show_html += '<input type="hidden" name="upload_pic_url['+data.insert_id+']" value="'+data.pic_url+'" />';
		show_html += '<input type="hidden" name="upload_insert_id['+data.insert_id+']" value="'+data.insert_id+'" />';
		show_html += '<input type="hidden" name="upload_name['+data.insert_id+']" value="'+data.name+'" />';
		show_html += '<img src="'+data.pic_url+'">';
		show_html += '<a href="javascript:void(0)" onclick="delCurrentPic(this)">删除</a>';		
		show_html += '</li>';
		
		jQuery('#show_upload_pic_ul').append(show_html);
	}
	catch (ex) {}
}

//删除图片
function delCurrentPic(thisObj)
{
	if(confirm('确认删除？'))
	{
		jQuery(thisObj).parent().remove();
	}
}

//目录
function gallery_tree_change(fid)
{
	var obj = fid;
	if(fid != 0)
	{
		fid = jQuery(obj).val();
		jQuery('#tree_id').val(fid);
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

//图片目录
gallery_tree_change(0);
