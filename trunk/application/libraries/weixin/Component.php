<?php

/*
 * 第三方平台接口封装
 * @author liuyang7@leju.com
 * @version 2015-03-09
 */

class Component {

    private $_component_appid;
    private $_component_appsecret;
    private $_component_token;
    private $_component_ticket;
    private $lib_http;
    private $lib_redis;
    private $_receive;
    private $api_host = 'https://api.weixin.qq.com/';
    private $encodingAesKey;
    private $token;
    private $errcode;
    private $errmsg;
    private $_ci;
    public function __construct($configs = WX_COMPONENT_CONFIG) {
        $this->_ci = & get_instance();
        $this->_component_appid = trim($configs['appid']);
        $this->_component_appsecret = trim($configs['appsecret']);
        $this->_component_token = trim($configs['token']);
        $this->encodingAesKey = trim($configs['aeskey']);
        $this->_ci->load->library('lib_http',array('host'=>$this->api_host),'lib_http_component');
        $this->lib_http = $this->_ci->lib_http_component;
        $this->lib_http->ssl_verifypeer = 'CURL_SSLVERSION_TLSv1';
        
        $this->lib_redis = $this->_ci->lib_redis;
    }

    /**
     * 验证
     * @return boolean
     */
    public function check_signature() {
        $signature = lib_context::get('signature', lib_context::T_STRING);
        $timestamp = lib_context::get('timestamp', lib_context::T_STRING);
        $nonce = lib_context::get('nonce', lib_context::T_STRING);

        $tmpArr = array($this->_token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);

        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        }

