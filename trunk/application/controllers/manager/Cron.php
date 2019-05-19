<?php

class Cron extends BASE_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('admin/admin_cron_model');
        $this->load->model('admin/admin_log_model');
    }

    /**
     * 任务列表
     */
    public function index() {

        $thread = $this->input->post('thread');
        $is_valid = $this->input->post('is_valid');
        $name = $this->input->post('name');
        $offset = (int)$this->input->get('per_page');

        $condition = '';
        if ($thread) {
            $condition = ' thread =' . $thread . ' AND';
        }

        if ($name) {
            $condition .= ' name like \'%' . $name . '%\' AND';
        }

        if ($is_valid === '0' || $is_valid == '1') {
            $condition .= ' is_valid = ' . $is_valid . ' AND';
        }

        $condition = rtrim($condition, 'AND');

        $cron_count_res = $this->admin_cron_model->get_count($condition);
        if ($cron_count_res['result'] == $this->_succ) {
            $cron_list_res = $this->admin_cron_model->get_list($condition, 'id DESC', $offset);
            if ($cron_list_res['result'] == $this->_succ) {
                $this->assign('cron_list', $cron_list_res['info']);
            }
            $this->assign('pager_html', get_pager_html($cron_count_res['info']), 'NO_DENY');
        }
        $this->display('cron_list');
    }

    /**
     * 添加任务
     */
    public function add() {
        if ($this->input->post()) {
            $data = array(
                'name' => $this->input->post('name'),
                'minute' => $this->input->post('minute'),
                'hour' => $this->input->post('hour'),
                'dom' => $this->input->post('dom'),
                'month' => $this->input->post('month'),
                'dow' => $this->input->post('dow'),
                'command' => $this->input->post('command'),
                'exec_mod' => $this->input->post('exec_mod'),
                'exec_act' => $this->input->post('exec_act'),
                'description' => $this->input->post('description'),
                'is_valid' => $this->input->post('is_valid'),
                'manual_op' => $this->input->post('manual_op'),
                'params' => $this->input->post('params'),
                'process_num' => $this->input->post('process_num'),
                'daemon_status' => $this->input->post('daemon_status'),
                'write_log' => $this->input->post('write_log'),
                'token' => random_string('unique')
            );
            if (!$this->_check_value($data)) {
                $this->show_message('任务添加失败，有部分字段数据为空，请正确填写任务信息！', '/manager/cron/add');
            }
            $data['last_modify_time'] = time();
            $data['last_mender_id'] = $this->login_model->get_admin_id();
            $data['last_mender'] = $_COOKIE['pet_admin_realname'];
            $res = $this->admin_cron_model->cron_add($data);
            if ($res['result'] == $this->_succ) {
                //操作日志
                $this->admin_log_model->log_add($data);
                $this->show_message('任务添加成功！', '/manager/cron/index');
            } else {
                $this->show_message('任务添加失败！', '/manager/cron/add');
            }
        }

        $threads = range(0, 20);
        unset($threads[0]);

        $this->assign('threads', $threads);

        $this->display('cron_update');
    }

    /**
     * 修改任务
     */
    public function update() {
        $id = $this->input->get('id');
        if ($id <= 0) {
            $this->show_message('参数错误', '/manager/cron/index');
        }

        $cron_info_res = $this->admin_cron_model->get_infos($id);
        if ($cron_info_res['result'] != $this->_succ) {
            $this->show_message('参数错误或任务不存在', '/manager/cron/index');
        }

        if ($this->input->post()) {
            $data = array(
                'name' => $this->input->post('name'),
                'minute' => $this->input->post('minute'),
                'hour' => $this->input->post('hour'),
                'dom' => $this->input->post('dom'),
                'month' => $this->input->post('month'),
                'dow' => $this->input->post('dow'),
                'command' => $this->input->post('command'),
                'exec_mod' => $this->input->post('exec_mod'),
                'exec_act' => $this->input->post('exec_act'),
                'description' => $this->input->post('description'),
                'is_valid' => $this->input->post('is_valid'),
                'manual_op' => $this->input->post('manual_op'),
                //'params' => $this->input->post('params'),
                'process_num' => $this->input->post('process_num'),
                'daemon_status' => $this->input->post('daemon_status'),
                'write_log' => $this->input->post('write_log'),
            );
            if (!$this->_check_value($data)) {
                $this->show_message('任务修改失败，有部分字段数据为空，请正确填写任务信息！', '/manager/cron/update?id=' . $id);
            }
            $data['last_modify_time'] = time();
            $data['last_mender_id'] = $this->login_model->get_admin_id();
            $data['last_mender'] = $_COOKIE['pet_admin_realname'];
            $res = $this->admin_cron_model->cron_update($id, $data);
            if ($res['result'] == $this->_succ) {
                //操作日志
                $data['id'] = $id;
                $this->admin_log_model->log_add($data);
                $this->show_message('任务修改成功！', '/manager/cron/index');
            } else {
                $this->show_message('任务修改失败！', '/manager/cron/update?id=' . $id);
            }
        }

        $threads = range(0, 20);
        unset($threads[0]);

        $url = "http://" . $_SERVER['SERVER_NAME'] . '/cron/';

        $this->assign('threads', $threads);
        $this->assign('cron_info', $cron_info_res['info']);
        $this->assign('url', $url);
        $this->display('cron_update');
    }

    /**
     * 删除任务
     */
    public function delete() {
        $id = $this->input->get('id');
        if ($id <= 0) {
            $this->show_message('参数错误', '/manager/cron/index');
        }

        $res = $this->admin_cron_model->cron_delete($id);
        if ($res['result'] == $this->_succ) {
            //操作日志
            $this->admin_log_model->log_add(array('id' => $id));
            $this->show_message('任务删除成功！', '/manager/cron/index');
        }
        $this->show_message('任务删除失败！', '/manager/cron/index');
    }

    /**
     * 检测数据
     * @param array $data
     * @return boolean
     */
    public function _check_value($data) {
        foreach ($data as $value) {
            if (empty($value) && $value != 0) {
                $ret = false;
                return false;
            }
        }
        return true;
    }
    /**
     * 实时任务错误队列
     */ 
    public function real_time_task_error_list()
    {
        $offset = $this->input->get('per_page') ? $this->input->get('per_page') : 0;
        $clear = $this->input->get('clear');
        $this->load->model('task_model');
        if($clear == 1)
        {
            $this->task_model->delete_error_list();
        }
        $count = $this->task_model->get_error_list_count();
        $list = $this->task_model->get_error_list($offset);
        $list = $list ? $list : array();
        $result = array();
        foreach($list as $k=>$v)
        {
            $v = unserialize($v);
            $v['data'] = print_r($v['data'],true);
            $v['result'] = print_r($v['result'],true);
            $result[$k] = $v;
        }
        $this->assign('list',$result);
        $this->assign('pager_html', get_pager_html($count), 'NO_DENY');
        $this->display('error_list');
    }
    /**
     * 定时任务异常队列
     */ 
    public function timer_task_error_list()
    {
        $offset = $this->input->get('per_page') ? $this->input->get('per_page') : 0;
        $cache_key = get_cache_key('timer_task_error_list');

        $count = $this->lib_redis->llen($cache_key);
        $list = $this->lib_redis->lrange($cache_key,$offset,20);
        $result = array();
        foreach($list as $k=>$v)
        {
            $v = unserialize($v);
            $v['params'] = print_r($v['params'],true);
            $v['result'] = print_r($v['result'],true);
            $result[$k] = $v;
        }
        $this->assign('list',$result);
        $this->assign('pager_html', get_pager_html($count), 'NO_DENY');
        $this->display('timer_task_error_list');
    }
    /**
     * 导出文件
     */
    public function export_cmd() {
        $url = "http://" . $_SERVER['SERVER_NAME'] . '/cron/';

        $params = array(
            'is_valid' => 1,
        );
        $cron_list_res = $this->admin_cron_model->get_list($params, 'id Desc', 0, 100);
        foreach ($cron_list_res['info'] as $row) {
            $content[] = $row['minute'] . ' ' . $row['hour'] . ' ' . $row['dom'] . ' '.  $row['month'] . ' ' . $row['dow'] . " curl \"{$url}index/index?id={$row['id']}{$row['params']}\"";
        }

        $html = implode("\r\n", $content);

        Header( "Content-type:   application/octet-stream ");
        header( "Content-Disposition:   attachment;   filename=ad_cron.txt ");
        header( "Pragma:   public ");
        echo $html;
    }
}