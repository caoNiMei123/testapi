<?php
    require "../sender.php";

    $sender = new PHPIpcSender(array(
        'machine' => '/tmp/tmp_socket',
        'connect_timeout' => 1000,
        'timeout' => 10000,
    ));
    $seed = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ';
    $seed_len = strlen($seed);
	
    $task_info = array(
    	'pid' => 123,
    	'user_id' => 456,
    	'phone' => 13412345678,
    	'ctime' => 123,
    	'mtime' => 456,
    	'price' => 111,
    	'mileage' => 444,
    	'src' => "beijing",
    	'dest' => 'changping',
    	'src_gps' => '',
    	'dest_gps' => '',
    	'timeout' => 1234,
    );
    echo "xxxxxxxxxx";
    $result = $sender->call(json_encode($task_info), 12345);
    var_dump($result);
    
    /*
    while(true){
        $word = '';
        //随机种子更唯一
        mt_srand((double)microtime() * 1000000 * getmypid());
        for ($i = 0; $i < 10; ++$i) {
            $word .= $seed{mt_rand() % $seed_len};
        }
        $result = $sender->call($word);
        var_dump($result);
        
    }   
    */
?>
