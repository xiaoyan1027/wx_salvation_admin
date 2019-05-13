<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tongji_model extends BASE_Model {
    private $_log_table;
    public function __construct()
	{
		parent::__construct();
        $this->load->library('lib_redis');
        $this->_log_table = 'pet_api_log_'.date("Ymd");
    }
    /**
     * 获取统计数据
     */ 
    public function get_data()
    {
        $data = array(
            "project_num" => "216",
            "enterprise_num" => "20",
            "have_num" => "7",
            "customer_num" => "30",
            "small_app_num" => "1",
            "princi_num" => "2",
            "log_api_num" => "3",
            "show_log" => "67",
        );
        return $data;
    }
}
