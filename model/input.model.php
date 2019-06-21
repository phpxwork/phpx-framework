<?php
/***********************************************************************************************
 * input类，系统关键类
 * 包含了一组和数据表单的配置文件（实表的配置文件）操作相关的方法
 * 数据输入输出接口经常要用到的通用方法
 * 以及调用输入输出接口的一组常用方法
 */
class InputModel extends Model
{
	/****************************************************************
	* 将扁平化的配置文件转换为具有层级关系的配置文件
	* 只能转换实表的配置文件格式，虚表的配置是在数据库中存储的，在转换之前
	* 必须通过data_template->serialize_into_config方法，先转换为普通实表的配置形式
	*/
	function serialize($config)
	{
		$pages=array();
		$page=array('title' => 'default','parts'=>array());
		$part = array('title' => 'default' , 'fields'=>array());
		foreach($config as $key => $field_config)
		{
			if(preg_match('/^\{(.+?)\}$/is',$key,$match)){
				if(sizeof($page['parts'])>0){
					$page['parts'][] = $part;
					$part = array(
						'title' => $match[1] ,
						'multi' => isset($field_config['multi'])?$field_config['multi']:false,
						'double'=>isset($field_config['double'])?$field_config['double']:false,
						'hide' => isset($field_config['hide'])?$field_config['hide']:false,
						'fields'=>array(),
					);

					$pages[] = $page;
					$page=array('title' => $match[1],'parts'=>array());
				}else{
					$page['title']=$match[1];
				}
			}else if(preg_match('/^\[(.+?)\]$/is',$key,$match))
			{
				if(sizeof($part['fields'])>0)
				{
					$page['parts'][] = $part;
					$part = array(
						'title' => $match[1] ,
						'multi' => isset($field_config['multi'])?$field_config['multi']:false,
						'double'=>isset($field_config['double'])?$field_config['double']:false,
						'hide' => isset($field_config['hide'])?$field_config['hide']:false,
						'fields'=>array(),
					);
				}else{
					$part['title']=$match[1];
					$part['multi'] = isset($field_config['multi'])?$field_config['multi']:false;
					$part['double'] = isset($field_config['double'])?$field_config['double']:false;
				}
			}else{
				$part['fields'][$key]=$field_config;
			}
		}
		if(sizeof($part['fields'])>0)
		{
			$page['parts'][]=$part;
		}
		
		$pages[]=$page;
		//print_r(debug_backtrace());
		return $pages;
	}

	//获取某个输入接口的实例的路径
	function build($config_file,$field,$config)
	{
		$base = $this->routine_helper->get_cache_path('input');//__ROOT__.'/data/input/';
		$filename = $config_file.'_'.md5($field);
		
		
		if(file_exists($base.$filename.'.php') && false)
		{
			return $filename;
		}
		
		foreach($config as $key => $val)
		{
			if(strpos($key,'list')===0)
			{
				unset($config[$key]);
			}
		}
		
		if(strpos($config['input'],'example.')===0)
		{
			$example_config = $this->data_helper->read($this->routine_helper->get_cache_path('example') . substr($config['input'],8) .'.php','config');
			foreach($example_config as $key => $val)
			{
				switch($key)
				{
					case 'name':
						$config['input']=$val;
						break;
					case 'show_name':
						break;
					default:
						$config[$key]=$val;
				}
			}
		}
		
		
		$php = '<input:'.$config['input'].'  name="'.$field.'"';
		foreach($config as $name => $value)
		{
			$value = str_replace('"','&quot;',$value);
			$value = str_replace('<','&lt;',$value);
			$value = str_replace('>','&gt;',$value);
			$php.= ' '.$name.'="'.$value.'"';
		}
		$php.=' />';
		

		file_put_contents($base.$filename.'.php',$php);
		return $filename;
	}
	

	private $checkers = array();
	/**
	* 加载某个数据输入接口的数据检查模型
	* 如果模型不存在，返回false
	*/
	function load_checker($input)
	{
		if(isset($this->checkers[$input])) return $this->checkers[$input];
		$path = __ROOT__.'/taglib/input/'.$input.'/__check.php';
		if(!file_exists($path))
		{
			$this->checkers[$input]=false;
			return false;
		}
		
		try{
			include $path;
			$class = $input.'CheckModel';
			$checker = new $class();
			$this->checkers[$input] = $checker;
			return $checker;
		}catch(Exception $expr){
			die('Your Checker of `input\\'.$input.'` has an error');
		}
	}
	
