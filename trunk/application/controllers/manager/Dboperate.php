<?php

class Dboperate extends BASE_Controller {
    private $_users;

    private $_ips;

    private $_data_dboperate;

    public function __construct() {
        //数据库操作打开报错
        ini_set('display_errors', 'On');
        error_reporting(E_ALL);

        parent::__construct();
        $this->_set_ips();
        $this->_set_users();

        $this->load->model('admin/admin_log_model');
    }

    /**
     * 设置权限用户
     */
    private function _set_users() {
        $this->_users = array(
            '1',
            
        );
    }

    /**
     * 设置允许的ip
     */
    private function _set_ips() {
        $this->_ips = array(
            '127.0.0.1',
            
        );
    }

    private $db_keys = array(
        'admin',
    );

    /**
     * 检查ip是否允许
     */
    private function _check_ip() {
        $client_ip = get_client_ip();
        $return = array('result' => true, 'ip' => $client_ip);
        //限制的ip地址为123.124.163.xxx;202.106.54.xxx;10.207.xxx.xxx
        if (!in_array($client_ip, $this->_ips)) {
            $flag = false;
            $ip_array = explode('.', $client_ip);
            if ($ip_array[0] == '202' && $ip_array[1] == '106' && $ip_array[2] == '54') {
                $flag = true;
            }

            if (!$flag && $ip_array[0] == '123' && $ip_array[1] == '124' && $ip_array[2] == '163') {
                $flag = true;
            }

            if (!$flag && $ip_array[0] == '10' && $ip_array[1] == '207') {
                $flag = true;
            }
            $return['result'] = $flag;
        }
        return $return;
    }

    /**
     * 检测用户权限
     */
    private function _check_user() {
        $user_id = $this->login_model->get_admin_id();
        if (in_array($user_id, $this->_users)) {
            return true;
        }
        return false;
    }

    /**
     * 数据库操作界面
     */
    public function index() {
        //检测ip
        /*
        $res = $this->_check_ip();
        if(!$res['result'])
        {
            die('您的IP('.$res['ip'].')地址不在允许的范围内！');
        }
        */
        $res = $this->_check_user();
        if (!$res) {
            $this->assign('check_user_fail', true);
        }

        //$db_keys = $this->load->config('database');
        $db_keys = $this->db_keys;
        $this->assign('db_keys', $db_keys);
        $this->display('dboperate');
    }

    /**
     * 操作数据库
     */
    public function operate() {
        ini_set('memory_limit', '512M');
        $key = $this->input->post('key');
        if ($key != '%$#@#@@#()00#$peer') die();
        //$sqls = explode( ';', $_POST['sql'] );
        $sqls[] = $this->input->post('sql');
        if (empty($sqls)) {
            $this->show_message('Sql语句不能为空！', '/manager/dboperate/index');
        }
        $action = $this->input->post('action');
        $format = $this->input->post('format');
        $db_targer = $this->input->post('db_targer');

        $db_keys = $this->db_keys;
        if (!in_array($db_targer, $db_keys)) {
            $this->show_message('数据库选择错误');
        }

        //设置具体操作的数据库

        switch ($action) {
            //执行某条sql
            case 'query':
                $is_open = $this->_check_user();
                if (!$is_open) {
                    $this->show_message('没有权限执行sql，请联系管理员！', '/manager/dboperate/index');
                }

                foreach ($sqls as $sql) {
                    if ($sql == '') continue;
                    $sql = str_replace('\\', '', $sql);
                    //判断环境检测是否包含where条件
                    //记录日志
                    //todo
                    $res = $this->admin_log_model->exec_sql($sql, 'master');

                    //操作日志
                    $this->admin_log_model->log_add(array('sql' => $sql));
                    dump($res);
                }
                break;
            case 'fetch':
                foreach ($sqls as $sql) {
                    if ($sql == '') continue;
                    $sql = str_replace('\\', '', $sql);
                    //记录日志

                    //判断是否是select语句
                    $sql_type = $this->admin_log_model->get_sql_type($sql);
                    if ($sql_type) {
                        $this->show_message('您暂时没有执行sql操作的权限,如果要开通请联系管理员！', '/manager/dboperate/index');
                    }

                    //todo
                    $res = $this->admin_log_model->exec_sql($sql);

                    //操作日志
                    $this->admin_log_model->log_add(array('sql' => $sql));

                    if (empty($res)) {
                        $this->show_message('没有数据返回！', '/manager/dboperate/index');
                    }
                    switch ($_POST['format']) {
                        //输出数组格式
                        case 'arr':
                            echo "SQL:" . $sql . "<br /><br />";
                            dump($res);
                            break;
                        //csv格式输出
                        case 'csv':
                            echo "SQL:" . $sql . "<br /><br />";
                            echo "<pre>\r\n";
                            $str = '';
                            foreach ($res as $result) {
                                foreach ($result as $r) {
                                    $str .= $r . ',';
                                }
                                $str = substr($str, 0, -1);
                                $str .= "\r\n";
                            }
                            echo $str, '</pre>';
                            break;
                        case 'table':
                            $this->assign('res', $res);
                            $this->assign('sql', $sql);

                            $this->display('dboperate_res');
                            break;
                    }
                }
                break;
        }

        $this->assign('action', $action);
        $this->assign('db_targer', $db_targer);
    }
}