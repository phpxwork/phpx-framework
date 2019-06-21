<?php
/**
 * PHPX 路由模型
 * 该模型是系统的核心模型，关联整个系统的路由结构
 * 该模型不可删除，否则PHPX系统将无法使用
 */
class RoutineModel extends PHPX{
	public $actionConfigFile = NULL;
	public $modelConfigFile = NULL;
	public $taglibConfigFile = NULL;
	
	//获取控制器的路由函数
	function get_controller_file($class){
		$class=strtolower($class);
		$actionFilePath = $this->env->get('app_dir').'/controller/'.$class.'.php';
		if(!file_exists($actionFilePath)){
			if($this->actionConfigFile===NULL){
				if(file_exists($this->env->get('app_dir').'/controller/_routine/'.$_SERVER['HTTP_HOST'].'.php'))
				{
					$this->actionConfigFile = $this->env->get('app_dir').'/controller/_routine/'.$_SERVER['HTTP_HOST'].'.php';
				}elseif(file_exists($this->env->get('app_dir').'/controller/_routine.php')){
					$this->actionConfigFile = $this->env->get('app_dir').'/controller/_routine.php';
				}else{
					$this->actionConfigFile = '';
				}
			}
			$r=false;
			$dir_list = [];
			if($this->actionConfigFile){
				include $this->actionConfigFile;
				foreach($_routine as $actionFolder){
					$dir_list[]=$this->env->get('app_dir').'/controller/'.$actionFolder.'/'.$class.'.php';
				}
			}
			$dir_list[] = PHPX_DIR.'/controller/'.$class.'.php';
			foreach($dir_list as $path){
				if(file_exists($path)){
					$actionFilePath = $path;
					define('ACTION_FILE',$actionFilePath);
					$r=true;
					break;
				}

			}


			if(!$r) exit('Controller file '.$class.'.php not found.');
		}
		return $actionFilePath;
	}
	
	//获取模型路由
	function get_model_file($name){
		$dir_list=array($this->env->get('app_dir').'/model');
		if(is_null($this->modelConfigFile))
		{
			if(@file_exists($dir_list[0].'/_routine/'.$_SERVER['HTTP_HOST'].'.php')){
				$this->modelConfigFile = $dir_list[0].'/_routine/'.$_SERVER['HTTP_HOST'].'.php';
			}elseif(file_exists($dir_list[0].'/_routine.php'))
			{
				$this->modelConfigFile = $dir_list[0].'/_routine.php';
			}else{
				$this->modelConfigFile = '';
			}
		}
		if($this->modelConfigFile){
			try{
				include $this->modelConfigFile;
				foreach($_routine as $dir){
					$dir_list[]=$dir_list[0].'/'.$dir;
				}
			}catch(Exception $e){
				echo 'Model dir config file:`'.$dir_list[0].'/_routine.php` has error';
			}
		}
		$dir_list[]=__ROOT__.'/model';
		$dir_list[]=PHPX_DIR.'/model';
		foreach($dir_list as $model_dir){
			$model_path=$model_dir.'/'.$name.'.model.php';
			if(file_exists($model_path)){
				if(isset($_SESSION['debugger']) && $_SESSION['debugger']=='PHPX')
				{
					echo '<div style="background:#cfc;border:1px solid #060;color:#060">';
					echo $model_path;
					echo '</div>';
				}
				return $model_path;
			}
		}
		
		return false;
	}
	
	function get_tag_dir($taglib,$tag)
	{
		$dir_list=array(
			array(
				'path'=>$this->env->get('app_dir').'/taglib',
				'url'=>'__WEB__/'.$this->env->get('app').'/taglib',
			)
		);
		if(is_null($this->taglibConfigFile))
		{
			if(file_exists($dir_list[0]['path'].'/_routine/'.$_SERVER['HTTP_HOST'].'.php')){
				$this->taglibConfigFile = $dir_list[0]['path'].'/_routine/'.$_SERVER['HTTP_HOST'].'.php';
			}elseif(file_exists($dir_list[0]['path'].'/_routine.php'))
			{
				$this->taglibConfigFile = $dir_list[0]['path'].'/_routine.php';
			}else{
				$this->taglibConfigFile = '';
			}
		}
		
		if($this->taglibConfigFile){
			try{
				include $this->taglibConfigFile;
				foreach($_routine as $dir){
					$dir_list[]=array(
						'path'=>$dir_list[0]['path'].'/'.$dir,
						'url'=>$dir_list[0]['url'].'/'.$dir,
					);
				}
			}catch(Exception $e){
				echo 'tag dir config file:`'.$dir_list[0].'/config.php` has error';
			}
		}
		$dir_list[]=array(
			'path'=>__ROOT__.'/taglib',
			'url'=>'__WEB__/taglib',
		);

		$dir=NULL;
		foreach($dir_list as $k => $tag_dir){
			$tag_path=$tag_dir['path'].'/'.$taglib.'/'.$tag;
			if(file_exists($tag_path) && is_dir($tag_path)){
				$dir=$tag_dir;
				break;
			}
		}
		if(is_null($dir)) return false;
		return $dir;
	}
}