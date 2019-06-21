<?php
if(!function_exists('mysql_connect'))
{
	$mysqli_conn = false;
	$connect_data = array();
	function mysql_connect($host,$user,$pass)
	{
		global $mysqli_conn,$connect_data;
		$connect_data = array(
			'host' => $host,
			'user' => $user,
			'pass' => $pass,
		);
		$mysqli_conn = mysqli_connect($host,$user,$pass);
		return $mysqli_conn;
	}

	function mysql_select_db($db,$conn)
	{
		global $connect_data;
		$connect_data['db']=$db;
		return mysqli_select_db($conn,$db);
	}
	function mysql_query($sql)
	{
		global $mysqli_conn,$connect_data;
		if(!mysqli_ping($mysqli_conn))
		{
			$mysqli_conn = mysqli_connect($connect_data['host'],$connect_data['user'],$connect_data['pass']);
			mysqli_select_db($mysqli_conn,$connect_data['db']);
			mysqli_query($mysqli_conn,"SET NAMES 'UTF8'");
			mysqli_query($mysqli_conn,"SET CHARACTER SET UTF8"); 
			mysqli_query($mysqli_conn,"SET CHARACTER_SET_RESULTS=UTF8'");
		}
		$query = mysqli_query($mysqli_conn,$sql);
		return $query;
	}
	function mysql_fetch_assoc($query)
	{
		if(!$query) return false;
		return mysqli_fetch_assoc($query);
	}
	function mysql_fetch_array($query)
	{
		if(!$query) return false;
		return mysqli_fetch_array($query);
	}
	function mysql_fetch_row($query)
	{
		return mysqli_fetch_row($query);
	}
	function mysql_insert_id()
	{
		global $mysqli_conn;
		return mysqli_insert_id($mysqli_conn);
	}
	
	function mysql_error()
	{
		return mysqli_error($mysqli_conn);
	}
}