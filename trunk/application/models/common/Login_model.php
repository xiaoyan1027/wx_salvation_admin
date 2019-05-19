<?php

define('PUBLIC_LOGIN_KEY', 'leju_edata_0o9i8u7y');

class Login_model extends BASE_Model {
    private $admin_user_model;
    private $_login_fail_cache_key = 'login_fail_keys_';
    private $_login_cookie_key = 'pet_login_fail';
    private $_login_num = 5;

    private $CI;

    /**
     * 管理员id
     * @var
     */
    private $_admin_id = '';

    public function __construct() {
        parent::__construct();
        $this->load->model('admin/admin_user_model');
        $this->CI =& get_instance();
    }

    /**
     * 用户登录处理
     * @param array $data
     * @return boolean|Ambigous <multitype:, multitype:mixed string >
     */
    public function login($data) {
        $data['passwd'] = md5($data['passwd']);

        $this->set_table('admin_users');
        $list = $this->fetch_row($data,'id,user_name,real_name,mobile');

        if(!empty($list)) {
            $this->set_login_success_cookie($list);
            $this->load->model('common/right_model');
            $this->right_model->delete_user_cache($list['id']);
            return $this->_formatreturndata(true);
        }

        return $this->_formatreturndata(false, '登录失败，密码有误');
    }

    /**
     * 退出登录
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function logout() {
        $this->load->model('common/right_model');
        $admin_uid = $this->input->cookie('admin_uid');
        $this->right_model->delete_user_cache($admin_uid);
        setcookie('pet_admin_uname', '', time() - 3600, '/');
        setcookie('pet_admin_uid', '', time() - 3600, '/');
        setcookie('pet_admin_realname', '', time() - 3600, '/');
        setcookie('pet_admin_sigcode', '', time() - 3600, '/');
        setcookie('pet_admin_groupid', '', time() - 3600, '/');
        
        return $this->_formatreturndata(true);
    }

    /**
     * post传递cookie进行ajax操作
     *
     * @example $validation_route = array(
     *              'site' => array(
     *                  'ctl' => array('act' => ''),
     *              ),
     *          )
     */
    public function set_post_cookie() {
        $validation_route = array();
        $cookies = $this->input->post('cookie');
        if ($this->input->post() && !empty($cookies)) {
            if (isset($validation_route[lib_router::ret_site()][lib_router::ret_controller()][lib_router::ret_action()])) {

                $cookies_arr = explode(';', $cookies);
                $cookie_keys = array('pet_admin_uname', 'pet_admin_uid', 'pet_admin_realname', 'pet_admin_sigcode');
                foreach ($cookies_arr as $value) {
                    $cookie_item = explode('=', $value);
                    if (in_array($cookie_item[0], $cookie_keys)) {
                        $_COOKIE[$cookie_item[0]] = $cookie_item[1];
                    }
                }
            }
        }
    }

    /**
     * 验证是否登录
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function is_login() {
        //post传递cookie进行ajax操作
        $this->set_post_cookie();
        $admin_sigcode = '';
        if (!empty($_COOKIE['pet_admin_sigcode'])) $admin_sigcode = $_COOKIE['pet_admin_sigcode'];

        if ($admin_sigcode) {
            $admin_uid = $this->get_admin_id();
            $admin_uname = $_COOKIE['pet_admin_uname'];
            $sigcode = $this->_get_sigcode($admin_uid, $admin_uname);
            if ($admin_sigcode == $sigcode) {
                $info = array('pet_admin_uid'=> $admin_uid,'pet_admin_uname'=>$admin_uname);
                return $this->_formatreturndata(true, $info);
            }
        }
        return $this->_formatreturndata(false);
    }

    /**
     * 返回登录sig code
     * @param int $user_id
     * @param string $user_name
     */
    private function _get_sigcode($user_id, $user_name) {
        return md5($user_id . $user_name . SIGN_KEY);
    }

    /**
     * 获取登录允许的次数
     * @return Ambigous <number, multitype:number , boolean, string>
     */
    public function get_login_num($username) {
        $cache_key = md5($this->_login_fail_cache_key . $username);
        $cache_time = 1800;
        $cache_value = $this->lib_redis->get($cache_key);
        if ($cache_value) {
            $cache_value = unserialize($cache_value);
            $cache_value['use_num'] = $this->_login_num - $cache_value['fail_num'];
        } else {
            $cache_value = array('fail_num' => 0, 'use_num' => $this->_login_num);
        }

        return $cache_value;
    }




    /**
     * get_admin_id
     * 获取用户ID
     *
     * @author:xionghui2@leju.com
     * @return bool
     */
    public function get_admin_id() {
        if ($this->_admin_id) {
            return $this->_admin_id;
        }

        $sign_code = $this->input->cookie('pet_admin_sigcode');
        $admin_uid =  $this->input->cookie('pet_admin_uid');
        $user_name = $this->input->cookie('pet_admin_uname');
        $true_sig_code = $this->_get_sigcode($admin_uid, $user_name);

        if ($sign_code == $true_sig_code) {
            $this->_admin_id = $admin_uid;
            return $admin_uid;
        } else {
            return false;
        }
    }

    /**
     * 设置用户登录成功需要设置的cookie信息
     * @author:xionghui2@leju.com
     *
     * @param $user_info_res
     */
    public function set_login_success_cookie($user_info_res) {
        $sigcode = $this->_get_sigcode($user_info_res['id'], $user_info_res['user_name']);
        setcookie('pet_admin_uname', $user_info_res['user_name'], time() + 10800, '/');
        setcookie('pet_admin_uid', $user_info_res['id'], time() + 10800, '/');
        setcookie('pet_admin_realname', $user_info_res['real_name'], time() + 10800, '/');
        setcookie('pet_admin_sigcode', $sigcode, time() + 10800, '/');
    }

}
