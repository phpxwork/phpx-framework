<?php
/**
 * Data_helper模型
 *
 * 用于生成和读取数组格式的缓存或者路由文件
 */
class Data_helperModel{
	function write($path,$data,$var_name="data_helper_saving_data"){
		$save_string ="<?php\r\n";
		$save_string.="\$$var_name=".$this->_get_data_format($data).";\r\n";
		file_put_contents($path,$save_string);
	}
	
	function _get_data_format($data,$depth=0){
		$format='';
		for($i=0;$i<$depth;$i++) $format.='	';
		if(is_array($data)){
			$format.="array(\r\n";
			foreach($data as $key => $value){
				for($i=0;$i<$depth+1;$i++) $format.='	';
				if(preg_match("/^[0-9]+?$/is",$key)){
					$format.=$this->_get_data_format($value,$depth+1).",\r\n";
				}else{
					$format.="'$key' => ".$this->_get_data_format($value,$depth+1).",\r\n";
				}
			}
			$format.=")";
		}else if(is_int($data)){
			$format=$data;
		}else if(is_bool($data)){
			$format=$data?"true":"false";
		}else if(is_float($data)){
			$format=$data;
		}else{
			$format="'".str_replace("'","\\'",$data)."'";
		}
		return $format;
	}
	
	function read($path,$var_name="data_helper_saving_data"){
		if(!file_exists($path)) return false;
		include $path;
		if(!isset($$var_name)) return false;
		return $$var_name;
	}
	
	function php_array_to_json($array=array()){
		$json='{';
		foreach($array as $key => $item){
			if($json!='{') $json.=',';
			$json.="'$key':";
			if(is_array($item)){
				$json.=$this->php_array_to_json($item);
			}else if(is_bool($item)){
				$json.=($item?'true':'false');
			}else{
				$json.="'$item'";
			}
		}
		$json.='}';
		return $json;
	}
	
}