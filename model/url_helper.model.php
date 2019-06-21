<?php
class Url_helperModel extends Model{
	private $rules=array();
	
	/**
	 * add_rules
	 *
	 * 作用：添加规则
	 */
	public function add_rules($rules=array()){
		$this->rules=array_merge($this->rules,$rules);
	}

	function parse($string,$intoHTML=false){
		foreach($this->rules as $rule){
			$model=$rule['model'];
			$method=$rule['method'];
			$string = preg_replace("/(href)[\s]*?=[\s]*?\"([^\"]*?".$rule['pattern']."[^\"]*?)\"/ise","'\\1=\"'.\$this->\$model->\$method('\\2').'\"'",$string);
			//echo "(.+?".$rule['pattern'].".+?)";exit;
			//echo $string;exit;//if(preg_match('/'.$rule['pattern'].'/is',$url)){
				//return $this->$model->$method($url);
			//}
		}
		
		if(function_exists("preg_replace_callback")){
			/*$string = preg_replace_callback("/(src|href|action)[\s]*?=[\s]*?\"(.+?)\"/is",function($match) use($intoHTML){
				return $match[1].'="'.$this->parseItem($match[2],$intoHTML).'"';
			},$string);*/
		}else{
			$string = preg_replace("/(src|href|action)[\s]*?=[\s]*?\"(.+?)\"/ise","'\\1=\"'.\$this->parseItem('\\2',\$intoHTML).'\"'",$string);
		}
		return $string;
	}
	
	/**
	 * parseItem
	 *
	 * 解析一条URL
	 *
	 * @param string
	 */
	function parseItem($url,$intoHTML=false){

		$url=str_replace('//','/',str_replace('\\','/',$url));
		$url=str_replace('http:/','http://',$url);
		if(strpos($url,'http://')===0){
		}elseif(strpos($url,'~')===0){
			$url=substr($url,1);
		}elseif(preg_match("/^([^<]*?)\?([\s\S]+?)$/i",$url,$matches)){
			if($intoHTML || true){
				$class=preg_replace("/^.*?[\?&]class=(.+?)[&$].*/is","\\1",$url);
				if(!$class || $class==$url) $class=$this->config->get('default_class');
				$method=preg_replace("/^.*?[\?&]method=([a-z0-9A-Z_]+)/is","\\1",$url);
				if(!$method || $method==$url) $method=$this->config->get('default_method');
				if($this->config->checkHtml($class,$method)!==false){
					if($class==$this->config->get('default_class') && $method==$this->config->get('default_method')){
						$turl=$this->env->get('app_url').'/index';
					}else if($method==$this->config->get('default_method')){
						$turl=$this->env->get('app_url').'/html/'.$class;
					}else{
						$turl=$this->env->get('app_url').'/html/'.$class.'/'.$method;
					}
					$querystring=preg_replace("/^.*?\?(.+?)$/is","\\1",$url);
					$array=explode('&',$querystring);
					foreach($array as $item){
						$p=explode('=',$item);
						if(sizeof($p)==2){
							$p[0]=strtolower($p[0]);
							if($p[0]!='class' && $p[0]!='method'){
								if($p[0]=='page'){
									if($p[1]>1){
										$turl.='_'.$p[1];
									}
								}else if($p[0]=='id'){
									$f=intval($p[1]/3000);
									if($f>0){
										$turl.='/'.$f.'/'.$p[1];
									}else{
										$turl.='/'.$p[1];
									}
								}else{
									$f=intval($p[1]/3000);
									if($f>0){
										$turl.='/'.$p[0].'/'.$f.'/'.$p[1];
									}else{
										$turl.='/'.$p[0].'/'.$p[1];
									}
								}
							}
						}
					}
					$turl.='.html';
					$url=$turl;
				}
			}else{
				if($this->config->get('virtual_path')){
					$url=preg_replace("/^([^<]*?)\?([\s\S]+?)$/ise","'".$this->env->get('php_file')."/'.str_replace('=','-',str_replace('&','/','\\2'))",$url);
				}
			}
		}else{
			if($url[0]!='/' && $url[0]!='#' && $url[0]!='{' && $url[0]!='<' && !strpos($url,':')){
				$url=$this->pathConvert($this->env->get('app_url'),$url);
			}
		}
		return $url;
	}
	
	function pathConvert($baseUrl,$destUrl){
		$baseUrl  = rtrim(str_replace('//','/',str_replace('\\','/',$baseUrl)),'/');
		if($baseUrl=='') $baseUrl='/';
		if($baseUrl[0]!='/' && $baseUrl[0]!='\\'){
			@trigger_error('BASEURL_MUST_BE_ABSOLUTE_PATTERN');
			return $destUrl;
		}
		$destUrl  = str_replace('//','/',str_replace('\\','/',$destUrl));
		if($destUrl[0]=='/') return $destUrl;
		$baseArray=explode('/',$baseUrl);
		$destArray=explode('/',$destUrl);
		$baseNumber=count($baseArray);
		$destNumber=count($destArray);
		$append='';
		for($i=0;$i<$destNumber;$i++)
		{
			if(isset($destArray[$i])){
				if($destArray[$i]=='..') $baseNumber--;
				else if($destArray[$i]!='.') $append.='/'.$destArray[$i];
			}
			if($baseNumber<0){
				return $destUrl;
			}
		}
		$pre='';
		for($i=0;$i<$baseNumber;$i++){
			if(trim($baseArray[$i])!='') $pre.='/'.$baseArray[$i];
		}
		return $pre.$append;
	}
}
