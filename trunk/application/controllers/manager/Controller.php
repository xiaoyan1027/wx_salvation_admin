<?php

/**
 * Controller管理
 * @author risun
 *
 */
class controller extends BASE_Controller {

    public $_filter_methods = array(
        'show_message',
        'show_alert_message',
        'show_404',
        'assign',
        'display',
        'display_base',
        'fetch',
        'get_instance',
        'init_menu',
        'build_condition',
        'ajax_return',
        '_build_condition',
        '__construct',
        'removeFilterBadChar',
        'special_customer',
        'handle_outside_page'
    );

    public function __construct() {
        parent::__construct();
        $this->load->model('admin/admin_controller_model');
        $this->load->model('admin/admin_compose_model');
        $this->load->model('admin/admin_log_model');
        $this->load->model('common/right_model');

        $this->load->library('Lib_http');
    }

    /**
     * Controller列表
     */
    public function index() {
        $offset = (int)$this->input->get('per_page');
        $condition = array('controller_id' => 0);
        $compose_id = $this->input->get('com_id');
        if ($compose_id > 0) {
            $condition['compose_id'] = $compose_id;
        }

        $compose_list = $this->_get_compose_list();
        $this->assign('compose_list', $compose_list);

        $controller_count_res = $this->admin_controller_model->get_count($condition);
        if ($controller_count_res['result'] == $this->_succ) {
            $controller_list_res = $this->admin_controller_model->get_list($condition, 'id DESC', $offset);
            if ($controller_list_res['result'] == $this->_succ) {
                $this->assign('controller_list', $controller_list_res['info']);
            }
            $this->assign('pager_html', get_pager_html($controller_count_res['info']), 'NO_DENY');
        }
        $domain_list = $this->admin_compose_model->get_domain_list();

        $this->assign('domain_list', $domain_list);
        $this->display('controller_list');
    }


    /**
     * 添加Controller
     */
    public function add() {
        if ($this->input->post()) {
            $compose_id = $this->input->post('compose_id');
            $func_name = $this->input->post('func_name');
            $func_name_cn = $this->input->post('func_name_cn');
            if (empty($compose_id) || empty($func_name) || empty($func_name_cn)) {
                $this->show_message('请正确填写Controller数据', '/manager/controller/index');
            }

            $data = array(
                'compose_id' => $compose_id,
                'func_name' => $func_name,
                'func_name_cn' => $func_name_cn,
                'orderid' => $this->input->post('orderid'),
                'is_show' => 'Y',
                'icon' => $this->input->post('icon'),
            );
            $res = $this->admin_controller_model->controller_add($data);
            if ($res['result'] == $this->_succ) {
                //操作日志
                $this->admin_log_model->log_add($data);
                $this->right_model->delete_menu_cache();

                $this->show_message('Controller添加成功！', '/manager/controller/index?com_id=' . $compose_id);
            } else {
                $this->show_message('Controller添加失败！', '/manager/controller/add');
            }
        }

        $compose_id = $this->input->get('compose_id');
        if ($compose_id > 0) {
            $this->assign('controller_info', array('compose_id' => $compose_id));
        }
        $compose_list = $this->_get_compose_list();
        $this->assign('compose_list', $compose_list);
        $this->display('controller_form');
    }

    /**
     * 更新Controller
     */
    public function update() {
        $id = $this->input->get('controller_id');
        if ($id <= 0) {
            $this->show_message('参数错误', '/manager/controller/index');
        }

        if ($this->input->post()) {
            $data = array(
                'compose_id' => $this->input->post('compose_id'),
                'func_name' => $this->input->post('func_name'),
                'func_name_cn' => $this->input->post('func_name_cn'),
                'orderid' => $this->input->post('orderid'),
                'icon' => $this->input->post('icon'),
            );

            $res = $this->admin_controller_model->controller_update($id, $data);
            if ($res['result'] == $this->_succ) {
                //操作日志
                $data['id'] = $id;
                $this->admin_log_model->log_add($data);

                //删除菜单缓存
                $compose_res = $this->_get_compose_info($id);
                $this->right_model->delete_menu_cache();

                $this->show_message('Controller修改成功！', '/manager/controller/index');
            } else {
                $this->show_message('Controller修改失败！', '/manager/controller/update?id=' . $id);
            }
        }

        $compose_list = $this->_get_compose_list();
        $this->assign('compose_list', $compose_list);

        $res = $this->admin_controller_model->get_infos($id);
        if ($res['result'] == $this->_succ) {
            $this->assign('controller_info', $res['info']);
        }
        $this->display('controller_form');
    }

