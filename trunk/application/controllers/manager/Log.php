<?php

class log extends BASE_Controller {
    private $_log_table;
    public function __construct() {
        parent::__construct();
        $this->load->model('admin/admin_log_model');
        $this->load->model('admin/api_log_model');
        $this->_log_table = 'pet_api_log'.date("Ymd");

    }

    /**
     * 日志列表
     */
    public function index() {
        $offset = (int)$this->input->get('per_page');
        $condition = array(
            'operator' => $this->input->get('operator'),
            'site' => $this->input->get('log_site'),
            'ctl' => $this->input->get('log_ctl'),
            'act' => $this->input->get('log_act'),
            'desc' => $this->input->get('log_desc'),
            'uri' => $this->input->get('log_uri'),
            'start_time' => $this->input->get('start_time'),
            'end_time' => $this->input->get('end_time'),
        );

        $log_count_res = $this->admin_log_model->get_count($condition);
        if ($log_count_res['result'] == $this->_succ) {
            $log_list_res = $this->admin_log_model->get_list($condition, 'id DESC', $offset);
            if ($log_list_res['result'] == $this->_succ) {
                $this->assign('log_list', $log_list_res['info']);
            }
            $this->assign('pager_html', get_pager_html($log_count_res['info']), 'NO_DENY');
        }
        $this->display('log_list');
    }

    /**
     * 查看日志详情
     */
    public function detail() {
        $id = $this->input->get('id');
        if ($id <= 0) {
            $this->show_message('参数错误', '?site=manager&ctl=log&act=index');
        }

        $res = $this->admin_log_model->get_infos($id);
        if ($res['result'] == $this->_succ) {
            $detail = $res['info'];
            $detail['data'] = @print_r(unserialize($detail['data']), true);
            $this->assign('log_info', $detail);
        }
        $this->display('log_detail');
    }



}
