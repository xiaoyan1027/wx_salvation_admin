<?php
/**
 * 任务操作
 */ 
class Task_model extends BASE_Model
{
	//待处理任务队列
    private $_wait_task_queue;
    //实时处理队列
    private $_real_time_task_queue;
    //延迟队列
    private $_delay_task_queue;
    //错误任务队列
    private $_error_task_queue;
    private $_redis;
    private $_ci;
    
	public function __construct()
	{
		parent::__construct();
        $this->_ci = & get_instance();
        $this->_redis = $this->_ci->lib_redis;
        $this->_error_task_queue = get_cache_key('error_task_queue');
        $this->_delay_task_queue = get_cache_key('delay_task_queue');
	}
    /**
     * 添加定时任务
     */ 
    public function add_wait_task($dateline,$task)
    {
        if(empty($dateline))
        {
            return $this->_formatreturndata(false,'时间不可以为空!');
        }
        elseif(!preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/",$dateline))
        {
            return $this->_formatreturndata(false,'时间格式不正确(格式：2017-09-09 20:00)');
        }
        elseif(!isset($task['module']))
        {
            return $this->_formatreturndata(false,'任务处理模块不可以为空!');
        }
        elseif(!isset($task['data']))
        {
            return $this->_formatreturndata(false,'任务数据不可以为空!');
        }
        $this->_wait_task_queue = get_cache_key("wait_task_queue",array(str_replace(array(" ","-",":"),'',$dateline)));
        $res = $this->_redis->lpush($this->_wait_task_queue,serialize($task));
        if($res)
        {
            return $this->_formatreturndata(true,'添加成功');
        }
        else
        {
            return $this->_formatreturndata(false,'添加失败');
        }
    }
    /**
     * 获取定时任务
     */ 
    public function get_wait_task($dateline)
    {
        $this->_wait_task_queue = get_cache_key("wait_task_queue",array(str_replace(array(" ","-",":"),'',$dateline)));
        $task = $this->_redis->rpop($this->_wait_task_queue);
        if(!empty($task))
        {
            $task = unserialize($task);
        }
        return $task;
    }
    /**
     * 获取实时任务
     */ 
    public function get_real_time_task($type='default')
    {
        $this->_real_time_task_queue = get_cache_key("real_time_task_queue",array($type));
        $task = $this->_redis->rpop($this->_real_time_task_queue);
        if(!empty($task))
        {
            $task = unserialize($task);
        }
        return $task;
    }
    /**
     * 添加实时任务
     */ 
    public function add_real_time_task($task,$type='default')
    {
        if(!isset($task['module']))
        {
            return $this->_formatreturndata(false,'任务处理模块不可以为空!');
        }
        elseif(!isset($task['data']))
        {
            return $this->_formatreturndata(false,'任务数据不可以为空!');
        }
        $this->_real_time_task_queue = get_cache_key("real_time_task_queue",array($type));
        $res = $this->_redis->lpush($this->_real_time_task_queue,serialize($task));
        if($res)
        {
            return $this->_formatreturndata(true,'添加成功');
        }
        else
        {
            return $this->_formatreturndata(false,'添加失败');
        }
    }
    /**
     * 情况任务队列
     */ 
    public function delete_real_time_task($type='default')
    {
        $this->_real_time_task_queue = get_cache_key("real_time_task_queue",array($type));
        return $this->_redis->del($this->_real_time_task_queue);
    }
    /**
     * 添加错误任务
     */ 
    public function add_error_task($task)
    {
        if(!isset($task['module']))
        {
            return $this->_formatreturndata(false,'任务处理模块不可以为空!');
        }
        elseif(!isset($task['data']))
        {
            return $this->_formatreturndata(false,'任务数据不可以为空!');
        }
        elseif(!isset($task['result']))
        {
            //return $this->_formatreturndata(false,'任务错误结果不可以为空!');
        }
        $task['add_time'] = time();
        $res = $this->_redis->lpush($this->_error_task_queue,serialize($task));
        if($res)
        {
            return $this->_formatreturndata(true,'添加成功');
        }
        else
        {
            return $this->_formatreturndata(false,'添加失败');
        }
    }
    /**
     * 获取错误列表
     */ 
    public function get_error_list($offset=0,$limit=20)
    {
        $list = $this->_redis->lrange($this->_error_task_queue,$offset,$offset + $limit);
        return $list;
    }
    /**
     * 获取错误队列数量
     */ 
    public function get_error_list_count()
    {
        return $this->_redis->llen($this->_error_task_queue);
    }
    /**
     * 删除错误队列
     */ 
    public function delete_error_list()
    {
        return $this->_redis->del($this->_error_task_queue);
    }
    
    /**
     * 获取错误任务
     */ 
    public function get_error_task()
    {
        $task = $this->_redis->rpop($this->_error_task_queue);
        if(!empty($task))
        {
            $task = unserialize($task);
        }
        return $task;
    }
    /**
     * 添加延迟任务
     */ 
    public function add_delay_task($dateline,$task)
    {
        if(empty($dateline))
        {
            return $this->_formatreturndata(false,'时间不可以为空!');
        }
        elseif(!isset($task['module']))
        {
            return $this->_formatreturndata(false,'任务处理模块不可以为空!');
        }
        elseif(!isset($task['data']))
        {
            return $this->_formatreturndata(false,'任务数据不可以为空!');
        }
        $task['id'] = $this->_create_id();
        $res = $this->_redis->zadd($this->_delay_task_queue,$dateline,serialize($task));
        if($res)
        {
            return $this->_formatreturndata(true,'添加成功');
        }
        else
        {
            return $this->_formatreturndata(false,'添加失败');
        }
    }
    /**
     * 获取延迟任务
     */ 
    public function get_delay_task($dateline)
    {
        $list = $this->_redis->zrevrangebyscore($this->_delay_task_queue,$dateline,0,array('withscores' => true));
        return $list;
    }
    
    /**
     * 删除延迟任务
     */
    public function delete_delay_task($dateline)
    {
        $res = $this->_redis->zremrangebyscore($this->_delay_task_queue,0,$dateline);
        return $res;
    }
    
    /**
     * 生产唯一标识,根据微秒
     */
    private function _create_id() {
        $sn = 'TKID';
        $sn .= floor(microtime(true) * 10000);
        $sn .= mt_rand(100000, 999999);
        return $sn;
    }
}
