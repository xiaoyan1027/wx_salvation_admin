<?php

class Right_model extends BASE_Model {

    private $_menus_data_cache_key = '';


    //角色表
    public $res_succ;
    public $res_fail;

    public $CI;

    public $redis;

    public function __construct() {
        parent::__construct();
        $this->res_succ = $this->_succ;
        $this->res_fail = $this->_fail;

        $this->load->model('admin/admin_user_model');
        $this->load->model('admin/admin_compose_model');
        $this->load->model('admin/admin_controller_model');
        $this->load->model('admin/admin_role_model');
        $this->load->model('common/login_model');
        $this->_menus_data_cache_key = get_cache_key('memus_data_cache');
        $this->CI = & get_instance();
    }

    /**
     * 路由权限限制
     */
    public function router_rights() {

        $filter_route = array(
            'manager/login/index',
            'manager/login/logout',
            'manager/login/verifycode',
        );
        $path = $this->router->directory . $this->router->class . "/" . $this->router->method;

        if(in_array($path, $filter_route)) {
            return true;
        }


        //验证是否登录
        $is_login_res = $this->CI->login_model->is_login();

        if($is_login_res['result'] != 'succ') {
            redirect('/manager/login/index');
        }

        //验证权限
        $check_rights_res = $this->check_rights($is_login_res['info']);

        if($check_rights_res['result'] != 'succ')
        {
            //$this->show_message('抱歉，您还没有操作权限，请联系管理员先给您分配权限','?site=manager&ctl=index&act=main');
            if($check_rights_res['reason'] != "没有操作权限，请与管理员联系！")
            {
                return $this->_formatreturndata(false,$check_rights_res['reason']);
            }
            return $this->_formatreturndata(false);
        }  else {
            return $this->_formatreturndata(true);
        }
    }



    
    /**
     * 获取当前登录人的角色权限
     * @return type
     */
    public function get_user_rights() {
        $user_name = $this->input->cookie('pet_admin_uname');
        $admin_uid = $this->input->cookie('pet_admin_uid');

        // $cache_key = get_cache_key('user_right_cache',array($admin_uid));
        // $cache_val = $this->lib_redis->get($cache_key);
        // if(!empty($cache_val))
        // {
        //     return $this->_formatreturndata(true, unserialize($cache_val));;
        // }

        // 通过用户姓名拿到权限列表
        $this->load->model('admin_user_model');
        $api_res = $this->admin_user_model->get_user_right($user_name);

        if(!empty($api_res))
        {
            $user_auth = $api_res;
        }
        else
        {
            return $this->_formatreturndata(false, '权限获取失败!');
        }

        
        if (empty($user_auth['right_list']))
            return $this->_formatreturndata(false, '当前登录用户暂无任何权限!');

        $method_ids = array();

        if(isset($user_auth['right_list'][0]) && is_array($user_auth['right_list'][0]))
        {
            foreach($user_auth['right_list'][0] as $k=>$v)
            {
                $compose_info = $this->admin_compose_model->fetch_row(array('en_name'=>$k,'domain'=>'admin'),'id');

                if(empty($compose_info)) continue;
                foreach($v as $kk=>$vv)
                {
                    $controller_info = $this->admin_controller_model->fetch_row(array('compose_id' => $compose_info['id'],'func_name' => $kk),'id');
                    if(empty($controller_info)) continue;
                    foreach($vv as $kkk=>$vvv)
                    {
                        $method_info = $this->admin_controller_model->fetch_row(array('controller_id' => $controller_info['id'],'func_name' => $kkk),'id');
                        if(empty($method_info)) continue;
                        $method_ids[] = $method_info['id'];
                    }
                }
            }
        }


        //获取所有非权限的功能
        $no_rights_method_ids = array();
        $no_rights_method_ids_res = $this->get_all_no_rights();
        if ($no_rights_method_ids_res['result'] == $this->_succ) {
            $no_rights_method_ids = $no_rights_method_ids_res['info'];
        }
        $method_ids = array_merge($method_ids, $no_rights_method_ids);

        $cache_data = array(
            'is_super' => $user_auth['is_super'],
            'method_ids' => $method_ids,
        );

        //$this->lib_redis->set($cache_key, serialize($cache_data));

        return $this->_formatreturndata(true, $cache_data);
    }

    /**
     * 所有非权限的功能
     */
    public function get_all_no_rights() {
        $all_not_rights_method = $this->CI->admin_controller_model->fetch_all_no_rights_method();

        if (!$all_not_rights_method) {
            return $this->_formatreturndata(false);
        } else {
            $all_not_rights_ids = array();
            foreach ($all_not_rights_method as $method) {
                $all_not_rights_ids[] = $method['id'];
            }
            return $this->_formatreturndata(true, $all_not_rights_ids);
        }
    }

    /**
     * 检查权限
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function check_rights() {
        //组件
        $directory = str_replace('/', '', $this->router->directory);

        $compose_res = $this->CI->admin_compose_model->fetch_row(array('en_name' => $directory, 'domain' => 'admin'));
        if (!$compose_res) {
            return $this->_formatreturndata(false, '组件不存在！');
        }


        //控制器
        $controller = $this->router->class;
        $controller_condition = array('func_name' => $controller, 'compose_id' => $compose_res['id']);
        $controller_res = $this->CI->admin_controller_model->fetch_row($controller_condition);
        if (!$controller_res) {
            return $this->_formatreturndata(false, '控制器不存在！');
        }

        //method
        $method = $this->router->method;

        $method_res = $this->CI->admin_controller_model->fetch_controller_by_func_name($method, $controller_res['id']);
        if (!$method_res) {
            return $this->_formatreturndata(false, '方法不存在！');
        }

        /**
         * [$user_rights_res description]
         * @var array
         */
        $user_rights_res = array();
        $user_rights_res = $this->get_user_rights();

