<?php
/**
 * 任务分发
 * @author mingxing@leju.com
 */
class C_task_model  extends BASE_Model {
    private $_ci;
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 延迟队列分发操作
     */ 
    public function distribute()
    {
        $this->load->model('task_model');
        $dateline = time();
        $result = array();
        //获取任务
        $task_list = $this->task_model->get_delay_task($dateline);
        if(empty($task_list)) return "任务为空！";
        //删除任务
        $this->task_model->delete_delay_task($dateline);
        foreach($task_list as $k =>$v)
        {
            $task = unserialize($k);
            if(empty($task))
            {
                break;    
            }
            $res = $this->task_model->add_real_time_task($task);
            $result[] = $res;
        }
        return $result;
    }
    /**
     * 任务执行
     */ 
    public function execute()
    {
        require(APPPATH."models/cron/scheduler/Sbase_model.php");
        $type = $this->input->get('type') ? $this->input->get('type') : 'default';
        $this->load->model('task_model');
        $this->_ci = & get_instance();
        $result = array();
        for($i=1;$i<=10;$i++)
        {
            $task = $this->task_model->get_real_time_task($type);
            if(empty($task))
            {
               break; 
            }
            $module = $task['module'];
            $class_name = ucfirst($module)."_model";
            $class_file = APPPATH."models/cron/scheduler/{$class_name}.php";
            if(file_exists($class_file))
            {
                $this->_ci->load->model('cron/scheduler/'.strtolower($class_name),$class_name);
                if(class_exists($class_name) && method_exists($class_name,'execute'))
                {
                    $res = call_user_func_array(array($this->$class_name,'execute'),array($task['data']));
                    $result[] = array(
                        'task' => $task,
                        'result' => $res
                    );
                }
                else
                {
                    $result[] = array(
                        'task' => $task,
                        'message' => "方法不存在！"
                    );
                }
            }
            else
            {
                $result[] = array(
                    'task' => $task,
                    'message' => "{$class_file}文件不存在！"
                );
            }
        }
        return $result;
    }
    /**
     * 任务重新执行
     */ 
    public function re_exec()
    {
        $this->load->model('task_model');
        for($i=1;$i<=1000;$i++)
        {
            $task = $this->task_model->get_error_task();
            if(empty($task))
            {
               echo "Empty!!!";
               break; 
            }
            $add_res = $this->task_model->add_real_time_task($task);
            print_r($add_res);
        }
    }
}