<?php
/**
 * PHPX_Tag_Parser parse the PHPX template tag
 *
 * this class compatible with old version of PHPX
 * the new version tag can define the param type on the tag defination
 * but the old version can not.
 * so this should compatible for the old version
 */
class View_tag_parserModel extends PHPX{
	/**
	 * instance handle of self
	 */
	private static $_instance=null;
	
	/** tag count */
	private $count=0;
	
	/**
	 * getInstance
	 */
	static function getInstance(){
		/**
		 * if _instance property is not a instance of self
		 * make it a PHPX_View instance
		 * and return it
		 */
		if(!self::$_instance){
			self::$_instance=new self();
		}
		return self::$_instance;
	}

	/**
	 * parse
	 *
	 * parse the taglib
	 *
	 * @param template String
	 */
	function parse($template){
		if(!function_exists("preg_replace_callback") || PHP_VERSION<=5.3)
		{
			$template=preg_replace("/\[([a-zA-Z0-9_]+?):([a-zA-Z0-9_]+?)\s(.+?)\]/ise","\$this->parseBracesTag('\\1','\\2','\\3')",$template);
			$template=preg_replace("/<([a-zA-Z0-9_]+?):([a-zA-Z0-9_]+?)\s([^>]*?)\/>/ise","\$this->parseTag('\\1','\\2','\\3')",$template);
			$template=preg_replace("/<([a-zA-Z0-9_]+?):([a-zA-Z0-9_]+?)\s([^>]*?)>([\s\S]*?)<\/\\1:\\2>/ise","\$this->parseTag('\\1','\\2','\\3','\\4')",$template);
		}else{
			$template=preg_replace_callback("/\[([a-zA-Z0-9_]+?):([a-zA-Z0-9_]+?)\s(.+?)\]/is",eval("return function(\$match){
				return \$this->parseBracesTag(\$match[1],\$match[2],\$match[3]);
			};"),$template);
			$template=preg_replace_callback("/<([a-zA-Z0-9_]+?):([a-zA-Z0-9_]+?)\s([^>]*?)\/>/is",eval("return function(\$match){
				return \$this->parseTag(\$match[1],\$match[2],\$match[3]);
			};"),$template);
			$template=preg_replace_callback("/<([a-zA-Z0-9_]+?):([a-zA-Z0-9_]+?)\s([^>]*?)>([\s\S]*?)<\/\\1:\\2>/is",eval("return function(\$match){
				return \$this->parseTag(\$match[1],\$match[2],\$match[3],\$match[4]);
			};"),$template);
		}

		return $template;
	}
	
	function parseBracesTag($taglib,$tag,$attributeStringB){
		$attributeStringB = trim($attributeStringB);
		$attributeStringB=preg_replace("/(^|,)(.+?)=([\$\{\}a-z0-9_\.]+?)(,|$)/is","\\1\\2=\"\\3\" ",$attributeStringB);
		$attributeStringB=preg_replace("/(^|,)(.+?)=([\$\{\}a-z0-9_\.]+?)(,|$)/is","\\1\\2=\"\\3\" ",$attributeStringB);
		$attributeStringB=preg_replace("/(^|,)(.+?)=([\$\{\}a-z0-9_\.]+?)(,|$)/is","\\1\\2=\"\\3\" ",$attributeStringB);
		return $this->parseTag($taglib,$tag,$attributeStringB);
	}
	
	/**
	 * parseTag
	 *
	 * parse a tag
	 *
	 * @param taglib String
	 * @param tag String
	 * @param attributeString String
	 * @param innerHTML String
	 */
	function parseTag($taglib,$tag,$attributeString,$innerHTML=''){
		static $tag_css=NULL;
		/**
		 * count tag
		 */
		$this->count++;
		$tagFlag=$this->view->flag.'---'.$this->count;
		
		
		$attributeString=str_replace("\\\"","\"",$attributeString);
		//$innerHTML=str_replace("\\\"","\"",$innerHTML);
		
		$attributes=$this->getTagAttributes($attributeString);
		$temp=$this->getTagDefination($taglib,$tag);
		
		/** tag not exists*/
		if(is_bool($temp) && !$temp){
			ob_clean();
			if($this->query->get('PHPX_TAGLIB_CHECK'))
			{
				echo json_encode(array(
					'error' => 'codemiss',
					'taglib' => $taglib,
					'tag' => $tag,
				));
			}else{
				include dirname(__FILE__).'/../view/view_tag_parse_model_tag_not_found.php';
			}
			exit;
		}
		
		/** tag exists */
		list($attributeDefination,$tagfilecontent,$tagurl,$tag_path)=$temp;


		/**
		 * check and replace attribute
		 * how to check:
		 * - if attribute not defined in attributeDefination
		 *   this param attribute would be passed
		 * - if attribute defined and required
		 *   but not sent,then return a tag error
		 * - if attribute type not match
		 *   then return a tag error
		 */
		foreach($attributeDefination as $name => $defination){
			foreach($defination as $def => $value){
				switch($def){
					case 'type':
						switch($value){
							case 'int':
								if(isset($attributes[$name]) && !@preg_match("/^[0-9]+?$/is",$attributes[$name])){
									return 'tag '.$taglib.':'.$tag.' attribute '.$name.' type not match.require a int.';
								}
								break;
							case 'bool':
								if(isset($attributes[$name]) && !@preg_match("/^(true|false)$/is",$attributes[$name])){
									return 'tag '.$taglib.':'.$tag.' attribute '.$name.' type not match.require a bool.';
								}
								break;
							case 'var':
								if(isset($attributes[$name]) && !preg_match("/^\\\$[a-zA-z0-9_]+?$/is",$attributes[$name]) && !preg_match("/^\{\\\$[a-zA-z0-9_]+?\}$/is",$attributes[$name])){
									return 'tag '.$taglib.':'.$tag.' attribute '.$name.' type not match.require a var.';
								}
								break;
							default:
						}
						break;
					case 'required':
						if($value=='true' && !isset($attributes[$name])){
							return 'tag '.$taglib.':'.$tag.' attribute '.$name.' required.';
						}
						break;
					case 'default':
						if(!isset($attributes[$name])){
							$attributes[$name]=$value;
						}
						break;
				}
			}
		}
		/**
		 * replacement:
		 * - varname replace arginally
		 * - int replace originally
		 * - bool replace originally
		 * - string
		 *   - between <? ? > replace originally
		 *   - or replace by 'string'
		 */
		 
		/**
		 * $param.rest
		 * is a special param for the not defined but passed params
		 */
		$paramRestString='';
		if($attributes){
			foreach($attributes as $name => $value){
				$name=strtolower($name);
				$value=trim($value);
				if(preg_match("/^[0-9]+?$/is",$value)){
					/** int */
					$tagfilecontent=str_replace("{\$param.$name}",$value,$tagfilecontent);
					$tagfilecontent=preg_replace("/\\\$param.$name([^0-9a-zA-Z_])/is","$value\\1",$tagfilecontent);
				}else if(preg_match("/^\{?\\\$[\s\S]+?\}?$/is",$value)){
					/** varname */
					$value=trim(trim($value,'{'),'}');
					$tagfilecontent=preg_replace("/\\\$param.$name([^0-9a-zA-Z_])/is","$value\\1",$tagfilecontent);
				}else if($value=="true" || $value=="false" || $value=="NULL" || $value=="null"){
					/** bool */
					$tagfilecontent=str_replace("{\$param.$name}",$value,$tagfilecontent);
					$tagfilecontent=preg_replace("/\\\$param.$name([^0-9a-zA-Z_])/is","$value\\1",$tagfilecontent);
				}else{
					/** string */
					$value = str_replace('&quot;','"',$value);
					$tagfilecontent=str_replace("{\$param.$name}",$value,$tagfilecontent);
					$tagfilecontent=preg_replace("/\\\$param\.$name([^0-9A-Za-z_])/is",'\''.str_replace("'","\\'",$value).'\'\1',$tagfilecontent);
				}
				if(!isset($attributeDefination[$name])){
					if(preg_match("/^\{?\\\$[\s\S]+?\}?$/is",$value)){
						$value=trim(trim($value,'{'),'}');
						$paramRestString .= ' '.$name.'="<?php echo '.$value.';?>"';
					}else{
						$paramRestString .= ' '.$name.'="'.$value.'"';
					}
				}
			}
		}
		//replace rest string
		$tagfilecontent=str_replace("{\$tag.rest}",$paramRestString,$tagfilecontent);
		$tagfilecontent=preg_replace("/\\\$tag\.rest([^0-9A-Za-z_])/is",'"'.$paramRestString.'"\1',$tagfilecontent);
		$tagfilecontent=str_replace("__HTML__",$innerHTML,$tagfilecontent);

		$tagfilecontent=str_replace("__TAG__",$tagurl,$tagfilecontent);
		/**
		 * replace tag flag
		 * this replacement is very useful for tag caching contents
		 * as every tag needs a special flag to mark its own data
		 */
		$tagfilecontent=str_replace("{\$tag.flag}",$tagFlag,$tagfilecontent);
		$tagfilecontent=preg_replace("/\\\$tag\.flag([^0-9A-Za-z_])/is",'"'.$tagFlag.'"\1',$tagfilecontent);
		
		$tagfilecontent=preg_replace("/\{?\\\$param\.[a-z0-9A-Z_]+\}?/is","NULL",$tagfilecontent);
		$tagfilecontent=$this->view->parse($tagfilecontent,false);

		return $tagfilecontent;
	}
	
	/**
	 * getTagDefination
	 *
	 * getTag program and param defination
	 * compatible with order version
	 * 
	 * @param taglib String
	 * @param tag String
	 */
	function getTagDefination($taglib,$tag){
		$attributeDefination=array();
		
		$dir_list=array(
			array(
				'path'=>$this->env->get('app_dir').'/taglib',
				'url'=>'__WEB__/'.$this->env->get('app').'/taglib',
			)
		);
		if(file_exists($dir_list[0]['path'].'/_routine.php')){
			try{
				include $dir_list[0]['path'].'/_routine.php';
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
		
		if(!file_exists($dir['path'].'/'.$taglib.'/'.$tag.'/'.$tag.'.tag.php')){
			return false;
		}
		
		if(file_exists($dir['path'].'/'.$taglib.'/'.$tag.'/'.$tag.'.def.php')){
			include $dir['path'].'/'.$taglib.'/'.$tag.'/'.$tag.'.def.php';
			@$attributeDefination=array_merge($attributeDefination,$params);
		}
		$tagfilecontent=file_get_contents($dir['path'].'/'.$taglib.'/'.$tag.'/'.$tag.'.tag.php');
		preg_match_all("/<define:([a-zA-Z0-9_]+?)\s([^>]*?)\/>/is",$tagfilecontent,$matches,PREG_SET_ORDER);
		foreach($matches as $match){
			$attributeName=$match[1];
			$attributeString=$match[2];
			$attributeDefination[strtolower($attributeName)]=array_change_key_case(self::getTagAttributes($attributeString));
		}
		$tagfilecontent=preg_replace("/<define[\s\S]+?\/>/is",'',$tagfilecontent);
		return array($attributeDefination,$tagfilecontent,$dir['url'].'/'.$taglib.'/'.$tag,$tag_path);
	}
	
	/**
	 * getTagAttributes
	 *
	 * parse the attribute string part
	 * and get attribute array
	 * in tag defination this array is a type def
	 * in tag calling this array is a param set
	 */
    function getTagAttributes($attr){
        /** security filter*/
        $attr = str_replace("&","&amp;", $attr);
        $attr = str_replace("<","&lt;", $attr);
        $attr = str_replace(">","&gt;", $attr);
        $xml =  '<tpl><tag '.$attr.' /></tpl>';
		@$xml = simplexml_load_string($xml);
		$xml = (array)($xml->tag->attributes());
		if(isset($xml['@attributes']) && is_array($xml['@attributes'])) $array = array_change_key_case($xml['@attributes']);
		else $array=array();
		$array = str_replace('&lt;','<',$array);
		$array = str_replace('&gt;','>',$array);
		$array = str_replace('&amp;','&',$array);
		return $array;
	}
}
?>