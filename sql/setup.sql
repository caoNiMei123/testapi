DROP DATABASE IF EXISTS `carpooldb`;
CREATE DATABASE `carpooldb`;
USE `carpooldb`;

CREATE TABLE `user_info` (
    `user_id` bigint  unsigned  NOT NULL AUTO_INCREMENT,
    `name` varchar(50) default '',
    `phone`  bigint not null,
    `email`  varchar(100) default '', 
    `sex`  tinyint default 0, 
    `car_type` varchar(50) default '',
    `seat` tinyint default 0,
    `detail` varchar(1024) default '',
    `car_num` varchar(50) default '',
    `car_engine_num` varchar(50) default '',
    `user_type`  int not NULL,
    `head_bucket` varchar(50) default '',
    `head_object` varchar(100) default '',
    `ctime` int not NULL,
    `mtime` int not NULL,
    `user_status` tinyint default 0,
    `driver_status` tinyint default 0,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `user_key` (`user_id`, `user_type`, `user_status`, `driver_status`),
    UNIQUE KEY `phone_index` (`phone`, `user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT = 10000 COMMENT='用户表' ;

CREATE TABLE `pickride_info` (
    `id` int  NOT NULL AUTO_INCREMENT,
    `pid` bigint  unsigned NOT NULL,
    `user_id` bigint  unsigned,
    `user_status` tinyint DEFAULT 0,
    `driver_dev_id` varchar(64) NOT NULL,
    `passenger_dev_id` varchar(64) NOT NULL,
    `phone` bigint(20) DEFAULT NULL,
    `src` varchar(256),
    `dest` varchar(256),
    `src_latitude` decimal(10, 6),
    `src_longitude` decimal(10,6),
    `dest_latitude` decimal(10, 6),
    `dest_longitude` decimal(10, 6),
    `seat` tinyint default 1,
    `ctime` int not NULL,
    `mtime` int not NULL,
    `driver_id` bigint  unsigned default 0,
    `driver_phone` bigint(20) DEFAULT 0,
	`mileage` bigint  unsigned default 0,
    `status` tinyint DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pid_key` (`pid`),
    KEY `user_key` (`user_id`, `status`),
    KEY `driver_key` (`driver_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='拼车表' ;

CREATE TABLE `driver_info` (
    `id` bigint  NOT NULL AUTO_INCREMENT,
    `user_id` bigint  unsigned ,
    `dev_id` varchar(64)  NOT NULL,
    `dev_id_sign` bigint unsigned not NULL,
    `latitude` decimal(10, 6),
    `longitude` decimal(10, 6),
    `ctime` int not NULL,
    `mtime` int not NULL,
    `status` tinyint DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_key` (`user_id`),
    INDEX `latitude_index` (`latitude`),
    INDEX `longitude_index` (`longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='司机信息表' ;

CREATE TABLE `device_info` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint  unsigned  NOT NULL,
  `client_id` varchar(64)  NOT NULL, # 推送id
  `dev_id` varchar(64)  NOT NULL,    # 设备id
  `dev_id_sign` bigint  unsigned not NULL, # dev_id签名
  `status` tinyint default 0, #0表示在线 1表示离线 
  `ctime` int not NULL, 
  `mtime` int not NULL, 
  PRIMARY KEY (`id`),
  UNIQUE KEY `udid_key` (`user_id`, `dev_id_sign`),
  INDEX `uid_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备表';



CREATE TABLE `secstr_info` (
    `id` bigint  NOT NULL AUTO_INCREMENT,
    `account`  varchar(256) not null,
    `type` tinyint default 0,
    `secstr` varchar(256) not null,
    `user_id` bigint default 0 ,
    `ctime` int not NULL,
    PRIMARY KEY (`id`),
    KEY `account_key` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='临时令牌表' ;

CREATE TABLE `client_version_info` (
    `id` bigint  NOT NULL AUTO_INCREMENT,
    `ctype` int  unsigned ,
    `update_type` int  unsigned ,
    `version` bigint unsigned,
    `detail` varchar(1024),
    `url` varchar(1024),
    `ctime` int not NULL,
    `mtime` int not NULL,
    `status` tinyint DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `version_key` (`ctype`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='版本信息表' ;



CREATE TABLE `task_info_0` (
    `id` bigint  NOT NULL AUTO_INCREMENT,
    `pid` bigint  unsigned NOT NULL,
    `user_id` bigint  unsigned,
    `phone`  bigint,
    `ctime` int not NULL, 
    `mtime` int not NULL, 
    `status` tinyint DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pid_key` (`pid`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='任务表' ;

CREATE TABLE `task_info_1` like `task_info_0`;
CREATE TABLE `task_info_2` like `task_info_0`;
CREATE TABLE `task_info_3` like `task_info_0`;
CREATE TABLE `task_info_4` like `task_info_0`;
CREATE TABLE `task_info_5` like `task_info_0`;
CREATE TABLE `task_info_6` like `task_info_0`;
CREATE TABLE `task_info_7` like `task_info_0`;
CREATE TABLE `task_info_8` like `task_info_0`;
CREATE TABLE `task_info_9` like `task_info_0`;
CREATE TABLE `task_info_10` like `task_info_0`;
CREATE TABLE `task_info_11` like `task_info_0`;
CREATE TABLE `task_info_12` like `task_info_0`;
CREATE TABLE `task_info_13` like `task_info_0`;
CREATE TABLE `task_info_14` like `task_info_0`;
CREATE TABLE `task_info_15` like `task_info_0`;

CREATE TABLE `complain_info` (
    `id` int  NOT NULL AUTO_INCREMENT,    
    `user_id` bigint  unsigned,
    `type` tinyint not null,
    `phone`  bigint not null,
    `content` varchar(2048),    
    `ctime` int not NULL,
    `mtime` int not NULL,    
    `status` tinyint DEFAULT 0,
    PRIMARY KEY (`id`)   
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='投诉表' ;

CREATE TABLE `log_info` (
    `id` int  NOT NULL AUTO_INCREMENT,
    `day` int  unsigned NOT NULL,
    `hour` int  unsigned NOT NULL,
    `item_1` bigint  unsigned default 0, -- 发布订单总数 
    `item_2` bigint  unsigned default 0, -- 成交订单总数
    `item_3` bigint  unsigned default 0, -- 超时订单总数
    `item_4` bigint  unsigned default 0, 
    `item_5` bigint  unsigned default 0, 
    `item_6` bigint  unsigned default 0, 
    `item_7` bigint  unsigned default 0, 
    `item_8` bigint  unsigned default 0, 
    `item_9` bigint  unsigned default 0, 
    `item_10` bigint  unsigned default 0,   
    PRIMARY KEY (`id`),
    UNIQUE KEY `day_key` (`day`, `hour`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统计表' ;
