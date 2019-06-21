<?php
class Source_service extends Controller
{
	function check()
	{
		set_time_limit(115);
		$q = $this->query->get('q');

		$res = $this->https->exec(array(
			'url' => 'http://www.phpx.work/source_service/service?q='.$q.'&r='.time()
		));


		if($res['body']=='' || $res['body']=='not_open' || $res['body']=='not_found')
		{
			exit($res['body']);
		}

		if(strpos($q,':')>0)
		{
			$this->sourcemanager->unpack($res['body'],'tag');
		}else{
			$this->sourcemanager->unpack($res['body'],'model');
		}
		echo 'success';
	}

	function service()
	{
		if(!$this->config->get('source_open'))
		{
			exit('not_open');
		}

		$q = $this->query->get('q');

		$cache_path = $this->sourcemanager->pack($q);
		if(!$cache_path)
		{
			exit('not_found');
		}

		echo file_get_contents($cache_path);
	}
}