<?php
/**
 * PHPX开发框架模版引擎
 *
 */
//include PHPX_DIR.'/core/lang/PHPX_lang.php';
//include PHPX_DIR.'/Core/Cache/PHPX_Cache.php';

/**
 * PHPX view class
 *
 * is also the PHPX template class
 * and main method PHPX use to make MVC mode
 */
class ViewModel extends PHPX{
	/** instance of absolute class */
	protected static $_instance=null;
	
	/** layout template */
	private $_layout='';
	
	/** static var list */
	public $_staticList=array();
	
	/** signed var */
	private $_var=array();
	private $_name=array();
	
	/** page flag */
	public $flag='';
	
	/** folder */
	private $_folder='';
	
	/** absolute_dir */
	private $_absolute_dir='';
		
	private $_type='html';
	
	/** action */
	private $action=NULL;
	
	/** static */
	private $static=array();
	
	function __construct(){
		self::$_instance=$this;
		parent::__construct();
	}
	
	public static function getInstance(){
		if(!self::$_instance){
			new self();
		}
		return self::$_instance;
	}
	
	function initAction($obj){
		$this->action=$obj;
	}
	
	function setFolder($f){
		$this->_folder=$f;
	}
	
	function setAbsoluteDir($_ad){
		$this->_absolute_dir=$_ad;
	}
	
	function setType($type='html'){
		$this->_type=$type;
	}
	
	private $_cache_pre = '';
	function setCachePrefix($pre)
	{
		$this->_cache_pre = $pre;
	}

