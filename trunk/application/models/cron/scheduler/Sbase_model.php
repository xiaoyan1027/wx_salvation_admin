<?php
/**
 * 基类
 */ 
class Sbase_model extends BASE_Model
{
    function __construct()
    {
        parent::__construct();
    }
    /**
     * 添加到错误队列
     */ 
    public function add_error_task($module,$data,$result)
    {
        $task = array(
            'module' => $module,
            'data' => $data,
            'result' => $result
        );
        $res = $this->task_model->add_error_task($task);
        return $res;
    }
}
