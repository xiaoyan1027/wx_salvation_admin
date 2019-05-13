<?php

class Admin_user_extend_model extends BASE_Model
{

    const TABLE_ADMIN_USER_EXTEND = 'admin_users_extend';
    public function __construct () {
        parent::__construct();
        $this->set_table(self::TABLE_ADMIN_USER_EXTEND);
    }


    /**
     * 获取用户详情
     *
     * @param array $params
     * @return array|mixed
     */
    public function get_user_ext_detail($params = array()) {
        if (empty($params)) {
            return array();
        }

        $result = $this->fetch_row($params);
        return $result;
    }

    
    /**
     * 获取管理员extend记录
     * @param array|string $condition
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_list($condition = '')
    {
        $condition = $this->build_sql($condition);
        $res = $this->fetch_all($condition);
        return $res;
    }
}
