<?php
/**
 * PHPX model basic class
 * we recommend all applications' model extends
 * this class
 * so that we can extern the model in here
 */
class Model extends PHPX{
	/**
	 * base action
	 */
	protected $action=NULL;
	
	protected $data_table = '',$id_column='';
	
	protected $latest_id = 0;
	
	protected $none_pre = false;
	
	/** db_pre */
	protected $db_pre = true;

	protected $filecache = false;

	function __construct($action=NULL,$data_table=''){
		parent::__construct();
		if(($action instanceof PHPX_Action)){
			//exit('action argument error');
			$this->action=$action;
		}
		$this->data_table=$data_table;
	}
	
	function get_table_and_column(){
		if($this->data_table && $this->id_column){
			//return array($this->data_table,$this->id_column);
		}

		if($this->filecache===false)
		{
			$cache_path = $this->routine_helper->get_cache_path('database').'structs.php';
			$file_cache = $this->data_helper->read($cache_path);
			if($file_cache)
			{
				$this->filecache = $file_cache;
			}else{
				$this->filecache = array();
			}
		}


		if($this->data_table){
			$table=$this->data_table;
		}else{
			$model_class=get_class($this);

			$table=strtolower(preg_replace("/model$/i","",$model_class));
			$this->data_table=$table;
		}
		if($this->db_pre && strpos($table,'plugin_')!==0 && $this->config->get('db_pre') && strpos($table,$this->config->get('db_pre'))!==0){
			$table=$this->config->get('db_pre').$table;
		}else{
			$this->db_pre = true;
		}


		if(isset($this->filecache[$table]))
		{
			return array($table,$this->filecache[$table]);
		}
		$query = $this->db->query("SHOW COLUMNS FROM `$table`");
		$rs = mysql_fetch_assoc($query);
		if($rs['Field']=='id')
		{
			$this->id_column = 'id';
		}else{
			if(preg_match("/(^|_)([a-z0-9]+?)$/i",$table,$matches)){
				$id_column=$matches[2].'_id';
				$this->id_column=$id_column;
			}else{
				$this->id_column='';
				return array($table,'');
			}

		}

		$cache_path = $this->routine_helper->get_cache_path('database').'structs.php';
		$this->filecache[$table]=$this->id_column;
		$this->data_helper->write($cache_path,$this->filecache);

		return array($table,$this->id_column);
	}
	
	function none_pre(){
		$this->db_pre=false;
		//$this->data_table=preg_replace("/^".$this->config->get('db_pre')."/",'',$this->data_table);
		//$this->data_table=preg_replace("/^".$this->config->get('db_pre').$this->config->get('db_pre')."/",$this->config->get('db_pre'),$this->data_table);

		return $this;
	}
	
	function get($id,$row=''){
		list($table,$id_column)=$this->get_table_and_column();
		$this->latest_id=$id;
		$query=$this->db->query("SELECT * FROM `$table` WHERE `$id_column`='$id'");
		$rs=mysql_fetch_assoc($query);
		if($rs && $row){
			return $rs[$row];
		}
		return $rs;
	}
	
	function get_first($state=''){
		list($table,$id_column)=$this->get_table_and_column();
		$sql="SELECT * FROM `$table`";
		if($state) $sql.=" ".$state;
		else $sql.=" ORDER BY `$id_column` ASC";
		$sql.=" LIMIT 1";
		$rs=$this->db->getOne($sql);
		if($rs && isset($rs[$id_column])){
			$this->latest_id=$rs[$id_column];
		}
		return $rs;
	}


	function get_by($row,$value='',$order=''){
		list($table,$id_column)=$this->get_table_and_column();
		
		$sql="SELECT * FROM `$table` WHERE 1";
		if(is_array($row)){
			foreach($row as $k => $v){
				if(is_array($v))
				{
					$vs = "";
					foreach($v as $vv)
					{
						if($vs!='') $vs.=",";
						if(is_int($vv))
						{
							$vs.=$vv;
						}else{
							$vs.="'$vv'";
						}
					}
					$sql .= " AND `$k` IN ($vs)";
				}
				else
				{
					$sql .= " AND `$k`='$v'";
				}
			}
			$sql.=" $value LIMIT 1";
		}else{
			$sql.=" AND `$row`='$value' $order LIMIT 1";
		}

		$rs=$this->db->getOne($sql);


		if($rs && isset($rs[$id_column])){
			$this->latest_id=$rs[$id_column];
		}
		return $rs;
	}
	
	function get_all($statement="")
	{
		list($table,$id_column)=$this->get_table_and_column();
		
		$sql="SELECT * FROM `$table` WHERE 1";
		if($statement) $sql.=" AND $statement";
		
		return $this->db->getMany($sql);
	}
	function get_all_sub_data($fid,$including_fid=true)
	{
		$array = array();
		if($including_fid) $array[]=$fid;
		list($table,$id_column)=$this->get_table_and_column();
		$sql = "SELECT * FROM `$table` WHERE fid=$fid";
		//echo $sql.'<br/>';
		$query = $this->db->query($sql);
		while($rs=mysql_fetch_assoc($query))
		{
			$array = array_merge($array,$this->get_all_sub_data($rs[$id_column],true));
		}
		return $array;
	}

