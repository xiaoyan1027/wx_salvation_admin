<?php
/**
 * 首页
 * @author  mingxing
 */ 
class Home extends BASE_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('tongji_model');
    }

    public function index()
    {
        $result = $this->tongji_model->get_data();
        $this->assign('result',$result);
        $this->display('index');
    }
}
