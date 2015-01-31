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
            $this->_socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            if(is_resource($this->_socket) === false)
            {
                return false;
            }
            if (!socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1))
            {
                return false;
            }	
            if (socket_bind($this->_socket, $this->_arr_conf['machine']) === false)
            {
                return false;
            }	
        }
        $timeout = 10 * 1000 * 1000;
        if (isset($this->_arr_conf['timeout'])) 
        {
            $timeout = $this->_arr_conf['timeout'] * 1000;
        }

		
        while (true)
        {   
            while (socket_listen($stream_socket, 0))
            {
                $socket_connection = socket_accept($this->_socket);
		        $result = $this->_fetch_msg($socket_connection, $timeout);                    
	            if($result !== false)
                {
                    return $result;
                }
            } 
        }
       
    }

    private function _fetch_msg($sock, $timeout)
    {
        $byte_left = 16;
        $start = gettimeofday();
        $received = 0;
        $receive_data = "";
        while ($byte_left > 0) {
            $tmp_receive_data = fread($sock, $byte_left);
            $received  = strlen($tmp_receive_data);
            if (0 == $received) 
            {
            	fclose($sock);
                return false;
            }        
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
            	fclose($sock);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000 + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
            	fclose($sock);
                return false;
            }
        }
        $head_arr = unpack("Ilog_id/IImagic_num/Ireserved/Ibody_len",$receive_data);        
        if (!$head_arr) {
            fclose($sock);
            return false;
        }
        $byte_left = $head_arr['body_len'];
        $receive_data = '';
        while ($byte_left > 0) {
            $tmp_receive_data = fread($sock, $byte_left);
            $received  = strlen($tmp_receive_data);           
            if (0 == $received) {
                fclose($sock);
                return false;
            } 
            else if ($received > 0 && $received <= $byte_left) {
                $receive_data .= $tmp_receive_data;
                $byte_left -= $received;
            } 
            else {
                fclose($sock);
                return false;
            }
            // manual timeout checking
            $current = gettimeofday();
            $us_gone = ($current['sec'] - $start['sec']) * 1000000
                    + ($current['usec'] - $start['usec']);
            if ($us_gone > $timeout) {
                fclose($sock);
                return false;
            }
    
        }
        fclose($sock);
        return $receive_data;        
    }

};




