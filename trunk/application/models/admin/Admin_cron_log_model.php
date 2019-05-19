<?php
class Admin_cron_log_model extends BASE_Model
{
    const TABLE_CRON_LOG = 'pet_cron_log';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('lib_mongo');
    }

    /**
     * 添加任务日志
     * @param array $data
     */
    public function log_add($data)
    {
        if(!is_array($data) || count($data) == 0)
        {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->lib_mongo->insert(self::TABLE_CRON_LOG,$data);
        if(!empty($res['err']))
        {
            return $this->_formatreturndata(false, '任务日志添加失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    public function log_update($id, $data)
    {
        $res = $this->lib_mongo->update(self::TABLE_CRON_LOG, array('_id'=>$id),$data);
        return $res;
    }
    
    public function get_list( $where = array(),$select = array(), $sorts = array(), $limit = FALSE, $offset = FALSE)
    {
        $res = $this->lib_mongo->get(self::TABLE_CRON_LOG, $where,$select, $sorts, $limit, $offset);
        return $res;
    }
    
    public function get_count($where)
    {
        $log_count = $this->lib_mongo->get_count(self::TABLE_CRON_LOG, $where);
        return $log_count;
    }
}