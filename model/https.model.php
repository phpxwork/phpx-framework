<?php
class HttpsModel extends PHPX
{
	public $proxy_host;
	public $proxy_port;

	private $method = 'POST';

	private $sockets = array();


	function set_proxy($proxy)
	{
		if(!$proxy) return;
		list($host,$port)=explode(':',$proxy);


		$this->proxy_host = $host;
		$this->proxy_port = (int)$port;
	}

	function check_proxy($loop = 0)
	{
		if(!$this->proxy_host || !$this->proxy_port)
		{
			//echo '-------------------------------------------------------';
			$proxy = $this->proxy->get_proxy();
			if(!$proxy)
			{
				exit('Cannt find proxy');
			}
			$this->set_proxy($proxy);
			return true;
		}
		return true;

		$check_ip = $this->ip->get_by('ip',$this->proxy_host.':'.$this->proxy_port);
		if(!$check_ip || $check_ip['createdate']<time()-50)
		{
			$proxy = $this->proxy->get_proxy();
			if(!$proxy)
			{
				exit('Cannt find proxy');
			}
			$this->set_proxy($proxy);
		}
		return true;
	}


	private $socket = null;

	function reset()
	{
		foreach($this->sockets as $socket)
		{
			fclose($socket);
		}

		$this->sockets = array();
		$this->proxy_host = '';
		$this->proxy_port = '';
	}

