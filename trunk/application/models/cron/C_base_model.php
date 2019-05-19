<?php

/**
 * cron基类
 * @author
 *
 */
class C_base_model extends BASE_model {

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 添加到错误队列
     */
    public function add_error_list($exec_fun,$params = array(),$result = array(),$key='timer_task_error_list')
    {
        $cache_key = get_cache_key($key);
        $data = array(
            'class' => get_class($this),
            'exec_fun' => $exec_fun,
            'params' => $params,
            'result' => $result,
            'add_time' => time()
        );
        $data['id'] = md5($data['class'].$data['exec_fun'].json_encode($params));
        $res = $this->lib_redis->lpush($cache_key,serialize($data));
        return $res;
    }
    
    /**
     * 添加到最终错误日志
     */ 
    public function add_error_log($data)
    {
        $data['add_time'] = time();
        $this->load->library('lib_mongo');
        $res = $this->lib_mongo->insert('sync_error_log',$data);
    }
}
