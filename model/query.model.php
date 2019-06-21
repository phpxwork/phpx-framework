<?php
class QueryModel extends PHPX{
	private $_data=array();
	
	private $inited=false;
	function init(){
		$this->inited = true;
		if(!$this->config->get('virtual_path')){
			/**
			 * if not virtual path mode
			 * initialize query data by $_GET
			 */
			$this->_data=array_merge($this->_data,array_change_key_case($_GET));
			return;
		}
		$this->_data=array_merge($this->_data,array_change_key_case($_GET));

		$uri = strpos($_SERVER['DOCUMENT_URI'],$_SERVER['SCRIPT_NAME'])===0?$_SERVER['DOCUMENT_URI']:$_SERVER['REQUEST_URI'];
		$querystring=str_replace($_SERVER['SCRIPT_NAME'],'',$uri);
		$querystring=str_replace('?','/',str_replace('&','/',str_replace('=','-',$querystring)));
		if($querystring==$uri) return ;
		$data=explode("/",$querystring);

		list($classes,$methodes)=$this->get_class_and_method();
		foreach($data as $key => $item){
			$nameValuePair=explode("-",$item);
			$size=sizeof($nameValuePair);
			if($nameValuePair[0]){
				$nameValuePair[0]=urldecode($nameValuePair[0]);
				if($size==2){
					$this->add(strtolower($nameValuePair[0]),$nameValuePair[1]);
				}else{
					if(preg_match('/^[0-9]+?$/i',$nameValuePair[0]))
					{
						$this->add('id',$nameValuePair[0]);
					}
					else if($key==1){
						if(in_array($nameValuePair[0],$classes)){
							$this->add('class',$nameValuePair[0]);
						}else if(in_array($nameValuePair[0],$methodes)){
							$this->add('class',$this->config->get('default_class'));
							$this->add('method',$nameValuePair[0]);
						}
					}elseif($key==2){
						$this->add('method',$nameValuePair[0]);
					}else{
						$val = preg_replace('/^'.$nameValuePair[0].'\-\-/is','',$item);
						$this->add(strtolower($nameValuePair[0]),$val);
					}
				}
			}
		}

	}

	function get_class_and_method()
	{
		$file = str_replace('/','',$_SERVER['SCRIPT_NAME']).'.env.php';
		$file = __ROOT__.'/'.$file;
		if(!$this->config->get('debug_mode') && file_exists($file))
		{
			return $this->data_helper->read($file);
		}

		$classes = array();
		$methodes = array();

		$class_files = $this->file_system->get_all_files($this->env->get('app_dir').'/controller');
		foreach($class_files  as $class_file)
		{
			$classes[] = str_replace('.php','',$class_file);
		}
		$class_files = $this->file_system->get_all_files(PHPX_DIR.'/controller');
		foreach($class_files  as $class_file)
		{
			if($class_file!='controller.php')
				$classes[] = str_replace('.php','',$class_file);
		}

		$content = file_get_contents($this->env->get('app_dir').'/controller/'.$this->config->get('default_class').'.php');
		preg_match_all('/function\s+?(\S+?)\s*?\(/is',$content,$matches,PREG_SET_ORDER);
		foreach($matches as $match)
		{
			$methodes[]=$match[1];
		}

		$this->data_helper->read($file,array(
			$classes,$methodes
		));
		return array($classes,$methodes);
	}
	
	/**
	 * get
	 *
	 * get a query value
	 *
	 * @param name String,NULL
	 */
	function get($name=null){
		if(!$this->inited) $this->init(self::$app_name);

		if(!$name) return $this->_data;
		$name=strtolower($name);
		if(isset($this->_data[$name])) return $this->_data[$name];
		switch($name){
			case 'class':
				return $this->config->get('default_class');
			case 'method':
				return $this->config->get('default_method');
		}
		return false;
	}
	
	/**
	 * set
	 */
	function add($name,$value){
		if(!$this->inited) $this->init(self::$app_name);
		if(substr($name,-2,2)=='[]')
		{
			$name = substr($name,0,strlen($name)-2);
			if(!isset($this->_data[strtolower($name)]))
			{
				$this->_data[strtolower($name)]=array();
			}
			$this->_data[strtolower($name)][]=$value;
		}else{
			$this->_data[strtolower($name)]=$value;
		}
	}
}