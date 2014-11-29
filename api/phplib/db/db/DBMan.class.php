<?php
/*
* class DBMan
*
* DB连接管理器，其管理策略用于UI页面，不适用后台脚本，后台脚本直接用DB类自己连接
*
* */

require_once('DB.class.php');


interface IHostSelector
{
    public function select(DBMan $dbman, $key = NULL);
}

interface IStatusMan
{
    public function load($host, $port);
    public function save($host, $port, $status);
    public function clean($host, $port);
    public function cleanAll();
}

class RandSelector implements IHostSelector
{
    public function select(DBMan $dbman, $key = NULL)
    {
        if(!count($dbman->validHosts))
        {
            return false;
        }
        return array_rand($dbman->validHosts);
    }
}

class StatusManFile implements IStatusMan
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
        if(!file_exists($path))
        {
            @mkdir($path, 0777, true);
        }
    }

    public function load($host, $port)
    {
        $file = $this->getFile($host, $port);
        if(!file_exists($file))
        {
            return NULL;
        }
        $ret = unserialize(file_get_contents($file));
        return ($ret !== FALSE)?$ret:NULL;
    }

    public function save($host, $port, $status)
    {
        $file = $this->getFile($host, $port);
        return file_put_contents($file, serialize($status)) > 0;
    }

    public function clean($host, $port)
    {
        @unlink($this->getFile($host, $port));
    }

    public function cleanAll()
    {
        $dir = @opendir($this->path);
        if(!$dir)
        {
            return true;
        }
        while(($file = readdir($dir)) !== false)
        {
            @unlink("{$this->path}/$file");
        }
        closedir($dir);
        return true;
    }

    private function getFile($host, $port)
    {
        return $this->path."/$host:$port";
    }
}

class DBMan
{
    // config, modify at the very begining
    public static $RETRY_INTERVAL = 15;
    public static $ENABLE_PROFILING = false;
    public static $CONN_TIMEOUT = 1;
    public static $CONN_FLAGS = 0;
    public static $MAX_TRY_COUNT = 5;

    private $userConf = array('uname' => NULL, 'passwd' => NULL); // user config for all hosts
    private $dbname = NULL;             // default db name

    private $allHosts = array();        // save all hosts
    private $validHosts = array();      // save valid hosts
    private $failedHosts = NULL;        // save failed hosts, which have been down
    private $justFailedHosts = NULL;    // save failed hosts, which was down just now
    private $currentHostIndex = NULL;   // index of the host that currently in use in the allHosts array

    private $hostSelector = NULL;       // IHostSelector object to select host
    private $statusMan = NULL;          // IStatusMan object to load and save hosts' status

    private $db = NULL;                 // DB object that currently in use

    public function __construct(
        $arrHosts,                   // array(array($host, $port), array(...), ...)
        $userConf,                   // array('uname' => 'foo', 'passwd' => 'bar')
        $dbname,                     // 'mydb'
        IHostSelector $hostSelector, // new RandSelector()
        IStatusMan $statusMan = NULL // new StatusManFile('tmp')
    )
    {
        $this->allHosts = $arrHosts;
        $this->validHosts = $arrHosts;
        $this->userConf = $userConf;
        $this->dbname = $dbname;
        $this->hostSelector = $hostSelector;

        $now = time();

        // load status
        if($statusMan !== NULL)
        {
            $this->statusMan = $statusMan;
            foreach($this->allHosts as $key => &$host)
            {
                $status = $statusMan->load($host[0], $host[1]);
                if(!empty($status))
                {
                    // filter failed host
                    if(($status['last_failed_time'] + DBMan::$RETRY_INTERVAL) > $now)
                    {
//                        echo "filter: {$host[0]}:{$host[1]}\n";
                        $this->failedHosts[$key] = $host;
                        unset($this->validHosts[$key]);
                    }
                    /*
                    else
                    {
                        $statusMan->clean($host[0], $host[1]);
                    }
                    */
                    $host[2] = $status;
                }
            }
        }
    }

    public function getDB($key = NULL, $getNew = false, $replace = true)
    {
        if($this->db !== NULL && !$getNew)
        {
            return $this->db;
        }

        $db = new DB(DBMan::$ENABLE_PROFILING);
        if(DBMan::$CONN_TIMEOUT > 0)
        {
            $db->setConnectTimeOut(DBMan::$CONN_TIMEOUT);
        }
        // set mysql error handler for retry
        set_error_handler( array( $this, "mysqlErrorHandler" ) );
        // 尝试的次数
        $try_count = 0;
        while(true)
        {
            if(count($this->validHosts) == 0 ||
                ($index = $this->hostSelector->select($this, $key)) === false)
            {
                return false;
            }
            // return the same host
            if($this->currentHostIndex === $index)
            {
                return $this->db;
            }
            // do connect
            $host = $this->allHosts[$index];
            // modify by linxiaogang
            try{
                $ret = $db->connect(
                    $host[0],
                    $this->userConf['uname'],
                    $this->userConf['passwd'],
                    $this->dbname,
                    $host[1],
                    DBMan::$CONN_FLAGS
                );
                // got it
                if( $ret != false )
                {
                    break;
                }
                // record failed host
                //$this->_recordFailedHost($index);
                // try count exceeded
                if(++$try_count == DBMan::$MAX_TRY_COUNT)
                {
                    $db = false;
                    break;
                }
            } catch ( Exception $ex ) {
                if ( $try_count < DBMan::$MAX_TRY_COUNT ) {
                    $try_count++;
                    CLog::warning( 'Connect to Mysql failed, try_time[%s], error_no[%s], error_msg[%s]', $try_count, $ex->getCode(), $ex->getMessage() );
                    continue;
                } else {
                    CLog::fatal( 'Connect to Mysql failed, try_time[%s], error_no[%s], error_msg[%s]', $try_count, $ex->getCode(), $ex->getMessage() );
                    $db = false;
                    break;
                }
            }
        }
        restore_error_handler(); 
        if ( $db != false ) {
            if($this->db == NULL || $replace)
            {
                $this->currentHostIndex = $index;
                $this->db = $db;
            }
        }
        return $db;
    }
/*
    public function setFailed()
    {
        if($this->currentHostIndex !== NULL)
        {
            $this->_recordFailedHost($this->currentHostIndex);
            $this->db = NULL;
        }
    }
*/
    // record failed host
    private function _recordFailedHost($index)
    {
        $status = array('last_failed_time' => time());
        $this->allHosts[$index][2] = $status;
        unset($this->validHosts[$index]);
        $this->justFailedHosts[$index] = $this->allHosts[$index];
        $this->failedHosts[$index] = $this->allHosts[$index];
        // save status
        if($this->statusMan !== NULL)
        {
            $host = $this->allHosts[$index];
            $this->statusMan->save($host[0], $host[1], $status);
        }
    }

    public function __get($name)
    {
        if($name == 'db')
        {
            return $this->getDB();
        }
        else if(property_exists($this, $name))
        {
            return $this->$name;
        }
        else if($name == 'currentHost')
        {
            if($this->currentHostIndex === NULL)
            {
                return NULL;
            }
            return $this->allHosts[$this->currentHostIndex];
        }
        return NULL;
    }

    // add by linxiaogang
    public static function mysqlErrorHandler( $errno, $errstr, $errfile, $errline ) 
    {
        throw new Exception($errstr, $errno);
    } 
}
?>
