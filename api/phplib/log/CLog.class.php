<?php

/**
 * @package Log
 **/

/**
 * @example:
 *
 <?php
 require_once('CLog.class.php');

 $GLOBALS['LOG'] = array(
 	'type'		=> 'COM_LOG',	// or 'LOCAL_LOG', if using local log file
	'level		=> 0x07,		// fatal, warning, notice
	'path'		=> 'log',		// use absolute path if using local log file
	'filename'	=> 'test.log',	// test.log.wf will be the waring/fatal log file
	'stats'		=> array(		// statistics types to be allowed in this module
		'acstat' => 'acstat.sdf.log',
	),
 );

 $str = 'biaji';
 CLog::notice('%s', $str);
 CLog::notice(1, '%s', $str);
 CLog::fatal('%s', $str);
 CLog::warning('%s', $str);
 CLog::debug('%s', $str);
 CLog::statlog('acstat', '%s', $str);
 **/

class CLog
{
	// Log level definition
    const LOG_LEVEL_NONE    = 0x00;
    const LOG_LEVEL_FATAL   = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE  = 0x04;
    const LOG_LEVEL_TRACE   = 0x08;
    const LOG_LEVEL_DEBUG   = 0x10;
    const LOG_LEVEL_ALL     = 0xFF;
    
    // Log type definition
    const LOG_TYPE_COMLOG	= 'COM_LOG';
    const LOG_TYPE_LOCALLOG	= 'LOCAL_LOG';
    
    /**
     * @var array
     */
    public static $logLevelMap = array(
        self::LOG_LEVEL_NONE    => 'NONE',
        self::LOG_LEVEL_FATAL   => 'FATAL',
        self::LOG_LEVEL_WARNING => 'WARNING',
        self::LOG_LEVEL_NOTICE  => 'NOTICE',
        self::LOG_LEVEL_TRACE	=> 'TRACE',
        self::LOG_LEVEL_DEBUG   => 'DEBUG',
        self::LOG_LEVEL_ALL     => 'ALL',
    );
    
    /**
     * @var array
     */
    public static $logTypes = array(
    	self::LOG_TYPE_COMLOG,
    	self::LOG_TYPE_LOCALLOG,
    );

    /**
     * Log output device type, can be "COM_LOG", "LOCAL_LOG", "STDOUT"
     * @var string
     */
    protected $type;
    /**
     * Log level
     * @var int
     */
    protected $level;
    /**
     * Log file path for local log file, or module name for comlog
     * @var string
     */
    protected $path;
    /**
     * Log file name
     * @var string
     */
    protected $filename;
    /**
     * statistics types allowed in this module
     * @var array
     */
    protected $stats;
    
    /**
     * Client IP
     * @var string
     */
    protected $clientIP;
    /**
     * Log Id for current request
     * @var uint
     */
    protected $logid;
    /**
     * PHP start time of current request
     * @var uint
     */
    protected $startTime;

    /**
     * NetComLog instance, invalid when using local log file
     * @var NetComLog
     */
    protected $comlog;
    
    /**
     * @var CLog
     */
    private static $instance = null;
    
    /**
     * @var string
     */
    private static $basicInfo = '';

    /**
     * @var array
     */
    private static $arrBasicInfo = array();
    
    /**
     * Constructor
     * 
     * @param array $conf
     * @param uint $startTime
     */
    private function __construct(Array $conf, $startTime)
    {
    	$this->type		= $conf['type'];
        $this->level	= $conf['level'];
        $this->path		= $conf['path'];
        $this->filename	= $conf['filename'];
        $this->stats	= $conf['stats'];
        if (!is_array($this->stats)) {
        	$this->stats = array();
        }
        
        $this->startTime	= $startTime;
        $this->logId		= $this->__logId();
        $this->clientIP		= $this->__clientIP();
    }

	/**
	 * @return CLog
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			$startTime = defined('PROCESS_START_TIME') ? PROCESS_START_TIME : microtime(true) * 1000;
			self::$instance = new CLog($GLOBALS['LOG'], $startTime);
		}
		
		return self::$instance;
	}

	/**
	 * Logs only for statistics demand
	 * <code>
	 * CLog::statlog($stat, $fmt, ...);
	 * </code>
	 * @param string $stat statistics type
	 * @return int
	 */
    public static function statlog($stat, $fmt)
    {
    	$args = func_get_args();
    	$stat = array_shift($args);
    	return CLog::getInstance()->writeStatLog($stat, $args);
    }
    
