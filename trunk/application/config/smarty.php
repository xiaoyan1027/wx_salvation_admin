<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['theme']        = 'default';
$config['template_dir'] = APPPATH . 'views';
//$config['compile_dir']  = $_SERVER['SINASRV_CACHE_DIR'];
//$config['cache_dir']    = $_SERVER['SINASRV_CACHE_DIR'];

$config['compile_dir']  = '/var/log/access_cache';
$config['cache_dir']    = '/var/log/access_cache';
$config['config_dir']   = APPPATH . 'configs';
$config['template_ext'] = '.html';
$config['caching']      = false;
$config['lefttime']     = 60;