    /**
     * 删除Controller
     */
    public function delete() {
        $id = $this->input->get('controller_id');
        if ($id <= 0) {
            $this->show_message('参数错误', '/manager/controller/index');
        }
        $compose_res = $this->_get_compose_info($id);
        //删除Controller下的Action
        $this->admin_controller_model->controller_delete_by_condition(array('controller_id' => $id));
        $res = $this->admin_controller_model->controller_delete($id);
        if ($res['result'] == $this->_succ) {
            //操作日志
            $this->admin_log_model->log_add(array('id' => $id));

            //删除菜单缓存

            $this->right_model->delete_menu_cache();

            $this->show_message('Controller删除成功！', '/manager/controller/index');
        }
        $this->show_message('Controller删除失败！', '/manager/controller/index');
    }

    /**
     * 隐藏Controller
     */
    public function hidden() {
        $id = $this->input->get('controller_id');
        $compose_id = $this->input->get('compose_id');
        $is_show = $this->input->get('is_show');
        $is_show = $is_show == 'Y' ? 'Y' : 'N';
        if ($id <= 0) {
            $this->show_message('参数错误', '/manager/controller/index');
        }

        $res = $this->admin_controller_model->controller_update($id, array('is_show' => $is_show));
        if ($res['result'] == $this->_succ) {

            //删除菜单缓存
            $compose_res = $this->_get_compose_info($id);
            $this->right_model->delete_menu_cache();

            $this->show_message('Controller隐藏成功！', '/manager/controller/index?compose_id=' . $compose_id);
        }
        $this->show_message('Controller隐藏失败！', '/manager/controller/index?compose_id=' . $compose_id);

    }


    /**
     * 方法列表
     */
    public function method_list() {
        $controller_id = $this->input->get('controller_id');
        if ($controller_id <= 0) {
            $this->show_message('参数错误', '/manager/controller/index');
        }

        //当前Controller信息
        $controller_info_res = $this->admin_controller_model->get_infos($controller_id);

        if ($controller_info_res['result'] == $this->_fail) {
            $this->show_message('控制器不存在', '/manager/controller/index');
        }
        $controller_info = $controller_info_res['info'];

        //更新方法
        if ($this->input->post()) {
            //更新已存在的及曾经用过的方法
            $update_func_name_cn = $this->input->post('update_func_name_cn');
            $update_is_menu = $this->input->post('update_is_menu');
            $update_is_right = $this->input->post('update_is_right');
            $update_orderid = $this->input->post('update_orderid');
            
            if (!empty($update_func_name_cn)) {
                foreach ($update_func_name_cn as $key => $value) {
                    $update_data = array('func_name_cn' => $value);
                    $update_data['is_menu'] = $update_is_menu[$key];
                    $update_data['is_right'] = $update_is_right[$key];
                    if (isset($update_orderid[$key])) {
                        $update_data['orderid'] = $update_orderid[$key];
                    }
                    $this->admin_controller_model->controller_update($key, $update_data);
                    //操作日志
                    $update_data['id'] = $key;
                    $this->admin_log_model->log_add($update_data, '更新方法');
                }
            }

            //添加新的方法
            $add_func_name_cn = $this->input->post('add_func_name_cn');
            $add_is_menu = $this->input->post('add_is_menu');
            $add_is_right = $this->input->post('add_is_right');
            $add_orderid = $this->input->post('add_orderid');
            $add_orderid = $this->input->post('add_orderid');
            if (!empty($add_func_name_cn)) {
                foreach ($add_func_name_cn as $key => $value) {
                    if (empty($value)) continue;
                    $add_data = array('func_name' => $key, 'func_name_cn' => $value);
                    $add_data['is_menu'] = $add_is_menu[$key];
                    $add_data['is_right'] = $add_is_right[$key];
                    $add_data['orderid'] = $add_orderid[$key];
                    $add_data['controller_id'] = $controller_id;

                    $this->admin_controller_model->controller_add($add_data);
                    //操作日志
                    $this->admin_log_model->log_add($add_data, '添加新的方法');
                }
            }

            //删除菜单缓存
            $compose_res = $this->_get_compose_info($controller_id);
            $this->right_model->delete_menu_cache();

            $this->show_message('方法更新成功', '/manager/controller/method_list?controller_id=' . $controller_id);
        }

        //获取表中已记录的当前Controller的方法
        $past_methods_res = $this->admin_controller_model->get_list(array('controller_id' => $controller_id), 'id ASC');
        $past_methods = array();
        if ($past_methods_res['result'] == $this->_succ) {
            foreach ($past_methods_res['info'] as $rights) {
                $past_methods[$rights['func_name']] = $rights;
            }
        }

        //compose
        $compose_info_res = $this->admin_compose_model->get_infos($controller_info['compose_id']);
        $exists_methods = $this->get_file_method($compose_info_res['info']['en_name'], $controller_info['func_name'], $compose_info_res['info']['domain']);

        $filter_methods = $this->_filter_methods;
        $new_methods = $current_methods = array();

        if ($exists_methods) {
            foreach ($exists_methods as $method) {
                if (substr($method, 0, 1) == '_' || in_array($method, $filter_methods)) continue;

                if (isset($past_methods[$method])) {
                    $current_methods[$method] = $past_methods[$method];
                    unset($past_methods[$method]);
                } else {
                    $new_methods[$method] = $method;
                }
            }
        }

        $this->assign('controller_info', $controller_info);
//        $this->assign('compose_info', $compose_info_res['info']);
        $this->assign('past_methods', $past_methods);
        $this->assign('new_methods', $new_methods);
        $this->assign('current_methods', $current_methods);
        $this->display('controller_function_list');
    }

