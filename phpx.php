<?php
include dirname(__FILE__).'/compatible7.php';
define('PHPX_DIR',dirname(__FILE__));
define('PHPX_CLOCK_BEGIN',microtime(true));
ini_set('display_errors','Off');
error_reporting(E_ALL);
@session_start();

set_error_handler(array('PHPX','error_handler'));
register_shutdown_function(array('PHPX','fetal_error_handler'));

//包含框架基础文件
include PHPX_DIR.'/controller/controller.php';
if(!class_exists('Model')) include PHPX_DIR.'/model/.model.php';
/*
include PHPX_DIR.'/core/model/PHPX_cache.php';
include PHPX_DIR.'/core/db/PHPX_mysql.php';
include PHPX_DIR.'/core/env/PHPX_query.php';
include PHPX_DIR.'/core/env/PHPX_config.php';
include PHPX_DIR.'/core/env/PHPX_env.php';
include PHPX_DIR.'/core/pac/pac.php';*/

ini_set('magic_quotes_gpc',0);
/**
 * PHPX核心类
 * 通过入口文件中本类的PHPX::getAction方法，可以实现一个PHPX的应用
 * 所谓入口文件，通常就是网站根目录下的index.php
 */
class PHPX{
	//控制器钩子
	public static $_action=null;
			  
	protected static $_set=array();
	
	//已加载的模型
	public static $_model=array();
	
	//应用名
	public static $app_name='';

		  /*
		  $db    = null,
		  $lang  = null,
		  $cache = null,
		  $query = null,
		  $env   = null,
		  $config= null,
		  $pac   = null,
		  $share = null;*/
	public static $_intoHTML=false;
	
	/**
	 * private __construct
	 * so that PHPX class cannt form instance
	 */
	function __construct(){
		//$this->query  = PHPX_Query::getInstance();
		//$this->env    = PHPX_Env::getInstance();
		//$this->config = PHPX_Config::getInstance();
		//$this->cache  = PHPX_Cache::getInstance();
		//$this->db     = PHPX_Mysql::getInstance();
		//$this->view   = PHPX_View::getInstance();
		//$this->share   = PHPX_Share::getInstance();
		//$this->pac   = PAC::getInstance();
	}
	
	/**
	 * return instance of PHPX_Action
	 */
	static function getAction($app='app',$class=null,$method=null){
		//setApplication($app);
		define('__APP__',$app);
		self::$app_name=$app;

		/*
		PHPX_Config::getInstance() -> init($app);
		PHPX_Query::getInstance() -> init($app);
		PHPX_Env::getInstance()-> init($app);*/
		if(!self::$_action){
			$env = @self::load('env');
			/**
			 * initialize the action
			 */
			if($class) $class=strtoupper($class[0]).substr($class,1);
			if(!$class){
				$class=$env->get('class');
			}else{
				$env->set('class',$class);
			}
			$class=strtoupper($class[0]).substr($class,1);
			$class=urldecode($class);
			if(strpos($class,'{')===0)
			{
				$class=trim(trim($class,'}'),'{');
				list($taglib,$tag)=explode(':',$class);
				$dir = @self::load('routine')->get_tag_dir($taglib,$tag);
				if(!$dir){
					ob_clean();
					if(self::load('query')->get('PHPX_TAGLIB_CHECK'))
					{
						echo json_encode(array(
							'error' => 'codemiss',
							'taglib' => $taglib,
							'tag' => $tag,
						));
					}else{
						include dirname(__FILE__).'/view/view_tag_parse_model_tag_not_found.php';
					}
					exit;
				}
				$actionFilePath = $dir['path'].'/'.$taglib.'/'.$tag.'/__server.php';
				$class='Server';
			}else{
				$actionFilePath = @self::load('routine')->get_controller_file($class);
			}
			include $actionFilePath;
			if(!class_exists($class)) exit('Controller class not defined');
			$PHPXClass=$class;
			$obj=new $PHPXClass();
			
			if(!$method)
				$method=$env->get('method');
			else
				$env->set('method',$method);
			if(!method_exists($obj,$method)) exit('method `'.$method.'` not exists');
			self::$_action=$obj;
			//self::$_intoHTML=PHPX_Config::getInstance()->checkHtml();
		}
		return self::$_action;
	}
	
	static function accessCheck(){
		return PAC::getInstance()->checkAccess();
	}


	/**
	 * __get方法定义
	 *
	 * 根据控制器中的$this->model_name返回一个模型的实例
	 * 如果模型实例已经存在，则直接返回该实例
	 * 如果实例不存在，则通过load方法加载该实例，并且保存在
	 * $_models数组中
	 */
	function __get($name){
		$name=strtolower($name);
		if(!isset(PHPX::$_model[$name])){
			$this->load($name);
		}
		if(!isset(PHPX::$_model[$name])){
			trigger_error('model `'.$name.'` not exists');
		}
		return PHPX::$_model[$name];
	}
	
