<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class BASE_Log extends CI_Log {

    public $mongoClient;
    public $mongoDB;
    public $mongoCollection;
    public $mongoCursor;
    public $logTable = "pet_system_log";
    private $_inserted_id = false;
    private $_query_safety = true;
    private $_conf; 
    
	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();
        //require(APPPATH.'config/mongo.php');
        //$this->_conf = $config['default']['w'];
	}
    
    /**
     * ����mongo
     */ 
    public function connect()
    {
        $conf = $this->_conf;
        $connection_string = 'mongodb://'.$conf['user'].':'.$conf['pass'].'@'.$conf['host'].':'.$conf['port'];
        try{
            $this->mongoClient = $this->mongoClient ? $this->mongoClient : new MongoClient($connection_string, array("connect" => true,'db'=>$conf['name']));
            $this->select_db($conf['name']);
            return $this; 
        }
        catch(MongoConnectionException $e)
        {
            exit("Unable to connect to MongoDB: {$e->getMessage()}");
        }
            
    }
    /**
     * �ر�����
     */ 
    public function close($conn = TRUE)
    {
        $this->mongoClient->close($conn);
    }
    /**

     * �л�db
     * @param $database
     */
    public function select_db($database)
    {
        try{
            $this->mongoDB = $this->mongoClient->{$database};
            return $this->mongoDB;
        }
        catch (Exception $e)
        {
            exit("Unable to switch Mongo Databases: {$e->getMessage()}");
        }
    }
    
    /**
     * 
     * ����һ������
     * @param $collection
     * @param $insert
     * @desc ��������ڲ���$insert����"_id"ֵ���贫���ã��磺$b = array('x' => 3);$ref = &$b;$mongo->insert('test',$ref);
     */
    public function insert($collection = "", $insert = array()) 
    {

        if($collection == '' || !$insert)
        {
            return false;
        }
        $this->_inserted_id = FALSE;
        try{
            $res = $this->mongoDB->selectCollection($collection)->insert($insert, array("w" => $this->_query_safety));
            if (isset($insert['_id'])) 
            {
                $this->_inserted_id = $insert['_id'];
            }
            return $res;
        }
        catch(Exception $e)
        {
            exit("Insert of data into MongoDB failed: {$e->getMessage()}");
        }
    }

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string	$level 	The error level: 'error', 'debug' or 'info'
	 * @param	string	$msg 	The error message
	 * @return	bool
	 */
	public function write_log($level, $msg)
	{

		$level = strtoupper($level);

		if (( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
			&& ! isset($this->_threshold_array[$this->_levels[$level]]))
		{
			return FALSE;
		}
        
        $this->connect();
        
		$message = '';

		// Instantiating DateTime with microseconds appended to initial date is needed for proper support of this format
		if (strpos($this->_date_fmt, 'u') !== FALSE)
		{
			$microtime_full = microtime(TRUE);
			$microtime_short = sprintf("%06d", ($microtime_full - floor($microtime_full)) * 1000000);
			$date = new DateTime(date('Y-m-d H:i:s.'.$microtime_short, $microtime_full));
			$date = $date->format($this->_date_fmt);
		}
		else
		{
			$date = date($this->_date_fmt);
		}

		$message .= $msg;//$this->_format_line($level, $date, $msg);
        
        $data = array(
            'host'  => $_SERVER['HTTP_HOST'],
            'page_url' => CUR_URL,
            'level' => $level,
            'date' => $date,
            'message' => $message
        );
		
        $res = $this->insert($this->logTable,$data);
        
        $this->close();
        
        return $res;
        
	}
}
