<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BASE_Model extends CI_Model {
    
    //从库
    protected $slave;
    //主库
    protected $master;

    protected $db;

    protected $_succ = 'succ';
    protected $_fail = 'fail';


    public function __construct($table='',$active_group='default') {
        parent::__construct();
        $this->_init($table,$active_group);
    }

    private function _init($table,$active_group) {
        $this->slave = Lib_connect_pool::get_instance($active_group.'_r','database');
        $this->db = $this->slave;
        $this->master = Lib_connect_pool::get_instance($active_group.'_w','database');
        if(!empty($table))
        {
            $this->set_table($table);
        }
    }
    /**
     * 更改当前表名称
     * @param $table
     * @return null
     */
    protected function set_table($table) {
        $this->table = $table;
    }

    /**
     * 添加
     *
     * @param array $data
     * @return mixed
     */
    public function insert($data = array()) {
        $this->master->insert($this->table, $data);
        $insert_id = $this->master->insert_id();
        return $insert_id;
    }
    
    public function insert_ignore($data = array()) {
        $sql = $this->get_insert_sql($data);
        $sql = str_replace("INSERT INTO","INSERT IGNORE INTO",$sql);
        $this->master->query($sql);
        $insert_id = $this->master->insert_id();
        return $insert_id;
    }
    public function insert_duplicate($data = array(),$update_str='') {
        $sql = $this->get_insert_sql($data);
        $sql .= " ON DUPLICATE KEY UPDATE {$update_str}"; 
        return $this->master->query($sql);
    }
    /**
     * 获取insert_sql
     *
     * @param array $data
     * @return mixed
     */
    public function get_insert_sql($data = array()) {
        $sql =$this->master->set($data)->get_compiled_insert($this->table);
        return $sql;
    }
    public function get_last_query() {
        $sql =$this->db->last_query();
        return $sql;
    }
    /**
     * 批量添加
     */ 
    public function insert_batch($data = array())
    {
        return $this->master->insert_batch($this->table, $data);
    }
    /**
     * 获取单条记录信息
     *
     * @return mixed
     */
    function fetch_row($where = '', $fileds = '*', $orderBy = 'id DESC', $groupBy = '', $offset = 0, $limit = 1) {
        if (!empty($where)) {
            $this->db->where($where);
        }

        $this->db->select($fileds);
        if (!empty($orderBy)) {
            $this->db->order_by($orderBy);
        }
        if (!empty($groupBy)) {
            $this->db->group_by($groupBy);
        }
        if (!empty($offset) && !empty($limit)) {
            $this->db->limit($limit, $offset);
        } elseif (!empty($limit)) {
            $this->db->limit($limit);
        }
        $query = $this->db->get($this->table);
        if ($query) {
            $data = $query->row_array();
        }

        if (empty($data)) {
            return array();
        }
        return $data;
    }
    /**
     * 获取主库单条记录信息
     *
     * @return mixed
     */
    function fetch_master_row($where = '', $fileds = '*', $orderBy = 'id DESC', $groupBy = '', $offset = 0, $limit = 1) {
        if (!empty($where)) {
            $this->master->where($where);
        }

        $this->master->select($fileds);
        if (!empty($orderBy)) {
            $this->master->order_by($orderBy);
        }
        if (!empty($groupBy)) {
            $this->master->group_by($groupBy);
        }
        if (!empty($offset) && !empty($limit)) {
            $this->master->limit($limit, $offset);
        } elseif (!empty($limit)) {
            $this->master->limit($limit);
        }
        $query = $this->master->get($this->table);
        if ($query) {
            $data = $query->row_array();
        }

        if (empty($data)) {
            return array();
        }
        return $data;
    }
    /**
     * 获取一条记录
     *
     * @param $id
     * @return array|mixed
     */
    public function fetch_by_id($id) {
        if (empty($id)) {
            return array();
        }

        $result = $this->fetch_row(array('id' => $id));
        return $result;
    }
    /**
     * 获取主库一条记录
     *
     * @param $id
     * @return array|mixed
     */
    public function fetch_master_by_id($id) {
        if (empty($id)) {
            return array();
        }

        $result = $this->fetch_master_row(array('id' => $id));
        return $result;
    }
    /**
     * 获取条数
     *
     * @param string $where
     * @param string $group_by
     * @return int
     */
    public function fetch_count($where = '', $group_by = '') {
        $info = $this->fetch_row($where, 'count(*) as cnt', '', $group_by);
        return $info === false ? 0 : intval($info['cnt']);
    }
	
	/**
     * 获取条数
     *
     * @param string $where
     * @param string $distinct
     * @return int
     */
    public function fetch_count_distinct($where = '', $distinct) {
		if(empty($distinct)) return false;
        $info = $this->fetch_row($where, 'count(DISTINCT '.$distinct.') as cnt');
        return $info === false ? 0 : intval($info['cnt']);
    }
	
    /**
     * 获取主库条数
     *
     * @param string $where
     * @param string $group_by
     * @return int
     */
    public function fetch_master_count($where = '', $group_by = '') {
        $info = $this->fetch_master_row($where, 'count(*) as cnt', '', $group_by);
        return $info === false ? 0 : intval($info['cnt']);
    }
    /**
     * 查询多条记录
     *
     * @param string $condition
     * @param string $fileds
     * @param string $orderBy
     * @param string $groupBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    function fetch_all($condition = '', $order = 'id DESC', $offset = 0, $limit = 20, $group = '', $fileds = '*') {

        if (!empty($condition)) {
            if(is_array($condition))
            {
                foreach($condition as $k=>$v)
                {
                    if(is_array($v))
                    {
                        $this->db->where_in($k,$v);                
                        unset($condition[$k]);
                    }
                }
            }

            $this->db->where($condition);
        }
        $this->db->select($fileds);
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group)) {
            $this->db->group_by($group);
        }
        
        $this->db->offset($offset);
        if(!empty($limit))
        {
            $this->db->limit($limit);
        }
        $query = $this->db->get_where($this->table);
        if ($query->num_rows() > 0) {
            $data = $query->result_array();
        }

        if (empty($data)) {
            return array();
        }
        return $data;
    }
    /**
     * 查询主库多条记录
     *
     * @param string $condition
     * @param string $fileds
     * @param string $orderBy
     * @param string $groupBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    function fetch_master_all($condition = '', $order = 'id DESC', $offset = 0, $limit = 20, $group = '', $fileds = '*') {
        if (!empty($condition)) {
            if(is_array($condition))
            {
                foreach($condition as $k=>$v)
                {
                    if(is_array($v))
                    {
                        $this->master->where_in($k,$v);                
                        unset($condition[$k]);
                    }
                }
            }
            $this->master->where($condition);
        }
        $this->master->select($fileds);
        if (!empty($order)) {
            $this->master->order_by($order);
        }
        if (!empty($group)) {
            $this->master->group_by($group);
        }
        
        $this->master->offset($offset);
        if(!empty($limit))
        {
            $this->master->limit($limit);
        }

        $query = $this->master->get_where($this->table);
        if ($query->num_rows() > 0) {
            $data = $query->result_array();
        }

        if (empty($data)) {
            return array();
        }
        return $data;
    }
    /**
     * @param string $where
     * @param string $filed
     * @return array
     */
    public function fetch_field_all($where = '', $filed = '*') {
        if (!empty($where)) {
            if(is_array($where))
            {
                foreach($where as $k=>$v)
                {
                    if(is_array($v))
                    {
                        $this->db->where_in($k,$v);                
                        unset($where[$k]);
                    }
                }
            }
            $this->db->where($where);
        }
        $this->db->select($filed);

        $query = $this->db->get_where($this->table);

        $data = array();
        if ($query->num_rows() > 0) {
            $data = $query->result_array();
        }
        return $data;
    }
    
    /**
     * @param string $where
     * @param string $filed
     * @return array
     */
    public function fetch_master_field_all($where = '', $filed = '*') {
        if (!empty($where)) {
            if(is_array($where))
            {
                foreach($where as $k=>$v)
                {
                    if(is_array($v))
                    {
                        $this->master->where_in($k,$v);                
                        unset($where[$k]);
                    }
                }
            }
            $this->master->where($where);
        }
        $this->master->select($filed);

        $query = $this->master->get_where($this->table);

        $data = array();
        if ($query->num_rows() > 0) {
            $data = $query->result_array();
        }
        return $data;
    }
    /**
     * 设置插入更新的数据
     */ 
    public function set($key, $value = '', $escape = NULL)
    {
        $this->master->set($key, $value = '', $escape = NULL);
    }
    /**
     * 更新数据
     *
     * @param $data
     * @param $where
     * @return mixed
     */
    public function update($data, $where ,$escape = TRUE) {
        if (empty($data) || empty($where)) {
            return false;
        }
        $this->master->set($data,'',$escape);
        if(is_array($where))
        {
            foreach($where as $k=>$v)
            {
                if(is_array($v))
                {
                    $this->master->where_in($k,$v);
                    unset($where[$k]);
                }
            }
        }
        $this->master->where($where);
        $result = $this->master->update($this->table);
        return $this->master->affected_rows();
    }

    /**
     * 根据id更新数据
     *
     * @param $data
     * @param $where
     * @return mixed
     */
    public function update_by_id($id, $data = array(),$escape = TRUE) {
        if (empty($data) || empty($id)) {
            return false;
        }
        $this->master->set($data,'',$escape);
        $this->master->where(array('id' => $id));
        $result = $this->master->update($this->table);
        return $this->master->affected_rows();
    }

    /**
     * 删除数据
     *
     * @param $where
     * @return bool
     */
    public function delete($where) {
        if (empty($where)) {
            return false;
        }
        $this->master->delete($this->table, $where);
        return $this->master->affected_rows();
    }

    /**
     * 删除数据
     *
     * @param $where
     * @return bool
     */
    public function delete_by_id($id) {
        if (empty($id)) {
            return false;
        }
        $this->master->delete($this->table, array('id' => $id));
        return $this->master->affected_rows();;
    }



    public function exec_sql($sql = '', $db_type= 'default') {
        if (empty($sql)) {
            return false;
        }

        if ($db_type == 'master') {
            $query = $this->master->query($sql);
        } else {
            $query = $this->db->query($sql);
        }

        if (!is_object($query)) {
            return $query;
        }

        $result = array();
        if( $query ){
            $result = $query->result_array();
        }
        return $result;
    }

    /**
     * Build SQL
     * @param  array $config
     * @return string
     */
    public function build_sql($config) {
        $condition = array();
        foreach ($config as $k => $v) {
            if (is_array($v)) {
                switch ($v[0]) {
                    case "like":
                        if (is_array($v[1])) {
                            $condition[] = "(" . $k . " LIKE " . "'%{$v[1][0]}%' or " . $k . " LIKE " . "'%{$v[1][1]}')";
                        } else {
                            $condition[] = $k . " LIKE " . "'%{$v[1]}%'";
                        }
                        break;
                    case "gt":
                        $condition[] = $k . " >= " . "'{$v[1]}'";
                        break;
                    case "lt":
                        $condition[] = $k . " <= " . "'{$v[1]}'";
                        break;
                    case "nlt":
                        $condition[] = $k . " < " . "'{$v[1]}'";
                        break;
                    case "ngt":
                        $condition[] = $k . " > " . "'{$v[1]}'";
                        break;
                    case "eq":
                        $condition[] = $k . " = " . "'{$v[1]}'";
                        break;
                    case "neq":
                        $condition[] = $k . " != " . "'{$v[1]}'";
                        break;
                    case "between":
                        $str = explode(",", $v[1]);
                        if ($str[0] && $str[1]) {
                            $condition[] = $k . " BETWEEN " . "'{$str[0]}'" . " AND " . "'{$str[1]}'";
                        } else {
                            if ($str[0]) {
                                $condition[] = $k . " >= " . "'{$str[0]}'";
                            }
                            if ($str[1]) {
                                $condition[] = $k . "<=" . "'{$str[1]}'";
                            }
                        }
                        break;
                    case "in":
                        $condition[] = $k . " IN ('" . implode("', '", $v['1']) . "')";
                        break;
                }
            } elseif(strpos($k, '>=')) {
                $condition[] = $k . $v;
            } elseif(strpos($k, '<=')) {
                $condition[] = $k . $v; 
            }  else {
                $condition[] = $k . " = '" .  $v ."'";
            }
        }
        $condition = implode(' AND ', $condition);
        return $condition;
    }

    /**
     * 将要输出的数据格式成需要的形式返回
     * @param boolean $is_succ
     * @param mixed $info
     * @return array
     */
    protected function _formatreturndata($is_succ, $info = null) {
        $res = array();
        if ($is_succ) {
            $res['result'] = $this->_succ;
            $res['info'] = $info;
        } else {
            $res['result'] = $this->_fail;
            $res['reason'] = $info;
        }
        return $res;
    }
}
