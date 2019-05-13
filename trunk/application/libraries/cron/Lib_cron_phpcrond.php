<?php
/**
 * cron自动执行程序, 根据当前时间结合cron配置cron执行条件是否成立，成立执行
 * 不成立不执行
 *
 * @author:Riven
 * @date:2011-09-07
 */

include 'Lib_cron_cronentry.php';
include 'Lib_cron_tdcron.php';
 class Lib_cron_phpcrond {

        private $is_exec = 0;//执行条件是否满足,当五个执行条件都满足时值为等于5
        private $cron_key =  array('minute', 'hour', 'dom', 'month', 'dow');//传递的cron key值
        private $now_time;//当前时间详情
        private $command;//执行的命令
        private $exec_act;//执行的方法
        private $exec_mod;//执行的控制器
        private $_nxt_exec_time = 0;//下次执行时间
        private $_last_exec_time = 0;//上次执行时间
        private $CI;


        /**
         * 构造函数, 获得执行的cron配置
         *
         */
        public function __construct()
        {
            $this->now_time = strtotime(date('Y-m-d H:i', time()));
            $this->cron_dir = $_SERVER["DOCUMENT_ROOT"] . '/cron/run/';
            //$this->cron_dir = dirname(__FILE__) . '/../../cron/';

            $this->CI = & get_instance();
        }

        /**
         * 检查是否满足cron执行条件
         *@param $cron_config array example:
         *        array('m'=> , 'h' =>, 'dom' => , 'mod' => , 'dow' => , 'command' => , 'exec_file' => '')
         */
        public function check_cron($cron_config, $exec_immediate = 0)
        {
            $this->command = $cron_config['command'];
            $this->exec_mod = $cron_config['exec_mod'];
            $this->exec_act = $cron_config['exec_act'];
            $this->is_exec = 0;
            $cron_str = $this->get_cron_string($cron_config);
            if($exec_immediate == 1)
            {
                $this->is_exec = 1;
                $this->set_next_run_time($cron_str);
                $this->_last_exec_time = $this->now_time;
            }
            else
            {
                $this->set_next_run_time($cron_str);
                $this->set_last_run_time();
                if(lib_cron_tdcron::checkExecCase($cron_str, $this->now_time))
                {
                    $this->is_exec = 1;
                }
            }
        }

        //执行cron
        public function run_cron()
        {
            if($this->is_exec == 1)
            {
               return $this->exec();
            }
        }

        /**
         * 获得要执行的cron 字符串格式
         *
         * @param array  cron数组
         */
        public function get_cron_string($cron_config)
        {
            $cron_arr = array();
            foreach($this->cron_key as $v)
            {
                if(isset($cron_config[$v]))
                {
                    $cron_arr[$v] = $cron_config[$v];
                }
            }
            return implode(' ', $cron_arr);
        }
        /**
         * 获得下次cron执行时间
         * @param $cron string example:'5 0-23/1 * * *'
         *
         * @return int unix timestamp
         */
        public function set_next_run_time($cron)
        {
            $this->_nxt_exec_time = lib_cron_tdcron::getNextOccurrence($cron, $this->now_time + 60);
        }

        /**
         * 获得下次cron执行时间
         * @param $cron string example:'5 0-23/1 * * *'
         *
         * @return int unix timestamp
         */
        public function set_last_run_time()
        {
            $this->_last_exec_time = $this->now_time;
        }

        /**
         * 获得最后一次运行时间
         * @return int 上次运行unixtimestamp
         */
        public function get_last_run_time()
        {
            return $this->_last_exec_time;
        }

        /**
         * 获得下次运行时间
         * @return int 下次运行unixtimestamp
         */
        public function get_next_run_time()
        {
            return $this->_nxt_exec_time;
        }

        /**
         *  获得任务是否执行的状态
         */
        public function get_exec_status()
        {
            return $this->is_exec;
        }

        /**
         * 执行cron任务
         */
        public function exec()
        {
            switch($this->command)
            {
                case 'php':
                    if(empty($this->exec_mod) || empty($this->exec_act)) break;
                    $this->CI->load->model('cron/c_base_model');
                    $class_name = $this->exec_mod;
                    //$class = new $class_name;

                    $model = $class_name . '_model';
                    $this->CI->load->model('cron/'. $model);
                    $act = $this->exec_act;
                    $result = $this->CI->$model->$act();
                    return $result;
                    break;
            }
        }
 }
