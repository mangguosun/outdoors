/*删除表*/

DROP TABLE IF EXISTS `opensns_cat_data`, `opensns_cat_entity`, `opensns_cat_fav`, `opensns_cat_field`, `opensns_cat_info`, `opensns_cat_rate`, `opensns_cat_read`, `opensns_cat_send`;

/*删除menu相关数据*/
set @tmp_id=0;
select @tmp_id:= id from `opensns_menu` where title = '分类信息';
delete from `opensns_menu` where  id = @tmp_id or ( pid = @tmp_id  and pid !=0 );
delete from `opensns_menu` where  `url` like 'Cat/%';