	function exec($args,$loop=0,$check_proxy=false)
	{
		if($check_proxy && !$this->check_proxy())
		{
			return false;
		}


		$url = $args['url'];

		$scheme = strpos($url,'https')===0?'https':'http';
		if(!preg_match('/^https?:\/\/(.+?)(\/.*?)$/i',$url,$match))
		{
			return false;
		}

		$host = $match[1];
		$path = $match[2];
		if($host=='translate.google.cn')
		{
			$ip = '203.208.41.87';
		}else{
			$ip = gethostbynamel($host);
			$ip = $ip[0];
		}


		$port = $scheme=='https'?443:80;

		/////////////////////////////////////////////////header
		$args['host']=$host;
		if(isset($args['post']))
		{
			$post = $args['post'];
		}else{
			$post = '';
		}
		if(is_array($post))
		{
			$post = json_encode($post,JSON_UNESCAPED_UNICODE);

			$args['post']=$post;
		}
		$method=isset($args['method'])?$args['method']:($post?'POST':'GET');
		$headers = $this->build_header($args);
		$request = $method.' '.$path.' HTTP/1.1'."\r\n";
		/*
		$request.= ':authority: www.nike.com'."\r\n";
		$request.= ':method: POST'."\r\n";
		$request.= ':path: '.$path."\r\n";
		$request.= ':scheme: www.nike.com'."\r\n";
		*/
		foreach($headers as $header)
		{
			$request .= $header."\r\n";
		}
		if(isset($args['post']) && $args['post'])
		{
			$request .= "\r\n".$args['post']."\r\n\r\n";
		}else{
			$request.="\r\n";
		}


		if($scheme=='https'){
			$context = array(
				/*'http' => array(
			        'method' => $method,
			        'header' => $headers,
			        'follow_location' => true,
			        'protocol_version' => 1.1,
			        'timeout' => 60,

			        'proxy' => 'tcp://192.168.3.12:80',//'tcp://'.$this->proxy_host.':'.$this->proxy_port,
				),*/
				'ssl' => array(
					'allow_self_signed' => true,
					'verify_peer' => false,
					'verify_peer_name' => false,
      			        //'allow_self_signed' => false,
			        //'cafile' => __ROOT__.'/cacert.pem',
			        //'ciphers' => 'DHE-RSA-AES256-SHA:DHE-DSS-AES256-SHA:AES256-SHA:KRB5-DES-CBC3-MD5:KRB5-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:EDH-DSS-DES-CBC3-SHA:DES-CBC3-SHA:DES-CBC3-MD5:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA:AES128-SHA:RC2-CBC-MD5:KRB5-RC4-MD5:KRB5-RC4-SHA:RC4-SHA:RC4-MD5:RC4-MD5:KRB5-DES-CBC-MD5:KRB5-DES-CBC-SHA:EDH-RSA-DES-CBC-SHA:EDH-DSS-DES-CBC-SHA:DES-CBC-SHA:DES-CBC-MD5:EXP-KRB5-RC2-CBC-MD5:EXP-KRB5-DES-CBC-MD5:EXP-KRB5-RC2-CBC-SHA:EXP-KRB5-DES-CBC-SHA:EXP-EDH-RSA-DES-CBC-SHA:EXP-EDH-DSS-DES-CBC-SHA:EXP-DES-CBC-SHA:EXP-RC2-CBC-MD5:EXP-RC2-CBC-MD5:EXP-KRB5-RC4-MD5:EXP-KRB5-RC4-SHA:EXP-RC4-MD5:EXP-RC4-MD5',
			        //'protocol_version' => 1.1,
				),
			);

			//$post && $context['http']['content']=$post;
			//$check_proxy && $this->proxy_host && $this->proxy_port && $context['http']['proxy']=$this->proxy_host.':'.$this->proxy_port;

		}else{
			$context = array(
				'http' => array(
			        'method' => $method,
			        'header' => $headers,
			        'follow_location' => true,
			        'timeout' => 60,
				),
			);
			//$post && $context['http']['content']=$post;
			//$check_proxy && $this->proxy_host && $this->proxy_port && $context['http']['proxy']='tcp://'.$this->proxy_host.':'.$this->proxy_port;
		}

		$context = stream_context_create($context);

		if($check_proxy && $this->proxy_host && $this->proxy_port)
		{
			if(!isset($this->sockets[$host.":".$port])){
				//echo 'create new socket'."\n";
				$connect = "tcp://".$this->proxy_host.":".$this->proxy_port;
				$socket = stream_socket_client($connect,$errno,$errstr,20,STREAM_CLIENT_CONNECT,$context);
				//stream_socket_enable_crypto($this->socket, true , STREAM_CRYPTO_METHOD_SSLv3_CLIENT);
				
				if(!$socket || $errno)
				{
					$this->reset();
					if($loop==2) return false;
					return $this->exec($args,$loop+1,$check_proxy);
				}

				stream_set_blocking($socket, true);
				stream_set_timeout($socket,60);
				$proxy_header = "CONNECT ".$ip.":".$port." HTTP/1.1\r\n";
				//$proxy_header.= "Connection: keep-alive\r\n";
				//$proxy_header.= "Keep-Alive: 300\r\n";
				$proxy_header.= "Host: $host\r\n";
				$proxy_header.= "\r\n";
				$ret = fwrite($socket, $proxy_header);
				//$ret = fwrite($this->socket,$request);
				$proxy_response = '';
				$n = 0;
				while(!feof($socket) && ++$n<20)
				{
					$line = fgets($socket,1024);
					$proxy_response .= $line;
					if($line=="\r\n") break;
				}


				if(strpos(strtolower($proxy_response),strtolower('200 Connection Established'))===false)
				{
					$this->reset();
					if($loop==2) return false;
					return $this->exec($args,$loop+1,$check_proxy);
				}
				$ret = stream_socket_enable_crypto($socket, true , STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
				if(!$ret)
				{
					//echo 'PROXY HAND SHAKE ERROR'."\n";
					$this->reset();
					if($loop==2) return false;
					return $this->exec($args,$loop,$check_proxy);
				}


				$this->sockets[$host.":".$port]=$socket;
			}else{
				$socket = $this->sockets[$host.":".$port];
			}
		}else{
			$connect = ($scheme=='https'?'ssl':'tcp')."://$host:$port";
			$socket = stream_socket_client($connect,$errno,$errstr,60,STREAM_CLIENT_CONNECT,$context);
			
			
		}





		/*
		if(($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0)
		{
			return false;
		}
		*/

		//socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO,array("sec"=>10000, "usec"=>60000000));

		/*if(($result = socket_connect($this->socket, $ip, 443)) < 0)
		{
			socket_close($this->socket);
			return false;
		}*/

		//fwrite($this->socket,"\n");



		//fwrite($this->socket,"GET /_bm/data HTTP/1.1\r\n");
		//fwrite($this->socket,"Accept: */*\r\n");
		//fwrite($this->socket,"\r\n");

		//$request = "GET / HTTP/1.1\r\nHost: localhost\r\n\r\n";


		$ret = fwrite($socket,$request);
		//echo 'write:'.$ret.'-'.strlen($request)."\n";

		$response_header = "";
		//$loop = 0;

		$content_length = -1;
		$transfer_encoding = '';
		$content_encoding = '';
		$t = time();

		$read_start = false;

		$http_version_readed = false;
		while(!feof($socket))
		{
			//if(hexdec($line)==0) continue;

			$line = fgets($socket,1024);
			if(($line=="\r\n" || $line=='') && $response_header==''){
				$line = fgets($socket,1024);
			}

			if(preg_match('/^http\/1.1/i',$line)){
				$http_version_readed = true;
			}
			if(preg_match('/^content\-length: ?([0-9]+?)\r\n/i',$line,$match))
			{
				$content_length = (int)$match[1];
			}
			if(preg_match('/^Transfer\-Encoding: *?([a-z]+?)\r\n/i',$line,$match))
			{
				$transfer_encoding = $match[1];
			}
			if(preg_match('/^Content\-Encoding: *?([a-z]+?)\r\n/i',$line,$match))
			{
				$content_encoding = $match[1];
			}
			$response_header.=$line;

			if($line=="\r\n" || $line=='')
			{
				//头读完了
				break;
			}
		}
		//echo "response:\n";
		//echo $response_header."\n";

		if(!$response_header)
		{
			$this->reset();
			if($loop==2) return false;
			return $this->exec($args,$loop+1,$check_proxy);
		}

		if($content_length==-1 && $content_encoding=='gzip' && $transfer_encoding=='')
		{
			$content = fread($socket,4096);
		}else if($content_length>0 && !feof($socket))
		{
			$content = '';
			while(strlen($content)<$content_length && !feof($socket)){
				$read = fread($socket,$content_length-strlen($content));

				$content.=$read;
			}
		}else if(strtolower($transfer_encoding)=='chunked')
		{
			//echo 'chunked'."\n";
			


  			$content = '';
			while(!feof($socket))
			{
				$line = fgets($socket);
				/*echo 'line:';
				for($i=0;$i<strlen($line);$i++)
				{
					echo ord($i).' ';
				}
				echo "\n";*/
				//$line = substr($line,0,strlen($line)-strlen($line%2)-2);
				//$chunk_size = (integer)hexdec($line);
				if($line!="\r\n" && preg_match('/^.+?\r\n/i',$line,$match))
				{
					//$chunk_length = hexdec($match[1]);
					//echo 'match:'.$chunk_length."\n";
					$line = substr($line,0,strlen($line)-2);
					$arr=explode(';',$line,2);

					$chunk_size = hexdec($arr[0]);

					//echo 'chunk size:'.$chunk_size."\n";

					if($chunk_size==0) break;
					$size = 0;
					$chunk_data = '';
					while($size<$chunk_size)
					{
						$got_data = fread($socket,$chunk_size-$size);
						$size += strlen($got_data);
						//echo 'size:'.$size."\n";

						$chunk_data.=$got_data;
					}

					//echo 'got:'.strlen($chunk_data)."\n";
					$content .= $chunk_data;
					
				}
			}

			//echo '----------------------'."\n";
			
			//echo $content;

			//echo "\n---------------------------"."\n";
		}
		//echo ($content_encoding=='gzip'?gzdecode($content):$content)."\n";
		/********************************
		* 除非连接失效
		* 否则不主动关闭该连接*/
		//fclose($this->socket);
		//$this->socket=null;
		
		$headers = array();
		$response_headers = explode("\r\n",$response_header);
		foreach($response_headers as $str)
		{
			if(preg_match("/^(.+?):(.+?)$/i",$str,$match2))
			{
				$headers[]=array(
					'key' => strtolower(trim($match2[1])),
					'val' => trim($match2[2]),
				);
			}
		}

		$status = $response_headers[0];

		if(strtolower($content_encoding)=='gzip')
		{
			if(isset($content) && $content) $content = gzdecode($content);
			else $content='';
		}
		$ret = array('header' => $headers,'body' => isset($content)?$content:'' ,'status' => $status);
		return $ret;
	}

	function get($url)
	{
		$data = $this->exec(array(
			'url' => $url,
			'method' => 'GET',
		));

		return $data['body'];
	}

	function build_header($args)
	{
		$headers = array(
			//'Connection' => 'Keep-Alive',
			//'Accept-Language:zh-CN,zh;q=0.9',
			//'cookie:'.$cookie,
			//'user-agent' => ,
			//'Host' => '',
		);

		if(!isset($args['accept-encoding']))
		{
			$headers['Accept-Encoding']='gzip, deflate, br';
		}

		if(1)
		{
			$headers['Accept-Language']=$this->helper->lang;
		}


		$headers['Cache-Control']='no-cache';
		$headers['Connection']='keep-alive';



		if(isset($args['post']) && $args['post'])
		{
			$headers['Content-Length'] = strlen($args['post']);
		}else{
			$headers['Content-Length'] = 0;
		}

		if(isset($args['content_type']) && $args['content_type']=='unset')
		{

		}else{
			$headers['Content-Type'] = isset($args['content_type'])?$args['content_type']:'text/plain';	
		}

		$cookie = isset($args['cookie'])?$args['cookie']:'';
		//print_r($cookie);
		if(is_array($cookie))
		{
			$str='';
			foreach($cookie as $k => $v)
			{
				if($str!='') $str.='; ';
				$str.=$k.'='.$v;
			}
			$cookie = $str;
			echo $cookie."\n";
		}
		if($cookie)
		{
			//curl_setopt($ch, CURLOPT_COOKIE , $cookie);
			$headers['Cookie']=$cookie;
		}


		if(0)
		{
			$headers['Cache-Control'] = 'no-cache';
		}


		if(!isset($args['unset_except']))
		{
			//$headers['Expect']='';
		}

		foreach($args as $k => $v)
		{
			if(in_array($k,array('access-control-request-method','access-control-request-headers','x-requested-with','authorization','accept-encoding','x-newrelic-id','upgrade-insecure-requests',':authority',':method',':path',':scheme')))
			{
				//$k = preg_replace_callback('/(^|\-)([a-z])/is',function($match){
				//	return $match[1].strtoupper($match[2]);
				//},$k);
				$headers[$k] = $v;
			}
		}

		$headers['Host']=$args['host'];
		if(!isset($args['no_origin']))
		{
			//$headers['Origin'] = isset($args['origin'])?$args['origin']:'https://www.nike.com';
		}
		$headers['Pragma']='no-cache';
		
		if(isset($args['referer']) && $args['referer']=='unset')
		{

		}else{
			$headers['Referer'] = isset($args['referer'])?$args['referer']:$this->helper->referer;
		}


		//$headers['User-Agent']='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';

		//$headers['User-Agent']=$this->helper->user_agent;

		$case = 0;//rand(0,1);
		$space1 = rand(0,1);
		$space2 = 1;//rand(0,1);
		$final_headers = array(
			//':authority: '.$args['host'],
			//':method: '.'POST',
			//':path: '.preg_replace('/https:\/\/[^\/]+?\//i','/',$args['url']),
			//':scheme: '.'https',
			($case?'Accept: ':strtolower('Accept: ')).(isset($args['accept'])?$args['accept']:'*/*'),
		);
		$contained = array();
		$all_set = false;

		while(!$all_set)
		{
			$count = 0;
			$round_set = false;

			$all_set = true;
			foreach($headers as $k => $v)
			{
				if(!isset($contained[$k])){
					if(rand(0,3)==1 && false)
					{
						$final_headers[] = ($case?$k:strtolower($k)).':'.($space2?' ':'').$v;
						$contained[$k]=1;
						$round_set = true;
					}

					$all_set = false;
				}
			}

			if(!$round_set && !$all_set)
			{
				foreach($headers as $k => $v)
				{
					if(!isset($contained[$k])){
						$final_headers[] = ($case?$k:strtolower($k)).':'.($space2?' ':'').$v;
						$contained[$k]=1;
						break;
					}
				}
			}
		}

		$final_headers[]=($case?'User-Agent: ':strtolower('User-Agent: ')).'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';

		unset($headers);
		unset($contained);

		//echo 'final_headers:'."\n";
		//print_r($final_headers);

		/*$headers = array();
		foreach($final_headers as $hk => $hv)
		{
			$hk = preg_replace_callback('/(^|\-)([a-z])/is',function($matches){
				return $matches[1].strtouppper($matches[2]);
			},$hk);

			$headers[$hk]=$hv;
		}

		return $headers;*/

		return $final_headers;
	}

	function StrToBin($str){
	    //1.列出每个字符
	    $arr = array();
	    //2.unpack字符
	    for($i=0;$i<strlen($str);$i++){
	    	$v = $str[$i];
	        $temp = unpack('H*', $v);
	        $v = base_convert($temp[1], 16, 2);
	        unset($temp);

	        $arr[]=$v;
	    }

	    return join(' ',$arr);
	}

}