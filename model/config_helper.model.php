<?php
class Config_helperModel extends Model{
	private $_config=array();
	
	function create_default_config_file($tablename,$config_file){
		if(!isset($this->_config[$tablename])){
			$query=$this->db->query('SHOW FULL FIELDS FROM '.$tablename);
			$table=array();
			while($column=mysql_fetch_array($query)){
				$table[$column['Field']]=$column;
			}
			$this->_config[$tablename]=$table;
		}
		
		if(!isset($this->_config[$tablename])){
			return false;
		}
		
		$php ="<?php\r\n";
		$php.="\$config=array(\r\n";
		foreach($this->_config[$tablename] as $field => $column){
			$showname = $column['Comment']!=''?str_replace("'","\\'",$column['Comment']):$field;
			$type=preg_replace("/\(.+?\)/","",$column['Type']);
			switch($type){
				case 'varchar':
				case 'char':
					preg_match("/\((.+?)\)/",$column['Type'],$match);
					$max=$match[1];
					$php.= "	'$field' => array(\r\n";
					$php.= "		'showname' => '".$showname."',\r\n";
					$php.= "		'row' => '$field',\r\n";
					$php.= "		'type' => 'string',\r\n";
					$php.= "		'maxlength' => $max,\r\n";
					$php.= "		'minlength' => 0,\r\n";
					$php.= "        'list' => 'input',\r\n";
					$php.= "        'list_show' => 1,\r\n";
					$php.= "		'null' => ".($column['Null']=='NO'?'false':'true').",\r\n";
					$php.= "		'input' => 'text',\r\n";
					$php.= "	),\r\n";
					break;
				case 'int':
				case 'tinyint':
				case 'mediumint':
					preg_match("/\((.+?)\)/",$column['Type'],$match);
					$max=$match[1];
					$php.= "	'$field' => array(\r\n";
					$php.= "		'showname' => '".$showname."',\r\n";
					$php.= "		'row' => '$field',\r\n";
					$php.= "		'type' => 'int',\r\n";
					$php.= "		'max' => $max,\r\n";
					$php.= "		'min' => 0,\r\n";
					$php.= "        'list' => 'input',\r\n";
					$php.= "        'list_show' => 1,\r\n";
					$php.= "		'null' => ".($column['Null']=='NO'?'false':'true').",\r\n";
					if($column['Extra']!='auto_increment'){
						$php.= "		'input' => 'text',\r\n";
					}else{
						$php.= "		'input' => 'none',\r\n";
					}
					$php.= "	),\r\n";
					break;
				case 'text':
				case 'mediumtext':
				case 'longtext':
					$php.= "	'$field' => array(\r\n";
					$php.= "		'showname' => '".$showname."',\r\n";
					$php.= "		'row' => '$field',\r\n";
					$php.= "		'type' => 'int',\r\n";
					$php.= "		'max' => $max,\r\n";
					$php.= "		'min' => 0,\r\n";
					$php.= "        'list' => 'input',\r\n";
					$php.= "        'list_show' => 1,\r\n";
					$php.= "		'null' => ".($column['Null']=='NO'?'false':'true').",\r\n";
					$php.= "		'input' => 'textarea',\r\n";
					$php.= "	),\r\n";
					break;
			}
		}
		
		$php.=");\r\n";
		file_put_contents($config_file,$php);
	}
	
	function get_config($tablename){	
		if(strpos($tablename,'pre_')===0) $tablename=str_replace('pre_',$this->config->get('db_pre'),$tablename);
		if(defined('ACTION_FILE')){
			$config_dir=dirname(ACTION_FILE).'/../config';
		}else{
			$config_dir=$this->env->get('app_dir').'/config';
		}
		
		
		if(!is_dir($config_dir)){
			mkdir($config_dir);
		}
		
		$config_file=$config_dir.'/'.$tablename.'.php';
		if(!file_exists($config_file)){
			$this->create_default_config_file($tablename,$config_file);
		}
		include $config_file;
		
		if(!$config)
		{
			$this->create_default_config_file($tablename,$config_file);
			include $config_file;
		}
		return $config;
	}
	
	function get_list_config($tablename){
		if(strpos($tablename,'pre_')===0) $tablename=str_replace('pre_',$this->config->get('db_pre'),$tablename);
		if(!isset($this->_config[$tablename])){
			$this->_config[$tablename]=$this->get_config($tablename);
		}
		
		$config=$this->_config[$tablename];
		$list_config=array();
		foreach($config as $field => $c){
			if(@$c['list']!='null'){
				$list_config[$field]=array(
					'input' => @$c['input'],
					'list' => @$c['list'],
					'showname' => $c['showname'],
					'row' => $c['row'],
					'data_source' => isset($c['data_source'])?$c['data_source']:'',
				);
				if(@$c['list']=='img'){
					$list_config[$field]['path']=@$c['path'];
				}
			}
		}
		return $list_config;
	}
	
	function get_form_config($tablename,$action=0){
		//error_reporting(0);
		if(strpos($tablename,'pre_')===0) $tablename=str_replace('pre_',$this->config->get('db_pre'),$tablename);
		if(!isset($this->_config[$tablename])){
			$this->_config[$tablename]=$this->get_config($tablename);
		}
		$_config=$this->_config[$tablename];
		$form_config=array();
		foreach($_config as $field => $config){
			if($action==0 && @$config['add_lock'] || $action>0 && @$config['edit_lock']) continue;
			switch($config['input']){
				case 'text':
				case 'textarea':
				case 'editor':
				case 'select':
				case 'ui-selector':
					$form_config[$field]=array(
						'input' => $config['input'],
						'type' => $config['input_match'],
						'null' => $config['null'],
						'min' => @$config['min'],
						'max' => @$config['max'],
						'minlength' => @$config['minlength'],
						'maxlength' => @$config['maxlength'],
						'showname' => $config['showname'],
						'column' => $field,
						'data_source' =>$config['data_source'],
						'default' => $config['default'],
					);
					break;
				case 'createdate':
				case 'update':
					$form_config[$field]=array(
						'type' => 'now',
					);
					break;
				case 'password':
					$form_config[$field]=array(
						'input' => $config['input'],
						'type' => 'password',
						'null' => $config['null'],
						'min' => $config['min'],
						'max' => $config['max'],
						'minlength' => $config['minlength'],
						'maxlength' => $config['maxlength'],
						'showname' => $config['showname'],
						'column' => $field,
					);
					break;
				case 'file':
					$form_config[$field]=array(
						'input' => $config['input'],
						'type' => 'file',
						'ext' => $config['ext'],
						'null' => $config['null'],
						'maxsize' => $config['maxsize'],
						'path' => $config['path'],
						'showname' => $config['showname'],
						'column' => $field,
					);
					break;
				case 'check':
				case 'session':
					$form_config[$field]=array(
						'input' => $config['input'],
						'type' => $config['input'],
						'value' => $action,
					);
					break;
				case 'set':
					$form_config[$field]=array(
						'input' => $config['input'],
						'type' => 'string',
						'value' => $_POST[$field],
					);
			}
		}
		return $form_config;
	}
	
	function check_form_config($config=array(),$action='add'){
		//return config;
	}
}