<?php

class Admin_log_model extends BASE_Model {

    const TABLE_ADMIN_LOG = 'admin_log';

    public function __construct() {
        parent::__construct();
        $this->set_table(self::TABLE_ADMIN_LOG);
    }

    /**
     * 获取日志记录
     * @param array|string $condition
     * @param string $order
     * @param string $limit
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_list($condition = '', $order = '', $limit = '') {
        $condition = $this->format_condition($condition);
        $res = $this->fetch_all($condition, $order, $limit);
        if (!$res) {
            return $this->_formatreturndata(false, '日志记录获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 获取日志记录总数
     * @param array|string $condition
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_count($condition = '') {
        $condition = $this->format_condition($condition);
        $res = $this->fetch_count($condition);
        if ($res === false) {
            return $this->_formatreturndata(false, '日志记录总数获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 添加日志
     * @param array $data
     */
    public function add($data) {
        if (!is_array($data) || count($data) == 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->insert($data);
        if (!$res) {
            return $this->_formatreturndata(false, '日志添加失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 根据id获取
     */
    public function get_infos($id) {
        $id = intval($id);
        if ($id <= 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }

        $res = $this->fetch_by_id($id);
        if (!$res) {
            return $this->_formatreturndata(false, '日志数据获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 添加日志
     * @param array $data
     * @param string $desc
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function log_add($data = array(), $desc = '') {
        $user_id = isset($_COOKIE['pet_admin_uid']) ? $_COOKIE['pet_admin_uid'] : '';
        $log_data = array(
            'operator' => $user_id,
            'site' => $this->router->directory,
            'ctl' => $this->router->class,
            'act' => $this->router->method,
            'data' => serialize($data),
            'desc' => $desc,
            'time' => time(),
            'ip' => get_client_ip(),
            'uri' => $_SERVER['REQUEST_URI'],
        );
        $res = $this->add($log_data);

        return $res;
    }

    /**
     * 格式化条件
     * @param array|string $data
     */
    public function format_condition($data, $extra_sql = '') {
        $condition = '';
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                if (empty($value)) continue;
                switch ($key) {
                    case 'start_time':
                        $condition .= " AND `time` >= '" . strtotime($value) . "' ";
                        break;
                    case 'end_time':
                        $condition .= " AND `time` <= '" . strtotime($value) . "' ";
                        break;
                    default:
                        $condition .= " AND `{$key}` = '{$value}'";
                        break;
                }

            }
            $condition = substr($condition,4);
        } else {
            $condition = $data;
        }

        return $condition;
    }

    public function get_sql_type($sql) {
        $result = $this->db->is_write_type($sql);
        return $result;
    }
}