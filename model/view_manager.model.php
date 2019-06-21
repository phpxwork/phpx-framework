<?php
class View_managerModel extends PHPX
{
	function save($fp,$content)
	{
		$patterns = array(
			//'href="\\?(.+?)"',
			//"get\\('[a-zA-Z0-9\\.]+?\\?(.+?)'",
			//"url.*?:.*?['\"]\\?(.+?)['\"]",
		);
		if($this->config->get('virtual_path')==true)
		{
			foreach($patterns as $i => $pattern){
				preg_match_all('/'.$pattern.'/is',$content,$matches,PREG_SET_ORDER);
				foreach($matches as $match)
				{
					$querystring = $match[1];
					$array = explode('&',$querystring);
					$gets = array();
					foreach($array as $item)
					{
						$item=trim($item);
						if(!$item) continue;
						$arr = explode('=',$item);
						$key = trim($arr[0]);
						$val = trim($arr[1]);
						if(!$key) continue;
						$gets[$key]=$val;
					}
					
					$path = $this->env->get('php');
					if(isset($gets['class']))
					{
						$path.='/'.$gets['class'];
					}
					if(isset($gets['method']))
					{
						$path.='/'.$gets['method'];
					}
					foreach($gets as $key => $val)
					{
						switch($key)
						{
							case 'class':
							case 'method':
								break;
							default:
								$path.='/'.$key.'-'.$val;
						}
					}
					if($i==0){
						$content=str_replace($match[0],'href="'.$path.'"',$content);
					}else if($i==1)
					{
						$content=str_replace($match[0],'get("'.$path.'"',$content);
					}else{
						//$content=str_replace($match[0],'get("'.$path.'")',$content);
					}
				}
			}
		}
		file_put_contents($fp,$content);
	}
}