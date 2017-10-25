DROP TABLE IF EXISTS `opensns_store_category`;
DROP TABLE IF EXISTS `opensns_store_com`;
DROP TABLE IF EXISTS `opensns_store_currency`;
DROP TABLE IF EXISTS `opensns_store_data`;
DROP TABLE IF EXISTS `opensns_store_entity`;
DROP TABLE IF EXISTS `opensns_store_fav`;
DROP TABLE IF EXISTS `opensns_store_field`;
DROP TABLE IF EXISTS `opensns_store_goods`;
DROP TABLE IF EXISTS `opensns_store_item`;
DROP TABLE IF EXISTS `opensns_store_order`;
DROP TABLE IF EXISTS `opensns_store_rate`;
DROP TABLE IF EXISTS `opensns_store_read`;
DROP TABLE IF EXISTS `opensns_store_send`;
DROP TABLE IF EXISTS `opensns_store_shop`;
/*卸载掉关联广告位*/
DELETE FROM `opensns_advertising` WHERE `pos`  = 'store_index_focus';

/*删除menu相关数据*/
set @tmp_id=0;
select @tmp_id:= id from `opensns_menu` where title = '微店';
delete from `opensns_menu` where id = @tmp_id or (pid = @tmp_id and pid!=0);
delete from `opensns_menu` where  `url` like 'Store/%';
