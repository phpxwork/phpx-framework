<?php
class Dir_helperModel{
	function check_and_build($path){
		$path=str_replace("\\","/",$path);
		//if(substr($path,-1,1)=="/") $path=substr($path,0,strlen($path)-1);
		$array=explode("/",$path);
		$result='';
		$doc_root=str_replace('\\','/',str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['SCRIPT_FILENAME']));
		$doc_root_root=preg_replace("/^([a-z]+?):.+?$/is","\\1:",$doc_root);
		foreach($array as $i => $item){
			if($i==0 && $item==''){
				//$result.="/";
			}else{
				if(substr($item,-1,1)==":"){
					$result=preg_replace("/^([a-z]+?):/is",$doc_root_root,$item);
				}else if($item!=''){
					$result.="/".$item;
					if(strpos($result,$doc_root)===0 || strpos($doc_root,$result)===0){
						if(!is_dir($result)){
							//echo 'dir:'.$result;
							@mkdir($result);
						}
					}else{
						if(!is_dir($doc_root.'/'.$result)){
							mkdir($doc_root.'/'.$result);
						}
					}
				}
			}
		}
		return $result;
	}
	
	function clean($path){
		$path=$this->check_and_build($path);
		$array=explode("/",$path);
		$new_array=array();
		$i=0;
		foreach($array as $item){
			switch($item){
				case '.';
					break;
				case '..':
					if(sizeof($new_array)>0){
						$i--;
						unset($new_array[$i]);
					}else{
						$new_array[$i]='..';
						$i++;
					}
					break;
				default:
					$new_array[$i]=$item;
					$i++;
			}
		}
		$r=implode('/',$new_array);
		return $r;
	}
}