	function set($row_name,$row_value,$id=NULL){
		list($table,$id_column)=$this->get_table_and_column();
		if(!is_null($id)){
			$this->latest_id=$id;
		}
		$id=$this->latest_id;
		$this->db->query("UPDATE `$table` SET `$row_name`='$row_value' WHERE `$id_column`='$id'");
	}

	function count($statement=''){
		list($table,$id_column)=$this->get_table_and_column();
		$sql="SELECT COUNT(*) n FROM `$table`";
		if($statement) $sql.=" WHERE ".$statement;
		
		$rs = $this->db->getOne($sql);
		if(!$rs) return 0;
		return $rs['n'];
	}

	function sum($column,$statement=''){
		list($table,$id_column)=$this->get_table_and_column();
		$sql="SELECT SUM($column) n FROM `$table` WHERE 1=1";
		if($statement) $sql.=" AND ".$statement;
		
		$rs=$this->db->getOne($sql);
		if(!$rs || !$rs['n']) return 0;
		return $rs['n'];
	}

	function _insert($data=array(),$auto=1,$loop=false){
		list($table,$id_column)=$this->get_table_and_column();
		
		/*$uniqid = uniqid();
		if(!is_dir(__ROOT__.'/data')) mkdir(__ROOT__.'/data');
		if(!is_dir(__ROOT__.'/data/lock')) mkdir(__ROOT__.'/data/lock');
		if(!file_exists(__ROOT__.'/data/lock/'.$table.'.php'))
		{
			$this->data_helper->write(__ROOT__.'/data/lock/'.$table.'.php',array());
		}
		$q  = $this->data_helper->read(__ROOT__.'/data/lock/'.$table.'.php');
		if(!in_array($uniqid,$q))
		{
			array_push($q,array($uniqid,microtime(true));
		}
		while($q[0][0]!=$uniqid)
		{
			$q  = $this->data_helper->read(__ROOT__.'/data/lock/'.$table.'.php');
		}*/
		if(!$auto && !$loop)
		{
			//$this->db->query('set autocommit=0;');
			$this->db->query("SET AUTOCOMMIT=0");
			if(!$this->db->query('START TRANSACTION'))
			{
				print_r(mysql_error());
			}
		}
		$insert="";
		$values="";
		foreach($data as $key => $value){
			if($insert!=""){
				$insert.=",";
				$values.=",";
			}
			
			$insert.="`$key`";
			$values.="'$value'";
		}
		
		$sql="INSERT INTO `$table`($insert) VALUES($values)";
		$query = $this->db->query($sql);
		if($table=='dy_film') file_put_contents('d:/www/film/insert.txt',$sql);

		if(!$auto)
		{
			if($query){
				$this->db->query('COMMIT');
			}else{
				$this->db->query("ROLLBACK");
				return $this->_insert($data,$auto,true);
			}
			//$this->db->query('END');
			$this->db->query("SET AUTOCOMMIT=1");
		}
		
		if($auto==1){
			$this->latest_id=$this->db->insertId();
		}else{
			$one = $this->db->getOne("SELECT LAST_INSERT_ID() AS id");
			$this->latest_id = $one['id'];
		}

	
		//array_unshift($q);
		//$this->data_helper->write(__ROOT__.'/data/lock/'.$table.'.php',$q);
		//unlink(__ROOT__.'/data/lock/'.$table.'.lock');
		return $this->latest_id;
	}
	
	
	function _update($data=array(),$id=0){
		list($table,$id_column)=$this->get_table_and_column();
		$update='';
		foreach($data as $key => $value){
			if($update!=""){
				$update.=",";
			}
			if('#ADD'===$value){
				$update.="`$key`=`$key`+1";
			}else if('#MINUS'===$value){
				$update.="`$key`=`$key`-1";
			}else{
				$update.="`$key`='$value'";
			}
		}
		
		if(!is_array($id)){
			if(!$id) $id=$this->latest_id;
			$sql="UPDATE `$table` SET $update WHERE `$id_column`='$id'";
		}else{
			$sql="UPDATE `$table` SET $update WHERE 1";
			foreach($id as $key => $value){
				$sql.=" AND `$key`='$value'";
			}
		}
		$this->db->query($sql);
		
		return $id;
	}
	
	function _delete($row,$value=''){
		list($table,$id_column)=$this->get_table_and_column();
		
		$sql="DELETE FROM `$table` WHERE 1";
		if(is_array($row)){
			foreach($row as $k => $v){
				$sql.=" AND `$k`='$v'";
			}
		}else{
			$sql.=" AND `$row`='$value'";
		}
		$r=$this->db->query($sql);
		return $r;
	}
}
?>