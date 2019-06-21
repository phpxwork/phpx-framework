<?php
/**
 * routine_helper 路由助手模型
 *
 */
class Routine_helperModel extends PHPX{
	/**
	 * get_url 方法
	 *
	 * @$entrance String 入口文件
	 * @$class String 控制器
	 * @$method String 方法名
	 * @$param Array 参数名
	 */
	function get_url($entrance,$class,$method,$param=array()){
		$url=$entrance.'?class='.$class.'&method='.$method;
		foreach($param as $name => $value){
			$url.='&'.urlencode($name).'='.urlencode($value);
		}
		return $url;
	}

	function get_cache_path($folder)
	{
		if(!is_dir(__ROOT__.'/host')) mkdir(__ROOT__.'/host');
		if(!is_dir(__ROOT__.'/host/'.$_SERVER['HTTP_HOST'])) mkdir(__ROOT__.'/host/'.$_SERVER['HTTP_HOST']);
		if(!is_dir(__ROOT__.'/host/'.$_SERVER['HTTP_HOST'].'/'.$folder)) mkdir(__ROOT__.'/host/'.$_SERVER['HTTP_HOST'].'/'.$folder);

		return __ROOT__.'/host/'.$_SERVER['HTTP_HOST'].'/'.$folder.'/';

	}
}