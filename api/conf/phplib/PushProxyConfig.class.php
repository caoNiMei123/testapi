<?php

class PushProxyConfig
{
    public static $pushDebugMode = true;
    
    /*
     * 对应于个推的消息模板类别，目前只考虑了android支持
     * notify:
     * 对应NotificationTemplate
     * 点击通知启动应用，在通知栏显示一条含图标、标题等的通知，用户点击后弹出框，用户可以选择直接下载应用或者取消下载应用
     * 
     * link:
     * 对应LinkTemplate
     * 点击通知打开网页
     * 在通知栏显示一条含图标、标题等的通知，用户点击可打开您指定的网页
     * 
     * popload
     * 对应NotyPopLoadTemplate
     * 通知栏弹框下载模版
     * 在通知栏显示一条含图标、标题等的通知，用户点击后弹出框，用户可以选择直接下载应用或者取消下载应用
     * 
     * trans
     * 对应TransmissionTemplate
     * 透传（payload）  
     * 数据经SDK传给您的客户端，由您写代码决定如何处理展现给用户
     */
    public static $arrPushMsgType = array(
        'notify'    => 1,
        'link'      => 2,
        'popload'   => 3,
        'trans'     => 4,
    );

    public static $arrPushMsgNotify = array(
        //删除拼车
        'pickride_delete'=>
            array(
                'title' => '通知标题; 
                                                                                    必选; 
                            40中/英字符',
                'text'  => '通知内容; 
                                                                                    必选； 
                            600中/英字符',
                'logo'  => '通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png; 
                                                                                    必选; 
                            40中/英字符',
                'trans_type' => '收到消息是否立即启动应用：1为立即启动，2则广播等待客户端自启动; 
                                                                                                   必选;
                                 4Byte',
                'trans_content' => '透传内容，不支持转义字符;
                                                                                                            必选;
                                    2048中/英字符',
            
            
                'is_ring'       => '收到通知是否响铃：true响铃，false不响铃。默认响铃;
                                                                                                           可选',
                'is_vibrate'    => '收到通知是否振动：true振动，false不振动。默认振动; 
                                                                                                           可选',
                'is_clearable'  => '通知是否可清除：true可清除，false不可清除。默认可清除; 
                                                                                                            可选',
            ),
        'pickride_join'=>
            array(
                'title' => '通知标题; 
                                                                                    必选; 
                            40中/英字符',
                'text'  => '通知内容; 
                                                                                    必选； 
                            600中/英字符',
                'logo'  => '通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png; 
                                                                                    必选; 
                            40中/英字符',
                'trans_type' => '收到消息是否立即启动应用：1为立即启动，2则广播等待客户端自启动; 
                                                                                                   必选;
                                 4Byte',
                'trans_content' => '透传内容，不支持转义字符;
                                                                                                            必选;
                                    2048中/英字符',
            
            
                'is_ring'       => '收到通知是否响铃：true响铃，false不响铃。默认响铃;
                                                                                                            可选',
                'is_vibrate'    => '收到通知是否振动：true振动，false不振动。默认振动; 
                                                                                                            可选',
                'is_clearable'  => '通知是否可清除：true可清除，false不可清除。默认可清除; 
                                                                                                            可选',
            ),

        'pickride_quit'=>
            array(
                'title' => '通知标题; 
                                                                                    必选; 
                            40中/英字符',
                'text'  => '通知内容; 
                                                                                    必选； 
                            600中/英字符',
                'logo'  => '通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png; 
                                                                                    必选; 
                            40中/英字符',
                'trans_type' => '收到消息是否立即启动应用：1为立即启动，2则广播等待客户端自启动; 
                                                                                                   必选;
                                 4Byte',
                'trans_content' => '透传内容，不支持转义字符;
                                                                                                            必选;
                                    2048中/英字符',
            
            
                'is_ring'       => '收到通知是否响铃：true响铃，false不响铃。默认响铃;
                                                                                                            可选',
                'is_vibrate'    => '收到通知是否振动：true振动，false不振动。默认振动; 
                                                                                                            可选',
                'is_clearable'  => '通知是否可清除：true可清除，false不可清除。默认可清除; 
                                                                                                            可选',
            ),
        'pickride_agree'=>
            array(
                'title' => '通知标题; 
                                                                                    必选; 
                            40中/英字符',
                'text'  => '通知内容; 
                                                                                    必选； 
                            600中/英字符',
                'logo'  => '通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png; 
                                                                                    必选; 
                            40中/英字符',
                'trans_type' => '收到消息是否立即启动应用：1为立即启动，2则广播等待客户端自启动; 
                                                                                                   必选;
                                 4Byte',
                'trans_content' => '透传内容，不支持转义字符;
                                                                                                            必选;
                                    2048中/英字符',
            
            
                'is_ring'       => '收到通知是否响铃：true响铃，false不响铃。默认响铃;
                                                                                                            可选',
                'is_vibrate'    => '收到通知是否振动：true振动，false不振动。默认振动; 
                                                                                                            可选',
                'is_clearable'  => '通知是否可清除：true可清除，false不可清除。默认可清除; 
                                                                                                            可选',
            ),


    );        

    
    
    
    /*
     * link消息的内容示例，使用时按照下列格式构造消息
     */
    public static $arrPushMsgLink = array(
        'title' => '通知标题; 
                    必选; 
                    40中/英字符',
        'text'  => '通知内容; 
                    必选； 
                    600中/英字符',
        'logo'  => '通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png; 
                    必选; 
                    40中/英字符',
        'url'   => '点击通知后打开的网页地址;
                    必选;
                    200中/英字符',
    
    
        'is_ring'       => '收到通知是否响铃：true响铃，false不响铃。默认响铃;
                            可选',
        'is_vibrate'    => '收到通知是否振动：true振动，false不振动。默认振动; 
                            可选',
        'is_clearable'  => '通知是否可清除：true可清除，false不可清除。默认可清除; 
                            可选',
    );
    
