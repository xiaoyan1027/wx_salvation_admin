/**
 * 后台 选择企业
 * @example
*/
var orgPool = (function(){
    var target;
    var jqParentTarget;
    var housePanel;
    var lastSelectFun;      //上一次的自定义函数
    var selectData = [];
    //初始化
    function _init()
    {
        $(document).ready(function(){
            housePanel=$('<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
              '<div class="modal-dialog modal-lg">'+
                '<div class="modal-content">'+
                  '<div class="modal-header">'+
                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'+
                    '<h4 class="modal-title" id="myModalLabel">请选择</h4>'+
                  '</div>'+
                  '<div class="modal-body zdy_content" style="height:600px">'+
            			'<iframe id="house_iframe" name="house_iframe" frameborder="0" width="100%" height="100%" src=""></iframe>'+
                  '</div>'+
                  '<div class="modal-footer">'+
                    '<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>'+
                    '<button type="button" class="btn btn-primary">确定</button>'+
                  '</div>'+
                '</div>'+
            '</div>'+
            '</div>');
            $("body").append(housePanel);
            $(".btn-primary",housePanel).click(function(){
                orgPool.complete(selectData);
            });
        });
    }
    //展示
    function _show(obj)
    {
        this.target = obj;
        jqParentTarget=document;
        var parentTarget=$(obj).data('parent');//父对象选择器名称
        var selectFun=$(obj).data('select-fun');//自定义选择的函数名
        var url = $(obj).attr('data-url');
        selectFun=selectFun?selectFun:"select";
        if(selectFun!=lastSelectFun)
        {
            lastSelectFun=selectFun;
        }
        $("iframe",housePanel).attr('src',url+'&select_fun='+selectFun+"&timestamp="+new Date().getTime());
        if(parentTarget)
        {
            jqParentTarget=$(obj).parents(parentTarget);
            jqParentTarget=jqParentTarget.length?jqParentTarget:document;
        }
        housePanel.modal();
        
    }
    //完成
    function _complete()
    {
        _hide();
    }
    //选择
    function _select(value)
    {   
        if($.inArray(value, selectData) == -1)
        {
            selectData.push(value); 
        }
        console.log(selectData);                   
    }
    //取消选择
    function _cancel(value)
    {
        var pos = $.inArray(value, selectData);
        if(pos != -1)
        {
           selectData.splice(pos, 1);
        }
        console.log(selectData);
    }
    //隐藏
    function _hide()
    {
        housePanel.modal('hide');
    }
    //获取选择的数据
    function _get_select_data()
    {
        return selectData;
    }
    //清空选择数据
    function _clear_select_data()
    {
        selectData = [];
    }
    //执行初始化
    _init();
    return {
        show:_show,
        hide:_hide,
        select:_select,
        cancel:_cancel,
        complete:_complete,
        get_select_data:_get_select_data,
        clear_select_data:_clear_select_data
    };
})();
if (typeof define === "function" && define.cmd) {
  define(function() {
		return orgPool;
  });
}
