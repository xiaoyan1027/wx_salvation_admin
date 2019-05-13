<?php

class Index extends BASE_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('common/right_model');
        $this->load->model('admin/admin_user_model');
        $this->load->model('admin/admin_compose_model');
    }

    public function index() {
            
        $result = $this->right_model->get_user_rights();

        if ($result['result'] == $this->right_model->res_succ) {
            $this->_user_rights = $result['info'];
        }

        $compose_id = $this->input->get('compose_id');

        $user_compose_list = $this->right_model->get_user_tree($this->_user_rights);

        if ($user_compose_list) {
            $this->assign('user_compose_list', $user_compose_list);
            if ($compose_id && isset($user_compose_list[$compose_id])) {
                $left_menu = $user_compose_list[$compose_id]['child_info'];
                $current_site = $user_compose_list[$compose_id]['en_name'];
            } else {
                //默认展开第一个组件
                $first_compose = array_shift($user_compose_list);
                $compose_id = $first_compose['id'];
                $left_menu = $first_compose['child_info'];
                $current_site = $first_compose['en_name'];
            }
//            var_dump($left_menu);
            $this->assign('compose_id', $compose_id);
            $this->assign('current_site', $current_site);
            $this->assign('menu_list', $left_menu);
        }
        $user_id = $_COOKIE['pet_admin_uid'];
        $this->assign('user_id', $user_id);
        $user_info = $this->admin_user_model->get_user_info($user_id);
        $this->assign('user_info', $user_info);


        foreach ($left_menu as $key => $vale) {
            $controller = $vale['func_name'];
            foreach ($vale['child_info'] as $k => $v) {
                $method = $v['func_name'];
                continue;
            }
            break;
        }
        redirect('/' . $current_site . '/' . $controller . '/' . $method);
    }

}