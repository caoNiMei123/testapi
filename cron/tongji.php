<?php
/***************************************************************************
 *  输入格式 20140311 ， 获取当天的订单统计项 
 **************************************************************************/
 
 

/**
 * @file tongji.php
 * @date 2014/12/06 15:27:07
 * @brief 
 *  
 **/




$day = $argv[1];
$starttime = strtotime($argv[1]." 00:00:00");
$endtime = strtotime($argv[1]."24:00:00");

var_dump($day);

$mysql_ip= 'rdsv63ujvaq3yma.mysql.rds.aliyuncs.com';
$mysql_port = 3306;
$mysql_user = 'pinche';
$mysql_pass = 'pinche';
$mysql_db = 'carpooldb';
$start = 0;
$order = 0;
$succ_order = 0;
$timeout_order = 0;
$svr_conn = NULL;


while(true){
    echo "start:  $start\n";
    mysql_close($svr_conn);     
    $svr_conn = mysql_connect("$mysql_ip:$mysql_port",$mysql_user,$mysql_pass,1);
    if (!$svr_conn){
        echo "Connect to db fail\n";
        usleep(200);
        continue;
    }
    if(!mysql_select_db($mysql_db, $svr_conn)){
        echo "Select Db fail\n";
        usleep(200);
        continue;
    }
    $sql = "select * from  pickride_info where ctime > $starttime and ctime < $endtime limit $start, 2000;";
    $result_arr = mysql_query($sql, $svr_conn);
    if(!$result_arr){
        mysql_close($svr_conn);     
        $svr_conn = mysql_connect("$mysql_ip:$mysql_port",$mysql_user,$mysql_pass,1);
        if (!$svr_conn){
            echo "Connect to db fail\n";
            usleep(200);
            continue;
        }
        if(!mysql_select_db($mysql_db, $svr_conn)){
            echo "Select Db fail\n";
            usleep(200);
            continue;
        }
    }
    $cnt = 0;
    while ($result_row = mysql_fetch_assoc($result_arr)){
        $cnt++;
        $order++;
        if(intval($result_row['status']) == 4)
            $succ_order ++;
        if(intval($result_row)['status'] == 5)
            $timeout_order ++;
    }
    if($cnt == 0)
        break;    
     $start += 2000;
}
mysql_close($svr_conn);     
$svr_conn = mysql_connect("$mysql_ip:$mysql_port",$mysql_user,$mysql_pass,1);
if (!$svr_conn){
    exit(1);
}
if(!mysql_select_db($mysql_db, $svr_conn)){
    exit(1);
}

mysql_query("insert into log_info (`day`, `hour`, `item_1`, `item_2`, `item_3`) values ('$day', '0', $order, $succ_order, $timeout_order) on duplicate key update `item_1`=$order,`item_2`=$succ_order,`item_3`=$timeout_order;", $svr_conn);

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100 */
?>
