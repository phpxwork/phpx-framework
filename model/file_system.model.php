<?php
class File_systemModel extends PHPX
{
	function get_all_sub_folders($path)
	{
		$subs = array();
		$handle=opendir($path);
		while($file=readdir($handle)){
			if(is_dir($path.'/'.$file) && $file!='.' && $file!='..')
			{
				$subs[]=$file;
			}
		}
		return $subs;
	}
	
	function get_all_files($path)
	{
		$subs = array();
		$handle=opendir($path);
		while($file=readdir($handle)){
			if(is_file($path.'/'.$file) && $file!='.' && $file!='..')
			{
				$subs[]=$file;
			}
		}
		return $subs;
	}
	
	function get_all_sub_folders_and_files($path)
	{
		$subs = array();
		$handle=opendir($path);
		while($file=readdir($handle)){
			if($file!='.' && $file!='..')
			{
				$subs[]=$file;
			}
		}
		return $subs;
	}

	function clear_path($path)
	{
		$subs = array();
		$handle=opendir($path);
		while($file=readdir($handle)){
			if(is_file($path.'/'.$file) && $file!='.' && $file!='..')
			{
				unlink($path.'/'.$file);
			}
		}
	}
}