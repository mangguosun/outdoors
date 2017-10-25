SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*广告位*/
INSERT INTO `opensns_advertising` (`title`, `type`, `width`, `height`, `status`, `pos`, `style`) VALUES
( '微店首页焦点图广告位', 2, '665', '278', 1, 'store_index_focus', 2);
/*菜单*/
INSERT INTO `opensns_menu` (`title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`) VALUES
( '微店', 0, 21, 'store/config', 0, '', '', 0);

set @tmp_id=0;
select @tmp_id:= id from `opensns_menu` where title = '微店';


INSERT INTO `opensns_menu` ( `title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`) VALUES
( '设置', @tmp_id, 0, 'store/config', 0, '', '设置', 0),
( '分类管理', @tmp_id, 0, 'store/category', 0, '管理分类', '分类管理', 0),
( '编辑分类', @tmp_id, 0, 'store/add', 1, '', '', 0),
( '设置分类状态', @tmp_id, 0, 'store/setstatus', 1, '', '', 0),
( '商品管理', @tmp_id, 0, 'store/goods', 0, '', '管理', 0),
( '店铺管理', @tmp_id, 0, 'store/shop', 0, '', '管理', 0),
( '订单管理', @tmp_id, 0, 'store/order', 0, '', '管理', 0),
( '设置商品状态', @tmp_id, 0, 'store/setsgoodstatus', 1, '', '设置', 0),
( '设置店铺状态', @tmp_id, 0, 'store/setsshopstatus', 1, '', '设置', 0);


