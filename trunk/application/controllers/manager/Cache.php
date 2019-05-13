<?php

class Cache extends BASE_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $data = array();
        $key = $this->input->get_post('key');
        $type = $this->input->get_post('type') ? $this->input->get_post('type') : 'string';
        if ($key) {
            $del = $this->input->get('del');
            if(!empty($del))
            {
                $result = $this->lib_redis->del($key);
            }
            else
            {
                if($type == 'string')
                {
                    $result = $this->lib_redis->get($key);
                }
                elseif($type == 'hash')
                {
                    $result = $this->lib_redis->hgetall($key);
                    $result = print_r($result,true);
                }
                elseif($type == 'keys')
                {
                    $result = $this->lib_redis->keys($key);
                    if(count($result) > 1000){
                        $result = array_slice($result,0,1000);
                    }
                    $result = print_r($result,true);
                }
            }
            
            $data['result'] = $result;
        }


        //print_r($data);
        $this->assign('data', $data);
        $this->display('list');
    }

}