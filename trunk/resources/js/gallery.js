//删除户型图图片
function del_pic(id)
{
	var url = 'index.php?site=public&ctl=gallery_pic&act=del_pic&id='+id;
	if(confirm('你确实要删除该图片嘛？'))
	{
		jQuery.ajax({
		type:'GET',
		dataType:'json',
		url:url,
        success:function(json){
			if(json.result == 'succ')
			{
				jQuery("#pic_"+id).remove();
			}else
			{
				alert('删除失败!');
			}
		}
		});
	}
}

function img_load_error(id,url)
{
	url += "?ranmdo="+Math.random();
	setTimeout(function(){
		jQuery('#'+id).attr('src',url);
	},5000);
}

//上传处理
function uploadStart(file) {
	try {
		var tree_id = jQuery('#tree_id').val();
		var name = jQuery('#name').val();
		swfu.setPostParams({'tree_id':tree_id,'name':name,'cookie':document.cookie});
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("图片上传中...");
		progress.toggleCancel(true, this);
	}
	catch (ex) {}
	
	return true;
}

function uploadSuccess(file, serverData) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		progress.setStatus("上传完成.");
		progress.toggleCancel(false);
		serverData = jQuery.trim(serverData);
		var obj = jQuery.parseJSON(serverData);
		if(obj.result=='succ')
		{
			var img = '<p class="fl m5 tc" id="pic_'+obj.data.insert_id+'"><a href="'+obj.data.pic_url+'" target="_blank"><img src="'+obj.data.pic_url+'" class="fl m5" width="150px" height="120px" onerror="img_load_error(\'img_url_'+obj.data.insert_id+'\',\''+obj.data.pic_url+'\')" id="img_url_'+obj.data.insert_id+'"></a><br>'+obj.data.name+'<br/><a href="?mod=gallery_pic&act=edit&id='+obj.data.insert_id+'">编辑</a>|<a href="javascript:void(0)" onclick="del_pic(\''+obj.data.insert_id+'\')">删除</a></p>';
			jQuery("#pic_list").prepend(img);
			
		}else
		{	alert(serverData);
			progress.setStatus("上传失败.");
			alert('上传图片失败，请重试！');
		}
	} catch (ex) {alert(serverData);
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		progress.setStatus("上传失败.");
		progress.toggleCancel(false);
		//alert('上传图片失败，请重试！或登录已过期！');
		alert(serverData);
		this.debug(ex);
	}
}