CREATE TABLE IF NOT EXISTS `opensns_store_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `sort` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `ext` text NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `entity_id` int(11) NOT NULL COMMENT '绑定的属性模型',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=37 ;

INSERT INTO `opensns_store_category` (`id`, `title`, `sort`, `pid`, `ext`, `status`, `entity_id`) VALUES
(7, '数码产品', 4, 0, '', 1, 0),
(6, '图书', 3, 0, '', 1, 0),
(8, '美妆个护', 5, 0, '', 1, 0),
(9, '服饰内衣', 6, 0, '', 1, 0),
(10, '运动户外', 7, 0, '', 1, 0),
(11, '小说', 1, 6, '', 1, 0),
(12, '手机2', 1, 7, '', 1, 0),
(13, '衣服', 1, 9, '', 1, 0),
(14, '裤子', 2, 9, '', 1, 0),
(15, '化妆品', 1, 8, '', 1, 0),
(16, '登山工具', 1, 10, '', 1, 0),
(17, '登山鞋', 0, 16, '', 1, 0),
(18, '登山服', 0, 16, '', 1, 0),
(19, '山地车', 0, 16, '', 1, 0),
(20, '登山拐杖', 1, 10, '', 1, 0),
(21, '雨衣', 0, 16, '', 1, 0),
(22, '食品零食', 0, 0, '', 1, 0),
(23, '电脑办公', 0, 0, '', 1, 0),
(24, '家用电器', 0, 0, '', 1, 0),
(25, '母婴玩具', 0, 0, '', 1, 0),
(26, '水果', 0, 22, '', 1, 0),
(27, '手机', 1, 23, '', 1, 0),
(28, '电脑', 1, 23, '', 1, 0),
(29, '电视', 1, 24, '', 1, 0),
(30, '经管', 1, 6, '', 1, 0),
(31, '言情', 1, 11, '', 1, 0),
(32, '武侠', 1, 11, '', 1, 0),
(33, '无敌酷炫', 1, 20, '', 1, 0),
(34, '汉堡吧', 1, 20, '', 1, 0),
(35, '虚拟物品', 1, 0, '', 1, 0),
(36, '我的', 1, 0, '', 1, 0);

CREATE TABLE IF NOT EXISTS `opensns_store_com` (
  `com_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cTime` int(11) NOT NULL,
  `content` text NOT NULL,
  `info_id` int(11) NOT NULL,
  PRIMARY KEY (`com_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `opensns_store_currency` (
  `uid` int(11) NOT NULL COMMENT '用户id，一一对应',
  `currency` decimal(10,2) NOT NULL COMMENT '余额，持有货币数',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微店自带货币表';



CREATE TABLE IF NOT EXISTS `opensns_store_data` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `value` text NOT NULL,
  `info_id` int(11) NOT NULL,
  PRIMARY KEY (`data_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1101 ;



CREATE TABLE IF NOT EXISTS `opensns_store_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `can_post_gid` varchar(50) NOT NULL,
  `can_read_gid` varchar(50) NOT NULL,
  `tpl3` text NOT NULL,
  `tpl1` text NOT NULL,
  `tpl2` text NOT NULL,
  `alias` varchar(20) NOT NULL,
  `tpl_detail` text NOT NULL,
  `tpl_list` text NOT NULL,
  `use_detail` int(11) NOT NULL,
  `use_list` int(11) NOT NULL,
  `des1` text NOT NULL,
  `des2` text NOT NULL,
  `des3` text NOT NULL,
  `can_over` int(11) NOT NULL COMMENT '允许设置截止日期',
  `show_nav` int(11) NOT NULL,
  `show_post` int(11) NOT NULL,
  `show_index` int(11) NOT NULL,
  `sort` int(11) NOT NULL,
  `can_rec` tinyint(4) NOT NULL,
  `rec_entity` varchar(50) NOT NULL,
  `need_active` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

INSERT INTO `opensns_store_entity` (`id`, `name`, `can_post_gid`, `can_read_gid`, `tpl3`, `tpl1`, `tpl2`, `alias`, `tpl_detail`, `tpl_list`, `use_detail`, `use_list`, `des1`, `des2`, `des3`, `can_over`, `show_nav`, `show_post`, `show_index`, `sort`, `can_rec`, `rec_entity`, `need_active`, `status`) VALUES
(8, 'good', '', '', '', '', '', '商品', '', '', -1, -1, '请仔细填写你的商品信息。确保商品的信息真实可靠。否则我们随时可能会将其下架。', '', '', 0, 1, 0, 0, 50, 0, '', 0, 1);

CREATE TABLE IF NOT EXISTS `opensns_store_fav` (
  `fav_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cTime` int(11) NOT NULL,
  `info_id` int(11) NOT NULL,
  PRIMARY KEY (`fav_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=38 ;



CREATE TABLE IF NOT EXISTS `opensns_store_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `input_type` int(11) NOT NULL,
  `option` text NOT NULL,
  `limit1` varchar(500) NOT NULL,
  `limit2` varchar(500) NOT NULL,
  `limit3` varchar(500) NOT NULL,
  `limit4` varchar(500) NOT NULL,
  `can_search` int(11) NOT NULL,
  `alias` varchar(30) NOT NULL,
  `name` varchar(20) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `sort` int(11) NOT NULL,
  `can_empty` int(11) NOT NULL,
  `over_hidden` int(11) NOT NULL COMMENT '到期后自动隐藏',
  `default_value` text NOT NULL,
  `tip` text NOT NULL,
  `args` text NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=86 ;

CREATE TABLE IF NOT EXISTS `opensns_store_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `create_time` int(11) NOT NULL,
  `read` int(11) NOT NULL,
  `sub` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL COMMENT '扩展属性模型ID',
  `over_time` int(11) NOT NULL COMMENT '截止时间',
  `rate` float NOT NULL,
  `sell` int(11) NOT NULL COMMENT '总销量',
  `has` int(11) NOT NULL COMMENT '库存',
  `shop_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `update_time` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `cat1` int(11) NOT NULL COMMENT '一级分类',
  `cat2` int(11) NOT NULL COMMENT '二级分类',
  `cat3` int(11) NOT NULL COMMENT '三级分类',
  `price` decimal(10,2) NOT NULL COMMENT '价格',
  `trans_fee` tinyint(4) NOT NULL COMMENT '运费形式，0买家承担运费，1卖家承担运费',
  `des` text NOT NULL COMMENT '商品描述',
  `cover_id` int(11) NOT NULL COMMENT '封面',
  `gallary` varchar(300) NOT NULL COMMENT '商品相册',
  `trans_fee_des` text NOT NULL COMMENT '运费描述',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=121 ;



CREATE TABLE IF NOT EXISTS `opensns_store_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `good_id` int(11) NOT NULL,
  `h_price` float NOT NULL,
  `cTime` int(11) NOT NULL,
  `h_name` varchar(50) NOT NULL,
  `order_id` int(11) NOT NULL,
  `h_price_bit` float NOT NULL,
  `count` int(11) NOT NULL,
  `h_pic` int(11) NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=63 ;



CREATE TABLE IF NOT EXISTS `opensns_store_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `create_time` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `response` tinyint(4) NOT NULL COMMENT '评分 0好评 1中评 2差评',
  `content` varchar(400) NOT NULL COMMENT '评价内容',
  `r_pos` varchar(100) NOT NULL COMMENT '收货人地址',
  `r_code` varchar(6) NOT NULL COMMENT '收货人邮编',
  `r_phone` varchar(15) NOT NULL COMMENT '收货人电话号码',
  `condition` tinyint(4) NOT NULL COMMENT '状态 0未付款 1已付款 2已发货 3已完成',
  `trans_code` varchar(40) NOT NULL,
  `trans_name` varchar(20) NOT NULL COMMENT '快递名称',
  `r_name` varchar(20) NOT NULL,
  `s_uid` int(11) NOT NULL COMMENT '卖家uid',
  `total_cny` float NOT NULL,
  `total_count` int(11) NOT NULL,
  `adj_cny` float NOT NULL COMMENT '调整的价钱',
  `trans_time` int(11) NOT NULL,
  `response_time` int(11) NOT NULL COMMENT '评论时间',
  `attach` varchar(200) NOT NULL,
  `pay_time` int(11) NOT NULL COMMENT '付款时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='订单表' AUTO_INCREMENT=89 ;


CREATE TABLE IF NOT EXISTS `opensns_store_rate` (
  `rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cTime` int(11) NOT NULL,
  `info_id` int(11) NOT NULL,
  `score` float NOT NULL,
  PRIMARY KEY (`rate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `opensns_store_read` (
  `read_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cTime` int(11) NOT NULL,
  `info_id` int(11) NOT NULL,
  PRIMARY KEY (`read_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=109 ;



CREATE TABLE IF NOT EXISTS `opensns_store_send` (
  `send_id` int(11) NOT NULL AUTO_INCREMENT,
  `send_uid` int(11) NOT NULL,
  `rec_uid` int(11) NOT NULL,
  `cTime` int(11) NOT NULL,
  `s_info_id` int(11) NOT NULL,
  `info_id` int(11) NOT NULL,
  `readed` tinyint(4) NOT NULL,
  PRIMARY KEY (`send_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `opensns_store_shop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `summary` varchar(500) NOT NULL,
  `logo` int(11) NOT NULL,
  `position` varchar(20) NOT NULL,
  `uid` int(11) NOT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `order_count` int(11) NOT NULL COMMENT '订单数',
  `visit_count` int(11) NOT NULL,
  `sell` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='商店表' AUTO_INCREMENT=4 ;

