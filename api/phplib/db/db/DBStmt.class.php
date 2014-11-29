<?php

/**
* brief of DBStmt.class.php:
*/

require_once('DB.class.php');

// never create instance by yourself
class DBStmt
{
    private $stmt;

    public function __construct(mysqli_stmt $stmt)
    {
        $this->stmt = $stmt;
    }

    public function __destruct()
    {
        if($this->stmt !== NULL)
        {
            $this->close();
        }
    }

    public function close()
    {
        $this->stmt->close();
        $this->stmt = NULL;
    }

    public function __get($name)
    {
        switch($name)
        {
            case 'stmt':
                return $this->stmt;
            case 'error':
                return $this->stmt->error;
            case 'errno':
                return $this->stmt->errno;
            case 'affectedRows':
                return $this->stmt->affected_rows;
            case 'paramCount':
                return $this->stmt->param_count;
            default:
                return NULL;
        }
    }

    public function bindParam()
    {
        $args = func_get_args();
        return call_user_func_array(array($this->stmt, 'bind_param'), $args);
    }

    public function execute($fetchType = DB::FETCH_ASSOC, $bolUseResult = false)
    {
        if(!$this->stmt->execute())
        {
            return false;
        }
        // get metadata
        $res = $this->stmt->result_metadata();
        // no result
        if(!$res)
        {
            return true;
        }
        $finfo = $res->fetch_fields();
        $res->free();

        // get an object
        if($fetchType == DB::FETCH_OBJ || $bolUseResult)
        {
            if(!$bolUseResult && !$this->stmt->store_result())
            {
                return false;
            }
            require_once('DBResult.class.php');
            return new StmtResult($this->stmt, $finfo, $this);
        }

        // store result
        if(!$this->stmt->store_result())
        {
            return false;
        }

        $count = 0;
        $bindString = '';
        foreach($finfo as $v)
        {
            // get bind string
            $bindString .= ', &$row['.$count.']';
            ++$count;
        }

        $row = array();
        $bindString = '$this->stmt->bind_result('.substr($bindString, 2).');';
        eval($bindString);
        // fetch
        $ret = array();
        while(true)
        {
            $tmp = array();
            $r = $this->stmt->fetch();
            if($r === NULL)
            {
                break;
            }
            else if($r === false)
            {
                $this->stmt->free_result();
                return false;
            }
 //           var_dump($row);
            if($fetchType == DB::FETCH_ASSOC)
            {
                foreach($row as $k => $v)
                {
                    $tmp[$finfo[$k]->name] = $v;
                }
            }
            else
            {
                // $row is ref, so we can't use $tmp = $row
                foreach($row as $v)
                {
                    $tmp[] = $v;
                }
            }

            $ret[] = $tmp;
        }
        $this->stmt->free_result();
        return $ret;
    }

    public function getAffectedRows()
    {
        return $this->stmt->affected_rows;
    }

    public function getParamCount()
    {
        return $this->stmt->param_count;
    }

    public function errno()
    {
        return $this->stmt->errno;
    }

    public function error()
    {
        return $this->stmt->error;
    }
}
?>