	/**
	 * display
	 *
	 * display a view page
	 */
	function display($tpl='',$layout='',$outputhtml='',$return_dir=false,$ignore_static=false){
		//if($this->_folder) $folder=$this->_folder;
		//else $folder=$this->env->get('class');
		$style=$this->config->get('style');
		$folder=strtolower($this->env->get('class'));
		$cache_folder=strtolower($this->env->get('php').'$'.$this->env->get('class').'$'.$this->env->get('method'));
		if($tpl=='') $tpl=$this->env->get('method');
		
		
		if(strpos($tpl,'#')>0)
		{
			$tpl_exploded=explode('#',$tpl);
			$true_tpl = $tpl_exploded[0];
			$tpl_key_part = $tpl_exploded[1];
		}else{
			$true_tpl = $tpl;
			$tpl_key_part = '';
		}
		$app=$this->env->get('app_dir');


		
		if($this->config->get('template_dir')){
			$style_dir = $this->config->get('template_dir');
		}else{
			$style_dir = $app.'/view'.($style?'/'.$style:'');
		}
		if(empty($this->_layout))
		{
			$layout_prefix='';
		}else if(is_string($this->_layout))
		{
			$layout_prefix=$this->_layout;
		}else{
			$layout_prefix=implode(',',$this->_layout);
		}
		if(strpos($tpl,'../../../')===0)
		{
			$path=$this->routine_helper->get_cache_path('tpl').strtolower( str_replace('/','_',($layout_prefix . '$'.$tpl)).'.php');
		}else{
			$path=$this->routine_helper->get_cache_path('tpl').strtolower( str_replace('/','_',($layout_prefix . '$'.$style . '--' . $cache_folder . '--' .$this->_cache_pre . str_replace("/","--",$tpl) )) .".php");
		}
		if(!file_exists($path) || $this->config->get('debug_mode')){
			//模版文档
			if($this->_absolute_dir){
				$tp=$this->_absolute_dir.'/'.$true_tpl.'.php';
				if(!file_exists($tp)){
					exit('template file `'.$tp.'` not exists');
				}
			}else{
				$tp="$style_dir/$folder/{$true_tpl}.php";
				if(!file_exists($tp)){
					if($this->_folder){
						$folder=$this->_folder;
						$tp="$style_dir/$folder/{$true_tpl}.php";
						if(!file_exists($tp)) exit('template file `'.$tp.'` not exists');
					}else{
						exit('template file `'.$tp.'` not exists');
					}
				}
			}
			if($this->_layout && !$layout && !is_null($layout)) $layout=$this->_layout;
			if($layout){
				if(!is_array($layout)) $layout=array($layout);
				$layoutHtml = '';
				foreach($layout as $_layout){
					if(strpos($_layout,'/')===0 || strpos($_layout,':')>0)
					{
						if(strpos($_layout,'.php')===false)
						{
							$_layout.='.php';
						}
						$layoutContent=$this->parse($_layout);
					}else if(strpos($_layout,'__ROOT__')===0)
					{
						$_layout=str_replace('__ROOT__',__ROOT__,$_layout);
						$layoutContent=$this->parse($_layout);
					}else if(file_exists("$style_dir/$folder/$_layout.php")){
						$layoutContent=$this->parse("$style_dir/$folder/$_layout.php");
					}else if(defined('ACTION_FILE') && file_exists(dirname(ACTION_FILE).'/../view/'.strtolower($this->env->get('class')).'/'.$_layout.'.php')){
						$layoutContent=$this->parse(dirname(ACTION_FILE).'/../view/'.strtolower($this->env->get('class')).'/'.$_layout.'.php');
					}else{
						$layoutContent=$this->parse("$style_dir/public/$_layout.php");
					}
					if($layoutHtml == '') $layoutHtml = $layoutContent;
					else $layoutHtml = str_replace('{layout}',$layoutContent,$layoutHtml);
					
				}
			}
		}


		if(!$this->flag) $this->flag=$style.'---'.$folder.'---'.$tpl;

		if(!file_exists($path) || $this->env->get('debug_mode')){
			$dir=$this->env->get('data_dir')."/tpl";
			if(!is_dir($dir)) mkdir($dir);
			/*if(strpos($style,'/')){
				$temps=explode('/',$style);
				$dir=$this->env->get('data_dir')."/Tpl";
				foreach($temps as $f){
					$f=trim($f);
					if($f!='.'){
						$dir=$dir.'/'.$f;
						if(!is_dir($dir)) mkdir($dir);
					}
				}
			}else{
				$dir=$this->env->get('data_dir')."/Tpl/$style";
				if(!is_dir($dir)) mkdir($dir);
			}
			
			$dir=$this->env->get('data_dir')."/Tpl/$style/$folder";
			if(!is_dir($dir)) mkdir($dir);
			
			if(strpos($tpl,'/')>0){
				$array=explode('/',$tpl);
				if(!is_dir($dir.'/'.$array[0])) mkdir($dir.'/'.$array[0]);
			}*/
			$content=$this->parse($tp);
			if($tpl_key_part && preg_match('/<\!\-\-\{#'.$tpl_key_part.'\}\-\->([\s\S]+?)<\!\-\-\{#end\}\-\->/i',$content,$match))
			{
				$content = $match[1];
			}else{
				if($this->_layout && !$layout && !is_null($layout)) $layout=$this->_layout;
				if($layout){
					$content=str_replace("{layout}",$content,$layoutHtml);
				}
			}
			if(strpos($tpl,'/')>=0){
				//$path=$this->env->get('data_dir')."/Tpl/$style/$folder/".md5($tpl).".php";
			}
			if($ignore_static===false){
				foreach($this->static as $key => $value){
					$content = str_replace("{static:$key}",$value,$content);
				}
				$content = preg_replace("/\{static:.+?\}/is","",$content);
			}
			$this->view_manager->save($path,$content);
		}
		
		if($return_dir){
			return $path;
		}
		/**
		 * include path
		 * this statement is the real part that executing the template
		 * so we can see the following points:
		 * - the template runs in display method of view class
		 * - template can use vars of view class
		 * - template cannt share vars with action class
		 */
		if($outputhtml){
			ob_start();
			include $path;
			$html=ob_get_contents();
			ob_clean();
			if($outputhtml=='return') return $html;
			file_put_contents($outputhtml,$html);
		}else{
			//register_shutdown_function(array($this,"shutdown_handler"));
			@header('Content-Type:text/'.$this->_type.';charset=utf-8');
			include $path;
			ob_flush();
		}
	}


