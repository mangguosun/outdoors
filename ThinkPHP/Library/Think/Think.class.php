<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Think;
/**
 * ThinkPHP 引导类
 */
class Think {

    // 类映射
    private static $_map      = array();

    // 实例化对象
    private static $_instance = array();

    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    static public function start() {
      // 注册AUTOLOAD方法
      spl_autoload_register('Think\Think::autoload');      
      // 设定错误和异常处理
      register_shutdown_function('Think\Think::fatalError');
      set_error_handler('Think\Think::appError');
      set_exception_handler('Think\Think::appException');

      // 初始化文件存储方式
      Storage::connect(STORAGE_TYPE);

      $runtimefile  = RUNTIME_PATH.APP_MODE.'~runtime.php';
      if(!APP_DEBUG && Storage::has($runtimefile)){
          Storage::load($runtimefile);
      }else{
          if(Storage::has($runtimefile))
              Storage::unlink($runtimefile);
          $content =  '';
          // 读取应用模式
          $mode   =   include is_file(CONF_PATH.'core.php')?CONF_PATH.'core.php':MODE_PATH.APP_MODE.'.php';
          // 加载核心文件
          foreach ($mode['core'] as $file){
              if(is_file($file)) {
                include $file;
                if(!APP_DEBUG) $content   .= compile($file);
              }
          }

          // 加载应用模式配置文件
          foreach ($mode['config'] as $key=>$file){
              is_numeric($key)?C(load_config($file)):C($key,load_config($file));
          }

          // 读取当前应用模式对应的配置文件
          if('common' != APP_MODE && is_file(CONF_PATH.'config_'.APP_MODE.CONF_EXT))
              C(load_config(CONF_PATH.'config_'.APP_MODE.CONF_EXT));  

          // 加载模式别名定义
          if(isset($mode['alias'])){
              self::addMap(is_array($mode['alias'])?$mode['alias']:include $mode['alias']);
          }

          // 加载应用别名定义文件
          if(is_file(CONF_PATH.'alias.php'))
              self::addMap(include CONF_PATH.'alias.php');

          // 加载模式行为定义
          if(isset($mode['tags'])) {
              Hook::import(is_array($mode['tags'])?$mode['tags']:include $mode['tags']);
          }

          // 加载应用行为定义
          if(is_file(CONF_PATH.'tags.php'))
              // 允许应用增加开发模式配置定义
              Hook::import(include CONF_PATH.'tags.php');   

          // 加载框架底层语言包
          L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');

          if(!APP_DEBUG){
              $content  .=  "\nnamespace { Think\Think::addMap(".var_export(self::$_map,true).");";
              $content  .=  "\nL(".var_export(L(),true).");\nC(".var_export(C(),true).');Think\Hook::import('.var_export(Hook::get(),true).');}';
              Storage::put($runtimefile,strip_whitespace('<?php '.$content));
          }else{
            // 调试模式加载系统默认的配置文件
            C(include THINK_PATH.'Conf/debug.php');
            // 读取应用调试配置文件
            if(is_file(CONF_PATH.'debug'.CONF_EXT))
                C(include CONF_PATH.'debug'.CONF_EXT);           
          }
      }

      // 读取当前应用状态对应的配置文件
      if(APP_STATUS && is_file(CONF_PATH.APP_STATUS.CONF_EXT))
          C(include CONF_PATH.APP_STATUS.CONF_EXT);   

      // 设置系统时区
      date_default_timezone_set(C('DEFAULT_TIMEZONE'));

      // 检查应用目录结构 如果不存在则自动创建
      if(C('CHECK_APP_DIR')) {
          $module     =   defined('BIND_MODULE') ? BIND_MODULE : C('DEFAULT_MODULE');
          if(!is_dir(APP_PATH.$module) || !is_dir(LOG_PATH)){
              // 检测应用目录结构
              Build::checkDir($module);
          }
      }

		self::domain_check();
	
			 //判断是否带参数 如果有带 则要对参数进行拼接成mobile 如果没有，则直接跳到mobile首页
			if(self::_is_mobile()){
				
				$mobile_router =  array(
						/*活动配置*/
						'event/index/index'			=>  'mobile/event/index',
						'event/index/detail'		=>  'mobile/event/detail',
						'event/index'				=>  'mobile/event/index',
						'event'						=>  'mobile/event/index',
						
						'issue/index/index'			=>  'mobile/issue/index',
						'issue/index/issuecontentdetail'		=>  'mobile/issue/issuecontentdetail',
						'issue/index'				=>  'mobile/issue/index',
						'issue'						=>  'mobile/issue/index',

						'shop/index/index'			=>  'mobile/shop/index',
						'shop/index/goods'			=>  'mobile/shop/goods',
						'shop/index/goodsdetail'    =>  'mobile/shop/detail',
						'shop/index'    			=>  'mobile/shop/index',
						'shop'    					=>  'mobile/shop/index',
						
						'blog/index/index'			=>  'mobile/blog/index',
						'blog/article/lists/category'			=>  'mobile/blog/index/id',
						'blog/article/lists'			=>  'mobile/blog/index',
						'blog/article/detail'			=>  'mobile/blog/detail',
						'blog/index'				=>  'mobile/blog/index',
						'blog'						=>  'mobile/blog/index',
						
						'tailor/index/index'			=>  'mobile/tailor/index',
						'tailor/index'			=>  'mobile/tailor/index',
						'tailor'			=>  'mobile/tailor/index',
						
						'home/index/index'			=>  'mobile/index/index',
						'home/index'				=>  'mobile/index/index',
						'home'						=>  'mobile/index/index',
						
					);
				
			
	
				
				if(!empty($_GET['s'])){
					$url = $_GET['s'];
				}else{
					$url = $_SERVER["PHP_SELF"];
					$url = str_replace('/index.php', '', strtolower($url));
				}	
				
				
				if($url && !self::check_url()){
					$depr       =   C('URL_PATHINFO_DEPR');
					if(0=== strpos($url,'/')) {// 定义路由
						$route      =   true;
						$url        =   substr($url,1);
						if('/' != $depr) {
							$url    =   str_replace('/',$depr,$url);
						}
					}
					foreach ($mobile_router as $var => $val) {
						
						if(strpos(strtolower($url),$var)===0){
							$url = str_replace($var, $val, strtolower($url));
							break;
						}
					}
					$url   =  (is_ssl()?'https://':'http://').$_SERVER['HTTP_HOST'].'/'.$url;
					header("location:".$url );
				}else{

					if(strtolower($_SERVER["HTTP_HOST"]) =="www.huodongli.cn" || strtolower($_SERVER["HTTP_HOST"]) =="huodongli.cn" || strtolower($_SERVER["HTTP_HOST"]) =="huodongli.com.cn" || strtolower($_SERVER["HTTP_HOST"]) =="huodongli.com.cn" ){

						C('DEFAULT_MODULE','Official');
					}else{
						C('DEFAULT_MODULE','Mobile');
					}
				}
			
		};
			//self::check_app();
      // 记录加载文件时间
      G('loadTime');
      // 运行应用
      App::run();
    }