    /**
     * Write debug log
     * <code>
     * int CLog::debug([int $depth,] string $fmt[, mixed $args[, mixed $...]]) .
     * </code>
     * @param int $depth Nesting depth relative to the log request point
     * @param string $fmt format string
     * @return int
     */
    public static function debug()
    {
    	$args = func_get_args();
    	return CLog::getInstance()->writeLog(self::LOG_LEVEL_DEBUG, $args);
    }
    
	/**
     * Write trace log
     * <code>
     * int CLog::trace([int $depth,] string $fmt[, mixed $args[, mixed $...]]) .
     * </code>
     * @param int $depth Nesting depth relative to the log request point
     * @param string $fmt format string
     * @return int
     */
    public static function trace()
    {
    	$args = func_get_args();
    	return CLog::getInstance()->writeLog(self::LOG_LEVEL_TRACE, $args);
    }

	/**
     * Write notice log
     * <code>
     * int CLog::notice([int $depth,] string $fmt[, mixed $args[, mixed $...]]) .
     * </code>
     * @param int $depth Nesting depth relative to the log request point
     * @param string $fmt format string
     * @return int
     */
    public static function notice()
    {
    	$args = func_get_args();
    	return CLog::getInstance()->writeLog(self::LOG_LEVEL_NOTICE, $args);
    }
    
	/**
     * Write warning log
     * <code>
     * int CLog::warning([int $depth,] string $fmt[, mixed $args[, mixed $...]]) .
     * </code>
     * @param int $depth Nesting depth relative to the log request point
     * @param string $fmt format string
     * @return int
     */
    public static function warning()
    {
    	$args = func_get_args();
    	return CLog::getInstance()->writeLog(self::LOG_LEVEL_WARNING, $args);
    }
    
	/**
     * Write fatal log
     * <code>
     * int CLog::fatal([int $depth,] string $fmt[, mixed $args[, mixed $...]]) .
     * </code>
     * @param int $depth Nesting depth relative to the log request point
     * @param string $fmt format string
     * @return int
     */
    public static function fatal()
    {
    	$args = func_get_args();
    	return CLog::getInstance()->writeLog(self::LOG_LEVEL_FATAL, $args);
    }

    /**
     * Set logId for current http request
     * @return int
     */
    public static function setLogId($logId)
    {
        return CLog::getInstance()->logId = $logId;
    }
    
    /**
     * Get logId for current http request
     * @return int
     */
    public static function logId()
    {
        return CLog::getInstance()->logId;
    }

    /**
     * Get the real remote client's IP
     * @return string
     */
    public static function getClientIP()
	{
		return CLog::getInstance()->clientIP;
	}

	public static function addBasicInfo($arrBasicInfo)
	{
		self::$arrBasicInfo = array_merge($arrBasicInfo, self::$arrBasicInfo);
		CLog::getInstance()->genBasicInfo();
	}
	
	public static function clearBasicInfo()
	{
		self::$arrBasicInfo = array();
		self::$basicInfo = '';
	}
	
	private function genLogPart($str) {
		return "[ " . $str . "]";
	}
	
