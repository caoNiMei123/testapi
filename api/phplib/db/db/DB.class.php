<?php
/*
* class DB
*
* get samples from the "test" dir
* get manual from the "doc" dir
*
**/


class DB
{
    const T_NUM = 'n';
    const T_NUM2 = 'd';
    const T_STR = 's';
    const T_RAW = 'S';
    const T_RAW2 = 'r';
    const V_ESC = '%';

    // hook types
    const HK_BEFORE_QUERY = 0;
    const HK_AFTER_QUERY = 1;

    // query result types
    const FETCH_RAW = 0;    // return raw mysqli_result
    const FETCH_ROW = 1;    // return numeric array
    const FETCH_ASSOC = 2;  // return associate array
    const FETCH_OBJ = 3;    // return DBResult object

    private $mysql = NULL;
    private $dbConf = NULL;
    private $isConnected = false;
    private $lastSQL = NULL;

    private $enableProfiling = false;
    private $arrCost = NULL;
    private $lastCost = 0;
    private $totalCost = 0;

    private $hkBeforeQ = array();
    private $hkAfterQ = array();
    private $onfail = NULL;

    private $sqlAssember = NULL;

    //////////////////////////// init ////////////////////////////

    public function __construct($enableProfiling = false)
    {
        $this->mysql = mysqli_init();
        if($enableProfiling)
        {
            $this->enableProfiling(true);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function setOption($optName, $value)
    {
        return $this->mysql->options($optName, $value);
    }

    // use it before connect and reconnect
    public function setConnectTimeOut($seconds)
    {
        if($seconds <= 0)
        {
            return false;
        }
        return $this->setOption(MYSQLI_OPT_CONNECT_TIMEOUT, $seconds);
    }

    public function __get($name)
    {
        switch($name)
        {
            case 'error':
                return $this->mysql->error;
            case 'errno':
                return $this->mysql->errno;
            case 'insertID':
                return $this->mysql->insert_id;
            case 'affectedRows':
                return $this->mysql->affected_rows;
            case 'lastSQL':
                return $this->lastSQL;
            case 'lastCost':
                return $this->lastCost;
            case 'totalCost':
                return $this->totalCost;
            case 'isConnected':
                return $this->isConnected;
            case 'db':
                return $this->mysql;
            default:
                return NULL;
        }
    }

    //////////////////////////// connection ////////////////////////////

    public function connect($host, $uname = NULL, $passwd = NULL, $dbname = NULL, $port = NULL, $flags = 0)
    {
        $port = intval($port);
        if(!$port)
        {
            $port = 3306;
        }

        $this->dbConf = array(
            'host' => $host,
            'port' => $port,
            'uname' => $uname,
            'passwd' => $passwd,
            'flags' => $flags,
            'dbname' => $dbname,
        );
        
        $this->isConnected = $this->mysql->real_connect(
            $host, $uname, $passwd, $dbname, $port, NULL, $flags
        );

        return $this->isConnected;
    }

    public function reconnect()
    {
        if($this->dbConf === NULL)
        {
            return false;
        }
        $conf = $this->dbConf;
        $this->isConnected = $this->mysql->real_connect(
            $conf['host'], $conf['uname'], $conf['passwd'],
            $conf['dbname'], $conf['port'], NULL, $conf['flags']
        );

        return $this->isConnected;
    }

    public function close()
    {
        if(!$this->isConnected)
        {
            return;
        }
        $this->isConnected = false;
        $this->mysql->close();
    }

    // note: mysqli.reconnect should be off
    public function isConnected($bolCheck = false)
    {
        if($this->isConnected && $bolCheck && !$this->mysql->ping())
        {
            $this->isConnected = false;
        }
        return $this->isConnected;
    }

    //////////////////////////// query ////////////////////////////

    public function query($sql, $fetchType = DB::FETCH_ASSOC, $bolUseResult = false)
    {
        if(!$this->isConnected())
        {
            return false;
        }

        if(!is_string($sql))
        {
            require_once('ISQL.class.php');
            // get sql text
            if(!($sql instanceof ISQL) || !($sql = $sql->getSQL()))
            {
                return false;
            }
        }

        // execute hooks before query
        foreach($this->hkBeforeQ as $arrCallback)
        {
            $func = $arrCallback[0];
            $extArgs = $arrCallback[1];
            if(call_user_func_array($func, array($this, &$sql, $extArgs)) === false)
            {
                return false;
            }
        }

        $this->lastSQL = $sql;
        		
        $beg = intval/* 64 bits int */(microtime(true)*1000000);
        $res = $this->mysql->query($sql, $bolUseResult?MYSQLI_USE_RESULT:MYSQLI_STORE_RESULT);

        // record cost
        $this->lastCost = intval/* 64 bits int */(microtime(true)*1000000) - $beg;
        $this->totalCost += $this->lastCost;

        // do profiling
        if($this->enableProfiling)
        {
            $this->arrCost[] = array($sql, $this->lastCost);
        }

        $ret = false;

        // res is NULL if mysql is disconnected
        if(is_bool($res) || $res === NULL)
        {
            $ret = ($res == true);
            // call fail handler
            if(!$ret && $this->onfail !== NULL)
            {
                call_user_func_array($this->onfail, array($this, &$ret));
            }
        }
        // we have result
        else
        {
            switch($fetchType)
            {
                case DB::FETCH_OBJ:
                    require_once('DBResult.class.php');
                    $ret = new DBResult($res);
                    break;

                case DB::FETCH_ASSOC:
                    $ret = array();
                    while($row = $res->fetch_assoc())
                    {
                        $ret[] = $row;
                    }
                    $res->free();
                    break;

                case DB::FETCH_ROW:
                    $ret = array();
                    while($row = $res->fetch_row())
                    {
                        $ret[] = $row;
                    }
                    $res->free();
                    break;

                default:
                    $ret = $res;
                    break;
            }
        }

        // execute hooks after query
        foreach($this->hkAfterQ as $arrCallback)
        {
            $func = $arrCallback[0];
            $extArgs = $arrCallback[1];
            call_user_func_array($func, array($this, &$ret, $extArgs));
        }

        return $ret;
    }

    public function queryf(/* $sql_fmt, ..., $fetchType = DB::FETCH_ASSOC, $bolUseResult = false */)
    {
        $arrArgs = func_get_args();

        if(($argNum = count($arrArgs)) == 0)
        {
            return false;
        }

        $fmt = $arrArgs[0];
        $fmtLen = strlen($fmt);
        $sql = '';
        $cur = 1;
        $next_pos = 0;

        while(true)
        {
            $esc_pos = strpos($fmt, DB::V_ESC, $next_pos);
            if($esc_pos === false)
            {
                $sql .= substr($fmt, $next_pos);
                break;
            }

            $sql .= substr($fmt, $next_pos, $esc_pos - $next_pos);

            $esc_pos++;
            $next_pos = $esc_pos + 1;

            if($esc_pos == $fmtLen)
            {
//                echo "no char after '%'\n";
                return false;
            }

            $type_char = $fmt{$esc_pos};

            if($type_char != DB::V_ESC)
            {
                if($argNum <= $cur)
                {
//                    echo "no enough args\n";
                    return false;
                }
                $arg = $arrArgs[$cur++];
            }

            switch($type_char)
            {
            case DB::T_NUM:
            case DB::T_NUM2:
                $sql .= intval($arg);
                break;

            case DB::T_STR:
                $sql .= $this->escapeString($arg);
                break;

            case DB::T_RAW:
            case DB::T_RAW2:
                $sql .= $arg;
                break;

            case DB::V_ESC:
                $sql .= DB::V_ESC;
                break;

            default:
//                echo "unknow type: $type_char\n";
                return false;
            }
        }

        $fetchType = DB::FETCH_ASSOC;
        $bolUseResult = false;

        if($argNum > $cur)
        {
            $fetchType = $arrArgs[$cur++];
        }

        if($argNum > $cur)
        {
            $bolUseResult = $arrArgs[$cur++];
        }

        return $this->query($sql, $fetchType, $bolUseResult);
    }

    private function __getSQLAssember()
    {
        if($this->sqlAssember == NULL)
        {
            require_once('SQLAssember.class.php');
            $this->sqlAssember = new SQLAssember($this);
        }
        return $this->sqlAssember;
    }

    public function select(
        $tables, $fields, $conds = NULL, $options = NULL, $appends = NULL,
        $fetchType = DB::FETCH_ASSOC, $bolUseResult = false
    )
    {
        $this->__getSQLAssember();
        $sql = $this->sqlAssember->getSelect($tables, $fields, $conds, $options, $appends);
        if(!$sql)
        {
            return false;
        }

        return $this->query($sql, $fetchType, $bolUseResult);
    }

    public function selectPage(
        $table, $fields, $conds = NULL, $options = NULL, $appends = NULL, $orderBy = NULL,
        $fetchType = DB::FETCH_ASSOC, $bolUseResult = false
    )
    {
        $this->__getSQLAssember();
        $searchResult = $this->sqlAssember->getSelect($table, array('id'), $conds, $options, $appends);
        if(!$searchResult)
        {
            return false;
        }
        $searchTables = array(
            '(' . $searchResult . ') t1',
            $table . ' t2'
        ); 
        $searchConditions = array(
            't1.id = t2.id'
        );
        $sql = $this->sqlAssember->getSelect( $searchTables, $fields, $searchConditions, NULL, $orderBy );
        return $this->query($sql, $fetchType, $bolUseResult);
    }


    public function selectCount($tables, $field = '*', $conds = NULL, $options = NULL, $appends = NULL)
    {
        $this->__getSQLAssember();
        $fields = 'COUNT(' . $field . ')';
        $sql = $this->sqlAssember->getSelect($tables, $fields, $conds, $options, $appends);
        if(!$sql)
        {
            return false;
        }
        $res = $this->query($sql, DB::FETCH_ROW);
        if($res === false)
        {
            return false;
        }
        return intval($res[0][0]);
    }

    // added by zl
    public function selectForUpdate($tables, 
		    					    $fields, 
		    					    $conds = NULL, 
		    					    $appends = NULL,
		    					    $fetchType = DB::FETCH_ASSOC, 
		    					    $bolUseResult = false)
    {
        $this->__getSQLAssember();
        $sql = $this->sqlAssember->getSelectForUpdate($tables, $fields, $conds, $appends);
        if (!$sql)
        {
            return false;
        }

        return $this->query($sql, $fetchType, $bolUseResult);
    }
    // added by zl
    
    public function foundRows()
    {
        $sql = 'SELECT FOUND_ROWS()';
        $res = $this->query($sql);
        if($res === false)
        {
            return false;
        }
        return intval($res[0]['FOUND_ROWS()']);
    }

    public function insert($table, $row, $options = NULL, $onDup = NULL)
    {
        $this->__getSQLAssember();
        $sql = $this->sqlAssember->getInsert($table, $row, $options, $onDup);
        if(!$sql || !$this->query($sql))
        {
            return false;
        }
        return $this->mysql->affected_rows;
    }

	 public function insertValues($table, $columns, $values, $options = NULL, $onDup = NULL)
    {
        $this->__getSQLAssember();
        $sql = $this->sqlAssember->getInsertValues($table, $columns, $values, $options, $onDup);
        if(!$sql || !$this->query($sql))
        {
            return false;
        }
        return $this->mysql->affected_rows;
    }


    public function update($table, $row, $conds = NULL, $options = NULL, $appends = NULL)
    {
        $this->__getSQLAssember();
        $sql = $this->sqlAssember->getUpdate($table, $row, $conds, $options, $appends);
        {
            return false;
        }
        return $this->mysql->affected_rows;
    }

    public function delete($table, $conds = NULL, $options = NULL, $appends = NULL)
    {
        $this->__getSQLAssember();
        $sql = $this->sqlAssember->getDelete($table, $conds, $options, $appends);
        if(!$sql || !$this->query($sql))
        {
            return false;
        }
        return $this->mysql->affected_rows;
    }

    public function prepare($query, $getRaw = false)
    {
        $stmt = $this->mysql->prepare($query);
        if($stmt === false)
        {
            return false;
        }
        if($getRaw)
        {
            return $stmt;
        }
        else
        {
            require_once('DBStmt.class.php');
            return new DBStmt($stmt);
        }
    }

    public function getLastSQL()
    {
        return $this->lastSQL;
    }

    public function getInsertID()
    {
        return $this->mysql->insert_id;
    }

    public function getAffectedRows()
    {
        return $this->mysql->affected_rows;
    }
/*
    public function getLastQueryInfo()
    {
        return $this->mysql->info;
    }
*/
    //////////////////////////// hooks ////////////////////////////

    public function addHook($where, $id, $func, $extArgs = NULL)
    {
        switch($where)
        {
            case self::HK_BEFORE_QUERY:
                $dest = &$this->hkBeforeQ;
                break;
            case self::HK_AFTER_QUERY:
                $dest = &$this->hkAfterQ;
                break;
            default:
                return false;
        }
        if(!is_callable($func))
        {
            return false;
        }
        $dest[$id] = array($func, $extArgs);
        return true;
    }

    public function onFail($func = 0)
    {
        if($func === 0)
        {
            return $this->onfail;
        }
        if($func === NULL)
        {
            $this->onfail = NULL;
            return true;
        }
        if(!is_callable($func))
        {
            return false;
        }
        $this->onfail = $func;
        return true;
    }

    public function removeHook($where, $id)
    {
        switch($where)
        {
            case self::HK_BEFORE_QUERY:
                $dest = &$this->hkBeforeQ;
                break;
            case self::HK_AFTER_QUERY:
                $dest = &$this->hkAfterQ;
                break;
            default:
                return false;
        }
        if(!array_key_exists($id, $dest))
        {
            return false;
        }
        unset($dest[$id]);
        return true;
    }

    //////////////////////////// profiling ////////////////////////////

    public function getLastCost()
    {
        return $this->lastCost;
    }

    public function getTotalCost()
    {
        return $this->totalCost;
    }

    public function getProfilingData()
    {
        return $this->arrCost;
    }

    public function cleanProfilingData()
    {
        $this->arrCost = NULL;
    }

    public function enableProfiling($enable = NULL)
    {
        if($enable === NULL)
        {
            return $this->enableProfiling;
        }
        $this->enableProfiling = ($enable == true);
    }

    //////////////////////////// transaction ////////////////////////////

    public function autoCommit($bolAuto = NULL)
    {
        if($bolAuto === NULL)
        {
            $sql = 'SELECT @@autocommit';
            $res = $this->query($sql);
            if($res === false)
            {
                return NULL;
            }
            return $res[0]['@@autocommit'] == '1';
        }

        return $this->mysql->autocommit($bolAuto);
    }

    public function startTransaction()
    {
        $sql = 'START TRANSACTION';
        return $this->query($sql);
    }

    public function commit()
    {
        return $this->mysql->commit();
    }

    public function rollback()
    {
        return $this->mysql->rollback();
    }

    //////////////////////////// util ////////////////////////////

    public function escapeString($string)
    {
        return $this->mysql->real_escape_string($string);
    }

    public function selectDB($dbname)
    {
        if($this->mysql->select_db($dbname))
        {
            $this->dbConf['dbname'] = $dbname;
            return true;
        }
        return false;
    }

    public function getTables($pattern = NULL, $dbname = NULL)
    {
        $sql = 'SHOW TABLES';
        if($dbname !== NULL)
        {
            $sql .= ' FROM '.$this->escapeString($dbname);
        }
        if($pattern !== NULL)
        {
            $sql .= ' LIKE \''.$this->escapeString($pattern).'\'';
        }

        if(!($res = $this->query($sql, false)))
        {
            return false;
        }

        $ret = array();
        while($row = $res->fetch_row())
        {
            $ret[] = $row[0];
        }
        $res->free();
        return $ret;
    }

    public function isTableExists($name, $dbname = NULL)
    {
        $tables = $this->getTables($name, $dbname);
        if($tables === false)
        {
            return NULL;
        }
        return count($tables) > 0;
    }

/*
    public function changeUser($uname, $passwd, $dbname = NULL)
    {
        if(!$this->isConnected())
        {
            return false;
        }

        if($this->dbConf['uname'] == $name &&
            $this->dbConf['passwd'] == $passwd)
        {
            if($dbname !== NULL)
            {
                return $this->selectDB($dbname);
            }
            return true;
        }

        $ret = $this->mysql->change_user($uname, $passwd, $dbname);
        if($ret)
        {
            $this->dbConf['uname'] = $uname;
            $this->dbConf['passwd'] = $passwd;
            $this->dbConf['dbname'] = $dbname;
        }
        return $ret;
    }
*/

    public function charset($name = NULL)
    {
        if($name === NULL)
        {
            return $this->mysql->character_set_name();
        }
        return $this->mysql->set_charset($name);
    }

    public function getConnConf()
    {
        if($this->dbConf == NULL)
        {
            return NULL;
        }

        return array(
            'host' => $this->dbConf['host'],
            'port' => $this->dbConf['port'],
            'uname' => $this->dbConf['uname'],
            'dbname' => $this->dbConf['dbname']
            );
    }

    //////////////////////////// error ////////////////////////////

    public function errno()
    {
        return $this->mysql->errno;
    }

    public function error()
    {
        return $this->mysql->error;
    }
/*
    public function ansiError()
    {
        return $this->mysql->sqlstate;
    }
*/
    //////////////////////////// env ////////////////////////////
/*
    public static function getClientVersion($bolNum = true)
    {
        if($bolNum)
        {
            return mysqli_get_client_version();
        }
        else
        {
            return mysqli_get_client_info();
        }
    }

    public function getServerVersion($bolNum = true)
    {
        if($bolNum)
        {
            return $this->mysql->server_version;
        }
        else
        {
            return $this->mysql->server_info;
        }
    }

    public function getServerStat($detail = false)
    {
        if($detail === false || $detail === NULL)
        {
            return $this->mysql->stat();
        }

        $sql = 'SHOW STATUS';
        if(!($res = $this->query($sql, false)))
        {
            return false;
        }

        // get full detail
        if($detail === true)
        {
            $ret = array();
            while($row = $res->fetch_row())
            {
                $ret[$row[0]] = $row[1];
            }
        }
        // get one detail
        else
        {
            $ret = false;
            while($row = $res->fetch_row())
            {
                if($detail == $row[0])
                {
                    $ret = $row[1];
                }
            }
        }

        $res->free();
        return $ret;
    }

    public function getThreadID()
    {
        return $this->mysql->thread_id;
    }

    public function getHostInfo()
    {
        if($this->dbConf === NULL)
        {
            return NULL;
        }

        return array(
            'host' => $this->dbConf['host'],
            'port' => $this->dbConf['port']
            );
    }

    public function getProtocol()
    {
        return $this->mysql->protocol_version;
    }
*/
 
}


?>
