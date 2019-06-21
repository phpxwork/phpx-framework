<?php
class ConfigModel extends Model{
	private $_config=array();
	
	private $host;

	private $inited=false;
	function init($app='app',$ext=NULL){
		$this->inited=true;
		$this->host=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'cmd';
		$this->host = str_replace(':','_',$this->host);
		if(!is_null($ext)){
			$externDir = $ext;
		}
		/**
		 * if config file not exists in application dir
		 * copy it from PHPX default config file
		 */

		if(file_exists(__ROOT__.'/host/'.$this->host.'.php')){
			include __ROOT__.'/host/'.$this->host.'.php';
		}
		else if(file_exists(__ROOT__.'/host/default.php'))
			include __ROOT__.'/host/default.php';
		else
		{
			$this->_config = array('debug_mode' => true);
			return;
		}
		$this->_config=array_change_key_case($config);
		unset($config);
		if(isset($_SESSION['config_extern_dir'])){
			$externDir = $_SESSION['config_extern_dir'];
		}
		if(isset($externDir)){
			/**
			 * this config file requires another child config file
			 */
			if(strpos($externDir,':')==-1){
				$externDir = __ROOT__.'/'.$externDir;
			}
			include $externDir.'/config.php';
			$this->_config=array_merge(array_change_key_case($config),$this->_config);
		}



		foreach($this->_config as $key => $value){
			switch($key){
				case 'db_pre':
					if(!defined('DB_PRE')) define('DB_PRE',$value);
					break;
			}
		}
	}
	
	function setDefaultConfig(){
		$this->readFromFile(PHPX_DIR.'/template/config/default.php');
	}
	
	/**
	 * readFromFile
	 *
	 * read a set of config info from a php file
	 *
	 * @param filename String
	 */
	function readFromFile($filename){
		try{
			/**
			 * a return array() format file
			 * would return a value
			 */
			include $filename;
		}catch(Exception $e){
			//on error happends do nothing
		}
		return $config;
	}
	
	/**
	 * set
	 *
	 * the config set
	 *
	 * @param name String,Array
	 * @param value NULL,...
	 */
	function set($name=NULL,$value=NULL,$_=NULL){
		if(!$this->inited) $this->init(self::$app_name);
		if(!$name) return;
		if(is_string($name)){
			if(is_null($value)){
				/**
				 * join a config file to self::$_config
				 * change all keys into lower case
				 */
				$this->_config=array_merge($this->_config,array_change_key_case($this->readFromFile($name)));
				return;
			}
			$this->_config[strtolower($name)]=$value;
			return;
		}
		if(is_array($name)){
			$this->_config=array_merge(array_change_key_case($name));
			return;
		}
	}
	
	//获取配置项
	function get($name=null,$_=null){
		if(!$this->inited) $this->init(self::$app_name);
		if(!$name) return $this->_config;
		$name=strtolower($name);
		if(isset($this->_config[$name])) return $this->_config[$name];
		/**
		 * if the following key-value pair not exists in self::$_config list
		 * return it a system level default value
		 * - default_class  : index
		 * - default_method : _default
		 */
		switch($name){
			case 'default_class':
				return 'index';
			case 'default_method':
				return '_default';
			case 'save_handler':
				return 'pix.upload';
		}
		return false;
	}
	
	function checkHtml($class=null,$method=null){
		if(!$this->inited) $this->init(self::$app_name);

		static $html=null;
		if(is_null($html)){
			if(file_exists($this->env->get('app_dir').'/html_config.php')){
				include_once $this->env->get('app_dir').'/html_config.php';
			}else{
				$html=array();
			}
		}
		if(!$class) $class=$this->env->get('class');
		if(!$method) $method=$this->env->get('method');
		if(isset($html[strtolower($class.'-'.$method)])) return $html[strtolower($class.'-'.$method)];
		return false;
	}
}