	private function genBasicInfo() {
		if(!empty(self::$arrBasicInfo)) {
			foreach (self::$arrBasicInfo as $key => $value) {
				self::$basicInfo .= CLog::getInstance()->genLogPart ( "$key:" . $value ) . " ";
			}
		}
	}
    /**
     * Write log
     * 
     * @param int $level Log level
     * @param array $args format string and parameters
     * @return int
     */
	protected function writeLog($level, Array $args)
	{
		if ($level > $this->level || !isset(self::$logLevelMap[$level])) {
			return 0;
		}
		
		$depth = 1;
		if (is_int($args[0])) {
    		$depth = array_shift($args) + 1;
    	}
		
		$trace = debug_backtrace();
		if ($depth >= count($trace)) {
			$depth = count($trace) - 1;
		}
		$file = basename($trace[$depth]['file']);
		$line = $trace[$depth]['line'];

        $timeUsed = microtime(true)*1000 - $this->startTime;

        $fmt = array_shift($args);
		$str = vsprintf($fmt, $args);
		if(!empty(self::$basicInfo)) {
			$str = self::$basicInfo . $str;
		}
                        
		if ($this->type === self::LOG_TYPE_COMLOG) {
			// added by zhanglei18
        	$str = sprintf("[%s:%d] request_id[%u] uri[%s] exec_time=[%d] %s",
                           $file, $line,
        				   $this->logId,
                           isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                           $timeUsed, $str);
                        
	        $host = empty($_SERVER['HTTP_HOST']) ? '-' : $_SERVER["HTTP_HOST"];
	        $source = empty($_SERVER['HTTP_CLIENTIP']) ? (empty($_SERVER["REMOTE_ADDR"]) ?
	                '-' : $_SERVER["REMOTE_ADDR"]) : $_SERVER["HTTP_CLIENTIP"];
	        $ip = empty($_SERVER['SERVER_ADDR']) ? '-' :$_SERVER['SERVER_ADDR'];
	       	$str = "$host $source $ip $str";
	       	
			switch ($level) {
			    case self::LOG_LEVEL_FATAL:
			    	{
			        	return $this->comlog->fatal($str);
			        	break;
			    	}
				case self::LOG_LEVEL_WARNING:
					{
			        	return $this->comlog->warning($str);
			        	break;
			    	}
			    case self::LOG_LEVEL_NOTICE:
			    	{
			        	return $this->comlog->notice($str);
			        	break;
			    	}
				case self::LOG_LEVEL_TRACE:
					{
			        	return $this->comlog->trace($str);
			        	break;
			    	}
				case self::LOG_LEVEL_DEBUG:
					{
			        	return $this->comlog->debug($str);
			        	break;
			    	}
				default :
					{
						trigger_error ("unknown log level", E_USER_WARNING);
					}
			}
			// return $this->comlog->writeLog($level, $str);
		} elseif ($this->type === self::LOG_TYPE_LOCALLOG) {
        	$str = sprintf( "%s: %s [%s:%d] ip[%s] request_id[%u] uri[%s] exec_time=[%d] %s\n",
                        self::$logLevelMap[$level],
                        date('m-d H:i:s:', time()),
                        $file, $line,
                        $this->clientIP,
                        $this->logId,
                        isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                        $timeUsed, $str);
                        
			$filename = $this->path . '/' . $this->filename;
			if ($level < self::LOG_LEVEL_NOTICE) {
				$filename .= '.wf';
			}
			return file_put_contents($filename, $str, FILE_APPEND);
		} else { // use stdout instead
			echo $str . '<br/>';
			return strlen($str);
		}
    }

    /**
     * Write log string for statistics demand.
     * 
     * @param string $type statistics type
     * @param array $args format string and parameters
     */
	protected function writeStatLog($stat, Array $args)
	{
		if (!isset($this->stats[$stat])) {
			return 0;
		}
		
        $fmt = array_shift($args);
		$str = vsprintf($fmt, $args);
        $str = sprintf( "STAT[%s]: %s ip[%s] request_id[%u] uri[%s] %s\n",
                        $stat,
                        date('m-d H:i:s:', time()),
                        $this->clientIP,
                        $this->logId,
                        isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                        $str);

        if ($this->type === self::LOG_TYPE_COMLOG) {
			return $this->comlog->writeLog(self::LOG_LEVEL_NOTICE, $str);
		} elseif ($this->type === self::LOG_TYPE_LOCALLOG) {
			$filename = $this->path . '/' . $this->stats[$stat];
			return file_put_contents($filename, $str, FILE_APPEND);
		} else { // use stdout instead
			echo $str . '<br/>';
			return strlen($str);
		}
    }
	
	private function __clientIP()
	{
        if (isset($_SERVER['HTTP_CLIENTIP'])) {
			$ip = $_SERVER['HTTP_CLIENTIP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
			$_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1') {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = '127.0.0.1';
		}
		
		$pos = strpos($ip, ',');
		if ($pos > 0) {
			$ip = substr($ip, 0, $pos);
		}
		
		return trim($ip);
    }

	private function __logId()
	{
		$arr = gettimeofday();
		return ((($arr['sec']*100000 + $arr['usec']/10) & 0x7FFFFFFF) | 0x80000000);
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=90 noet: */