    // 注册classmap
    static public function addMap($class, $map=''){
        if(is_array($class)){
            self::$_map = array_merge(self::$_map, $class);
        }else{
            self::$_map[$class] = $map;
        }        
    }

    // 获取classmap
    static public function getMap($class=''){
        if(''===$class){
            return self::$_map;
        }elseif(isset(self::$_map[$class])){
            return self::$_map[$class];
        }else{
            return null;
        }
    }

    /**
     * 类库自动加载
     * @param string $class 对象类名
     * @return void
     */
    public static function autoload($class) {
        // 检查是否存在映射
        if(isset(self::$_map[$class])) {
            include self::$_map[$class];
        }elseif(false !== strpos($class,'\\')){
          $name           =   strstr($class, '\\', true);
          if(in_array($name,array('Think','Org','Behavior','Com','Vendor')) || is_dir(LIB_PATH.$name)){ 
              // Library目录下面的命名空间自动定位
              $path       =   LIB_PATH;
          }else{
              // 检测自定义命名空间 否则就以模块为命名空间
              $namespace  =   C('AUTOLOAD_NAMESPACE');
              $path       =   isset($namespace[$name])? dirname($namespace[$name]).'/' : APP_PATH;
          }
          $filename       =   $path . str_replace('\\', '/', $class) . EXT;
          if(is_file($filename)) {
              // Win环境下面严格区分大小写
              if (IS_WIN && false === strpos(str_replace('/', '\\', realpath($filename)), $class . EXT)){
                  return ;
              }
              include $filename;
          }
        }elseif (!C('APP_USE_NAMESPACE')) {
            // 自动加载的类库层
            foreach(explode(',',C('APP_AUTOLOAD_LAYER')) as $layer){
                if(substr($class,-strlen($layer))==$layer){
                    if(require_cache(MODULE_PATH.$layer.'/'.$class.EXT)) {
                        return ;
                    }
                }            
            }
            // 根据自动加载路径设置进行尝试搜索
            foreach (explode(',',C('APP_AUTOLOAD_PATH')) as $path){
                if(import($path.'.'.$class))
                    // 如果加载类成功则返回
                    return ;
            }
        }
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class,$method='') {
        $identify   =   $class.$method;
        if(!isset(self::$_instance[$identify])) {
            if(class_exists($class)){
                $o = new $class();
                if(!empty($method) && method_exists($o,$method))
                    self::$_instance[$identify] = call_user_func(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            }
            else
                self::halt(L('_CLASS_NOT_EXIST_').':'.$class);
        }
        return self::$_instance[$identify];
    }

    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException($e) {
        $error = array();
        $error['message']   =   $e->getMessage();
        $trace              =   $e->getTrace();
        if('E'==$trace[0]['function']) {
            $error['file']  =   $trace[0]['file'];
            $error['line']  =   $trace[0]['line'];
        }else{
            $error['file']  =   $e->getFile();
            $error['line']  =   $e->getLine();
        }
        $error['trace']     =   $e->getTraceAsString();
        Log::record($error['message'],Log::ERR);
        // 发送404信息
        header('HTTP/1.1 404 Not Found');
        header('Status:404 Not Found');
        self::halt($error);
    }

    /**
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
          case E_ERROR:
          case E_PARSE:
          case E_CORE_ERROR:
          case E_COMPILE_ERROR:
          case E_USER_ERROR:
            ob_end_clean();
            $errorStr = "$errstr ".$errfile." 第 $errline 行.";
            if(C('LOG_RECORD')) Log::write("[$errno] ".$errorStr,Log::ERR);
            self::halt($errorStr);
            break;
          default:
            $errorStr = "[$errno] $errstr ".$errfile." 第 $errline 行.";
            self::trace($errorStr,'','NOTIC');
            break;
      }
    }
    
    // 致命错误捕获
    static public function fatalError() {
        Log::save();
        if ($e = error_get_last()) {
            switch($e['type']){
              case E_ERROR:
              case E_PARSE:
              case E_CORE_ERROR:
              case E_COMPILE_ERROR:
              case E_USER_ERROR:  
                ob_end_clean();
                self::halt($e);
                break;
            }
        }
    }

    /**
     * 错误输出
     * @param mixed $error 错误
     * @return void
     */
    static public function halt($error) {
        $e = array();
        if (APP_DEBUG || IS_CLI) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace          = debug_backtrace();
                $e['message']   = $error;
                $e['file']      = $trace[0]['file'];
                $e['line']      = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace']     = ob_get_clean();
            } else {
                $e              = $error;
            }
            if(IS_CLI){
                exit(iconv('UTF-8','gbk',$e['message']).PHP_EOL.'FILE: '.$e['file'].'('.$e['line'].')'.PHP_EOL.$e['trace']);
            }
        } else {
            //否则定向到错误页面
            $error_page         = C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);
            } else {
                $message        = is_array($error) ? $error['message'] : $error;
                $e['message']   = C('SHOW_ERROR_MSG')? $message : C('ERROR_MESSAGE');
            }
        }
        // 包含异常页面模板
        $exceptionFile =  C('TMPL_EXCEPTION_FILE',null,THINK_PATH.'Tpl/think_exception.tpl');
        include $exceptionFile;
        exit;
    }

    /**
     * 添加和获取页面Trace记录
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别(或者页面Trace的选项卡)
     * @param boolean $record 是否记录日志
     * @return void
     */
    static public function trace($value='[think]',$label='',$level='DEBUG',$record=false) {
        static $_trace =  array();
        if('[think]' === $value){ // 获取trace信息
            return $_trace;
        }else{
            $info   =   ($label?$label.':':'').print_r($value,true);
            $level  =   strtoupper($level);
            
            if((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE')  || $record) {
                if(true==APP_DEBUG){//修正非调试模式下无法进入后台编辑的问题
                    Log::record($info,$level,$record);
                }

            }else{
                if(!isset($_trace[$level]) || count($_trace[$level])>C('TRACE_MAX_RECORD')) {
                    $_trace[$level] =   array();
                }
                $_trace[$level][]   =   $info;
            }
        }
    }
    
    static public function domain_check() {
        
		$domain = $_SERVER['HTTP_HOST'];
    

	
		if((strpos(strtolower($domain), 'huodongli.cn') == true)||(strpos(strtolower($domain), 'huodongli.com.cn') == true) || strtolower($domain) == 'huodongli.cn'|| strtolower($domain) == 'huodongli.com.cn'){
			$domain_prefix = strstr(strtolower($domain), '.huodongli.cn', true)?strstr(strtolower($domain), '.huodongli.cn', true):strstr(strtolower($domain), '.huodongli.com.cn', true);
			
			
			
		//站内三级域名
			if($domain_prefix == 'admin'||$domain_prefix == 'www'|| $domain == 'huodongli.cn'|| $domain == 'huodongli.com.cn'){
				defined('SITEID') or define('SITEID', 1);
				          $website_info=read_website_info_cash($domain);
				          if(!$website_info){ 
				            $website_info = D('websit')->where(array('status' => 1,'siteid' => SITEID))->find();
            
				            write_website_info_cash($domain,$website_info);
				          }

					if($website_info){
						 C('DEFAULT_MODULE','Official');
						 //C('DEFAULT_MODULE','Home');
						 C('DEFAULT_THEME', 'default');
						 $website_info['domaintype']=0;
						 defined('WEBSITEINFO') or define('WEBSITEINFO', json_encode($website_info));
						 defined('SITEID') or define('SITEID', $website_info['siteid']);
						 C('GETWEBSITE_INFO',$website_info);
						
					}else{
						header('HTTP/1.1 404 Not Found'); 
						header("status: 404 Not Found"); 
						exit;
					}
			}else if($domain_prefix == 'pay'){
					C('DEFAULT_MODULE','Pays');
					C('DEFAULT_THEME', 'default');
      }else if($domain_prefix == 'passport'){
          C('DEFAULT_MODULE','Passports');
          C('DEFAULT_THEME', 'default');    
					
			}else{
			        $website_info=read_website_info_cash($domain);
			        if(!$website_info){ 
			          $website_info = D('websit')->where(array('status' => 1,'url' => $domain_prefix))->find();
			          write_website_info_cash($domain,$website_info);
			        }
				if($website_info){
					 C('DEFAULT_MODULE','Home');
					 C('DEFAULT_THEME', $website_info['theme']);
					 $website_info['domaintype']=1;
					 defined('WEBSITEINFO') or define('WEBSITEINFO', json_encode($website_info));
					 defined('SITEID') or define('SITEID', $website_info['siteid']);
					 C('GETWEBSITE_INFO',$website_info);
			         
				}else{
                                
					header('HTTP/1.1 404 Not Found'); 
					header("status: 404 Not Found"); 
					exit;
				}
				
			}
		}else{
			
			
			if($domain == 'hdlcdn.com'){
					C('DEFAULT_MODULE','cdn');
					C('DEFAULT_THEME', 'default');	
			}else{
				
				$domain_info = D('webdomain')->where(array('domain' => $domain))->find();
				if($domain_info){
				            $website_info=read_website_info_cash($domain);
				            if(!$website_info){
				              $website_info = D('websit')->where(array('status' => 1,'siteid' => $domain_info['siteid']))->find();
				              write_website_info_cash($domain,$website_info);
				            }
						if($website_info){
							C('DEFAULT_MODULE','Home');
							C('DEFAULT_THEME', $website_info['theme']);
							$website_info['domaintype']=2;
							 defined('WEBSITEINFO') or define('WEBSITEINFO', json_encode($website_info));
							 defined('SITEID') or define('SITEID', $website_info['siteid']);
							C('GETWEBSITE_INFO',$website_info);
						}else{
							header('HTTP/1.1 404 Not Found'); 
							header("status: 404 Not Found"); 
							exit;
						}
					
				}else{
					header('HTTP/1.1 404 Not Found'); 
					header("status: 404 Not Found"); 
					exit;
				}
			}
		}
    }	
	static public function _is_mobile(){
		 static $is_mobile;
		 if (isset($is_mobile))
		 return $is_mobile;
	     if (empty($_SERVER['HTTP_USER_AGENT'])) {
			$is_mobile = false;
	    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false  && strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') == false
		  || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
		  || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
		  || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
		  || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false) {
			$is_mobile = true;
		} else {
			$is_mobile = false;
		}
			return $is_mobile;
		}

	static public function check_url(){
		$str = $_SERVER["PHP_SELF"];
		$str = str_replace('/index.php', '', strtolower($str));
		$str_arr = explode('/',$str);
		if(strtolower($str_arr[1]) == 'mobile' ||  strtolower($str_arr[1]) == 'home' ||  strtolower($str_arr[1]) == 'pays'||  strtolower($str_arr[1]) == 'official'||  strtolower($str_arr[1]) == 'passports'){
			return true;
		}else{
			return false;
		}
	}
	
	static public function check_app(){
		header("Content-type: text/html; charset=utf-8"); 
		$str = $_SERVER["QUERY_STRING"];
		$str_arr = explode('/',$str);
		$model = ucfirst($str_arr[1]);
		if($model != 'Home' && $model != 'Usercenter' && $model != 'Websit' && $model != '' && $model != 'Event' && $model != 'Mobile'){
			$app_common = D('websit_apply')->where(array('status'=>1,'app_model'=>$model))->find();
			$app_primary = D('websit_install_apply')->where(array('status'=>1,'siteid'=>SITEID,'app_model'=>$model))->find();
			if(!$app_common) {
				echo '访问错误，请联系管理员！';
				exit;
			}else{
				if($app_common['status'] != 1 ){
					echo '访问错误，请联系管理员！';
					exit;
				}elseif(!$app_primary){
					echo '访问错误，请联系管理员！';
					exit;
				}
			}
		}
	}
}
