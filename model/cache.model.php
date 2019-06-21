<?php
class CacheModel extends Model{
	//写入缓存
	function write($name,$data){
		$dir=$this->env->get('data_dir')."/cache/";
		if(!is_dir($dir)) mkdir($dir);
		$path=$this->env->get('data_dir')."/cache/{$name}.php";
		
		$file=fopen($path,"w");
		if(!is_null($data)) fwrite($file,$data);
		else fwrite($file,$data);
		fclose($file);
	}
	
	//读取缓存
	function read($name,$time=0){
		$dir=$this->env->get('data_dir')."/cache/";
		if(!is_dir($dir)) mkdir($dir);
		$path=$this->env->get('data_dir')."/cache/{$name}.php";
		if(!is_readable($path))
		{
			return false;
		}
		if($time==0 || time()-filemtime($path)<$time)
		{
			return file_get_contents($path);
		}
		else
		{
			return false;
		}
	}
	
	//删除缓存
	function remove($name){
		$dir=$this->env->get('data_dir')."/cache/";
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
			$path=$this->env->get('data_dir')."/cache/{$name}.php";
			if(file_exists($path)) unlink($path);
		}
	}
}