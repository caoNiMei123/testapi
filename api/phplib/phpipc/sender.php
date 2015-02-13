<?php
/**
 * @file sender.php
 * @date 2014/08/06 09:50:41
 * @brief 
 *  
 **/


class PHPIpcSender
{
    
    private $_arr_conf = null;
    private $_error = null;
    private $_is_debug = false;

    public function __construct($arr_conf)
    {
        $this->_arr_conf = $arr_conf;
    }
    
    public function set_debug_model()
    {
        $_is_debug = true;
    }
    
    public function call($json_body, $log_id = 0)
    {
        if (!isset($this->_arr_conf['machine'])) 
        {
            return false;
        }         
        $conn_timeout_sec = 1;
        if (isset($this->_arr_conf['connection_timeout'])) 
        {
            $conn_timeout_sec = $this->_arr_conf['connection_timeout'] / 1000;
        }
        $timeout = 10 * 1000 * 1000;
        if (isset($this->_arr_conf['timeout'])) 
        {
            $timeout = $this->_arr_conf['timeout'] * 1000;
        }

        $fp = @fsockopen("unix://".$this->_arr_conf['machine'], -1, $errno, $errstr, $conn_timeout_sec);
        if(is_resource($fp) === false)
        {
            CLog::warning("fsockopen failed [errno: %s, errstr: %s, local_host: %s]", 
                          $errno, $errstr, "unix://" . $this->_arr_conf['machine']);
            return false;
        } 

        $magic_num = 0x1016;
        $reserved = 0;
        $body_len = strlen($json_body);   

        $struct .= pack("I",$log_id);
        $struct .= pack("I",$magic_num);
        $struct .= pack("I",$reserved);
        $struct .= pack("I",$body_len);        
        $struct .= $json_body;
        
        $timeout_sec      = intval($timeout / 1000000);
        $timeout_micro = intval($timeout % 1000000);
        
        stream_set_timeout($fp, $timeout_sec, $timeout_micro);
        
        $sent = fwrite($fp, $struct, strlen($struct));        
        if($sent != strlen($struct))
        {
            CLog::warning("send data failed");
            fclose($fp);
            return false;
        }
       
        $receive_data    = '';
        $byte_left    = 16;
        $start    = gettimeofday();
        
        while ($byte_left > 0) {
            $tmp_receive_data = fread($fp, $byte_left);
            $received  = strlen($tmp_receive_data);
            if (0 == $received) {
                CLog::warning("receive data failed");
                fclose($fp);
                return false;
            } 
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
                fclose($fp);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000
                    + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
                CLog::warning("read none response data before timeout");
                fclose($fp);
                return false;
            }
        }
        $head_arr = unpack("Ilog_id/IImagic_num/Ireserved/Ierrno",$receive_data);        
        if (!$head_arr) {
            fclose($fp);
            return false;
        }

        $errno = $head_arr['errno'];
        if($errno != 0)
        {
            CLog::warning("send data succ, but response error [response_errno: %s]", $errno);
            fclose($fp);
            return false;
        }
        
        fclose($fp);
        return true;        
    }




};




