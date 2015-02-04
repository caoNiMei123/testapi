<?php
    require "./receiver.php";
    $receiver = new PHPIpcReceiver(array(
        'machine' => '/tmp/tmp_socket',
        'connect_timeout' => 1000,
        'timeout' => 10000,
    ));
    while(true){
        $result = $receiver->get_task();
        var_dump($result);
    }
?>
