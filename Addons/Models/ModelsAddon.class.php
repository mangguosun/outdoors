<?php

namespace Addons\Models;
use Common\Controller\Addon;

/**
 * 模块管理插件
 * @author 启城
 */

    class ModelsAddon extends Addon{

        public $info = array(
            'name'=>'Models',
            'title'=>'模块管理',
            'description'=>'模块管理',
            'status'=>1,
            'author'=>'启城',
            'version'=>'0.1'
        );

        public $admin_list = array(
            'model'=>'Example',		//要查的表
			'fields'=>'*',			//要查的字段
			'map'=>'',				//查询条件, 如果需要可以再插件类的构造方法里动态重置这个属性
			'order'=>'id desc',		//排序,
			'listKey'=>array( 		//这里定义的是除了id序号外的表格里字段显示的表头名
				'字段名'=>'表头显示名'
			),
        );

        public function lists(){
            //所有模块列表
            $list = array();
            //系统默认模块
            $defaultModules = array('.','..','Admin','Common','Install','User');
            //检测所有模块
            $dirHandle = opendir(APP_PATH);
            //获取已安装的模块
            $installedModules = M('Module')->field(true)->select();
            // var_dump($installedModules);die;
            F('module/modules',$installedModules);   //更新缓存
            $installedNames = array();
            if(!empty($installedModules)){
                foreach ($installedModules as $v){
                    $installedNames[] = $v['name'];
                }
            }
            while (false !== ($moduleDir = readdir($dirHandle))){
                //不处理的模块
                if (in_array($moduleDir, $defaultModules) || in_array($moduleDir, $installedNames)){
                    continue;
                }
                $module = array();
                //获取未安装模块
                // dump($moduleDir);die;
                if ($this->checkModule($moduleDir) === true){
                    $confPath = APP_PATH.$moduleDir.'/Conf/config.php';
                    //获取配置信息
                    $config = include_once $confPath;
                    $module['name']         = $moduleDir;
                    $module['title']        = $config['MODULE_TITLE'];
                    $module['description']  = $config['MODULE_DESCRIPTION'];
                }
                !empty($module) && array_push($list, $module);
            }
            closedir($dirHandle);
            $list = empty($installedModules) ? $list : array_merge($list, $installedModules);
            int_to_string($list);

            $this->assign('list', $list);
            $this->meta_title = '模块管理';
            // var_dump('Addons/Models@adminlist');die;
            $this->display('index');
        }

        public function install(){
           $db_prefix = C('db_prefix'); //表前缀
            $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
$sql = <<<ROTSTR
CREATE TABLE IF NOT EXISTS `{$db_prefix}module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` char(50) NOT NULL DEFAULT '' COMMENT '模块标识',
  `title` char(100) NOT NULL DEFAULT '' COMMENT '模块名称',
  `description` text NOT NULL COMMENT '模块描述',
  `default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0：非默认模块，1：默认',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装日期',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ROTSTR;
$Model->execute($sql);
$sqls = array(
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Home', '前台', '这是OneThink的前台模块，用以展示文档和一些基本的操作。', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Api', 'API', '未知。', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Event', '活动', 'ThinkOX活动模块。', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Forum', '贴吧', 'ThinkOX贴吧模块。', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Issue', '专辑', 'ThinkOX专辑模块', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'People', '未知', 'ThinkOX自带模块,功能未知', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Shop', '商城', 'ThinkOX商城模块', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Usercenter', '个人主页', 'ThinkOX个人主页模块(猜的)', 1, 1, ".NOW_TIME .");",
    "INSERT INTO `{$db_prefix}module` ( `name`, `title`, `description`, `default`, `status`, `create_time`) VALUES( 'Weibo', '微博', 'ThinkOX微博模块', 1, 1, ".NOW_TIME .");",
    );

        foreach ($sqls as $sql) {
            $Model->execute($sql);
        }
            return true;
        }

        public function uninstall(){
            $db_prefix = C('db_prefix'); //表前缀
            $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
            $Model->execute('DROP TABLE '.$db_prefix.'module');
            return true;
        }

        //实现的app_begin钩子方法
        public function app_begin($param){
           //  if(!$this->IN_Module(MODULE_NAME)){
           //    exit('不存在的模块或被禁用！');
           //  }
           // return true;
        }

    /**
     * 检查模块规范
     * @author huajie <banhuajie@163.com>
     */
    protected function checkModule($name = ''){
        $configPath = APP_PATH.$name.'/Conf/config.php';
        if (empty($name)) {
            return '参数错误！';
        } elseif (!is_dir(APP_PATH.$name)){
            return '该模块不存在！';
        } elseif (!file_exists($configPath)){
            return '该模块配置文件缺失！';
        } elseif (!is_writeable($configPath)){
            return '模块配置文件不可写！';
        }
        //TODO:检查目录权限
        return true;
    }

    /**
     * 检查模块是否可用
     * @author huajie <banhuajie@163.com>
     */
    protected function IN_Module($name = ''){
        if(in_array($name, array('Admin','Home'))) return true;   //这里设置模块白名单 不需要判断的     
        // if(in_array($name, array('Forum'))) return FALSE;这里设置模块黑名单 ,则不能在前台访问的模块
         $models = F('module/modules');
        if(!$models){
            $models = M('Module')->field(true)->select();
        }        
        F('module/modules',$models);
        // var_dump($models);die;
       foreach ($models as $key => $value) {
          if($value['name'] == $name)  return true;
       }
        return FALSE;
    }


    }