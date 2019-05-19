<?php

class Login extends BASE_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('common/login_model');
        $this->load->library('lib_verifycode');
        $this->load->library('lib_weakpassword');
        $this->load->library('lib_validate');
    }

    /**
     * 登录页面
     */
    public function index() {

        if ($this->user_info) {
            redirect('/');
        }
        
        if ($this->input->post()) {
            //验证验证码
            $code = $this->input->post('code');
           if ($code == '') {
               $this->show_message('验证码不能为空！', '/manager/login/index');
           }


           $check_res = $this->lib_verifycode->check_code($code);
           if (!$check_res) {
               $this->show_message('验证码错误，请正确输入验证码！', '/manager/login/index');
           }

            $data = array(
                'user_name' => trim($this->input->post('user_name')),
                'passwd' => trim($this->input->post('passwd')),
            );


            if (empty($data['user_name'])) {
                $this->show_message('登录失败，用户名不能为空！', '/manager/login/index');
            }
            if (empty($data['passwd'])) {
                $this->show_message('登录失败，密码不能为空！', '/manager/login/index');
            }

            if ($data['user_name'] == $data['passwd'])//帐号密码一样直接不让登录
            {
                $this->show_message('登录失败，帐号密码不能相同！请与管理员联系！', '/manager/login/index');
            }
            if (in_array($data['passwd'], lib_weakpassword::get_instance()->get_weak_password()))//弱密码直接不让登录
            {
                $this->show_message('登录失败，密码太弱！请与管理员联系！', '/manager/login/index');
            }

            $res = $this->login_model->login($data);
    
            if ($res['result'] == $this->_succ) {

                redirect('/');
            } else {

                if ($res['reason']) {
                    $this->show_message($res['reason'], '/manager/login/index');
                }

                $this->show_message('登录失败，用户名或密码错误！', '/manager/login/index');
            }
        }

        $this->display('login');
    }
    
    /**
     * 退出登录
     */
    public function logout() {
        $res = $this->login_model->logout();
        if ($res['result'] == $this->_succ) {
            $this->show_message('退出登录成功！', '/manager/login/index');
        }
    }

    /**
     * 验证码
     */
    public function verifycode() {
        $this->lib_verifycode->get_code();
    }
}
