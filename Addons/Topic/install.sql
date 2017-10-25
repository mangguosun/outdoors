DROP TABLE IF EXISTS `onethink_topic`;
CREATE TABLE IF NOT EXISTS `onethink_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '话题名',
  `topiclogo` int(11) NOT NULL DEFAULT '0' COMMENT '话题logo',
  `intro` varchar(255) NOT NULL COMMENT '导语',
  `qrcode` varchar(255) NOT NULL COMMENT '二维码',
  `is_top` int(1) NOT NULL DEFAULT '0' COMMENT '是否为推荐话题',
  `uadmin` int(11) NOT NULL DEFAULT '0' COMMENT '话题管理   默认无',
  `readCount` int(11) NOT NULL DEFAULT '0' COMMENT '阅读',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;