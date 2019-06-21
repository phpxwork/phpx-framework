<?php
class DbModel extends PHPX{
	private $groupDbAccess=NULL;
	private $groupType='limit';
	public $connection=NULL;

	/** instance of absolute class */
	static $_instance=null;
	
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

	function connect($h=null,$u=null,$p=null){
		if(!$h) $h=$this->config->get('db_host');
		if(!$u) $u=$this->config->get('db_username');
		if(!$p) $p=$this->config->get('db_password');
		$this->connection=mysql_connect($h,$u,$p);
		if(!$this->connection){
			return false;
		}
		if($this->config->get('db_name')) mysql_select_db($this->config->get('db_name'),$this->connection);
		mysql_query("SET NAMES 'UTF8'",$this->connection);
		mysql_query("SET CHARACTER SET UTF8",$this->connection); 
		mysql_query("SET CHARACTER_SET_RESULTS=UTF8'",$this->connection);
		return true;
	}
	
	function checkAccess($table,$method){
		if(is_null($this->groupDbAccess)){
			$groupPermission=PHPX_Share::getInstance()->get('GroupPermission'.$_SESSION['groupid']);
			$this->groupDbAccess=$groupPermission["dbAccess"];
			$this->groupType=$groupPermission["type"];
		}
		if($_SESSION['grouptype']=='admin') return true;
		if(isset($this->groupDbAccess[strtolower($table."-".$method)]) || isset($this->groupDbAccess[strtolower($table."-*")])) return true;
		return false;
	}
	
	
	function getOne($sql,$switch=false){
		$sql=str_replace(" pre_",' '.$this->config->get('db_pre'),$sql);
		if(is_null($this->connection)) $this->connect();
		$query=@mysql_query($sql,$this->connection);
		$rs = mysql_fetch_assoc($query);
		return $rs;
	}
	
	function getMany($sql,$switch=false){
		$sql=str_replace(" pre_",' '.$this->config->get('db_pre'),$sql);
		if(is_null($this->connection)) $this->connect();
		$query=mysql_query($sql,$this->connection);
		$data=array();
		while($item=mysql_fetch_assoc($query)){
			$data[]=$item;
		}
		return $data;
	}
	
	function query($sql,$switch=false){
		$sql=str_replace("`pre_",'`'.$this->config->get('db_pre'),$sql);
		$sql=str_replace(" pre_",' '.$this->config->get('db_pre'),$sql);
		if(is_null($this->connection)) $this->connect();
		return @mysql_query($sql,$this->connection);
	}
	
	function fetchArray($query){
		return @mysql_fetch_assoc($query);
	}
	
	function insertId(){
		if(is_null($this->connection)) $this->connect();
		return @mysql_insert_id($this->connection);
	}
}