	function shutdown_handler()
	{
		$error = error_get_last();
		if ($error && in_array($error['type'],array(E_ERROR,4))) {//把你需要记录的错误类型修改下就行
			echo '<div style="background:#ffa;border:1px solid #555;padding:5px;border-radius:3px;">';
			echo '<a href="/dev.php?class=debug&path='.$error['file'].'">点击打开：'.$error['file'].'</a>';
			echo '</div>';
		}
	}
	/**
	 * parse
	 *
	 * parse a template file into a valid php
	 */
	function parse($tpl,$isFileName=true,$ignore_static=false){
		$tagParser = $this->view_tag_parser;
		if($isFileName) $tplcontent=file_get_contents($tpl);
		else{
			$tplcontent=$tpl;
			$tpl='';
		}
		/**
		 * get PHPX tags parsed
		 */
		$tplcontent=$tagParser->parse($tplcontent);

		/**
		 * replace all static vars
		 */
		$this->initStaticVar();
		$tplcontent = $this->replaceStaticVar($tplcontent);
		
		/**
		 * execute template command
		 */
		if(function_exists("preg_replace_callback") && PHP_VERSION>5.3)
		{
			$tplcontent = preg_replace_callback("/\{template\s+(.+?)\}/is",eval("return function(\$match) use(\$tpl){
				return \$this->templateInclude(\$match[1],\$tpl);
			};"), $tplcontent);
		}else{
			$tplcontent = preg_replace("/\{template\s+(.+?)\}/ise", "\$this->templateInclude('\\1','$tpl')", $tplcontent);
		}
		
		$tplcontent = preg_replace("/\{display\s+(.+?)\}/is","<?php include \$this->display(\\1,NULL,'',true);?>",$tplcontent);
		
		if(function_exists("preg_replace_callback") && PHP_VERSION>5.3)
		{
			$tplcontent = preg_replace_callback("/^[\s\S]*?\{extends\s+(.+?)\}[\s\S]*?$/is",eval("return function(\$match) use(\$tplcontent){
				return \$this->templateExtends(\$match[1],\$tplcontent);
			};"),$tplcontent);
		}else{
			$tplcontent = preg_replace("/^[\s\S]*?\{extends\s+(.+?)\}[\s\S]*?$/ise","\$this->templateExtends('\\1',\$tplcontent);",$tplcontent);
		}
		
		
		/**
		 * parse if statement
		 * pattern:
		 * - {if a==true}
		 * - ...
		 * - {/if}
		 */
		$tplcontent = preg_replace("/\{if\s(.+?)\}/is","<?php if(\\1){?>",$tplcontent);
		$tplcontent = preg_replace("/\{elseif\s(.+?)\}/is","<?php }elseif(\\1){?>",$tplcontent);
		$tplcontent = preg_replace("/\{else\}/is","<?php }else{?>",$tplcontent);
		$tplcontent = preg_replace("/\{\/if\}/is","<?php }?>",$tplcontent);
		/**
		 * parse loop statement
		 *
		 * pattern:
		 * - {loop $array as $name}
		 * - ...
		 * - {/loop}
		 */
		$tplcontent = preg_replace("/\{loop\s(.+?)\}/is","<?php foreach(\\1){?>",$tplcontent);
		$tplcontent = preg_replace("/\{\/loop\}/is","<?php }?>",$tplcontent);
		
		/**
		 * parse {} pattern vars
		 * - {$varname} template self::$_var property that signed in action
		 * - {~varname} language replace
		 */

		
		//$urlParser=PHPX_URL_Parser::getInstance();
		$tplcontent=$this->url_helper->parse($tplcontent);

		if(function_exists("preg_replace_callback") && PHP_VERSION>5.3){
			$tplcontent = preg_replace_callback("/\\\$([a-zA-Z_][a-zA-Z0-9_]+?)([^a-zA-Z0-9_\"])/is",eval("return function(\$match)
			{
				return \$this->getVar(\$match[1]).\$match[2];
			};"),$tplcontent);
		}else{
			$tplcontent = preg_replace("/\\\$([a-zA-Z_][a-zA-Z0-9_]+?)([^a-zA-Z0-9_\"])/ise","\$this->getVar('\\1').'\\2'",$tplcontent);
		}
		
		$pattern    = "\{\\\$([a-zA-Z0-9_]+?)\}";
		$tplcontent = preg_replace("/$pattern/is","<?php echo @$\\1;?>",$tplcontent);

		$tplcontent = preg_replace("/\\\$([a-zA-Z0-9_\.\[\]\'\"\-\>]+?)\.([a-zA-Z0-9_]+?)([^a-zA-Z0-9_\"])/is","\$\\1['\\2']\\3",$tplcontent);
		
		$tplcontent = preg_replace("/\{\\\$(.+?)\}/is","<?php echo @\$\\1;?>",$tplcontent);
		$tplcontent = preg_replace("/\{~(.+?)\}/is","<?php echo \$this->lang->get('\\1');?>",$tplcontent);
		$tplcontent = preg_replace("/\{self(.+?)\}/is","<?php echo self\\1;?>",$tplcontent);
		
		//$tplcontent = preg_replace("/switch\((.+?)\)\{[^\}]+?case/is","switch(\\1){\r\ncase",$tplcontent);
		
		/** static */
		if(!$ignore_static){
			preg_match_all("/\{set:([a-z0-9_]+?)(\.=|=)(.+?)\}/is",$tplcontent,$matches,PREG_SET_ORDER);
			foreach($matches as $match){
				switch($match[2]){
					case '=':
						$this->static[$match[1]]=$match[3];
						break;
					case '.=':
						if(!isset($this->static[$match[1]]))
							$this->static[$match[1]]='';
						$this->static[$match[1]].=$match[3];
						break;
				}
			}
			$tplcontent = preg_replace("/\{set:(.+?)(\.=|=)(.+?)\}/is","",$tplcontent);
		}
		
		return $tplcontent;
	}
	
	/**
	 * getVar
	 *
	 * get a var from self::$_var list
	 * if var not exists return the key
	 */
	function getVar($key){
		if(@isset($this->_name[$key])){
			return "\$this->_var['$key']";
		}else{
			return "\$$key";
		}
	}
	
	/**
	 * sign
	 */
	function sign($name,$value){
		$this->_var[$name]=$value;
		$this->_name[$name]='__SIGN__';
	}
	
	/**
	 * get_var
	 *
	 * 本函数与getVar不同
	 * getVar函数是用于模板解析的，本函数是用于在控制器或模型中回调模板变量
	 */
	function get_var($key){
		if(isset($this->_var[$key])) return $this->_var[$key];
		return null;
	}
	
	/**
	 * setLayout
	 */
	function setLayout($layout){
		$this->_layout=$layout;
	}
	
	function setSubLayout($layout)
	{
		if($this->_layout=='') $this->_layout=array();
		else if(!is_array($this->_layout))
		{
			$this->_layout=array($this->_layout);
		}
		$this->_layout[]=$layout;
	}
	
	/**
	 * defineStaticVar
	 *
	 * define a static var that would be replaced after parsed
	 *
	 * @param name String
	 * @param value String
	 */
	function defineStaticVar($name,$value){
		$this->_staticList[$name]=$value;
	}
	
	function initStaticVar(){
		$style = $this->config->get('style');
		if(!isset($this->_staticList['__PHPX__']))
			$this->_staticList['__PHPX__']=$this->env->get('PHPX_url');
		if(!isset($this->_staticList['__WEB__']))
			$this->_staticList['__WEB__']=$this->env->get('app_url');

		if(!isset($this->_staticList['__ENTER__']))
			$this->_staticList['__ENTER__']=$this->config->get('static')?$this->env->get('app_url'):($this->env->get('app_url').preg_replace('/\.php.*?$/i','.php',$_SERVER['PHP_SELF']));
		if(!isset($this->_staticList['../public']))
			$this->_staticList['../public']=$this->env->get('app_url').'/'.$this->env->get('app').'/view/'.($style?$style.'/':'').'public';;
		if(!isset($this->_staticList['../Public']))
			$this->_staticList['../Public']=$this->env->get('app_url').'/'.$this->env->get('app').'/view/'.($style?$style.'/':'').'public';;
		if(!isset($this->_staticList['../../../../']))
			$this->_staticList['../../../../']=$this->env->get('app_url').'/';
		if(!isset($this->_staticList['__PHP__']))
			$this->_staticList['__PHP__']=$this->env->get('php');
		if(!isset($this->_staticList['__page__']))
			$this->_staticList['__page__']="$('body')";
	}
	
	/**
	 * replaceStaticVar
	 *
	 * replace static var in template
	 *
	 * @param string String
	 */
	function replaceStaticVar($string){
		foreach($this->_staticList as $name => $value){
			$string=str_ireplace($name,$value,$string);
		}
		return $string;
	}
	
	/**
	 * templateInclude
	 *
	 * @param tpl String
	 */
	function templateInclude($tpl,$referer=''){
		global $config;
		$style = $this->config->get('style');
		if($tpl[0]=='/'){
			$doc_root =preg_replace('/'.str_replace('/','\\/',str_replace('.','\\.',$_SERVER['SCRIPT_NAME'])).'$/i', '/', $_SERVER['DOCUMENT_ROOT']);
			return $this->parse($doc_root.$tpl.".php",true);
		}
		else if(preg_match("/App\/view\/".($style?$style.'\/':'')."public\//is",$tpl))
		{
			return $this->parse($this->env->get('app_dir').'/'.$tpl.".php");
		}
		else
		{
			return $this->parse(dirname($referer)."/".$tpl.".php");
		}
	}
	function templateExtends($tpl,$tplcontent){
		global $config;
		$tplcontent = str_replace('{extends '.$tpl.'}','',$tplcontent);

		$tpl = str_replace('__ROOT__',__ROOT__,$tpl);

		if(preg_match('/[a-zA-Z]:/is',$tpl)){
			$layout = $this->parse($tpl.".php",true);
		}else if($tpl[0]=='/'){
			$layout = $this->parse($_SERVER['DOCUMENT_ROOT'].$tpl.".php",true);
		}
		else if(preg_match("/app\/view\/".$this->config->get('style')."\/public\//is",$tpl))
		{
			$layout = $this->parse($this->env->get('app_dir').'/'.$tpl.".php");
		}
		else
		{
			$layout = $this->parse(dirname($referer)."/".$tpl.".php");
		}
		return str_replace('{layout}',$tplcontent,$layout);
	}

}
?>