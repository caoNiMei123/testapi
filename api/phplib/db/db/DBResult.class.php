<?php
/**
* brief of DBResult.class.php:
*/

// abstract DBResult
abstract class AbsDBResult
{
    abstract public function next($type = DB::FETCH_ASSOC);
    abstract public function seek($where);
    abstract public function tell();
    abstract public function count();
    abstract public function free();

    public function walk($callback, $type = DB::FETCH_ASSOC)
    {
        // seek head
        if($this->tell() != 0 && !$this->seek(0))
        {
            return false;
        }

        $args = func_get_args();
        if(count($args) <= 2)
        {
            $args = array();
        }
        else
        {
            array_shift($args);
            array_shift($args);
        }

        $count = 0;
        while($row = $this->next($type))
        {
            $count++;
            $tmp = $args;
            array_unshift($tmp, $row);
            if(call_user_func_array($callback, $tmp) === false)
            {
                break;
            }
        }
        return $count;
    }
}

// result of DB query
class DBResult extends AbsDBResult
{
    private $result;
    private $pos = 0;

    public function __construct(mysqli_result $result)
    {
        $this->result = $result;
    }

    public function __destruct()
    {
        if($this->result !== NULL)
        {
            $this->free();
        }
    }

    public function next($type = DB::FETCH_ASSOC)
    {
        if($type == DB::FETCH_ASSOC)
        {
            $row = $this->result->fetch_assoc();
        }
        else
        {
            $row = $this->result->fetch_row();
        }
        if($row)
        {
            $this->pos++;
        }
        return $row;
    }

    public function seek($where)
    {
        if($where < 0 || $where >= $this->result->num_rows)
        {
            return false;
        }

        if(!$this->result->data_seek($where))
        {
            return false;
        }
        $this->pos = $where;
        return true;
    }

    public function tell()
    {
        return $this->pos;
    }

    public function count()
    {
        return $this->result->num_rows;
    }

    public function free()
    {
        $this->result->free();
        $this->result = NULL;
    }
}

// result of stmt execute
class StmtResult extends AbsDBResult
{
    private $stmt;
    private $finfo;
    private $row;
    private $pos = 0;
    private $noused_stmt_obj;

    public function __construct(mysqli_stmt $stmt, array $finfo, $stmt_obj = NULL)
    {
        $this->finfo = $finfo;
        $this->stmt = $stmt;
        $this->noused_stmt_obj = $stmt_obj;
        // do bind result
        $count = 0;
        foreach($finfo as $v)
        {
            $bindString .=', &$this->row['.$count.']';
            ++$count;
        }
        $bindString = '$this->stmt->bind_result('.substr($bindString, 2).');';
        eval($bindString);
    }

    public function __destruct()
    {
        if($this->stmt !== NULL)
        {
            $this->free();
        }
    }

    public function next($type = DB::FETCH_ASSOC)
    {
        $ret = $this->stmt->fetch();
        if(!$ret)
        {
            return $ret;
        }

        if($type == DB::FETCH_ASSOC)
        {
            foreach($this->row as $k => $v)
            {
                $tmp[$this->finfo[$k]->name] = $v;
            }
        }
        else
        {
            // $row is ref, so we can't use $tmp = $row
            foreach($this->row as $v)
            {
                $tmp[] = $v;
            }
        }
        $this->pos++;
        return $tmp;
    }

    public function seek($where)
    {
        // unseekable
        if($this->stmt->num_rows == 0)
        {
            return false;
        }

        if($where < 0 || $where >= $this->stmt->num_rows)
        {
            return false;
        }

        if($this->stmt->data_seek($where) === false)
        {
            return false;
        }

        $this->pos = $where;
        return true;
    }

    public function tell()
    {
        return $this->pos;
    }

    public function count()
    {
        return $this->stmt->num_rows;
    }

    public function free()
    {
        $this->stmt->free_result();
        $this->stmt = NULL;
    }
}
?>
