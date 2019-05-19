/**
 * 后台图片库公共JS模块
 * @example
 * <img id="ppp" /><input type="text" id="ccc" /><button data-max-size="100" data-allow-wh="344,344" data-preview="#ppp,#ccc" data-parent=".dddd"  onclick="picturePool.show(this)">显示图库</button>
*/
var picturePool = (function(){
    var previewTarget;
    var jqParentTarget;
    var picPanel;
    var maxSize;            //图片大小
    var allowWH;            //严格允许的宽高
    var maxWH;              //最大的宽高(0为不限制)
    var lastSelectFun;      //上一次的自定义函数
    var selectType;         //图片选择类型
    var selectSuccessHandler;//选择成功回调函数
    //初始化
    function _init()
    {
        $(document).ready(function(){
            picPanel=$('<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
              '<div class="modal-dialog modal-lg">'+
                '<div class="modal-content">'+
                  '<div class="modal-header">'+
                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'+
                    '<h4 class="modal-title" id="myModalLabel">请选择图片</h4>'+
                  '</div>'+
                  '<div class="modal-body zdy_content" style="height:600px">'+
            			'<iframe id="pic_iframe" frameborder="0" width="100%" height="100%" src=""></iframe>'+
                  '</div>'+
                '</div>'+
            '</div>'+
            '</div>');
            $("body").append(picPanel);
        });
    }
    //展示图片库
    function _show(obj)
    {
        jqParentTarget=document;
        previewTarget=$(obj).data('preview');//预览对象
        maxSize=$(obj).data('max-size');//最大尺寸
        allowWH=$(obj).data('allow-wh');//允许尺寸
        maxWH=$(obj).data('max-wh');//最大尺寸
        selectSuccessHandler=$(obj).data('select-success-handler');
        var parentTarget=$(obj).data('parent');//父对象选择器名称
        var selectFun=$(obj).data('select-fun');//自定义选择的函数名
        selectFun=selectFun?selectFun:"select";
        if(selectFun!=lastSelectFun)
        {
            $("iframe",picPanel).attr('src','?site=activity&ctl=album&act=select_pic&select_fun='+selectFun);
            lastSelectFun=selectFun;
        }
        if(parentTarget)
        {
            jqParentTarget=$(obj).parents(parentTarget);
            jqParentTarget=jqParentTarget.length?jqParentTarget:document;
        }
        if(maxSize){
            maxSize=parseInt(maxSize)*1024;
        }
        picPanel.modal();
        this.target = obj;
    }
    //选择图片
    function _select(pic_url,thumb_url,expand)
    {
        //图片大小限制
        if(maxSize&&maxSize<expand.size)
        {
            lejuDialog.alert({message:"已超过允许的图片大小("+(maxSize/1024)+"KB)"});
            return false;
        }
        //图片尺寸严格限制
        if(allowWH)
        {
            var wh=expand.width+","+expand.height;
            if(allowWH!=wh)
            {
                lejuDialog.alert({message:"不符合允许的图片尺寸("+allowWH.replace(",","×")+")"});
                return false;
            }
        }
        //图片最大尺寸限制
        if(maxWH)
        {
            var wh=maxWH.split(",");
            var w=parseInt(wh[0]);
            var h=parseInt(wh[1]);
            if((w!=0 && expand.width>w) || (h!=0 && expand.height>h))
            {
                lejuDialog.alert({message:"宽高超过了图片尺寸("+maxWH.replace(",","×")+")"});
                return false;
            }
        }
        //预览
        if(previewTarget)
        {
            var target=previewTarget.split(",");
            for(var i=0;i<target.length;i++)
            {
                //赋值目标对象、当前仅支持img,input标签
                var jq=$(target[i],jqParentTarget);
                if(jq.is('img'))
                {
                    jq.parents(".j_imgset").show();
                    jq.attr('src',pic_url);
                }
                else if(jq.is('input'))
                {
                    jq.val(pic_url);
                }
            }
        }
        _hide();
        if(selectSuccessHandler)
        {
            eval(selectSuccessHandler+"(pic_url,thumb_url,expand)");
        }
    }
    //隐藏图片库
    function _hide()
    {
        picPanel.modal('hide');
    }
    //删除图片
    function _del(obj)
    {
        if($(obj).data('preview'))
        {
            previewTarget=$(obj).data('preview');
        }
        var parentTarget=$(obj).data('parent');
        if(parentTarget)
        {
            jqParentTarget=$(obj).parents(parentTarget);
            jqParentTarget=jqParentTarget.length?jqParentTarget:document;
        }
        if(previewTarget)
        {
            var target=previewTarget.split(",");
            for(var i=0;i<target.length;i++)
            {
                //赋值目标对象、当前仅支持img,input标签
                var jq=$(target[i],jqParentTarget);
                if(jq.is('img'))
                {
                    jq.parents(".j_imgset").hide();
                    jq.attr('src','');
                }
                else if(jq.is('input'))
                {
                    jq.val('');
                }
            }
        }
    }
    //执行初始化
    _init();
    return {
        show:_show,
        hide:_hide,
        select:_select,
        del:_del
    };
})();
if (typeof define === "function" && define.cmd) {
  define(function() {
		return picturePool;
  });
}