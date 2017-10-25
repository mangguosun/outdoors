/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50534
Source Host           : localhost:3306
Source Database       : thinksns_3

Target Server Type    : MYSQL
Target Server Version : 50534
File Encoding         : 65001

Date: 2014-01-21 10:17:02
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for ts_schedule
-- ----------------------------
DROP TABLE IF EXISTS `ts_schedule`;
CREATE TABLE `ts_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_to_run` varchar(255) NOT NULL COMMENT '计划任务执行方法',
  `schedule_type` varchar(255) NOT NULL COMMENT '执行频率',
  `modifier` varchar(255) DEFAULT NULL COMMENT '执行频率,类型为MONTHLY时必须；ONCE时无效；其他时为可选，默认为1',
  `dirlist` varchar(255) DEFAULT NULL COMMENT '指定周或月的一天或多天。只与WEEKLY和MONTHLY共同使用时有效，其他时忽略。',
  `month` varchar(255) DEFAULT NULL COMMENT '指定一年中的一个月或多个月.只在schedule_type=MONTHLY时有效，其他时忽略。当modifier=LASTDAY时必须，其他时可选。有效值：Jan - Dec，默认为*(每个月)',
  `start_datetime` datetime NOT NULL COMMENT '开始时间',
  `end_datetime` datetime DEFAULT NULL COMMENT '结束时间',
  `last_run_time` datetime DEFAULT NULL COMMENT '最近执行时间',
  `info` varchar(255) DEFAULT NULL COMMENT '对计划任务的简要描述',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