        return false;
    }

    /**
     * 验证是否有效
     * @return boolean
     */
    public function valid() {
        $check_res = $this->check_signature();
        if (!$check_res) {
            die('no access');
        }

        //第一次验证接入
        $echostr = lib_context::get('echostr', lib_context::T_STRING);
        if (!empty($echostr)) {
            die($echostr);
        }

        return true;
    }

    /**
     * 获取第三方平台access_token
     * @return array
     * https://api.weixin.qq.com/cgi-bin/component/api_component_token
     */
    public function component_token($is_cache = true) {

        $cache_key = 'weixin_component_token_' . $this->_component_appid;
        //从缓存取
        $cache_value = $this->lib_redis->get($cache_key);
        if ($cache_value && $is_cache) {
            $cache_value = unserialize($cache_value);
            if ($cache_value['expires_in'] > time()) {
                return $cache_value['component_access_token'];
            }
        }

        //从接口取
        $data = array();
        $data['component_appid'] = $this->_component_appid;
        $data['component_appsecret'] = $this->_component_token;
        $data['component_verify_ticket'] = $this->_component_ticket;

        $jsondata = json_encode($data);

        $url = "cgi-bin/component/api_component_token";
        $res = $this->lib_http->post($url, $jsondata);

        if ($res) {

            //记录错误日志
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->error_log($url, $res);
                return false;
            }

            //重置缓存
            $expire = $res['expires_in'] ? $res['expires_in'] - 120 : 5400;

            $cache_data = array(
                'component_access_token' => $res['component_access_token'],
                'expires_in' => time() + $expire,
            );
            $cache_data = serialize($cache_data);

            $this->lib_redis->set($cache_key, $cache_data);
            $this->lib_redis->expire($cache_key, $expire);

            return $res['component_access_token'];
        }

        return false;
    }

    /**
     * 获取预授权码
     * @return string
     * https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=xxx
     */
    public function preauthcode($is_cache = true) {
        $cache_key = 'weixin_component_preauthcode_' . $this->_component_appid;
        //从缓存取
        $cache_value = $this->lib_redis->get($cache_key);
        if ($cache_value && $is_cache) {
            $cache_value = unserialize($cache_value);
            if ($cache_value['expires_in'] > time()) {
                return $cache_value['pre_auth_code'];
            }
        }

        //取token
        $component_access_token = $this->component_token();
        if (!$component_access_token) {
            return false;
        }

        //从接口取
        $data = array();
        $data['component_appid'] = $this->_component_appid;
        $jsondata = json_encode($data);

        $url = "cgi-bin/component/api_create_preauthcode?component_access_token =" . $component_access_token;
        $res = $this->lib_http->post($url, $jsondata);

        if ($res) {

            //记录错误日志
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->error_log($url, $res);
                return false;
            }

            //重置缓存
            $expire = $res['expires_in'] ? $res['expires_in'] - 60 : 500;

            $cache_data = array(
                'pre_auth_code' => $res['pre_auth_code'],
                'expires_in' => time() + $expire,
            );
            $cache_data = serialize($cache_data);

            $this->lib_redis->set($cache_key, $cache_data);
            $this->lib_redis->expire($cache_key, $expire);

            return $res['pre_auth_code'];
        }

        return false;
    }

    /**
     * 使用授权码换取公众号的授权信息
     * @return string
     * https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=xxxx
     */
    public function get_token($is_cache = true, $auth_code = '', $ret_auth = false) {
        $cache_key = 'weixin_component_query_auth_' . $this->_component_appid;

        //从缓存取
        $cache_value = $this->lib_redis->get($cache_key);
        if ($cache_value && $is_cache) {
            $cache_value = unserialize($cache_value);
            if ($cache_value['expires_in'] > time()) {
                if ($ret_auth) {
                    return $cache_value['data']; //授权方令牌
                } else {
                    return $cache_value['data']['authorizer_access_token']; //授权方令牌
                }
            }
        }

        //取token
        $component_access_token = $this->component_token();
        if (!$component_access_token) {
            return false;
        }

        //取授权code
        if ($auth_code) {
            $authorization_code = $auth_code;
        } else {
            $module_oauth = new module_oauth();
            $authorization_code = $module_oauth->get_auth_code();
            if (!$authorization_code) {
                return false;
            }
        }

        //接口请求
        $data = array();
        $data['component_appid'] = $this->_component_appid;
        $data['authorization_code'] = $authorization_code;
        $jsondata = json_encode($data);

        $url = "cgi-bin/component/api_query_auth?component_access_token =" . $component_access_token;
        $res = $this->lib_http->post($url, $jsondata);

        if ($res) {

            //记录错误日志
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->error_log($url, $res);
            }

            $expire = $res['expires_in'] ? $res['expires_in'] - 120 : 5400;

            $cache_data = array(
                'data' => $res,
                'expires_in' => time() + $expire,
            );

            $cache_data = serialize($cache_data);

            $this->lib_redis->set($cache_key, $cache_data);
            $this->lib_redis->expire($cache_key, $expire);

            if ($ret_auth) {
                return $res;
            } else {
                return $res['authorizer_access_token'];
            }
        }

        return false;
    }

    /*
     * 获取（刷新）授权公众号的令牌
     * https:// api.weixin.qq.com /cgi-bin/component/api_authorizer_token?component_access_token=xxxxx
     */

    public function authorizer_token() {
        
    }

    /*
     * 获取授权方的账户信息
     * https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=xxxx
     */

    public function authorizer_info() {
        
    }

    /*
     * 获取授权方的选项设置信息
     * https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option?component_access_token=xxxx
     */

    public function get_authorizer_option() {
        
    }

    /*
     * 设置授权方的选项信息
     * https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option?component_access_token=xxxx
     */

    public function set_authorizer_option() {
        
    }

    /**
     * 第三平台接收消息
     * @return string
     */
    public function get_message() {
        include_once(APPPATH.'libraries/weixin/wxBizMsgCrypt.php');
        $signature = $this->_ci->input->get('msg_signature');
        $timestamp = $this->_ci->input->get('timestamp');
        $nonce = $this->_ci->input->get('nonce');
        $postdata = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : '';
        $pc = new WXBizMsgCrypt($this->_component_token, $this->encodingAesKey, $this->_component_appid);
        $errCode = $pc->decryptMsg($signature, $timestamp, $nonce, $postdata, $msg);
        if ($errCode == 0) {
            $this->_receive = (array) simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return $this->_receive;
    }

    /**
     * 返回发送方
     * @return boolean
     */
    public function get_rev_form() {
        return isset($this->_receive['FromUserName']) ? $this->_receive['FromUserName'] : false;
    }

    /**
     * 返回接收方
     * @return boolean
     */
    public function get_rev_to() {
        return isset($this->_receive['ToUserName']) ? $this->_receive['ToUserName'] : false;
    }

    /**
     * 设置回复消息
     * @param type $string
     * @return string
     */
    public function reply_text($text = '') {
        $msg = array(
            'ToUserName' => $this->get_rev_form(),
            'FromUserName' => $this->get_rev_to(),
            'MsgType' => 'text',
            'Content' => $text,
            'CreateTime' => time(),
        );
        return $this->reply($msg);
    }

    /**
     * 设置回复图文消息
     * @param array $newsData
     * @return string
     */
    public function reply_news($newsData = array()) {
        $count = count($newsData);

        $msg = array(
            'ToUserName' => $this->get_rev_form(),
            'FromUserName' => $this->get_rev_to(),
            'MsgType' => 'news',
            'CreateTime' => time(),
            'ArticleCount' => $count,
            'Articles' => $newsData,
        );
        return $this->reply($msg);
    }

    /**
     * 回复消息
     * @param array $msg
     * @param boolean $return
     * @return type
     */
    public function reply($msg = array(), $return = false) {
        include_once(APPPATH.'libraries/weixin/wxBizMsgCrypt.php');
        $pc = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->_component_appid);
        $timestamp = time();
        $nonce = mt_rand(10000, 99999);

        $xmldata = $this->xml_encode($msg);

        $errCode = $pc->encryptMsg($xmldata, $timestamp, $nonce, $encryptMsg);
        if ($return)
            if ($errCode == 0) {
                return $encryptMsg;
            } else {
                return '';
            } else
            echo $encryptMsg;
    }

    /**
     * 创建菜单
     * @param array $data
     * @return boolean
     */
    public function create_menu($data) {
        $authorizer_access_token = $this->get_token();
        if (!$authorizer_access_token)
            return false;

        //https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN
        $url = 'cgi-bin/menu/create?authorizer_access_token=' . $authorizer_access_token;
        $data = json_encode($data);
        $res = $this->lib_http->post($url, $data);
        //记录错误日志
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $errdata = serialize($res);
            $this->wx_errlog->add($url, $res['errcode'], $errdata, $this->_appid);
        }

        if ($res) {
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                if ($res['errcode'] == '42001') {
                    //清除token缓存
                    $this->get_token(false);
                }
                $this->errcode = $res['errcode'];
                $this->errmsg = $res['errmsg'];
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * 获取菜单
     * @return boolean|Ambigous <mixed, string>
     */
    public function get_menu() {
        $authorizer_access_token = $this->get_token();
        if (!$authorizer_access_token)
            return false;

        $url = 'cgi-bin/menu/get?authorizer_access_token=' . $authorizer_access_token;

        $res = $this->lib_http->get($url);
        
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->error_log($url, $res);
        }
        
        return $res;
    }

    /**
     * 发送客服消息
     *      
     */
    public function send_customer_msg($data, $auth_code) {
        $jsondata = json_encode($data);

        $access_token = $this->get_token(false, $auth_code);

        $url = "cgi-bin/message/custom/send?authorizer_access_token={$access_token}";
        if (!$access_token) {
            return false;
        }
        $res = $this->lib_http->post($url, $jsondata);
        //记录错误日志
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->error_log($url, $res);
        }
        
        return $res;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    public function xml_encode($data, $root = 'xml', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8') {
        if (is_array($attr)) {
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml = "<{$root}{$attr}>";
        $xml .= self::data_to_xml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
    }

    /**
     * 记录错误日志
     */
    private function error_log($url, $res) {
        $this->_ci->load->model('wxerrlog_model');

        $errcode = $res['errcode'];

        //记录日志
        $errdata = serialize($res);
        $this->wxerrlog_model->add($url, $errcode, $errdata, $this->_component_appid);
    }

}
