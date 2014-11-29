<?php
/**
 * @file rpc.php
 * @date 2014/08/06 09:50:41
 * @brief 
 *  
 **/

class NbHeadSenderError
{
    
    public static $SERVER_CONF_ERROR = array('message' => "Server Configure Error");
    public static $SERVER_CONN_ERROR = array('message' => "Server Connect Error");
    public static $SERVER_TIMEOUT = array('message' => "Server Timeout");
    public static $SERVER_SEND_ERROR = array('message' => "Server Send Error");
    public static $PARAM_ERROR = array('message' => "Param Error");
    public static $INVALID_RES = array('message' => 'Invalid Response');

}


class NbheadSender
{
	
	private $_arr_conf = null;
	private $_error = null;

	public function __construct($arr_conf)
    {
        $this->_arr_conf = $arr_conf;
    }
    public function get_last_error() 
    {
        return $this->_error;
    }
    private function set_last_error($err)
    {
        $this->_error = $err;
    }
    public function call($json_body, $log_id = 0)
    {
        $this->set_last_error(array());        

        if (!isset($this->_arr_conf['machine'])) 
        {
            $this->set_last_error(NbHeadSenderError::$SERVER_CONF_ERROR);
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
        $count = count($this->_arr_conf['machine']); 
        $pos = rand(0, $count -1);        
        
        $socket = @fsockopen ($this->_arr_conf['machine'][$pos]['ip'],$this->_arr_conf['machine'][$pos]['port'],$errno,$str_errno,$conn_timeout_sec);
        
        if(is_resource($socket) === false)
        {
            $this->set_last_error(NbHeadSenderError::$SERVER_CONN_ERROR);
            return false;
        }
        $struct = '';
        
        $magic_num = 0x1016;
        $reserved = 0;
        if(CarpoolConfig::$snappy_compress)
        {
            $compress_body = snappy_compress($json_body);
        }else
        {
            $compress_body = $json_body;
        }
        
        
        $body_len = strlen($compress_body);   

        var_dump($body_len);    
        
        $struct .= pack("I",$log_id);
        $struct .= pack("I",$magic_num);
        $struct .= pack("I",$reserved);
        $struct .= pack("I",$body_len);        
        $struct .= $compress_body;
        $timeout_sec      = intval($timeout / 1000000);
        $timeout_micro = intval($timeout % 1000000);
        stream_set_timeout($socket, $timeout_sec, $timeout_micro);
        $sent = fwrite($socket, $struct, strlen($struct));        
        if($sent != strlen($struct))
        {
            $this->set_last_error(NbHeadSenderError::$SERVER_SEND_ERROR);
            return false;
        }
       
        $receive_data    = '';
        $byte_left    = 16;
        $start    = gettimeofday();
        
        while ($byte_left > 0) {
            $tmp_receive_data = fread($socket, $byte_left);

            $received  = strlen($tmp_receive_data);
            if (0 == $received) {
                $this->set_last_error(NbHeadSenderError::$INVALID_RES);
                return false;
            } 
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
                $this->set_last_error(NbHeadSenderError::$INVALID_RES);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000
                    + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
                $this->set_last_error(NbHeadSenderError::$SERVER_TIMEOUT);
                return false;
            }
        }
        $head_arr = unpack("Ilog_id/IImagic_num/Ireserved/Ibody_len",$receive_data);        
        if (!$head_arr) {
            $this->set_last_error(NbHeadSenderError::$INVALID_RES);
            return false;
        }

        $byte_left = $head_arr['body_len'];
        $receive_data = '';

        while ($byte_left > 0) {
            $tmp_receive_data = fread($socket, $byte_left);
            $received  = strlen($tmp_receive_data);           
            if (0 == $received) {
                $this->set_last_error(NbHeadSenderError::$INVALID_RES);
                return false;
            } 
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
                $this->set_last_error(NbHeadSenderError::$INVALID_RES);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000
                    + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
                $this->set_last_error(NbHeadSenderError::$SERVER_TIMEOUT);
                return false;
            }
        }      
        if(CarpoolConfig::$snappy_compress)
        {
            $receive_data = snappy_uncompress($receive_data);
        } 
        
        return $receive_data;        
    }




};




