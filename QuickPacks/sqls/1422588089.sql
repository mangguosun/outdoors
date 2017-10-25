/*修改签到表，放置到第一行放置被反复执行*/
ALTER TABLE  `opensns_check_info` ADD  `total_score` INT( 11 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `opensns_check_info` ADD  `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

DELETE FROM `opensns_menu` WHERE `url` like '%module/%';
INSERT INTO `opensns_menu` ( `title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`) VALUES
( '云平台', 0, 999999, 'module/lists', 0, '', '', 0);
set @tmp_id=0;
select @tmp_id:= id from `opensns_menu` where `title` = '云平台';
INSERT INTO `opensns_menu` ( `title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`) VALUES
( '模块管理', @tmp_id, 0, 'module/lists', 0, '', '云平台', 0),
( '卸载模块', @tmp_id, 0, 'module/uninstall', 1, '', '云平台', 0),
( '模块安装', @tmp_id, 0, 'module/install', 1, '', '云平台', 0);


DELETE FROM `opensns_hooks` WHERE `name` = 'userRegister';
INSERT INTO `opensns_hooks` ( `name`, `description`, `type`, `update_time`, `addons`) VALUES
( 'userRegister', '用户注册钩子，参数uid为用户ID', 2, 1419563244, '');

DELETE FROM `opensns_menu` WHERE `url` like 'Admin/Update%';
DELETE FROM `opensns_menu` WHERE `url` like 'Update/%';
INSERT INTO `opensns_menu` ( `title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`) VALUES
( '全部补丁', 68, 0, 'Update/quick', 0, '', '升级补丁', 0),
( '新增补丁', 68, 0, 'Update/addpack', 1, '', '升级补丁', 0);
delete FROM  `opensns_menu` WHERE  id >=58 and id<=67;

INSERT INTO `opensns_hooks` (`name`, `description`, `type`, `update_time`, `addons`) VALUES
( 'homeIndex', '网站首页钩子', 2, 1420525722, '');





create table `opensns_tmp` select max(`id`) as `id` from `opensns_check_info` group by `uid`;
create table `opensns_tmp2` select `opensns_check_info`.* from `opensns_check_info`,`opensns_tmp` where `opensns_check_info`.`id` = `opensns_tmp`.`id`;
drop table `opensns_check_info`;
drop table `opensns_tmp`;
rename table `opensns_tmp2` to `opensns_check_info`;
alter table `opensns_check_info` drop column `id`;