    /*
     * popload消息的内容示例，使用时按照下列格式构造消息
     */
    public static $arrPushMsgPopload = array(
        // todo
    );
    
    /*
     * trans消息的内容示例，使用时按照下列格式构造消息
     */
    public static $arrPushMsgTrans = array(
        'trans_type' => '收到消息是否立即启动应用：1为立即启动，2则广播等待客户端自启动; 
                           必选;
                         4Byte',
        'trans_content' => '透传内容，不支持转义字符;
                            必选;
                            2048中/英字符',
    );

    /*
     * group类型
     * app: 应用类型，对应的group成员是个推的appid
     * ctype: 设备类型，对应的group成员是ANDROID、IOS
     * province: 省份，对应group成员是江西、浙江
     * tag：标签，这需要个推后台设置
     */
	public static $arrPushGroupType = array(
		'app' 		=> 1,
		'ctype' 	=> 2,
		'province'	=> 3,
		'tag'		=> 4,
	);
	
	// 个推appid
	public static $arrPushAppid = array(
		'driver' => '6N67PoCPHM6whyzs2QX4z5',
		'passenger' => '6N67PoCPHM6whyzs2QX4z5',
	);
	
	// 个推appkey
	public static $arrPushAppkey = array(
		'driver' => 'B7ZXT5FW1c9mBK2QzYhII3',
		'passenger' => 'B7ZXT5FW1c9mBK2QzYhII3',
	);
	
	// 个推mastersecret
	public static $arrPushMasterSecret = array(
		'driver' => '1zQgZL1uSU81i9cCwOWWc3',
		'passenger' => '1zQgZL1uSU81i9cCwOWWc3',
	);
	
	// 个推host
    public static $host = 'http://sdk.open.api.igexin.com/apiex.htm';
    
    // 离线消息默认超时时间，单位秒
    const PUSH_OFFLINE_EXPIRE_TIME = 86400;
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
