<?php
    require "./sender.php";

    $sender = new PHPIpcSender(array(
        'machine' => '/tmp/tmp_socket',
        'connect_timeout' => 1000,
        'timeout' => 10000,
    ));
    $seed = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ';
    $seed_len = strlen($seed);

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
?>
