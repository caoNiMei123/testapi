<?php
/**
 * @file sender.php
 * @date 2014/08/06 09:50:41
 * @brief 
 *  
 **/

class PHPIpcReceiver
{
    private $_arr_conf = null;
    private $_error = null;
    private $_socket = null; 
    public function __construct($arr_conf)
    {
        $this->_arr_conf = $arr_conf;
    }
    
    //如果没有任务会阻塞
    public function get_task()
    {
        if (!isset($this->_arr_conf['machine'])) 
        {
            return false;
        }     

        if(is_resource($this->_socket) === false)
        {
            unlink($this->_arr_conf['machine']);
            
            $this->_socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            if(is_resource($this->_socket) === false)
            {
            	$socket_errno = socket_last_error();
            	CLog::warning("socket_create failed [errno: %s, errstr: %s]", 
            				  $socket_errno,  socket_strerror($socket_errno));
                return false;
            }
            
            if (!socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1))
            {
                return false;
            }
			
            // 设置socket接收数据超时时间
            $arr_recv_timeout = array(
            	'sec' => 0,
            	'usec' => IPCConfig::$domain_info['receive_timeout'],
            );
            if (!socket_set_option($this->_socket, SOL_SOCKET, SO_RCVTIMEO, $arr_recv_timeout)))
            {
                return false;
            }
            
            if (socket_bind($this->_socket, $this->_arr_conf['machine']) === false)
            {
            	$socket_errno = socket_last_error();
            	CLog::warning("socket_bind failed [errno: %s, errstr: %s]", 
            				  $socket_errno,  socket_strerror($socket_errno));
                return false;
            }
            
			if (!socket_listen($this->_socket, 0))
            {
            	$socket_errno = socket_last_error();
            	CLog::warning("socket_listen failed [errno: %s, errstr: %s]", 
            				  $socket_errno,  socket_strerror($socket_errno));
                return false;
            }
        }
        $timeout = 10 * 1000 * 1000;
        if (isset($this->_arr_conf['timeout'])) 
        {
            $timeout = $this->_arr_conf['timeout'] * 1000;
        }
		
        // accept socket直至accept成功或socket接收数据超时
        $socket_connection = socket_accept($this->_socket);
        if (false == $socket_connection)
        {
        	$ret = NULL;
        	$socket_errno = socket_last_error();
        	if (11 == $socket_errno) // socket接收数据超时
        	{
        		$ret = array();
        	}
        	else
        	{
        		$ret = false;
            	CLog::warning("socket_accept failed [errno: %s, errstr: %s]", 
            				  $socket_errno,  socket_strerror($socket_errno));
        	}
        	
        	return $ret;
        }
        
        // accept请求成功，获取请求数据
		$arr_result = $this->_fetch_msg($socket_connection, $timeout);               
		if ($arr_result !== false)
		{
			$this->_send_res($socket_connection, 0);
			return $arr_result;
		}
		
		$this->_send_res($socket_connection, -1);

		return false;       
    }

    private function _fetch_msg(&$sock, $timeout)
    {
        $byte_left = 16;
        $start = gettimeofday();
        $received = 0;
        $arr_receive = array();
        $receive_data = "";
        
        while ($byte_left > 0) {
            $tmp_receive_data = socket_read($sock, $byte_left);
            $received  = strlen($tmp_receive_data);
            if (0 == $received) 
            {
                socket_close($sock);
                return false;
            }        
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
                socket_close($sock);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000 + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
            	CLog::warning("read none data before timeout");
                socket_close($sock);
                return false;
            }
        }
        $head_arr = unpack("Ilog_id/IImagic_num/Ireserved/Ibody_len",$receive_data);        
        if (!$head_arr) {
            socket_close($sock);
            return false;
        }
        $byte_left = $head_arr['body_len'];
        $receive_data = '';
        while ($byte_left > 0) {
            $tmp_receive_data = socket_read($sock, $byte_left);
            $received  = strlen($tmp_receive_data);           
            if (0 == $received) {
                socket_close($sock);
                return false;
            } 
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
                socket_close($sock);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000
                    + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
                socket_close($sock);
                return false;
            }
        }
        
        $arr_receive['header'] = $head_arr;
        $arr_receive['body'] = $receive_data;
        
        return $arr_receive;

        
    }
    private function _send_res($sock, $errno){
        //回包
        $log_id = 0; 
        $magic_num = 0x1016;
        $reserved = 0;
    
        $struct .= pack("I",$log_id);
        $struct .= pack("I",$magic_num);
        $struct .= pack("I",$reserved);
        $struct .= pack("I",$errno);        
        $sent = socket_write($sock, $struct, strlen($struct));        
        socket_close($sock);
    }

};




