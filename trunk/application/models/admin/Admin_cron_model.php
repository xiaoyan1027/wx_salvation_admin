<?php

class Admin_cron_model extends BASE_Model {

    const TABLE_ADMIN_CRON = 'admin_cron';

    public function __construct() {
        parent::__construct();
        $this->set_table(self::TABLE_ADMIN_CRON);
    }

    /**
     * 获取任务记录
     * @param array|string $condition
     * @param string $order
     * @param string $limit
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_list($condition = '', $order = '', $offset = 0, $limit = '20') {
        $res = $this->fetch_all($condition, $order, $offset, $limit);
        if (!$res) {
            return $this->_formatreturndata(false, '任务记录获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 获取任务记录总数
     * @param array|string $condition
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_count($condition = '') {
        $res = $this->fetch_count($condition);
        if ($res === false) {
            return $this->_formatreturndata(false, '任务记录总数获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 添加任务
     * @param array $data
     */
    public function cron_add($data) {
        if (!is_array($data) || count($data) == 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->insert($data);
        if (!$res) {
            return $this->_formatreturndata(false, '任务添加失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 任务修改
     * @param int $id
     * @param array $data
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function cron_update($id, $data) {
        $id = intval($id);
        if ($id <= 0 || !is_array($data) || count($data) == 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }

        $res = $this->update_by_id($id, $data);
        if ($res === false) {
            return $this->_formatreturndata(false, '任务修改失败！');
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
            return $this->_formatreturndata(false, '任务数据获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 根据任务id删除任务
     * @param int $id
     * @return array
     * @author yapeng1@leju.com 2012-07-12
     */
    public function cron_delete($id) {
        $id = intval($id);
        if ($id <= 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->delete_by_id($id);
        if (!$res) {
            return $this->_formatreturndata(false, '任务删除失败！');
        }
        return $this->_formatreturndata(true, $res);
    }
}