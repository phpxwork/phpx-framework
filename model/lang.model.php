<?php
class LangModel extends PHPX
{
	protected $cache=false;
	function get($id)
	{
		if($this->cache===false)
		{
			$cache = $this->data_helper->read($this->routine_helper->get_cache_path('lang').'lang.php');
			if(!$cache)
			{
				$cache = array();
			}

			$this->cache = $cache;
		}

		if(!isset($this->cache[$id]))
		{
			$this->cache[$id]=$id;
			$this->data_helper->write($this->routine_helper->get_cache_path('lang').'lang.php',$this->cache);
			return $id;
		}

		return $this->cache[$id];
	}
}