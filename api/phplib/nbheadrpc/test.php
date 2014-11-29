<?php
 
 
 
/**
 * @file test.php
 * @date 2014/08/06 12:03:21
 * @brief 
 *  
 **/
require_once("./rpc.php");
$rpc = new NbheadSender(
	array(
		'connection_timeout' => 1000,
		'timeout'=>5000,
		'machine'=>array(
			array('ip'=> '10.81.64.85', 'port'=>8081),
		),
	)
);

$ret = $rpc->call('{"CMD":"ADD_USER","USERS":[{"USER":1}, {"USER":2},{"USER":3},{"USER":4}]}');
var_dump($ret);
var_dump($rpc->get_last_error());
$ret = $rpc->call('{"CMD":"ADD_ADD_RELA","SRC":1,"USERS":[{"USER":2},"USER":3},"USER":4}]}');
var_dump($ret);
var_dump($rpc->get_last_error());
$ret = $rpc->call('{"CMD":"GET_2RELA","SRC":1}');
var_dump($ret);
