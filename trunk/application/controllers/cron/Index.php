<?php
set_time_limit(0);

class Index extends CI_Controller
{
    private $_module_cron;
    private $_lib_cron_phpcrond;
    private $_module_cronlog;

    protected $_succ = 'succ';
    protected $_fail = 'fail';

    public function __construct()
    {
        parent::__construct();

        $this->load->model('admin/admin_cron_model');
        $this->load->model('admin/admin_cron_log_model');
        $this->load->library('cron/lib_cron_phpcrond');

    }

    /**
     * 控制器默认方法，执行当前入口
     */
    public function index()
    {
        //cron操作打开报错
        ini_set('display_errors', 'On');
        error_reporting(E_ALL);
        
        $id = $this->input->get('id');
        $test = $this->input->get('test');
        $token = $this->input->get('token');
        $res = $this->admin_cron_model->get_infos($id);
        if($res['result'] == $this->_succ)
        {
            if ($res['info']['is_valid'] == 0 && $test != 1) {
                $str = "<h3>任务不可用!</h3>";
                $str.= '<p><a onclick="javascript:window.history.go(-1);">返回</a></p>';
                echo $str;
                exit;
            }
            elseif($res['info']['token'] != $token)
            {
                $str = "<h3>任务不合法!</h3>";
                $str.= '<p><a onclick="javascript:window.history.go(-1);">返回</a></p>';
                echo $str;
                exit;
            }
            $this->run($res['info'], 1);
        }
    }    
    /**
     * 执行cron
     * @param array $data
     * @param number $exec_immediate
     */
    private function run($data, $exec_immediate = 0)
    {
        $begin_time = time();
        $this->lib_cron_phpcrond->check_cron($data,$exec_immediate);


        //添加日志
        if($this->lib_cron_phpcrond->get_exec_status())
        {
            if($data['write_log'] == 'Yes')
            {
                
                $log_data = array(
                    'cron_id' => $data['id'],
                    'exec_mod' => $data['exec_mod'],
                    'exec_act' => $data['exec_act'],
                    'params' => $data['params'],
                    'name' => $data['name'],
                    'begin_time' => $begin_time,
                    'end_time' => 0,
                    'result' => '',
                    'user_id' => isset($_COOKIE['pet_admin_uid']) ? $_COOKIE['pet_admin_uid'] : '',
                );
                $this->admin_cron_log_model->log_add($log_data);
            }
            
        }
        
        $this->update_cron_exec_info($data['id']);
        $result = $this->lib_cron_phpcrond->run_cron();
        //更新日志
        if($this->lib_cron_phpcrond->get_exec_status())
        {
            if($data['write_log'] == 'Yes')
            {
                if(isset($log_data['_id']))
                {
                    $update_params = array(
                        'end_time' => time(),
                        'result' => serialize($result),
                    );
                    $res = $this->admin_cron_log_model->log_update($log_data['_id'],array('$set' => $update_params));
                }
            }
        }
        if(!empty($result))
        {
            print_r($result);
        }
        
    }
    
    /**
     * 更新cron
     * @param number $id
     * @return Ambigous <Ambigous, multitype:, multitype:mixed string >
     */
    private function update_cron_exec_info($id)
    {
        $options = array();
        if($this->lib_cron_phpcrond->get_exec_status())
        {
            $options['pre_time'] = $this->lib_cron_phpcrond->get_last_run_time();
        }
        $options['nxt_time'] = $this->lib_cron_phpcrond->get_next_run_time();
        if(!empty($options))
        {
            return $res = $this->admin_cron_model->cron_update($id, $options);
        }
    }
    
    /**
     * 检测cron域名
     * @return boolean
     */
    private function check_cron_domain()
    {
        //if(isset($_SERVER['HTTP_HOST']) && preg_match('/^i\.(.*)/is', $_SERVER['HTTP_HOST']) || strpos($_SERVER['HTTP_HOST'], 'dev') !== false)
        //{
        return TRUE;
        // }
        header('Content-Type: text/html; charset=utf-8');
        header("HTTP/1.1 404 Not Found");
        echo '<b>Forbid</b>';
        exit;
    }

    /**
     * 获取所有的cron命令
     * @author
     */
    public function get_all_cron() {

        // 校验token
        $token = $this->input->get('token');
        if ($token != 'AilIverSOn!211@@')
            exit('非法请求');

        $params = array(
            'is_valid' => 1,
        );

        $count_result = $this->admin_cron_model->get_count($params);

        $list = $this->admin_cron_model->get_list($params, '', 0, $count_result['info']);

        $data = array();
        foreach ($list['info'] as $row) {
            $time_rule = $row['minute'] . ' ' . $row['hour'] . ' ' . $row['dom'] . ' '.  $row['month'] . ' ' . $row['dow'];
            $url = ADMIN_XCX_LEJU_HOST . "cron/index/index?id={$row['id']}{$row['params']}&token=".$row['token'];

            if ($row['daemon_status'] == 1 && $row['process_num'] <= 1)
            {
                $data[] = "* * * * * /data1/cronroot/shell/check_process.sh \"curl_daemon.sh {$url}\" >/dev/null 2>&1";
            }
            elseif($row['daemon_status'] == 1 && $row['process_num'] > 1)
            {
                $data[] = "* * * * * /data1/cronroot/shell/check_process.sh \"thread_daemon.sh {$row['process_num']} {$url}\" >/dev/null 2>&1";
            }
            elseif ($row['daemon_status'] == 0 && $row['process_num'] > 1)
            {
                $data[] = $time_rule . " /data1/cronroot/shell/thread_exec.sh {$row['process_num']} \"{$url}\" >/dev/null 2>&1";
            }
            elseif($row['daemon_status'] == 0 && $row['process_num'] <= 1)
            {
                $data[] = $time_rule. " /data1/cronroot/shell/curl_exec.sh \"{$url}\" >/dev/null 2>&1";
            }
            
        }

        $html = implode(PHP_EOL, $data);
        $html .= PHP_EOL;
        echo $html;
    }
}
