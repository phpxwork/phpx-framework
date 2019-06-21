<?php
class Table_configModel extends Model
{
	function get_path($config_name)
	{
		if($config_name[0]=='$')
		{
			$path = __ROOT__.'/host/'.$_SERVER['HTTP_HOST'].'/config/'.$config_name.'.php';
		}else if(is_dir($this->env->get('app_dir').'/config/config') && file_exists($this->env->get('app_dir').'/config/config/'.$config_name.'.php')){
			$path = $this->env->get('app_dir').'/config/config/'.$config_name.'.php';
		}else{
			$path = $this->routine_helper->get_cache_path('config').$config_name.'.php';
		}
		return $path;
	}
	
	function read($config_name)
	{
		$path = $this->get_path($config_name);
		if($config_name[0]=='$' && !file_exists($path))
		{
			$template_id = substr($config_name,strlen('$template_'));

			$this->data_template->serialize_template_into_config($template_id,true);
			$config = $this->data_helper->read($path,'config');
			$this->write($config_name,$config);
			return $config;
		}

		return $this->data_helper->read($path,'config');
	}
	
	function write($config_name,$data)
	{
		$path = $this->get_path($config_name);
		$this->data_helper->write($path,$data,'config');
	}
	
	function output($field,$field_config,$rst)
	{
		switch(@$field_config['list']){
		case 'text':
			echo @$rst[$field];
			break;
		case 'date-time':
			echo date('Y-m-d H:i',$rst[$field]);
			break;
		case 'date':
			echo date('Y-m-d',$rst[$field]);
			break;
		case 'time':
			echo date('H:i',$rst[$field]);
			break;
		case 'replace':
			echo preg_replace('/\[(.+?)\]/ise',"\$rst['\\1']",$field_config['list_replacement']);
			break;
		case 'input':
			if(!isset($rst[$field])) echo '&nbsp;';
			else
			if(isset($field_config['input']) && $field_config['input'] && $field_config['input']!='none')
			{
				if(!isset($output_class)) $output_class=array();
				if(strpos($field_config['input'],'example.')===0)
				{
					$field_config = $this->input->get_example_detail($field_config);
				}
				$class_name = strtoupper($field_config['input'][0]) . substr($field_config['input'],1) . 'OutputModel';
				if(!isset($output_class[$field]))
				{
					$dir = $this->routine->get_tag_dir('input',$field_config['input']);
					$file = $dir['path'] . '/input/' . $field_config['input'] . '/__output.php';
					if(file_exists($file))
					{
						include_once $file;
						$output_class[$field] = new $class_name($this);
					}else{
						//如果该输入接口，没有定义输出方法类
						//则即使list设置为“按照输入接口定义的进行输出”，
						//也只是按照普通的方法进行输出
						echo $rst[$field];
						return;
					}
				}


				$obj = $output_class[$field];
				$obj->output($rst[$field],$field_config);
				//$$function_name($rst[$field],$field_config);
			}
			break;
		}
	}
}