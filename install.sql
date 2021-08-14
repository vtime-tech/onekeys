DROP TABLE IF EXISTS `jianzhan_admin`;
CREATE TABLE `jianzhan_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL COMMENT '用户名',
  `password` varchar(255) DEFAULT NULL COMMENT '密码',
  `salt` varchar(255) DEFAULT NULL COMMENT '盐',
  `phone` varchar(255) DEFAULT NULL COMMENT '联系电话',
  `email` varchar(255) DEFAULT NULL COMMENT '联系邮箱',
  `identity` varchar(255) DEFAULT '2' COMMENT '身份 1超级管理员 2管理员',
  `status` varchar(255) NOT NULL DEFAULT '0' COMMENT '状态',
  `create_time` varchar(255) DEFAULT NULL,
  `update_time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='管理员表';

INSERT INTO `jianzhan_admin` VALUES (1,'admin','$2y$10$6RXJ17YLCwzln9Ik3fxd/e/ODT6NYnFqMvL7m7N7NbeRbbIWxcUvC','5hHKQT','0419-5868757','188jianzhan@vtime.cn','1','0','1615125119','1628824996');

DROP TABLE IF EXISTS `jianzhan_config`;
CREATE TABLE `jianzhan_config` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '配置项名',
  `value` longtext NOT NULL COMMENT '配置项值',
  `notes` varchar(255) DEFAULT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COMMENT='配置信息表';

INSERT INTO `jianzhan_config` VALUES (1,'site_name','玩客建站系统','网站名称'),(2,'site_keywords','玩客建站系统,自助建站系统','网站SEO关键词'),(3,'site_description','玩客建站系统快速帮您构建网站，1分钟轻松搭建。','网站描述'),(4,'site_url','http://192.168.10.157','网站地址'),(5,'site_title','玩客建站系统-您值得信赖的自助建站专家','网站标题'),(6,'site_icp','辽ICP备17013660号-8','ICP备案号'),(7,'site_gov','','公安备案号'),(8,'site_email','admin@admin.com','网站邮箱'),(9,'site_company','辽宁微时光科技有限公司','公司名'),(10,'sms_switch','1','短信开关'),(11,'sms_username','','短信宝用户名'),(12,'sms_password','','短信宝密码'),(13,'sms_sign','','短信签名'),(14,'sms_options','','短信选项'),(15,'email_switch','0','邮箱开关'),(16,'email_smtp','smtp.qq.com','邮箱SMTP地址'),(17,'email_port','465','邮箱端口号'),(18,'email_ssl','0','邮箱SSL开关'),(19,'email_username','123456@qq.com','邮箱用户名'),(20,'email_password','123123','邮箱密码'),(21,'email_options','','邮箱选项'),(22,'realname_switch','0','实名开关'),(23,'realname_price','1.5','实名单价'),(24,'realname_tydata','','天眼数据APPCODE'),(25,'realname_options','','实名认证选项'),(28,'epay_url','','易支付地址'),(29,'epay_id','','易支付ID'),(30,'epay_key','','易支付Key'),(32,'alipay_switch','1','支付宝开关'),(33,'wxpay_switch','1','微信支付开关'),(34,'qqpay_switch','1','QQ支付开关'),(35,'epay_switch','1','易支付开关'),(36,'alipay_appId','','支付宝AppID'),(37,'alipay_privateKey','','支付宝私钥'),(38,'alipay_publicKey','','支付宝公钥'),(39,'alipay_realnameSwitch','0','支付宝身份验证开关'),(40,'tydata_realnameSwitch','1','天眼数据实名开关'),(46,'pay_switch','1','支付开关'),(47,'wxpay_appId','','微信支付APPID'),(48,'wxpay_mchId','','微信支付商户ID'),(49,'wxpay_key','','微信支付密钥');

DROP TABLE IF EXISTS `jianzhan_daili`;
CREATE TABLE `jianzhan_daili` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` int(11) DEFAULT '1' COMMENT '代理等级',
  `discount` int(11) DEFAULT '10' COMMENT '代理折扣',
  `price` int(11) DEFAULT NULL COMMENT '价钱',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='代理等级';

INSERT INTO `jianzhan_daili` VALUES (1,1,10,0),(2,2,9,20),(3,3,7,30),(4,4,6,50),(5,5,5,60),(6,6,4,70),(7,7,3,100);

DROP TABLE IF EXISTS `jianzhan_domain`;
CREATE TABLE `jianzhan_domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) DEFAULT NULL COMMENT '服务器ID',
  `domain` varchar(255) DEFAULT NULL COMMENT '一级域名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='域名绑定';

DROP TABLE IF EXISTS `jianzhan_expense_record`;
CREATE TABLE `jianzhan_expense_record` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户ID',
  `money` varchar(255) DEFAULT NULL COMMENT '消费金额',
  `before` varchar(255) DEFAULT NULL COMMENT '消费前余额',
  `after` varchar(255) DEFAULT NULL COMMENT '消费后余额',
  `detail` varchar(255) DEFAULT NULL COMMENT '消费了什么',
  `create_time` varchar(255) DEFAULT NULL,
  `update_time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='消费记录';

DROP TABLE IF EXISTS `jianzhan_login_log`;
CREATE TABLE `jianzhan_login_log` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL COMMENT '来访IP',
  `ua` varchar(255) DEFAULT NULL COMMENT '来访UA',
  `status` int(11) DEFAULT NULL COMMENT '登录状态',
  `create_time` int(10) DEFAULT NULL,
  `update_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='登录记录表';

