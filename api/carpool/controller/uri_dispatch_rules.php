<?php

/*
 * hash匹配
 * 1. 没有dispatch返回，表示根据uri就能确定action
 * 2. 有dispatch返回，值为openapi，表示rest风格api，按method区分action，并且所有
 *      的action都在同一个文件中
 * 3. 有dispatch返回，值为openapi2，同2，只是所有的action在不同文件中
 */
ActionControllerConfig::$config['hash_mapping'] = array(
    
    // 情况1
    /*'/rest/2.0/internal/acl/pubcheck' => array(
        'AclPubCheckAction',
    ),*/
    
    

    /*
    // 情况2
    '/rest/2.0/test' => array(
        'TestAction',
        'dispatch'=>'openapi'
    ),
    */

    
    // 情况3，还需要根据method再进行一次hash匹配
    '/rest/2.0/carpool/user' => array(
        'method_hash' => array(
            'reg' => array(
                'UserRegisterAction',
            ),

            'query' => array(
                'UserQueryAction',
            ),
            
            'login' => array(
                'UserLoginAction',
            ),
            
            'gettoken' => array(
                'UserGetTokenAction',
            ),
            'report' => array(
                'UserReportAction',
            ),
            'auth' => array(
                'UserAuthAction',
            ),
            'modify' => array(
                'UserModifyAction',
            ),

        ),
        
        'dispatch'=>'openapi2',
    ),

    '/rest/2.0/carpool/order' => array(
        'method_hash' => array(
            'create' => array(
                'CarpoolCreateAction',
            ),
            'cancel' => array(
                'CarpoolCancelAction',
            ),
            'accept' => array(
                'CarpoolAcceptAction',
            ),
            'aboard' => array(
                'CarpoolAboardAction',
            ),
            'finish' => array(
                'CarpoolFinishAction',
            ),
            'list' => array(
                'CarpoolListAction',
            ),  
            'query' => array(
                'CarpoolQueryAction',
            ),    
            'batch_query' => array(
                'CarpoolBatchQueryAction',
            ),  
            'nearby' => array(
                'CarpoolNearbyAction',
            ),  
            
        ),
        
        'dispatch'=>'openapi2',
    ),
    
    '/rest/2.0/carpool/driver' => array(
        'method_hash' => array(
            'report' => array(
                'DriverReportAction',
            ),
        ),
        
        'dispatch'=>'openapi2',
    ),
    '/rest/2.0/carpool/feedback' => array(
        'method_hash' => array(
            'create' => array(
                'FeedbackCreateAction',
            ),
        ),
        
        'dispatch'=>'openapi2',
    ),
    '/rest/2.0/carpool/image' => array(
        'method_hash' => array(
            'thumbnail' => array(
                'ImageThumbnailAction',
            ),
        ),
        
        'dispatch'=>'openapi2',
    ),
    '/rest/2.0/carpool/file' => array(
        'method_hash' => array(
            'upload' => array(
                'ImageUploadAction',
            ),
        ),
        
        'dispatch'=>'openapi2',
    ),
);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
