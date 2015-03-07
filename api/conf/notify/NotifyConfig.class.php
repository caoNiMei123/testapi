<?php


class NotifyConfig
{   
    const DRIVER_LIMIT = 50;
    
    public static $notifyTimeout = 300; // 单位: 秒
    
    // 单位：公里
    public static $NotifyRectangleRange = 7;
    
    // 单位：公里
    public static $NotifyBetweenDistance = 4;
    
    // 需要进行筛选的司机数量下限
    public static $driverNumLowerBound = 10;
    
    public static $arrNotifyTaskStatus = array(
        'new'   => 0,
        'processing' => 1,
        'succ'  => 2,
        'cancel' => 3,
        'error_invalid_pid' => 4,
        'error_no_driver' => 5,
        'error_push' => 6,
    );
    
    public static $arrPushType  = array(
        'create_order' => 1,
        'cancel_order' => 2,
        'accept_order' => 3,
        'driver_location' => 4,
        'order_info' => 5,
    );


    public static $arrMsg  = array(
        'accept_order' => array(
            'title'=>'易拼车',
            'content'=>"司机%s接单啦，请准时到达%s哦",
            'ticker_text'=>"司机%s接单啦，请准时到达%s哦",
        ),
        'cancel_order_driver' => array(
            'title'=>'易拼车',
            'content'=>"抱歉司机%s临时有事，取消了与您的拼车计划~~",
            'ticker_text'=>"抱歉司机%s临时有事，取消了与您的拼车计划~~",
        ),
        'cancel_order_passenger' => array(
            'title'=>'易拼车',
            'content'=>"抱歉乘客%s临时有事，取消了与您的拼车计划~~",
            'ticker_text'=>"抱歉乘客%s临时有事，取消了与您的拼车计划~~",
        ),
        'finish_order' => array(
            'title'=>'易拼车',
            'content'=>"恭喜您完成拼车，感谢您为北京蓝天做出的贡献",
            'ticker_text'=>"恭喜您完成拼车，感谢您为北京蓝天做出的贡献",
        ),
    );

    
    // 数据库server配置
    public static $DBServer = array(
        'host' => array(
            array('rdsv63ujvaq3yma.mysql.rds.aliyuncs.com', 3306),
            array('rdsv63ujvaq3yma.mysql.rds.aliyuncs.com', 3306), 
        ),
        
        'uname'    => 'pinche',
        'password' => 'pinche',
        'dbname'   => 'carpooldb',
        'charset'  => 'utf8',
    );
}

 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
