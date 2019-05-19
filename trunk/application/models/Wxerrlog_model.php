<?php
class Wxerrlog_model extends BASE_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->set_table('weixin_errorlog');
    }
    /**
     * 添加错误日志
     * @param string $api
     * @param string $errcode
     * @param string $errdata
     * @return boolean
     */
    public function add($api,$errcode,$errdata,$appid='')
    {   
        if(empty($api) || empty($errcode) || empty($errdata)) return false;  
        $data = array(
                    'api' => $api,
                    'errcode' => $errcode,
                    'errinfo' => $errdata,
                    'wx_appid' => $appid,
                    'addtime' => time(),
                );   
        $res = $this->insert($data);
    }
    
}