	/**
	 * load函数
	 *
	 * 加载一个模型，并且返回该模型
	 * 返回该模型的实例的路由规则是：
	 * 首先从控制器的目录同级的Model目录下根据名称搜索模型
	 * 如果模型不存在，则根据在该Model目录__extern.php中定义的
	 * 扩展目录进行搜索，如果扩展目录中仍然不存在，则在
	 * PHPX/Model目录中进行搜索，这个目录默认是所有模型目录
	 * 的扩展目录，以保证PHPX默认提供的模型总是有被加载的
	 * 可能
	 */
	public function load($name){
		if(in_array($name,array('env','routine'))){
			/**
			 * 由于routine和env模型的特殊性，这两个模型都要进行特殊加载
			 * env模型的特殊调用方式，是为了防止死循环
			 */
			if(!isset(PHPX::$_model[$name])){

				include_once PHPX_DIR.'/model/'.$name.'.model.php';
				$model=strtoupper($name[0]).substr($name,1).'Model';
				PHPX::$_model[$name]=new $model();
			}
		}
		//防止同一个模型被多次加载
		if(isset(PHPX::$_model[strtolower($name)])){
			return PHPX::$_model[strtolower($name)];
		}
		
		if(strpos($name,'.')>0)
		{
			#加载控件专属模型
			list($taglib,$tag,$model) = explode('.',$name);
			$dir = @self::load('routine')->get_tag_dir($taglib,$tag);
			if(!$dir){
				if($this->query->get('PHPX_TAGLIB_CHECK'))
				{
					echo json_encode(array(
						'error' => 'codemiss',
						'taglib' => $taglib,
						'tag' => $tag,
					));
				}else{
					include dirname(__FILE__).'/view/view_tag_parse_model_tag_not_found.php';
				}
				exit;
			}
			$model_path = $dir['path'].'/'.$taglib.'/'.$tag.'/'.$model.'.model.php';
			include_once $model_path;
			$modelClass=$taglib.'_'.$tag.'_'.$model.'Model';
			$obj=new $modelClass(@$this);
			PHPX::$_model[$name]=$obj;
			return PHPX::$_model[$name];
		}
		$model_path=self::load('routine')->get_model_file($name);
		
		if($model_path && file_exists($model_path)){
			//self::load('sql_debugger')->out_print($model_path);
			try{
				include_once $model_path;
				if(!class_exists($name.'Model')){
					trigger_error('model class '.$name.'Model not exists');
				}else{
					$modelClass=$name.'Model';
					$obj=new $modelClass($this);
					PHPX::$_model[$name]=$obj;
					return PHPX::$_model[$name];
				}
			}catch(Error $e){
				var_dump($e);
				exit('Model File Error:`'.$model_path.'`');
			}
		}
		
		//虚模型，就是没有定义的模型，可以根据模型的名称索引相关的数据表进行操作
		if(isset($this)){
			if(self::load('config')->get('db_pre') && strpos($name,self::load('config')->get('db_pre'))!==0)
			{
				$pre = self::load('config')->get('db_pre');
			}else{
				$pre = '';
			}
			$obj=new Model($this,strtolower($name));
			PHPX::$_model[$name]=$obj;
			return PHPX::$_model[$name];
		}else{
			return false;
		}
	}
	
	static function error_handler($errno,$errstr,$errfile,$errline,$errcontext)
	{
		if(!error_reporting()) return true;
		switch($errno)
		{
			case 2:
				echo '<div style="border:1px solid #e00;background:#ffc;color:#e00;padding:10px;">';
				echo "[$errno] $errstr :";
				echo '<a href="'.Controller::getInstance()->env->get('app_url').'?class={base:debug}&path='.$errfile.'" target="_blank" style="background:#099;color:white;">'.$errfile.'</a>'." on line : $errline<br/>\n";
				echo '</div>';
				break;
			case 8:
				if($errstr=='Array to string conversion') return;
				echo '<div style="border:1px solid #e00;background:#ffc;color:#e00;padding:10px;">';
				echo "[$errno] $errstr :";
				echo '<a href="'.Controller::getInstance()->env->get('app_url').'?class={base:debug}&path='.$errfile.'" target="_blank" style="background:#099;color:white;">'.$errfile.'</a>'." on line : $errline<br/>\n";
				echo '</div>';
			break;
		}
	}

	static function fetal_error_handler()
	{
	    $e = error_get_last();    
	    if(preg_match("/Uncaught Error: Call to undefined method Model::(.+?)\\(\\) in (.+?):([0-9]+?)\n/i",$e['message'],$match))
	    {
			if(self::load('query')->get('PHPX_TAGLIB_CHECK'))
			{
		    	$method = $match[1];
		    	$file = $match[2];
		    	$line = $match[3];

		    	$line_str = self::read_file_line($file,$line);
		    	ob_clean();
		    	header('HTTP/1.1 200 OK');
		    	header('content-type:text/plain;charset=utf-8');
		    	if(preg_match("/\->\s*?([a-z0-9_]+?)\s*?\->{$method}/i",$line_str,$match2))
		    	{
					echo json_encode(array(
						'error' => 'codemiss',
						'model' => $match2[1],
					));
		    	}else{
		    		echo json_encode(array(
		    			'error' => 'codemiss',
		    			'cant_match_model_at' => $file.':'.$line,
		    		));
		    	}

			}else{
				include dirname(__FILE__).'/view/view_tag_parse_model_tag_not_found.php';
			}
			exit;
	    }else{
	    	print_r($e);
	    }
	}

	function read_file_line($file, $line, $length = 4096){
		$returnTxt = null; // 初始化返回
		$i = 1; // 行数
		$handle = @fopen($file, "r");
		if ($handle) {
			while (!feof($handle)) {
				$buffer = fgets($handle, $length);
				if($line == $i){
					$returnTxt = $buffer;
					break;
				}
				$i++;
			}
			fclose($handle);
		}
		return $returnTxt;
	}
}

?>