<?php
class SourcemanagerModel extends Model
{
	function pack($q)
	{
		if(strpos($q,':')>0)
		{
			//taglib
			list($taglib,$tag)=explode(':',$q);

			$cache_path = $this->routine_helper->get_cache_path('source_pack').'tag.'.$taglib.'.'.$tag.'.php';
			if(file_exists($cache_path) && false)
			{
				return $cache_path;
			}
			$tag_dir = $this->routine->get_tag_dir($taglib,$tag);
			if(!$tag_dir)
				return false;

			$packed_data = $this->serialize($tag_dir['path'],$taglib.'/'.$tag);

			file_put_contents($cache_path,$packed_data);
			return $cache_path;
		}else{
			$cache_path = $this->routine_helper->get_cache_path('source_pack').'model.'.$q.'.php';
			if(file_exists($cache_path))
			{
				return $cache_path;
			}
			$model_path = $this->routine->get_model_file($q);
			if(!$model_path)
				return false;
			$packed_data = '{@path@}'.$q.'.model.php'.'{@data@}'.file_get_contents($model_path);

			$model_path = dirname($model_path);
			if(is_dir($model_path.'/'.$q))
				$packed_data .= $this->serialize($model_path,$q);

			file_put_contents($cache_path,$packed_data);
			return $cache_path;
		}
	}

	function serialize($base_dir,$path='')
	{
		$packed_data = '';
		$sub_folders = $this->file_system->get_all_sub_folders($base_dir.'/'.$path);
		foreach($sub_folders as $sf)
		{
			$packed_data .= $this->serialize($base_dir,$path.'/'.$sf);
		}
		$files = $this->file_system->get_all_files($base_dir.'/'.$path);
		foreach($files as $file)
		{
			$packed_data .= '{@path@}'.$path.'/'.$file.'{@data@}'.file_get_contents($base_dir.'/'.$path.'/'.$file);
		}

		return $packed_data;
	}

	function unpack($packed_data,$type)
	{
		if($type=='tag')
		{
			$base_dir = __ROOT__.'/taglib';
		}else if($tag=='model')
		{
			$base_dir = __ROOT__.'/model';
		}else{
			$base_dir = $type;
		}
		if(!is_dir($base_dir)) mkdir($base_dir);

		$array = explode('{@path@}',$packed_data);
		for($i=1;$i<sizeof($array);$i++)
		{
			list($path,$data)=explode('{@data@}',$array[$i]);

			if(strpos($path,'/')!==false)
			{
				$check = $base_dir;
				$items = explode('/',$path);
				for($j=0;$j<sizeof($items)-1;$j++)
				{
					if($items[$j])
					{
						$check.='/'.$items[$j];
						if(!is_dir($check)) mkdir($check);
					}
				}
			}

			file_put_contents($base_dir.'/'.$path,$data);
		}
	}
}