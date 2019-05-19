<?php
class Compose extends BASE_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/admin_compose_model');
        $this->load->model('admin/admin_controller_model');
        $this->load->model('admin/admin_log_model');
    }

    /**
     * 组件列表
     */
    public function index()
    {
        $offset = (int)$this->input->get('per_page');
        $domain = $this->input->get('domain');
        $order = $this->input->get('order');

        $order_by = 'id DESC';
        if ($order) {
            $order_by =  $order == 2 ? 'orderid DESC' : 'orderid ASC';
        }

        $params = array();
        if ($domain) {
            $params['domain'] = $domain;
        }
        $compose_count_res = $this->admin_compose_model->get_count();
        if($compose_count_res['result'] =='succ' )
        {
            $compose_list_res = $this->admin_compose_model->get_list($params, $order_by, $offset);
            if($compose_list_res['result'] == $this->_succ)
            {
                $this->assign('compose_list', $compose_list_res['info']);
            }
            $pager_html = get_pager_html($compose_count_res['info']);
            $this->assign('pager_html', $pager_html, 'NO_DENY');
        }
        $domain_list = $this->admin_compose_model->get_domain_list();

        $this->assign('domain_list', $domain_list);
        $this->display('compose_list');
    }

    /**
     * 添加组件
     */
    public function add()
    {
        if($this->input->post())
        {
            $en_name = $this->input->post('en_name');
            $cn_name = $this->input->post('cn_name');
            $domain = $this->input->post('domain');
            $icon = $this->input->post('icon');
            if(empty($en_name) || empty($cn_name) || empty($domain))
            {
                $this->show_message('组件添加失败，请正确填写组件信息！', '/manager/compose/add');
            }

            $compose_exists = $this->admin_compose_model->get_count(array('en_name' => $en_name, 'domain' => $domain));
            if($compose_exists['result'] == $this->_succ && $compose_exists['info'] > 0)
            {
                $this->show_message('组件添加失败，'.$en_name.'组件已存在！', '/manager/compose/add');
            }

            $data = array(
                'en_name' => $en_name,
                'cn_name' => $cn_name,
                'orderid' => $this->input->post('orderid'),
                'domain' => $domain,
                'icon' => $icon,
            );

            $res = $this->admin_compose_model->compose_add($data);
            if($res['result'] == $this->_succ)
            {
                $this->admin_log_model->log_add($data);
                $this->right_model->delete_menu_cache();
                $this->show_message('组件添加成功！', '/manager/compose/index');
            }
            else
            {
                $this->show_message('组件添加失败！', '/manager/compose/add');
            }
        }

        //$this->assign('compose_info', array());
        $domain_list = $this->admin_compose_model->get_domain_list();
        $this->assign('domain_list', $domain_list);
        $this->display('compose_form');
    }

    /**
     * 更新组件
     */
    public function update()
    {
        $id = $this->input->get('id');
        if($id <= 0)
        {
            $this->show_message('参数错误', '/manager/compose/index');
        }

        if($this->input->post())
        {
            $en_name = $this->input->post('en_name');
            $cn_name = $this->input->post('cn_name');
            $domain = $this->input->post('domain');

            if(empty($en_name) || empty($cn_name) || empty($domain))
            {
                $this->show_message('组件添加失败，请正确填写组件信息！', '/manager/compose/add');
            }

            $data = array(
                'en_name' => $en_name,
                'cn_name' => $cn_name,
                'orderid' => $this->input->post('orderid'),
                'domain' => $domain,
            );
            $res = $this->admin_compose_model->compose_update($id, $data);
            if($res['result'] == $this->_succ)
            {
                //操作日志
                $data['id'] = $id;
                $this->admin_log_model->log_add($data);
                $this->right_model->delete_menu_cache();
                $this->show_message('组件修改成功！', '/manager/compose/index');
            }
            else
            {
                $this->show_message('组件修改失败！', '/manager/compose/update?id=' . $id);
            }
        }

        $res = $this->admin_compose_model->get_infos($id);

        if($res['result'] == $this->_succ)
        {
            $this->assign('compose_detail', $res['info']);
        }
        $domain_list = $this->admin_compose_model->get_domain_list();
        $this->assign('domain_list', $domain_list);
        $this->display('compose_form');
    }

    /**
     * 删除组件
     */
    public function delete()
    {
        $id = $this->input->get('id');
        if($id <= 0)
        {
            $this->show_message('参数错误', '/manager/compose/index');
        }

        //删除组件下的Controller
        $controllers_res = $this->admin_controller_model->get_list(array('compose_id' => $id));
        if($controllers_res['result'] == $this->_succ)
        {
            foreach ($controllers_res['info'] as $controller_info)
            {
                $this->admin_controller_model->controller_delete_by_condition(array('controller_id' => $controller_info['id']));
                $this->admin_controller_model->controller_delete($controller_info['id']);
            }
        }

        $res = $this->admin_compose_model->compose_delete($id);
        if($res['result'] == $this->_succ)
        {
            //操作日志
            $this->right_model->delete_menu_cache();
            $this->admin_log_model->log_add(array('id' => $id));
            $this->show_message('组件删除成功！', '/manager/compose/index');
        }
        $this->show_message('组件删除失败！', '/manager/compose/index');
    }

    /**
     * 隐藏组件
     */
    public function hidden()
    {
        $id = $this->input->get('id');
        $is_show = $this->input->get('is_show');
        $is_show = $is_show == 'Y' ? 'Y' : 'N';
        if($id <= 0)
        {
            $this->show_message('参数错误', '/manager/compose/index');
        }

        $res = $this->admin_compose_model->compose_update($id, array('is_show' => $is_show));
        if($res['result'] == $this->_succ)
        {
            //操作日志
            $this->admin_log_model->log_add(array('id' => $id), '隐藏组件');
            $this->right_model->delete_menu_cache();
            $this->show_message('组件隐藏成功！', '/manager/compose/index');
        }
        $this->show_message('组件隐藏失败！', '/manager/compose/index');

    }

    /**
     *  根据站点获取组件列表
     *  @author:xionghui2@leju.com
     */
    public function get_compose_list_by_domain() {
        $domain = $this->input->get('domain');
        $params = array();
        if (!empty($domain)) {
            $params['domain'] = $domain;
        }
        $compose_list = $this->admin_compose_model->get_list($params);
        output_ok($compose_list['info']);
    }
}