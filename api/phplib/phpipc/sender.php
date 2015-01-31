<?php
/**
 * @file sender.php
 * @date 2014/08/06 09:50:41
 * @brief 
 *  
 **/
class PHPIpcSenderError
{
    
    public static $SERVER_CONF_ERROR = array('message' => "Server Configure Error");
    public static $SERVER_CONN_ERROR = array('message' => "Server Connect Error");
    public static $SERVER_TIMEOUT = array('message' => "Server Timeout");
    public static $SERVER_SEND_ERROR = array('message' => "Server Send Error");
    public static $PARAM_ERROR = array('message' => "Param Error");
    public static $INVALID_RES = array('message' => 'Invalid Response');

}

class PHPIpcSender
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
            $this->set_last_error(PHPIpcSenderError::$SERVER_CONF_ERROR);
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

	$fp = @fsockopen($this->_arr_conf['machine'], -1, $errno, $errstr, $conn_timeout_sec);
        if(is_resource($fp) === false)
	{
            $this->set_last_error($errstr ($errno));
            return false;
        } 
	 
       	$log_id = 0; 
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
            $this->set_last_error(PHPIpcSenderError::$SERVER_SEND_ERROR);
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
                $this->set_last_error(PHPIpcSenderError::$INVALID_RES);
            	fclose($fp);
                return false;
            } 
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
                $this->set_last_error(PHPIpcSenderError::$INVALID_RES);
            	fclose($fp);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000
                    + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
                $this->set_last_error(PHPIpcSenderError::$SERVER_TIMEOUT);
            	fclose($fp);
                return false;
            }
        }
        $head_arr = unpack("Ilog_id/IImagic_num/Ireserved/Ierrno",$receive_data);        
        if (!$head_arr) {
            $this->set_last_error(PHPIpcSenderError::$INVALID_RES);
            fclose($fp);
            return false;
        }

        $errno = $head_arr['errno'];
	if($errno != 0)
	{
            $this->set_last_error(PHPIpcSenderError::$INVALID_RES);
            return false;
	}
        
        fclose($fp);
        return true;        
    }




};




