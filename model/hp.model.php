<?php
class HpModel extends Model{
	function get_options($config){
		if(!isset($config['data_source']) || !$config['data_source']){
			return array();
		}
		$config['data_source'] = str_replace("，",",",$config['data_source']);
		if(strpos($config['data_source'],'=')===false && strpos($config['data_source'],',')===false || preg_match('/SELECT\s+?\*\s+?FROM/i',$config['data_source'])){
			$data_source=$config['data_source'];
			if(preg_match('/SELECT\s+?\*\s+?FROM\s+?([a-z0-9A-Z_]+?)(\s|$)/i',$data_source,$match))
			{
				$table = $match[1];
			}else{
				$table = $data_source;
			}
			
			
			$ds_config=$this->table_config->read($table);
			if(!$ds_config)
			{
				$ds_config = array();
			}
			
			if(isset($config['id_column']) && $config['id_column'])
			{
				$id_column=$config['id_column'];
			}else{
				preg_match("/(^|_)([^_]+?)$/",$table,$match);
				$name_last=$match[2];
				$id_column=$name_last.'_id';
			}
			
			$title_column='';
			if(isset($config['title_column']) && $config['title_column'])
			{
				$title_column = $config['title_column'];
			}else{
				foreach($ds_config as $field => $c_config){
					if(isset($c_config['row']) || isset($c_config['list']) && $c_config['list']=='text')
						$title_column=$field;
					if(isset($c_config['as_title']) && $c_config['as_title']) break;
				}
			}
			if(!$title_column) return array();
			if(preg_match('/SELECT\s+?\*\s+?FROM/i',$data_source))
			{
				$sql = $data_source;
				if(preg_match('/WHERE\s*?$/i',$sql)) $sql.=' 1';
			}else{
				$sql="SELECT `$id_column`,`$title_column` FROM `$data_source`";
				if(isset($config['data_source_state']) && $config['data_source_state']){
					$data_source_stage = $config['data_source_state'];
					$sql.=" WHERE ".$data_source_stage;
				}
			}
			
			if(isset($config['empty_value']) && '[no]'!==$config['empty_value'])
			{
				$result=array(''.$config['empty_value'] => '不限');
			}else{
				$result = array();
			}
			$sql = $this->input->reflect($sql);
			$query=$this->db->query($sql);
			while($option=mysql_fetch_array($query)){
				$result[$option[$id_column]]=$option[$title_column];
				if(isset($config['data_source_state2']) && $config['data_source_state2'] && preg_match('/\$\.(.+?)$/',$config['data_source_state2'],$match)){
					$column=$match[1];
					$state=str_replace('$.'.$column,$option[$column],$config['data_source_state2']);
					$sql="SELECT `$id_column`,`$title_column` FROM `$data_source` WHERE $state";
					$sql = $this->input->reflect($sql);
					$query2=$this->db->query($sql);
					while($option2=mysql_fetch_array($query2)){
						$result[$option2[$id_column]]='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&gt;&nbsp;'.$option[$title_column];
					}
				}
			}
			return $result;
		}
		
		$array=explode(",",$config['data_source']);
		$options=array();
		if(isset($config['empty_value']) && '[no]'!==$config['empty_value']){
			$options['']=$this->lang->get('请选择');
		}
		foreach($array as $item){
			$nameValuePair=explode("=",$item);
			$size=sizeof($nameValuePair);
			if($size==1){
				$name=$nameValuePair[0];
				$value=$nameValuePair[0];
				$option=array();
				$options[$value]=$name;
			}elseif($size==2){
				$name=$nameValuePair[0];
				$value=$nameValuePair[1];
				$option=array();
				$options[$value]=$name;
			}
		}
		return $options;
	}
	
	
	function get_form_config($table_config,$action=0){
		$form_config=array();
		foreach($table_config as $field => $config){
			if($action==0 && @$config['add_lock'] || $action>0 && @$config['edit_lock']) continue;
			switch($config['input']){
				case 'text':
				case 'textarea':
				case 'editor':
				case 'select':
				case 'ui-selector':
					$form_config[$field]=array(
						'type' => $config['input_match'],
						'null' => $config['null'],
						'min' => $config['min'],
						'max' => $config['max'],
						'minlength' => $config['minlength'],
						'maxlength' => $config['maxlength'],
						'showname' => $config['showname'],
						'column' => $field,
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
						'type' => $config['input'],
						'value' => $action,
					);
					break;
			}
		}
		return $form_config;
	}
}