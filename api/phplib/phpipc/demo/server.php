<?php
    require "./receiver.php";
    $receiver = new PHPIpcReceiver(array(
        'machine' => '/tmp/tmp_socket',
        'connect_timeout' => 1000,
        'timeout' => 10000,
    	'receive_timeout' => 10000, // socket接收数据超时，单位: 微秒
    ));
    while(true){
        $result = $receiver->get_task();
        if (!empty($result))
        {
        	var_dump($result);	
        }
    }
?>