    /**
     * 删除方法
     */
    public function delete_method() {
        $controller_id = $this->input->get('controller_id');
        $id = $this->input->get('id');
        if ($id <= 0) {
            $this->show_message('参数错误', '/manager/controller/method_list?controller_id=' . $controller_id);
        }

        $res = $this->admin_controller_model->controller_delete($id);
        if ($res['result'] == $this->_succ) {
            //操作日志
            $this->admin_log_model->log_add(array('id' => $id), '删除方法');

            //删除菜单缓存
            $compose_res = $this->_get_compose_info($id);
            $this->right_model->delete_menu_cache();

            $this->show_message('Method删除成功！', '/manager/controller/method_list?controller_id=' . $controller_id);
        }
        $this->show_message('Method删除失败！', '/manager/controller/method_list?controller_id=' . $controller_id);
    }

    /**
     * 返回组件列表
     * @return multitype:unknown
     */
    private function _get_compose_list() {
        //compose list
        //$module_compose = $this->load->model('admin/admin_compose_model');
        $compose_list_res = $this->admin_compose_model->get_list('', 'orderid DESC');
        $compose_list_arr = array();
        if ($compose_list_res['result'] == $this->_succ) {
            foreach ($compose_list_res['info'] as $value) {
                $type = $value['domain'] == 'admin' ? '后台' : '前台';
                $compose_list_arr[$value['id']] = $value['cn_name'] . '—' . $type;
            }
        }

        return $compose_list_arr;
    }

    /**
     * 获取组件信息
     * @param $controller_id
     */
    private function _get_compose_info($controller_id) {
        $controller_info = $this->admin_controller_model->get_infos($controller_id);
        if ($controller_info['result'] == 'fail') {
            return false;
        }

        $res = $this->admin_compose_model->get_map_info(array('id' => $controller_info['info']['compose_id']));
        if ($res['result'] == 'fail') {
            return false;
        }
        return $res['info'];
    }


    /**
     * 获取方法列表
     */
    public function get_method_list($compose, $controller) {
        if (empty($compose)) {
            return array();
        }

        if (empty($controller)) {
            return array();
        }

        $file = $_SERVER['DOCUMENT_ROOT'] . '/application/controllers/' . $compose . '/' . ucfirst($controller) . '.php';

        if (!isset($file) && !file_exists($file)) {
            output_error('文件不存在');
        }

        require_once $file;

        $method_list = get_class_methods($controller);
        if ($method_list) {
            return $method_list;
        } else {
            return array();
        }
    }

    /**
     * 获取文件中的方法列表
     * @author:xionghui2@leju.com
     *
     * @param $composer
     * @param $controller
     * @param string $domain
     * @return array
     */
    private function get_file_method($composer, $controller, $domain = 'admin') {
        if ($domain == 'admin') {
            $file_method = $this->get_method_list($composer, $controller);
            return $file_method;
        }

        $file_method = array();
        $data = array(
            'compose' => $composer,
            'controller' => $controller,
        );
        $params['sign'] = $this->encrypt->encode(serialize($data));

        $result = $this->lib_http->post( XCX_LEJU_HOST . 'api/method/get_method_list',  $params);

        if (!isset($result['error_code'])) {
            $file_method = $result['entry'];
        } else {
            print_r($result);
            exit;
        }
        return $file_method;
    }

}