	//获取某个可复用IO接口的INPUT原型所对应的配置信息
	//可复用的IO接口，就是我们实现对某种接口做好一些配置
	//例如Tree_selector树形选择器，我们对他事先做好一些和HR数据对应的配置，并且保存为可复用IO接口，命名为HR选择器
	//那么在需要用到选择“负责HR”，“所属HR”，“上级HR“等需要选择HR信息的地方，不用每次都配置该树形选择
	//可以直接在配置中选用 “ HR选择器” 作为输入接口，避免重复劳动
	//但系统在获取“HR选择器”的参数时，就必须通过本方法还原出HR选择器的实际参数，才能让该tree_selector发挥作用
	function get_example_detail($config)
	{
		if(isset($config['input']) && strpos($config['input'],'example.')===0)
		{
			$example_config = $this->data_helper->read($this->routine_helper->get_cache_path('example') . substr($config['input'],8) .'.php','config');
			foreach($example_config as $key => $val)
			{
				switch($key)
				{
					case 'name':
						$config['input']=$val;
						break;
					case 'show_name':
						break;
					default:
						$config[$key]=$val;
				}
			}
		}
		return $config;
	}
	
	function check($config_name,&$index_array=NULL,$namefix='')
	{
		if(is_array($config_name)){
			$configs=$config_name;
		}else{
			$configs = $this->table_config->read($config_name);
		}
		$array = array();
		foreach($configs as $field => $config)
		{
			if(!isset($config['input']) || $config['input']=='' || $config['input']=='none') continue;
			$config = $this->get_example_detail($config);
			//前置验证，检查是否留空
			if(isset($config['is_must']) && $config['is_must']=='1' && (!isset($_POST[$field]) || !$_POST[$field]))
			{
				//exit($config['showname'].'是必填项目，不能留空或忽略。');
			}
			$checker = $this->load_checker($config['input']);
			if(false!=$checker)
			{
				$value = $checker->check($field,$config);
			}else{
				$value = isset($_POST[$field])?$_POST[$field]:'';
			}
			if($value!==false){
				if(!is_null($index_array))
				{
					$index_field = preg_replace('/'.$namefix.'$/is','',$field);
					#创建索引
					#如果是有可重复字段的部分,可重复字段值以,连接
					
					if(isset($index_array[$index_field]) && $index_array[$index_field]!==''){
						$index_array[$index_field].=','.$value;
						
					}else $index_array[$index_field]=$value;
				}
				if(is_array($value)) $array = array_merge($array,$value);
				else $array[$field] = $value;
			}
		}
		return $array;
	}
	
	function get_page_config($page)
	{
		$config=array();
		foreach($page['parts'] as $part)
		{
			$config=array_merge($config,$part['fields']);
		}
		return $config;
	}
	
	function get_value($data,$field,$format='common')
	{
		foreach($data as $key => $val)
		{
			if(is_array($val))
			{
				$value = $this->get_value($val,$field,$format);
				if($value!='' && $value!=array()) return $value;
			}else{
				if($key===$field)
				{
					switch($format)
					{
						case 'explode':
							$array = array();
							$arr = explode(',',$val);
							foreach($arr as $item)
							{
								$item = trim($item);
								if($item!='') $array[]=$item;
							}
							return $array;
						default:
							return $val;
					}
				}
			}
		}
		switch($format)
		{
			case 'explode':
				return array();
			default:
				return '';
		}
	}
	
	function set_value(&$data,$key,$value,$is_root = true)
	{
		$set = false;
		foreach($data as $key1 => $value1)
		{
			if($key1===$key)
			{
				$data[$key1] = $value;
				$set = true;
			}
			if(is_array($value1))
			{
				$flag = $this->set_value($value1,$key,$value,false);
				if($flag)
				{
					$data[$key1] = $value1;
					$set = true;
				}
			}
		}
		if($is_root && !$set)
		{
			$data[$key] = $value;
			$set = true;
		}
		return $set; 
	}
	
	function get_true_value($value)
	{
		if(strpos($value,'session:')===0)
		{
			return @$_SESSION[substr($value,8)];
		}else if(strpos($value,'sign:')===0)
		{
			return $this->view->get_var(substr($value,5));
		}else if(strpos($value,'post:')===0)
		{
			return @$_POST[substr($value,5)];
		}else if(preg_match('/^(.+?)\->(.+?):(.+?)$/i',$value,$match))
		{
			//[user->get:<user:id>]
			if(preg_match('/^<(.+?)>$/i',$match[3],$match2))
			{
				$match[3]=$this->get_true_value($match2[1]);
			}
			return $this->{$match[1]}->{$match[2]}($match[3]);
		}else if(preg_match('/\[(.+?)\]/is',$value,$match))
		{
			$value=str_replace($match[0],$this->get_true_value($match[1]),$value);
		}
		return $value;
	}
	