DROP TABLE IF EXISTS `jianzhan_program`;
CREATE TABLE `jianzhan_program` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(255) DEFAULT NULL COMMENT '程序所属服务器',
  `productId` varchar(255) DEFAULT NULL COMMENT '所属服务器的对应搭建产品ID',
  `name` varchar(255) DEFAULT NULL COMMENT '程序名称',
  `install` varchar(255) DEFAULT NULL COMMENT '安装PHP文件名称',
  `htaccess` longtext COMMENT '伪静态内容',
  `price` varchar(255) DEFAULT NULL COMMENT '价格',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网站类型';

DROP TABLE IF EXISTS `jianzhan_realname_personal`;
CREATE TABLE `jianzhan_realname_personal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `type` varchar(255) NOT NULL DEFAULT '' COMMENT '认证方式',
  `name` varchar(255) NOT NULL COMMENT '姓名',
  `mobile` varchar(255) DEFAULT NULL COMMENT '手机号',
  `idcard` varchar(18) NOT NULL COMMENT '身份证号',
  `certify_id` varchar(255) NOT NULL DEFAULT '',
  `create_time` int(10) DEFAULT NULL,
  `update_time` int(10) DEFAULT NULL,
  `status` int(1) DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='个人实名认证信息表';

DROP TABLE IF EXISTS `jianzhan_recharge`;
CREATE TABLE `jianzhan_recharge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户ID',
  `out_trade_no` varchar(255) DEFAULT NULL COMMENT '订单号',
  `name` varchar(255) DEFAULT NULL COMMENT '订单名称',
  `money` varchar(255) DEFAULT NULL COMMENT '充值金额',
  `status` int(11) DEFAULT '1' COMMENT '充值状态',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `gateway` varchar(255) DEFAULT NULL COMMENT '充值网关',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值记录表';

DROP TABLE IF EXISTS `jianzhan_server`;
CREATE TABLE `jianzhan_server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '服务器名',
  `type` int(11) DEFAULT NULL COMMENT '被控类型 1为kangle',
  `ip` varchar(255) DEFAULT NULL COMMENT '被控ip',
  `port` varchar(255) DEFAULT NULL COMMENT '被控端口',
  `authcode` varchar(255) DEFAULT NULL COMMENT '被控授权码-安全码',
  `realname` int(11) NOT NULL DEFAULT '1' COMMENT '实名 0需要 1不需要',
  `create_time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='建站服务器';

DROP TABLE IF EXISTS `jianzhan_sms`;
CREATE TABLE `jianzhan_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
  `code` varchar(255) NOT NULL DEFAULT '' COMMENT '验证码',
  `ip` varchar(255) NOT NULL DEFAULT '' COMMENT '用户IP地址',
  `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) DEFAULT '0' COMMENT '使用时间',
  `status` int(1) DEFAULT '0' COMMENT '状态 0未使用 1已使用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信信息表';

DROP TABLE IF EXISTS `jianzhan_users`;
CREATE TABLE `jianzhan_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) DEFAULT NULL COMMENT '用户名',
  `password` varchar(255) DEFAULT NULL COMMENT '密码',
  `salt` varchar(255) NOT NULL DEFAULT 'fikcdn' COMMENT '盐',
  `phone` varchar(11) DEFAULT NULL COMMENT '手机号',
  `email` varchar(20) DEFAULT NULL COMMENT '邮箱',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
  `level` varchar(255) NOT NULL DEFAULT '1' COMMENT '等级',
  `realname` int(1) DEFAULT '1' COMMENT '实名状态',
  `secret_id` varchar(255) DEFAULT NULL,
  `secret_key` varchar(255) DEFAULT NULL,
  `create_time` varchar(255) DEFAULT NULL COMMENT '创建时间',
  `update_time` varchar(255) DEFAULT NULL COMMENT '更改时间',
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '账号状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

DROP TABLE IF EXISTS `jianzhan_web`;
CREATE TABLE `jianzhan_web` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '所属用户',
  `server_id` int(11) DEFAULT NULL COMMENT '网站所在服务器',
  `program_id` varchar(255) DEFAULT NULL COMMENT '程序包ID',
  `secondName` varchar(255) DEFAULT NULL COMMENT '二级域名/主机用户名/数据库用户名',
  `domain_id` int(11) DEFAULT NULL COMMENT '一级域名ID',
  `password` varchar(255) DEFAULT NULL COMMENT '主机密码/数据库密码',
  `begin_time` varchar(255) DEFAULT NULL COMMENT '搭建时间',
  `end_time` varchar(255) DEFAULT NULL COMMENT '到期时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COMMENT='网站目录';