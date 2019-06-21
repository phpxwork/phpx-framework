<?php
class EnvModel extends PHPX{
	public $data=array();
	private $inited=false;

	function init($appname='App'){
		$this->inited=true;
		if(defined('ENV_INIT')) return;
		/** define WebRoot Url*/
		if(substr(php_sapi_name(),0,3)=='cgi'){
			/** in cgi/fcig mode */
			$temp  = explode('.php',$_SERVER["PHP_SELF"]);
			$phpFile= rtrim(str_replace($_SERVER["HTTP_HOST"],'',$temp[0].'.php'),'/');
		}else{
			$phpFile= isset($_SERVER["SCRIPT_NAME"])?rtrim($_SERVER["SCRIPT_NAME"],'/'):'';
		}
		$this->set('php_file',$phpFile);
		
		
		$root = dirname($phpFile);
		$url = ($root=='/' || $root=='\\')?'':$root;
		
		
		$app_url = $_SERVER['PHP_SELF'];
		if(strpos($app_url,'.php')>0)
		{
			$app_url = dirname(preg_replace('/\\.php.+?$/is','.php',$app_url));
		}else{
			$app_url = dirname($app_url);
		}

		$app_url = str_replace('\\','/',$app_url);
		if($app_url=='/') $app_url='';
		$this->set('app_url',$app_url);
		$this->set('php',preg_replace('/\.php.+?$/i','.php',$_SERVER['SCRIPT_NAME']));
		$this->set('PHPX_DIR',PHPX_DIR);
		
		$script = $_SERVER['SCRIPT_FILENAME'];
		if(strpos($_SERVER['SERVER_SOFTWARE'],"Win")>0 || strpos($_SERVER['DOCUMENT_ROOT'],':')>0)
		{
			//Windows
			if(empty($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
				$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
			}
			if(empty($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['PATH_TRANSLATED'])) {
				$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
			}
			if(substr($_SERVER['DOCUMENT_ROOT'],-1,1)=='/')
			{
				$_SERVER['DOCUMENT_ROOT']=substr($_SERVER['DOCUMENT_ROOT'],0,strlen($_SERVER['DOCUMENT_ROOT'])-1);
			}
			$str = str_replace('\\','/',strtolower(PHPX_DIR));
			
			$find = str_replace('\\','/',strtolower($_SERVER['DOCUMENT_ROOT']));
			$phpx_url = str_replace($find,'',$str);
		}else{
			//$_SERVER['DOCUMENT_ROOT'] = str_replace(preg_replace('/\.php\/.+?$/is','.php',$_SERVER['PHP_SELF']),'',$_SERVER['SCRIPT_FILENAME']);
			$_SERVER['DOCUMENT_ROOT'] =preg_replace('/'.str_replace('/','\\/',str_replace('.','\\.',$_SERVER['SCRIPT_NAME'])).'$/i', '/', $_SERVER['DOCUMENT_ROOT']);

			$phpx_url = str_replace(str_replace('\\,','/',$_SERVER['DOCUMENT_ROOT']),'',str_replace('\\','/',PHPX_DIR));
		}
		
		define('__ROOT__',str_replace('\\','/',dirname($_SERVER['SCRIPT_FILENAME'])));
		
		$this->set('phpx_url',$phpx_url);
		//echo dirname($_SERVER['SCRIPT_FILENAME']).'<br />';
		//echo $_SERVER['DOCUMENT_ROOT'].'<br />';
		
		/** Root Dir */
		$this->set('root_dir',__ROOT__);
		$this->set('app',$appname);
		$appDir=str_replace('\\','/',dirname($_SERVER['SCRIPT_FILENAME'])).'/'.__APP__;
		$this->set('app_dir',$appDir);
		if(!is_dir($appDir)){
			//include PHPX_DIR.'/model/dir_helper.model.php';
			//PHPX_DIR::buildBase($appDir);
			//PHPX_DIR::makeFile($this);
		}
		
		/** Data dir */
		if($this->config->get('data_dir')){
			$this->set('data_dir',$this->config->get('data_dir'));
		}else{
			$this->set('data_dir',$this->routine_helper->get_cache_path('data'));
		}
		if(!is_dir($this->get('data_dir'))) mkdir($this->get('data_dir'));
		
		$this->set('clock_start',microtime());
		$this->set('now',time());
		
		/** Action Class */
		$class=$this->query->get('class');
		$class=strtoupper($class[0]).substr($class,1);
		$this->set('class',$class);
		
		/** Action method */
		$method=$this->query->get('method');
		$this->set('method',$method);
		
		/** PAC action */
		$pac=$this->query->get('pac');
		$this->set('pac',$pac);
		
		/** debug mode */
		$this->set('debug_mode',$this->config->get('debug_mode'));
		
		if(!file_exists(__ROOT__.'/env.data'))
		{
			file_put_contents(__ROOT__.'/env.data',serialize($this->data));
		}
		define('ENV_INIT',TRUE);

		$this->set('https',$this->is_https());
		
	}

	function is_https() {
	    if ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
	        return true;
	    } elseif ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	        return true;
	    } elseif ( !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
	        return true;
	    }
	    return false;
	}
	
	function set($name,$value){
		if(!$this->inited) $this->init(self::$app_name);
		$this->data[strtolower($name)]=$value;
	}
	
	function get($name,$strtolower=false){
		if(!$this->inited) $this->init(self::$app_name);
		if(sizeof($this->data)==0)
		{
			$this->data = unserialize(file_get_contents(__ROOT__.'/env.data'));
		}
		if($strtolower) return strtolower($this->data[strtolower($name)]);
		return $this->data[strtolower($name)];
	}
}