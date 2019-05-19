<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BASE_Router  extends CI_Router{

	
	public function __construct($routing = NULL)
	{
	   parent::__construct($routing);
	}

	/**
	 * Set default controller
	 *
	 * @return	void
	 */
	protected function _set_default_controller()
	{
		if (empty($this->default_controller))
		{
			show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
		}

		// Is the method being specified?
        $df = explode("/",$this->default_controller);
        $cnt = count($df);
        if($cnt == 3)
        {
            list($directory,$class,$method) = $df;
        }
		elseif($cnt == 2)
        {
            list($class,$method) = $df;
        }
        else
        {
            list($class) = $df;
            $method = 'index';
        }
        $directory = $directory ? $directory."/" : '';
		if ( ! file_exists(APPPATH.'controllers/'.$directory.ucfirst($class).'.php'))
		{
			// This will trigger 404 later
			return;
		}
        $this->set_directory($directory);
		$this->set_class($class);
		$this->set_method($method);

		// Assign routed segments, index starting from 1
		$this->uri->rsegments = array(
			1 => $class,
			2 => $method
		);

		log_message('debug', 'No URI present. Default controller set.');
	}
}
