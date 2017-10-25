<?php

namespace Addons\Schedule;
use Common\Controller\Addon;

/**
 * 计划任务插件
 * @author iszhang
 */

    class ScheduleAddon extends Addon{

        public $info = array(
            'name'=>'Schedule',
            'title'=>'计划任务',
            'description'=>'执行计划任务插件',
            'status'=>0,
            'author'=>'iszhang',
            'version'=>'0.1'
        );

        public $admin_list = array(
            'model'=>'Schedule',	//要查的表
			'fields'=>'*',			//要查的字段
			'map'=>'',				//查询条件, 如果需要可以再插件类的构造方法里动态重置这个属性
			'order'=>'id desc',		//排序,
			'listKey'=>array( 		//这里定义的是除了id序号外的表格里字段显示的表头名
				'id'=>'编号', 	
				'task_to_run'=>'执行函数',
        		'schedule_type'=>'类型',
        		'modifier'=>'执行频率',
        		'dirlist'=>'dirlist',
        		'month'=>'month',
        		'start_datetime'=>'开始时间',
		        'end_datetime'=>'失效时间',
		        'last_run_time'=>'上次执行',
		        'info'=>'简介',
        
			),
        );

        public $custom_adminlist = 'adminlist.html';

        public function install(){
        	$sql_file = ONETHINK_ADDON_PATH.'Schedule/install.sql';
        	// 执行sql文件
        	$res = $this->executeSqlFile($sql_file);
        	// 错误处理
        	if(!empty($res)) {
        		// 清除已导入的数据
        		$this->uninstall();
        	}   
        	return true;
        }

        public function uninstall(){
            // 数据库表前缀
        	$db_prefix = C('DB_PREFIX');
        	$sql = array("DROP TABLE IF EXISTS `{$db_prefix}schedule`;",);
        	// 执行SQL
        	foreach($sql as $v) {
        		M('')->execute($v); 
        	}        	
            return true;
        }

        //实现的app_begin钩子方法
        public function app_begin($param){//print_r(date('Y-h-d H:i:s','1390284571'));
        	$Schedule = D('Addons://Schedule/Schedule');
        	//锁定自动执行 修正一下
			$lockfile = $Schedule->getLogPath() . '/schedule.lock';
			//锁定未过期 - 返回
			if( file_exists($lockfile) && ( (filemtime($lockfile))+60 > $_SERVER['REQUEST_TIME'] )){
				return ;
			} else {
				//重新生成锁文件
				touch($lockfile);
			}
	
			//忽略中断\忽略过期
			set_time_limit(0);
			ignore_user_abort(true);
			
			//执行计划任务
			$Schedule->runScheduleList($Schedule->getScheduleList());
			
			//解除锁定
			unlink($lockfile);
			return ;
        }
             
        
        /**
         * 执行SQL文件
         * @access public
         * @param string  $file 要执行的sql文件路径
         * @param boolean $stop 遇错是否停止  默认为true
         * @param string  $db_charset 数据库编码 默认为utf-8
         * @return array
         */
        public function executeSqlFile($file,$stop = true,$db_charset = 'utf-8') {
            if (!is_readable($file)) {
                $error = array(
                    'error_code' => 'SQL文件不可读',
                    'error_sql'  => '',
                 );
                return $error;
            }

            $fp = fopen($file, 'rb');
            $sql = fread($fp, filesize($file));
            fclose($fp);

            $sql = str_replace("\r", "\n", str_replace('`'.'ts_', '`'.C('DB_PREFIX'), $sql));

            foreach (explode(";\n", trim($sql)) as $query) {
                $query = trim($query);
                if($query) {
                    if(substr($query, 0, 12) == 'CREATE TABLE') {
                        //预处理建表语句
                        $db_charset = (strpos($db_charset, '-') === FALSE) ? $db_charset : str_replace('-', '', $db_charset);
                        $type   = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $query));
                        $type   = in_array($type, array("MYISAM", "HEAP")) ? $type : "MYISAM";
                        $_temp_query = preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $query).
                            (mysql_get_server_info() > "4.1" ? " ENGINE=$type DEFAULT CHARSET=$db_charset" : " TYPE=$type");

                        $res = M('')->execute($_temp_query);
                    }else {
                        $res = M('')->execute($query);
                    }
                    if($res === false) {
                        $error[] = array(
                            'error_code' => M('')->getDbError(),
                            'error_sql'  => $query,
                           );

                        if($stop) return $error[0];
                    }
                }
            }
            return $error;
        }            
    }