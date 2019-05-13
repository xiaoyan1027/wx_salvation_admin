<?php

    $config['redis']['flexihash'] = false;
    //缓存配置,为主从配置
//    $redis_defult = explode(':', $_SERVER['SINASRV_REDIS_HOST']);
//    $redis_slave = explode(':', $_SERVER['SINASRV_REDIS_HOST_R']);
    $redis_defult = '127.0.0.1';
    $redis_slave = '6379';
    $config['redis_default']['host'] = $redis_defult[0]; // IP address or host
    $config['redis_default']['port'] = $redis_defult[1]; // Default Redis port is 6379
    $config['redis_default']['password'] = ''; // Can be left empty when the server does not require AUTH

    $config['redis_slave']['host'] = $redis_slave[0]; // IP address or host
    $config['redis_slave']['port'] = $redis_slave[1]; // Default Redis port is 6379
    $config['redis_slave']['password'] = ''; // Can be left empty when the server does not require AUTH

//    $redis_cluster = $_SERVER['SINASRV_REDIS_HOST'];
//    $config['cluster'] = $redis_cluster;

    $config['key_set'] = array(
        'real_time_task_queue' => 'real_time_task_queue_%s',//实时任务队列
        'wait_task_queue' => 'wait_task_queue_%s',//等待处理的任务队列
        'delay_task_queue' => 'delay_task_queue',//延迟队列
        'memus_data_cache' => 'memus_data_xcx_cache',//菜单数据缓存
        'user_right_cache' => 'user_admin_right_cache_%s',//用户权限缓存
        'user_cache' => 'user_cache',//后台用户缓存
        'api_logs' => 'api_logs',//api日志队列
        'timer_task_error_list' => 'timer_task_error_list',//定时任务错误队列
        'error_task_queue' => 'error_task_queue',//用户任务错误队列
        'error_rehand_count' => 'error_rehand_count',//异常错误重新处理的次数

        
    );
