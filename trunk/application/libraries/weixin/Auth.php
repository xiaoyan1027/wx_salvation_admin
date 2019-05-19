<?php

/**
 * 微信授权代码
 *
 */
class Auth {
    private $_authorizer_appid;
    private $_component_appid;
    private $_component_appsecret;
    private $_component_token;
    private $encodingAesKey;
    private $_lib_redis;
    private $_wx_errlog;
    private $_lib_http;
    private $_api_host = 'https://api.weixin.qq.com/';
    private $_authorizer_refresh_token;
    private $_ci;
    private $_receive;

    public function __construct($config = array()) {
        $this->_ci = &get_instance();

        if ($this->_ci->config->load('weixin', TRUE, TRUE)) {
            $global_config = $this->_ci->config->item('weixin');
        }
        $this->_component_appid = isset($config['component_appid']) ? $config['component_appid'] : $global_config['options']['component_appid'];
        $this->_component_appsecret = isset($config['component_appsecret']) ? $config['component_appsecret'] : $global_config['options']['component_appsecret'];
        $this->_component_token = isset($config['component_token']) ? $config['component_token'] : $global_config['options']['component_token'];
        $this->encodingAesKey = isset($config['component_aeskey']) ? $config['component_aeskey'] : $global_config['options']['component_aeskey'];
        $this->_authorizer_appid = isset($config['authorizer_appid']) ? $config['authorizer_appid'] : '';
        $this->_lib_redis = $this->_ci->lib_redis;
        $this->_ci->load->model('wxerrlog_model');
        $this->_ci->load->model('service/component_authorize_model');
        $this->_ci->load->library('lib_http', array('host' => $this->_api_host), 'lib_http_auth');
        $this->_lib_http = $this->_ci->lib_http_auth;
        $this->_lib_http->ssl_verifypeer = 'CURL_SSLVERSION_TLSv1';
        $this->_ci->load->model('api/logger_model');
        $this->_logger_model = $this->_ci->logger_model;
    }

    /**
     * 获取第三方授权平台APPID
     */
    public function get_component_appid() {
        return $this->_component_appid;
    }

