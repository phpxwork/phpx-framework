<?php if(!defined('PHPX_DIR')) exit('access denied');
class Controller extends PHPX{

	//所有已经加载的模型
	public static $_model=array();
	
	//控制器的句柄
	private static $_instance=null;
	
	//构造器，生成一个控制器实例，并且用单例模式保存该实例
	function __construct(){
		self::$_instance=$this;
		parent::__construct();
		$this->view->initAction($this);
	}

	public static function getInstance(){
		if(!self::$_instance){
			new self();
		}
		return self::$_instance;
	}
	
	
	//向试图中注册一个变量或者一组变量
	public function sign($name,$value){
		if(is_array($name)){
			foreach($name as $key => $value){
				$this->view->sign($key,$value);
			}
		}else{
			$this->view->sign($name,$value);
		}
	}
	
	function display($tpl='',$layout='',$outputhtml=''){
		@header('content-type:text/html;charset='.$this->config->get('charset'));
		$this->view->display($tpl,$layout,$outputhtml);
	}
	
	function setLayout($tpl){
		$this->view->setLayout($tpl);
	}
	
	function message($msg='操作成功',$url=''){
		if(isset($_POST['ajaxpost']) || $this->query->get('ajaxpost')=='true'){
			$html="<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><body><div id=\"msg\">$msg</div><div id=\"url\">{$url}</div></body></html>";
			header('content-type:text/html;charset=utf-8');
			echo $html;
			exit;
		}else{
			if(file_exists($this->env->get('app_dir')."/v/".$this->config->get('style')."/public/message.php")){
				$this->view->setLayout('');
				$this->view->sign('msg',$msg);
				$this->view->sign('url',$url);
				$this->view->setAbsoluteDir($this->env->get('app_dir').'/view/'.$this->config->get('style').'/public');
				$this->display('message');
			}else{
				header('content-type:text/plain;charset='.$this->config->get('charset'));
				echo($msg);
			}
		}
		exit;
	}
	
	/**
	 * error函数
	 *
	 * 显示运行中出现的错误
	 * 如果是普通表单，则用消息提示的方式显示错误
	 * 如果是ajax表单，则返回错误信息同时，返回出错的表单项名称
	 */
	function error($msg,$input=''){
		$this->message($msg,'false:'.$input);
	}

	/**
	 * run方法
	 * 程序运行的总启动方法
	 */
	function run(){		
		/**
		 * run a proper method
		 */
		$method=$this->env->get('method');
		
		$this->$method();
		
	}
}
?>