	//获取所有输入接口
	function get_all_inputs()
	{
		$dir = PHPX_DIR.'/taglib/input';
		if(!is_dir($dir)) return array();
		
		$dh = opendir($dir);
		if(!$dh) return array();
		
		$array = array();
		while (($file = readdir($dh)) !== false)
		{
			if(is_dir($dir.'/'.$file) && $file!='.' && $file!='..')
			{
				if(file_exists($dir.'/'.$file.'/__def.php')){
					$title = file_get_contents($dir.'/'.$file.'/__def.php');
					if(!preg_match('/<\?php/i',$title))
					{
						//$group = 'helper';
					}else{
						$config = $this->data_helper->read($dir.'/'.$file.'/__def.php','config');
						$title = isset($config['title'])?$config['title']:$file;
						//$group = isset($config['group'])?$config['group']:'helper';
					}
				}else $title = $file;
				$array[$title] = $file;
			}
		}
		
		$dir = $this->routine_helper->get_cache_path('example');
		$dh = @opendir($dir);
		while(($file = @readdir($dh)) !== false)
		{
			if(is_file($dir.'/'.$file) && strpos($file,'.php')>0)
			{
				include $dir.'/'.$file;
				$key = $config['show_name'];
				$input = $config['name'];
				$array[$key] = 'example.'.$input;
			}
		}
		return $array;
	}
	
	function get_all_examples()
	{
		$dir = $this->routine_helper->get_cache_path('example');
		if(!is_dir($dir)) return array();
		
		$dh = opendir($dir);
		if(!$dh) return array();
		
		$array = array();
		while (($file = readdir($dh)) !== false)
		{
			if(is_file($dir.'/'.$file) && $file!='.' && $file!='..')
			{
				$array[] = $this->data_helper->read($dir.'/'.$file,'config');
			}
		}
		
		return $array;
	}
	
	
	#获取被分组的输入接口
	function get_grouped_inputs()
	{
		$dir = PHPX_DIR.'/taglib/input';
		if(!is_dir($dir)) return array();
		
		$dh = opendir($dir);
		if(!$dh) return array();
		
		$array = array(
			'text' => array(),
			'date' => array(),
			'select' => array(),
			'file' => array(),
			'helper' => array(),
			'example' => array(),
		);
		while (($file = readdir($dh)) !== false)
		{
			if(is_dir($dir.'/'.$file) && $file!='.' && $file!='..')
			{
				if(file_exists($dir.'/'.$file.'/__def.php')){
					$title = file_get_contents($dir.'/'.$file.'/__def.php');
					if(!preg_match('/<\?php/i',$title))
					{
						$group = 'helper';
					}else{
						$config = $this->data_helper->read($dir.'/'.$file.'/__def.php','config');
						$title = isset($config['title'])?$config['title']:$file;
						$group = isset($config['group'])?$config['group']:'helper';
					}
				}else{
					$title = $file;
					$group = 'helper';
				}
				$array[$group][$title] = $file;
			}
		}
		
		$dir = $this->routine_helper->get_cache_path('example');
		$dh = opendir($dir);
		while(($file = readdir($dh)) !== false)
		{
			if(is_file($dir.'/'.$file) && strpos($file,'.php')>0)
			{
				include $dir.'/'.$file;
				$key = $config['show_name'];
				$input = $config['name'];
				$group = 'example';
				$array[$group][$key] = 'example.'.$input;
			}
		}
		
		return $array;
	}
	
	
	function reflect($value)
	{
		if(preg_match_all('/(^|\\[|\\{)(post|get|request|session|env|sql|call):(.+?)(\\}|\\]|$)/is',$value,$matches,PREG_SET_ORDER))
		{
			foreach($matches as $match)
			{
				switch($match[2])
				{
					case 'request':
						$replace_value = $this->query->get($match[3]);
						if(!$replace_value){
							$replace_value = isset($_POST[$match[3]])?$_POST[$match[3]]:'';
						}
						if(!$replace_value)
						{
							$main_row = $this->env->get('MAIN_ROW');
							if($main_row && isset($main_row[$match[3]]))
							{
								$replace_value = $main_row[$match[3]];
							}
						}
						break;
					case 'post':
						$replace_value = isset($_POST[$match[3]])?$_POST[$match[3]]:'';
						break;
					case 'get':
						$replace_value = $this->query->get($match[3]);
						break;
					case 'env':
						$replace_value = $this->env->get($match[3]);
						break;
					case 'session':
						$replace_value = isset($_SESSION[$match[3]])?$_SESSION[$match[3]]:'';
						break;
					case 'sql':
						$rs = $this->db->getOne($match[3]);
						$replace_value="";
						if($rs)
						{
							foreach($rs as $key => $val)
							{
								$replace_value = $val;
								break;
							}	
						}
						break;
					case  'call':
						$array = explode(':',$match[3]);
						if(sizeof($array)==3){
							$replace_value = $this->{$array[0]}->{$array[1]}($array[2]);
						}else{
							$replace_value = $match[0];
						}
						break;
				}
				
				$value = str_replace($match[0],$replace_value,$value);
			}
		}
		return $value;
	}
}