        if($user_rights_res['result'] == $this->_fail)
        {
            return $this->_formatreturndata(false, $user_rights_res['reason']);
        }
        //超级管理员
        if ($user_rights_res['result'] == $this->_succ && $user_rights_res['info']['is_super'] == 'Y') {
            return $this->_formatreturndata(true);
        }

        if ($user_rights_res['result'] != $this->_succ || !is_array($user_rights_res['info']) || count($user_rights_res['info']['method_ids']) == 0) {
            return $this->_formatreturndata(false, '用户权限为空，请与管理员联系！');
        }
        $user_rights = $user_rights_res['info']['method_ids'];

        if (!in_array($method_res['id'], $user_rights) && $method_res['is_right'] == 1) {
            if(in_array($method_res['id'],array(699,686,669,610,608,607)))
            {
                return $this->_formatreturndata(false,$method_res['id']);
            }
            return $this->_formatreturndata(false, '没有操作权限，请与管理员联系！');
        }
        return $this->_formatreturndata(true);
    }

    

    
    /**
     * 根据权限获取用户组件
     * @param array $rights
     */
    public function get_user_tree($rights) {
        if (empty($rights)) {
            return false;
        }
        $menu_tree = $this->get_menu_tree();

        //超级管理员
        if ($rights['is_super'] == 'Y') {
            return $menu_tree;
        }

        if (empty($rights['method_ids'])) {
            return false;
        }
        $rights = $rights['method_ids'];
        foreach ($menu_tree as $cpk => $cpv) {
            foreach ($cpv['child_info'] as $ck => $cv) {
                foreach ($cv['child_info'] as $mk => $mv) {
                    //删除没有用户没有权限的菜单
                    if ($mv['is_right'] == 1 && !in_array($mv['id'], $rights)) {
                        unset($cv['child_info'][$mk]);
                        unset($menu_tree[$cpk]['child_info'][$ck]['child_info'][$mk]);
                    }
                }

                if (count($cv['child_info']) == 0) {
                    unset($cpv['child_info'][$ck]);
                    unset($menu_tree[$cpk]['child_info'][$ck]);
                }
            }

            if (count($cpv['child_info']) == 0) {
                unset($menu_tree[$cpk]);
            }
        }
        return $menu_tree;
    }
    
    /**
     * 获取菜单树
     */
    public function get_menu_tree() {

        //菜单树写缓存操作  （后续）
//        $this->config->load('right', TRUE, TRUE);
//        $serialize = $this->config->item("right");
//        echo "<pre />";
//        print_r(unserialize($serialize['trim']));die;
//        return unserialize($serialize['trim']);

//        if ($cache_data && CLEAR_CACHE) {

//          return unserialize($cache_data);
//        }

        $menus = array();
        $params = array(
            'is_show' => 'Y',
            'domain' => 'admin',
        );

        $compose_res = $this->CI->admin_compose_model->fetch_all($params, 'orderid desc,id asc');

        if ($compose_res) {
            foreach ($compose_res as $cpk => $cpv) {
                $controller_condition = array('compose_id' => $cpv['id'], 'is_show' => 'Y', 'controller_id' => 0);
                $controllers = $this->CI->admin_controller_model->fetch_all($controller_condition, 'orderid desc,id desc');

                //获取控制器
                if ($controllers) {
                    foreach ($controllers as $ck => $cv) {
                        $cv['func_name'] = str_replace('controller_', '', $cv['func_name']);
                        $method_condition = array('controller_id' => $cv['id'], 'is_show' => 'Y', 'is_menu' => 1);
                        $methods = $this->CI->admin_controller_model->fetch_all($method_condition, 'orderid desc,id desc');
                        //获取方法
                        if ($methods) {
                            foreach ($methods as $mk => $mv) {
                                unset($mv['compose_id']);
                                unset($mv['is_menu']);
                                unset($mv['orderid']);
                                unset($mv['is_show']);

                                $cv['child_info'][$mk] = $mv;
                            }

                            //加入菜单method
                            unset($cv['is_menu']);
                            unset($cv['orderid']);
                            unset($cv['is_show']);

                            $cpv['child_info'][$ck] = $cv;
                        }
                    }

                    //加入菜单controller
                    if (isset($cpv['child_info'])) {
                        unset($cpv['orderid']);
                        unset($cpv['is_show']);

                        $menus[$cpv['id']] = $cpv;
                    }
                }
            }
        }
        //写入缓存
//        if (!empty($menus)) {
//            $this->lib_redis->set($this->_menus_data_cache_key, serialize($menus));
//        }
        return $menus;
    }
    
    /**
     * 删除菜单缓存
     */
    public function delete_menu_cache() {
        $admin_cache = $this->_menus_data_cache_key;
        $this->lib_redis->del($admin_cache);
    }

    /**
     * 根据用户ID删除用户缓存
     * @param unknown $id
     */
    public function delete_user_cache($id) {
        $user_right_cache = get_cache_key('user_right_cache',array($id));
        //echo $user_right_cache;
        $this->lib_redis->del($user_right_cache);

    }


}
