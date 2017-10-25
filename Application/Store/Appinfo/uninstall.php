<?php
if (!defined('SITE_PATH')) exit();
$db_prefix = C('DB_PREFIX');
$sql = array(
    "DROP TABLE IF EXISTS `{$db_prefix}cat`;",
    "DROP TABLE IF EXISTS `{$db_prefix}store_com`;",

    "DROP TABLE IF EXISTS `{$db_prefix}store_data`;",
    "DROP TABLE IF EXISTS `{$db_prefix}store_entity`;",
    "DROP TABLE IF EXISTS `{$db_prefix}store_fav`;",
    "DROP TABLE IF EXISTS `{$db_prefix}store_field`;",
    "DROP TABLE IF EXISTS `{$db_prefix}Goods`;",
    "DROP TABLE IF EXISTS `{$db_prefix}store_rate`;",
    "DROP TABLE IF EXISTS `{$db_prefix}store_read`;",
    "DROP TABLE IF EXISTS `{$db_prefix}store_send`;",
    "DELETE FROM `{$db_prefix}system_config` WHERE `key` like 'store_Admin_%';",
    "DELETE FROM `{$db_prefix}system_data` WHERE `list` = 'store_Admin';",

);
foreach ($sql as $v) {
    D('')->execute($v);
}