    /**
     * 跳转至微信页面，获取code
     */
    public function get_code($redirect_uri = '', $state = '', $scope = 'snsapi_base') {
        $redirect_uri = empty($redirect_uri) ? 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $redirect_uri;
        $redirect_uri = urlencode($redirect_uri);
        $oauth_jump_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->_authorizer_appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}&component_appid={$this->_component_appid}#wechat_redirect";
        header('Location: ' . $oauth_jump_url);
        exit;
    }

    /**
     * 调整至第三方授权页面，获取auth_code
     */
    public function get_auth_code($redirect_uri = '') {
        $auth_code = $this->_ci->input->get('auth_code');
        $expires_in = $this->_ci->input->get('expires_in');
        if ($auth_code && $expires_in) {
            return $auth_code;
        }
        $pre_auth_code = $this->get_pre_auth_code();
        $redirect_uri = empty($redirect_uri) ? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $redirect_uri;
        $redirect_uri = urlencode($redirect_uri);
        $oauth_jump_url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid={$this->_component_appid}&pre_auth_code={$pre_auth_code}&redirect_uri={$redirect_uri}&auth_type=2";
        header('Location: ' . $oauth_jump_url);
        exit;
    }

    /**
     * 使用授权码换取公众号的授权信息
     */
    public function get_auth_info($authorization_code = '') {
        //获取公众号的授权信息
        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }
        $authorization_code = $authorization_code ? $authorization_code : $this->get_auth_code();
        $url = "cgi-bin/component/api_query_auth?component_access_token={$component_access_token}";
        $params = array(
            'component_appid' => $this->_component_appid,
            'authorization_code' => $authorization_code
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            if (isset($res['errcode'])) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
                return false;
            }

            //缓存授权方access_token
            $cache_key = 'weixin_authorizer_access_token_' . $this->_component_appid . "_" . $res['authorization_info']['authorizer_appid'];
            $expire = $res['authorization_info']['expires_in'] - 600;
            $cache_data = array(
                'access_token' => $res['authorization_info']['authorizer_access_token'],
            );
            $cache_data = serialize($cache_data);
            $this->_lib_redis->set($cache_key, $cache_data);
            $this->_lib_redis->expire($cache_key, $expire);
            //存储到数据库
            $data = array(
                'component_appid' => $this->_component_appid,
                'authorizer_appid' => $res['authorization_info']['authorizer_appid'],
                'authorizer_access_token' => $res['authorization_info']['authorizer_access_token'],
                'authorizer_refresh_token' => $res['authorization_info']['authorizer_refresh_token'],
                'func_info' => json_encode($res['authorization_info']['func_info']),
                'expires_in' => time() + $res['authorization_info']['expires_in'] - 600
            );
            $this->_ci->component_authorize_model->add($data);

            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            //返回公众号的授权信息
            return $res;
        }
        return false;
    }

    /**
     * 使用授权码换取公众号的授权信息
     */
    public function get_authorizer_info($authorizer_appid) {
        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }
        $url = "cgi-bin/component/api_get_authorizer_info?component_access_token={$component_access_token}";
        $params = array(
            'component_appid' => $this->_component_appid,
            'authorizer_appid' => $authorizer_appid
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            if (isset($res['errcode'])) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);

                return false;
            }
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 获取授权方令牌
     */
    public function get_authorizer_access_token($authorizer_appid = '') {
        $authorizer_appid = $authorizer_appid ? $authorizer_appid : $this->_authorizer_appid;
        //缓存提取公众号的授权信息
        $cache_key = 'weixin_authorizer_access_token_' . $this->_component_appid . "_" . $authorizer_appid;
        $cache_value = $this->_lib_redis->get($cache_key);
        if ($cache_value) {
            $cache_value = unserialize($cache_value);
            return $cache_value['access_token'];
        }
        //数据库读取授权信息
        $authorize_info = $this->_ci->component_authorize_model->get_info(array('component_appid' => $this->_component_appid, 'authorizer_appid' => $authorizer_appid));
        if (empty($authorize_info)) {
            return false;
        }
        if ($authorize_info['expires_in'] > time()) {
            return $authorize_info['authorizer_access_token'];
        }

        //获取公众号的授权信息
        $component_access_token = $this->get_component_access_token();
        //dump($component_access_token);die;
        if (!$component_access_token) {
            return false;
        }
        $url = "cgi-bin/component/api_authorizer_token?component_access_token={$component_access_token}";

        $params = array(
            'component_appid' => $this->_component_appid,
            'authorizer_appid' => $authorizer_appid,
            'authorizer_refresh_token' => $authorize_info['authorizer_refresh_token']
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        //dump($res);die;
        if ($res) {
            if (isset($res['errcode'])) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
                return false;
            }

            //缓存授权方access_token
            $expire = $res['expires_in'] - 600;
            $cache_data = array(
                'access_token' => $res['authorizer_access_token'],
            );
            $cache_data = serialize($cache_data);
            $this->_lib_redis->set($cache_key, $cache_data);
            $this->_lib_redis->expire($cache_key, $expire);
            //存储到数据库
            $data = array(
                'component_appid' => $this->_component_appid,
                'authorizer_appid' => $authorizer_appid,
                'authorizer_access_token' => $res['authorizer_access_token'],
                'authorizer_refresh_token' => $res['authorizer_refresh_token'],
                'expires_in' => time() + $res['expires_in'] - 600
            );
            $this->_ci->component_authorize_model->add($data);
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            //返回公众号的授权信息
            return $res['authorizer_access_token'];
        }
        return false;
    }

    public function get_component_verify_ticket($component_appid) {
        $cache_ticket = 'weixin_component_verify_ticket_' . $component_appid;
        $component_verify_ticket = $this->_lib_redis->get($cache_ticket);
        if (false && !empty($component_verify_ticket)) {
            return $component_verify_ticket;
        }
        $data_component_ticket = Table_model::get_instance('weixin_component_ticket');
        $data = $data_component_ticket->fetch_row(array('appid' => $component_appid));
        if (!empty($data)) {
            return $data['ticket'];
        }
        return false;
    }

    /**
     * 获取第三方平台access_token
     * @return  string|boolean
     */
    public function get_component_access_token() {
        //缓存提取第三方平台access_token
        $cache_key = 'weixin_component_access_token_' . $this->_component_appid;
        $cache_value = $this->_lib_redis->get($cache_key);
        if ($cache_value) {
            $cache_value = unserialize($cache_value);
            return $cache_value['access_token'];
        }
        $this->_component_verify_ticket = $this->get_component_verify_ticket($this->_component_appid);
        if (empty($this->_component_verify_ticket)) {
            return false;
        }
        //获取第三方平台access_token
        $url = "cgi-bin/component/api_component_token";
        $params = array(
            'component_appid' => $this->_component_appid,
            'component_appsecret' => $this->_component_appsecret,
            'component_verify_ticket' => $this->_component_verify_ticket
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            if (isset($res['errcode'])) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);

                return false;
            }
            //缓存第三方平台access_token
            $expire = $res['expires_in'] ? $res['expires_in'] - 600 : 3600;
            $cache_data = array(
                'access_token' => $res['component_access_token']
            );
            $cache_data = serialize($cache_data);
            $this->_lib_redis->set($cache_key, $cache_data);
            $this->_lib_redis->expire($cache_key, $expire);
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            //返回第三方平台access_token
            return $res['component_access_token'];
        }
        return false;
    }

    /**
     * 获取第三方平台pre_auth_code
     * @return  string|boolean
     */
    public function get_pre_auth_code() {
        //缓存提取第三方平台pre_auth_code
        $cache_key = 'weixin_pre_auth_code_' . $this->_component_appid;
        $cache_value = $this->_lib_redis->get($cache_key);
        if ($cache_value) {
            $cache_value = unserialize($cache_value);
            if ($cache_value['expires_in'] > time()) {
                return $cache_value['pre_auth_code'];
            }
        }
        //获取第三方平台pre_auth_code
        $component_access_token = $this->get_component_access_token();
        $url = "cgi-bin/component/api_create_preauthcode?component_access_token={$component_access_token}";
        $params = array(
            'component_appid' => $this->_component_appid
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            if (isset($res['errcode'])) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);

                return false;
            }
            //缓存第三方平台pre_auth_code
            $expire = $res['expires_in'] ? $res['expires_in'] - 120 : 1200;
            $cache_data = array(
                'pre_auth_code' => $res['pre_auth_code'],
                'expires_in' => time() + $expire,
            );
            $cache_data = serialize($cache_data);
            $this->_lib_redis->set($cache_key, $cache_data);
            $this->_lib_redis->expire($cache_key, $expire);
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            //返回第三方平台pre_auth_code
            return $res['pre_auth_code'];
        }
        return false;
    }

    /**
     * 通过code获取Access Token
     * @return array {access_token,expires_in,refresh_token,openid,scope}
     */
    public function get_user_access_token($code) {
        if (!$code) return false;
        $component_access_token = $this->get_component_access_token();
        $url = "sns/oauth2/component/access_token?appid={$this->_authorizer_appid}&code={$code}&grant_type=authorization_code&component_appid={$this->_component_appid}&component_access_token={$component_access_token}";
        $res = $this->_lib_http->get($url);
        if ($res) {
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->_logger_model->fail($res, array(), 'GET', $this->_api_host . $url);

                return false;
            }
            $this->_logger_model->success($res, array(), 'GET', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 获取授权后的用户资料,拉取用户信息
     * @param string $access_token
     * @param string $openid
     * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege}
     */
    public function get_oauth_userinfo($access_token, $openid) {
        $url = 'sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res = $this->_lib_http->get($url);
        if ($res) {
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->_logger_model->fail($res, array(), 'GET', $this->_api_host . $url);
                return false;
            }
            $this->_logger_model->success($res, array(), 'GET', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 用户授权
     * @param string $show_oauth_page 是否显示授权页面，显示则能获取用户信息，不显示则获取open_id
     * @param string $redirect_uri 跳转地址
     */
    public function user_auth($show_oauth_page = false, $redirect_uri = '') {
        $code = lib_context::get('code', lib_context::T_STRING);
        $state = lib_context::get('state', lib_context::T_STRING);
        $appid = lib_context::get('appid', lib_context::T_STRING);
        $scope = $show_oauth_page ? 'snsapi_userinfo' : 'snsapi_base';
        if (!$code && !$state && !$appid) {
            $this->get_code($redirect_uri, 'leju_oauth', $scope);
        }
        if ($code && $state == 'leju_oauth') {
            //用户拒绝授权
            if ($code == 'authdeny') {
                return false;
            }
            //获取用户access_token
            $res = $this->get_user_access_token($code);
            if (!$res) {
                return false;
            }
            $this->open_id = $res['openid'];
            if ($show_oauth_page) {
                //获取用户信息
                $userinfo = $this->get_oauth_userinfo($res['access_token'], $this->open_id);
                if ($userinfo && !empty($userinfo['nickname'])) {
                    return $userinfo;
                } else {
                    return false;
                }
            } else {
                //获取用户信息
                $userinfo = $this->get_oauth_userinfo($res['access_token'], $this->open_id);
                if ($userinfo && !empty($userinfo['nickname'])) {
                    return $userinfo;
                } else {
                    return array('openid' => $this->open_id);
                }
            }
        }
        return false;
    }

    /**
     * 第三平台接收消息
     * @return string
     */
    public function get_message() {
        include_once(APPPATH . 'libraries/weixin/wxBizMsgCrypt.php');
        $signature = $this->_ci->input->get('msg_signature');
        $timestamp = $this->_ci->input->get('timestamp');
        $nonce = $this->_ci->input->get('nonce');
        $postdata = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : '';
        $pc = new WXBizMsgCrypt($this->_component_token, $this->encodingAesKey, $this->_component_appid);
        $errCode = $pc->decryptMsg($signature, $timestamp, $nonce, $postdata, $msg);
        if ($errCode == 0) {
            $this->_receive = (array)simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return $this->_receive;
    }

    /**
     * 获取小程序代码模板
     */
    public function get_code_template_list() {
        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }
        $url = "wxa/gettemplatelist?access_token={$component_access_token}";
        $res = $this->_lib_http->get($url);
        if ($res) {
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->_logger_model->fail($res, array(), 'GET', $this->_api_host . $url);

                return false;
            }
            $this->_logger_model->success($res, array(), 'GET', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 设置小程序服务器域名
     *  access_token      请使用第三方平台获取到的该小程序授权的authorizer_access_token
     * action      add添加, delete删除, set覆盖, get获取。当参数是get时不需要填四个域名字段
     * requestdomain      request合法域名，当action参数是get时不需要此字段
     * wsrequestdomain      socket合法域名，当action参数是get时不需要此字段
     * uploaddomain      uploadFile合法域名，当action参数是get时不需要此字段
     * downloaddomain      downloadFile合法域名，当action参数是get时不需要此字段
     */
    public function modify_domain($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/modify_domain?access_token={$authorizer_access_token}";
        $params = array(
            "action" => $data['action']
        );
        if (isset($data['requestdomain'])) {
            $params['requestdomain'] = $data['requestdomain'];
        }
        if (isset($data['wsrequestdomain'])) {
            $params['wsrequestdomain'] = $data['wsrequestdomain'];
        }
        if (isset($data['uploaddomain'])) {
            $params['uploaddomain'] = $data['uploaddomain'];
        }
        if (isset($data['downloaddomain'])) {
            $params['downloaddomain'] = $data['downloaddomain'];
        }
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            $params['authorizer_appid'] = $data['authorizer_appid'];
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);

                return false;
            }
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 设置小程序业务域名
     * access_token      请使用第三方平台获取到的该小程序授权的authorizer_access_token
     * action      add添加, delete删除, set覆盖, get获取。当参数是get时不需要填webviewdomain字段。如果没有action字段参数，则默认见开放平台第三方登记的小程序业务域名全部添加到授权的小程序中
     * webviewdomain      小程序业务域名，当action参数是get时不需要此字段
     */
    public function set_webview_domain($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/setwebviewdomain?access_token={$authorizer_access_token}";
        $params = array(
            "action" => $data['action'],
            "webviewdomain" => $data['webviewdomain'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            $params['authorizer_appid'] = $data['authorizer_appid'];
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
                return false;
            }
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 为授权的小程序帐号上传小程序代码
     */
    public function commit_code($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/commit?access_token={$authorizer_access_token}";
        $params = array(
            "template_id" => $data['template_id'],
            'ext_json' => json_encode($data['ext_json']),
            'user_version' => $data['user_version'],
            'user_desc' => $data['user_desc']
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            $params['authorizer_appid'] = $data['authorizer_appid'];
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);

                return false;
            }
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 获取体验小程序的体验二维码
     */
    public function get_qrcode($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }

        $qrcode_url = $this->_api_host . "wxa/get_qrcode?access_token={$authorizer_access_token}&path=" . urlencode($data['path']);
        return $qrcode_url;

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
        $echostr = $this->_ci->input->get('echostr');
        if (!empty($echostr)) {
            die($echostr);
        }

        return true;
    }

    /**
     * 验证
     * @return boolean
     */
    public function check_signature() {
        $signature = $this->_ci->input->get('signature');
        $timestamp = $this->_ci->input->get('timestamp');
        $nonce = $this->_ci->input->get('nonce');

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
     * 微信登录code 换取 session_key
     */
    public function jscode2session($data) {
        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }
        $url = "sns/component/jscode2session?appid={$data["authorizer_appid"]}&js_code={$data['js_code']}&grant_type=authorization_code&component_appid={$this->_component_appid}&component_access_token={$component_access_token}";
        $res = $this->_lib_http->get($url);
        if ($res) {
            if (isset($res['errcode'])) {
                $this->_logger_model->fail($res, $params, 'GET', $this->_api_host . $url);

                return false;
            }
            $this->_logger_model->success($res, $params, 'GET', $this->_api_host . $url);
            return $res;
        }
        return false;
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
        include_once(APPPATH . 'libraries/weixin/wxBizMsgCrypt.php');
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
     * 数据XML编码
     * @param mixed $data 数据
     * @return string
     */
    public static function data_to_xml($data) {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml .= "<$key>";
            $xml .= (is_array($val) || is_object($val)) ? self::data_to_xml($val) : self::xmlSafeStr($val);
            list($key,) = explode(' ', $key);
            $xml .= "</$key>";
        }
        return $xml;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id 数字索引子节点key转换的属性名
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
     * 发送客服消息
     */
    public function send_custom_message($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "cgi-bin/message/custom/send?access_token=" . $authorizer_access_token;
        $params = array(
            "touser" => $data['touser'],
            'msgtype' => $data['msgtype'],
            'text' => array('content' => $data['content']),
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if ($res) {
            $params['authorizer_appid'] = $data['authorizer_appid'];
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);

                return false;
            }
            $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
            return $res;
        }
        return false;
    }

    /**
     * 获取所有需要使用JS-SDK的页面配置信息
     * @author  mingxing
     */
    public function get_js_sign_package($authorizer_appid) {
        $jsapi_ticket = $this->get_js_api_ticket($authorizer_appid);
        $timestamp = time();
        $nonce_str = $this->create_nonce_str();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$nonce_str&timestamp=$timestamp&url=" . CUR_URL;
        $signature = sha1($string);
        $sign_package = array(
            "noncestr" => $nonce_str,
            "timestamp" => $timestamp,
            "signature" => $signature,
        );
        return $sign_package;
    }

    /**
     * 生成签名的随机串
     * @author    mingxing
     */
    public function create_nonce_str($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取jsapi_ticket
     * @author    mingxing
     */
    public function get_js_api_ticket($authorizer_appid) {
        $authorizer_access_token = $this->get_authorizer_access_token($authorizer_appid);
        if (!$authorizer_access_token) {
            return false;
        }
        $cache_key = 'weixin_jsapi_ticket_' . $authorizer_appid;
        $cache_value = $this->_lib_redis->get($cache_key);
        if ($cache_value) {
            $cache_value = unserialize($cache_value);
            return $cache_value['jsapi_ticket'];
        }
        $url = "cgi-bin/ticket/getticket?access_token=$authorizer_access_token&type=jsapi";
        $res = $this->_lib_http->get($url);
        if ($res) {
            if ($res['errcode'] != 0) {
                $this->_logger_model->fail($res, array('authorizer_appid' => $authorizer_appid), 'GET', $this->_api_host . $url);

                return false;
            }
            $expire = $res['expires_in'] ? $res['expires_in'] - 600 : 3600;
            $cache_data = array(
                'jsapi_ticket' => $res['ticket'],
            );
            $cache_data = serialize($cache_data);
            $this->_lib_redis->set($cache_key, $cache_data);
            $this->_lib_redis->expire($cache_key, $expire);
            $this->_logger_model->success($res, array('authorizer_appid' => $authorizer_appid), 'GET', $this->_api_host . $url);
            return $res['ticket'];
        }
        return false;
    }

    /**
     * 获取微信卡券api_ticket
     * @author mingxing
     */
    public function get_jscardticket($authorizer_appid) {
        if ($authorizer_appid == 'wxb7f63d61476ef1a3') {
            $authorizer_access_token = '8_Bh2_ia3tI-5g1Jymz4moLgL32KJnOObIXFa0E7toKiGIG2-ikslIwRZFlS82fMdbbZf4HEFHQueQzbqL6zRBl9h5ZvasljfBj-_2Z8yNyZYXskMpFTeTxmqjim6_SGJuyuMcaM5yJl0KEIAtICIaAAAZTZ';
        } else {
            $authorizer_access_token = $this->get_authorizer_access_token($authorizer_appid);
        }


        if (!$authorizer_access_token) {
            return false;
        }
        $cache_key = 'weixin_card_api_ticket_' . $authorizer_appid;
        $cache_value = $this->_lib_redis->get($cache_key);
        if ($cache_value) {
            $cache_value = unserialize($cache_value);
            return $cache_value['ticket'];
        }
        $url = "cgi-bin/ticket/getticket?access_token={$authorizer_access_token}&type=wx_card";
        $res = $this->_lib_http->get($url);
        if ($res) {
            if ($res['errcode'] != 0) {
                $this->_logger_model->fail($res, array('authorizer_appid' => $authorizer_appid), 'GET', $this->_api_host . $url);
                return false;
            }
            $expire = $res['expires_in'] ? $res['expires_in'] - 600 : 3600;
            $cache_data = array(
                'ticket' => $res['ticket'],
            );
            $cache_data = serialize($cache_data);
            $this->_lib_redis->set($cache_key, $cache_data);
            $this->_lib_redis->expire($cache_key, $expire);
            $this->_logger_model->success($res, array('authorizer_appid' => $authorizer_appid), 'GET', $this->_api_host . $url);
            return $res['ticket'];
        }
        return false;
    }


    /*****************************微信模板 starts******************************************/

    /**
     * 获取草稿箱列表
     *
     * @return bool
     */
    public function gettemplatedraftlist() {
        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }

        $url = "wxa/gettemplatedraftlist?access_token={$component_access_token}";
        //echo $url."<br/>";
        $res = $this->_lib_http->get($url);
        //dump($res);die;
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->_logger_model->fail($res, array(), 'get', $this->_api_host . $url);
            return false;
        }

        $this->_logger_model->success($res, array(), 'get', $this->_api_host . $url);
        return $res['draft_list'];
    }

    /**
     * 获取模板列表
     *
     * @return bool
     */
    public function gettemplatelist() {

        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }

        $url = "wxa/gettemplatelist?access_token={$component_access_token}";

        $res = $this->_lib_http->get($url);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->_logger_model->fail($res, array(), 'get', $this->_api_host . $url);
            return false;
        }
        $this->_logger_model->success($res, array(), 'get', $this->_api_host . $url);
        return $res['template_list'];
    }

    /**
     * 获取模板列表
     *
     * @return bool
     */
    public function addtotemplate($draft_id) {
        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }

        $url = "wxa/addtotemplate?access_token={$component_access_token}";
        $params = array('draft_id' => $draft_id);

        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
        }

        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res;
    }

    /**
     * 删除模板
     *
     * @return bool
     */
    public function deletetemplate($template_id) {
        $component_access_token = $this->get_component_access_token();
        if (!$component_access_token) {
            return false;
        }

        $url = "wxa/deletetemplate?access_token={$component_access_token}";
        $params = array('template_id' => $template_id);

        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
            return false;
        }

        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res;
    }

    /*******************************微信模板 end******************************************/


    /*************************管理员 starts******************************************/
    /**
     * 获取用户列表
     *
     * @return bool
     */
    public function memberauth($authorizer_appid) {
        $authorizer_access_token = $this->get_authorizer_access_token($authorizer_appid);
        if (!$authorizer_access_token) {
            return false;
        }

        $url = "wxa/memberauth?access_token={$authorizer_access_token}";
        $params = array(
            'action' => 'get_experiencer',
        );

        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
        }

        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res['members'];
    }

    /**
     * 绑定体验者
     *
     * @param $authorizer_appid
     * @param $wechatid
     * @return bool
     */
    public function bind_tester($authorizer_appid, $wechatid) {
        $authorizer_access_token = $this->get_authorizer_access_token($authorizer_appid);
        if (!$authorizer_access_token) {
            return false;
        }

        $url = "wxa/bind_tester?access_token={$authorizer_access_token}";
        $params = array(
            'wechatid' => $wechatid,
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
        }

        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res;
    }

    /**
     * 解除绑定
     *
     * @param $authorizer_appid
     * @param $wechatid
     * @return bool
     */
    public function unbind_tester($authorizer_appid, $wechatid) {
        $authorizer_access_token = $this->get_authorizer_access_token($authorizer_appid);
        if (!$authorizer_access_token) {
            return false;
        }

        $url = "wxa/unbind_tester?access_token={$authorizer_access_token}";
        $params = array(
            'wechatid' => $wechatid,
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
        }

        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res;
    }

    /*************************管理员 end*********************************************/

    /*************************审核 starts******************************************/
    /*
     * 获取小程序授权的可选类目
     * zhangxin11
     * */
    public function get_category($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/get_category?access_token={$authorizer_access_token}";
        $res = $this->_lib_http->get($url);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];
            //记录日志
            $this->_logger_model->fail($res, array(), 'get', $this->_api_host . $url);
            return false;
        }
        $this->_logger_model->success($res, array(), 'get', $this->_api_host . $url);
        return $res['category_list'];

    }

    /*
     * 获取小程序的第三方提交代码的页面配置
     * zhangxin11
     * */
    public function get_page($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }

        $url = "wxa/get_page?access_token={$authorizer_access_token}";
        $res = $this->_lib_http->get($url);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, array(), 'get', $this->_api_host . $url);
            return false;
        }
        $this->_logger_model->success($res, array(), 'get', $this->_api_host . $url);
        return $res['page_list'];
    }

    /*
     * 将第三方代码包提交审核
     * zhangxin11
     * */
    public function submit_audit($access_token, $data) {
        $authorizer_access_token = $this->get_authorizer_access_token($access_token['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/submit_audit?access_token={$authorizer_access_token}";
        $params = array(
            "item_list" => $data,
        );

        $res = $this->_lib_http->post($url, json_encode($params, JSON_UNESCAPED_UNICODE));

        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, $params, 'post', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'post', $this->_api_host . $url);
        return $res;

    }

    /*
     * 查询某个指定版本的审核状态
     * zhangxin11
     * */
    public function get_auditstatus($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/get_auditstatus?access_token={$authorizer_access_token}";
        $params = array(
            "auditid" => $data['auditid'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, $params, 'get', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'get', $this->_api_host . $url);
        return $res;
    }

    /*
     * 查询最新一次提交的审核状态
     * zhangxin11
     * */
    public function get_latest_auditstatus($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/get_latest_auditstatus?access_token={$authorizer_access_token}";
        $res = $this->_lib_http->get($url);

        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, $data, 'get', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $data, 'get', $this->_api_host . $url);
        return $res;
    }
    /*************************审核 end******************************************/
    /*************************发布 start******************************************/
    /*
     * 发布已通过审核的小程序
     * zhangxin11
     * */
    public function release($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/release?access_token={$authorizer_access_token}";
        $params = array();

        $res = $this->_lib_http->post($url, json_encode($params,JSON_FORCE_OBJECT));

        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, $params, 'post', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'post', $this->_api_host . $url);
        return $res;

    }

    /*
     * 撤回审核
     * */
    public function withdrawal_review($data) {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }
        $url = "wxa/undocodeaudit?access_token={$authorizer_access_token}";
        $res = $this->_lib_http->get($url);

        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, array(), 'get', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, array(), 'get', $this->_api_host . $url);
        return $res;


    }
    /*************************发布 end******************************************/

    /*************************获取数据概览 start******************************************/

    /*
     * 获取用户增减数据
     * */
    public function getweanalysisappiddailysummarytrend($data) {

        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "datacube/getweanalysisappiddailysummarytrend?access_token={$authorizer_access_token}";
        $params = array(
            "begin_date" => $data['begin_date'],
            "end_date" => $data['end_date'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, $params, 'post', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'post', $this->_api_host . $url);
        return $res;
    }

    /*
     * 访问数据
     * */
    public function getweanalysisappiddailyvisittrend($data)
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "datacube/getweanalysisappiddailyvisittrend?access_token={$authorizer_access_token}";
        $params = array(
            "begin_date" => $data['begin_date'],
            "end_date" => $data['end_date'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];

            //记录日志
            $this->_logger_model->fail($res, $params, 'post', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'post', $this->_api_host . $url);
        return $res;
    }



    /*************************获取数据概览 end******************************************/
    
    /**
     * 获取小程序码
     */ 
    public function getwxacodeurl($data)
    {
        $authorizer_access_token=$this->get_authorizer_access_token($data['authorizer_appid']);
        if(!$authorizer_access_token)
        {
            return false;
        }
		$url = $this->_api_host."wxa/getwxacode?access_token=".$authorizer_access_token;
        return $url;
    }

    /*
     * 创建开放平台
     * */
    public function create_a_platform($data = array())
    {
        $aa = $this->get_access_token($data);
        echo "<pre />";
        print_r($aa);die;


        $url = "cgi-bin/open/create?access_token={$authorizer_access_token}";
        $params = array(
            "appid" => $data['appid'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        return $res;
    }

    /*
     * 将公众号/小程序绑定到开放平台账号下
     * */
    public function binding_platform($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }

        $url = "cgi-bin/open/bind?access_token={$authorizer_access_token}";
        $params = array(
            "appid" => $data['appid'],
            "open_appid" => $data['open_appid'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        return $res;
    }

    /*
     * 获取公众号或小程序绑定的开放平台账号
     * */
    public function get_platform($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }

        $url = "cgi-bin/open/get?access_token={$authorizer_access_token}";
        $params = array(
            "appid" => $data['appid'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        return $res;
    }

    /*************************小程序模板设置 start******************************************/

    /*
     * 获取小程序模板库标题列表
     * */
    public function title_list($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }

        $url = "cgi-bin/wxopen/template/library/list?access_token={$authorizer_access_token}";
        $params = array(
            "offset" => $data['offset'],
            "count" => $data['count'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        return $res;
    }

    /*
     * 获取模板库某个模板标题下关键词库
     * */
    public function get_key_word($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }
        $url = "cgi-bin/wxopen/template/library/get?access_token={$authorizer_access_token}";
        //模板标题id 可通过接口获取，也可登录小程序后台查看
        $params = array(
            "id" => $data['id'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        return $res;
    }

    /*
     * 组合模板并添加至帐号下的个人模板库
     * */
    public function add_composite_template($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }
        $url = "cgi-bin/wxopen/template/add?access_token={$authorizer_access_token}";
        //模板标题id 可通过接口获取，也可登录小程序后台查看
        $params = array(
            "id" => $data['id'],
            "keyword_id_list" => $data['keyword_id_list'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));

        if(isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res;
    }

    /*
     * 获取帐号下已存在的模板列表
     * */
    public function existing_list($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }
        $url = "cgi-bin/wxopen/template/list?access_token={$authorizer_access_token}";
        //模板标题id 可通过接口获取，也可登录小程序后台查看
        $params = array(
            "offset" => $data['offset'],
            "count" => $data['count'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if($res['errcode']) {
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res;
    }

    /*
     * 删除帐号下的某个模板
     * */
    public function template_del($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);

        if (!$authorizer_access_token) {
            return false;
        }
        $url = "cgi-bin/wxopen/template/del?access_token={$authorizer_access_token}";
        //模板标题id 可通过接口获取，也可登录小程序后台查看
        $params = array(
            "template_id" => $data['template_id'],
        );
        $res = $this->_lib_http->post($url, json_encode($params));
        if(isset($res['errcode']) && $res['errcode'] != 0) {
            $this->errcode = $res['errcode'];
            $this->errmsg = $res['errmsg'];
            $this->_logger_model->fail($res, $params, 'POST', $this->_api_host . $url);
        }
        $this->_logger_model->success($res, $params, 'POST', $this->_api_host . $url);
        return $res;
    }

    /*
     * 发送消息给开发者
     * */
    public function send_message($data = array())
    {
        $authorizer_access_token = $this->get_authorizer_access_token($data['authorizer_appid']);
        if (!$authorizer_access_token) {
            return false;
        }
        $url = "cgi-bin/message/wxopen/template/send?access_token={$authorizer_access_token}";
        //模板标题id 可通过接口获取，也可登录小程序后台查看
        $params = array(
            "touser" => $data['touser'],//接受者openID
            "template_id" => $data['template_id'],//需要下发模板消息的id
            "form_id" => $data['form_id'],//表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id
            "data" => $data['data'],//模板内容，不填则下发空模板
        );
        if(isset($data['page']))
        {
            $params["page"] = $data['page'];//点击跳转页面，仅支持小程序内页面不填则无跳转
        }
        if(isset($data['color']))
        {
            $params['color'] = $data['color'];//模板内容字体的颜色，不填默认黑色 【废弃】
        }
        if(isset($data['emphasis_keyword']))
        {
            $params['emphasis_keyword'] = $data['emphasis_keyword'];//模板需要放大的关键词，不填则默认无放大
        }

        $res = $this->_lib_http->post($url, json_encode($params));

        if(isset($res['errcode']) && $res['errcode'] != 0)
            $this->_logger_model->fail($res, $params,'POST',$this->_api_host.$url);
        else
            $this->_logger_model->success($res, $params,'POST',$this->_api_host.$url);

        return $res;
    }
    /*************************小程序模板设置 end******************************************/




}

