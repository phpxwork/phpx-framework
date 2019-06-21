<?php
class ShareModel extends PHPX{
	//设置share
	function set($name,$data){
		$dataDir=$this->env->get('data_dir');
		$dir=$dataDir."/sharedata/";
		if(!is_dir($dir)) mkdir($dir);
		if(strpos($name,'/')>0){
			$arr=explode('/',$name);
			foreach($arr as $key => $d){
				if($key<sizeof($arr)-1){
					$dir.='/'.$d;
					if(!is_dir($dir)) mkdir($dir);
				}
			}
		}
		$path=$dataDir."/ShareData/{$name}.php";
		$file=fopen($path,"w");
		fwrite($file,"<?php\r\n\$share = '".str_replace("'","\'",serialize($data))."';\r\n?>");
		fclose($file);
	}
	
	//读取share
	function get($name,$dir=null){
		if(!$dir) $dir=$this->env->get('data_dir')."/sharedata/";
		if(!is_dir($dir)) mkdir($dir);
		$path=$dir."{$name}.php";
		if(!is_readable($path)){
			return false;
		}
		include $path;
		$data=unserialize($share);
		return $data;
	}
	
	//读取表share
	function readTable($table){
		$data=$this->get('PHPX_table_'.$table);
		if(!is_array($data)){
			$data=array();
			$query=$this->db->query("SELECT * FROM `$table`");
			while($rs=$this->db->fetchArray($query)){
				$data[]=$rs;
			}
			unset($query);
			$this->set('PHPX_table_'.$table,$data);
		}
		return $data;
	}
	
	//删除share
	function remove($name){
		$dir=$this->env->get('data_dir')."/sharedata/";
		if(!is_dir($dir)) mkdir($dir);
		if($name[strlen($name)-1]=='*'){
			$name=substr($name,0,strlen($name)-1);
			$handle=opendir($dir);
			while($file=readdir($handle)){
				if(preg_match("/^$name.+/is",$file)){
					unlink($dir.'/'.$file);
				}
			}
		}else{
			//删除单个文件
			$path=$this->env->get('data_dir')."/sharedata/{$name}.php";
			if(file_exists($path)) unlink($path);
		}
	}
}