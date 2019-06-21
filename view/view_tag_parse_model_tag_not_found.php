<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="utf-8">
		<title>PHPX PAGE INITIALIZE</title>
	    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	    <script src="<?php echo PHPX::load("env")->get('phpx_url');?>/view/jquery.js"></script>
	    <?php
	    $url = $_SERVER["REQUEST_URI"];
	    if(strpos($url,'?')>0)
	    {
	    	$url.='&';
	    }else{
	    	$url.='?';
	    }
	    $url.='PHPX_TAGLIB_CHECK=1';
	    ?>
	    <script>
	    $(function(){
	    	function check_online(q){
	    		$.ajax({
	    			url : '<?php echo @PHPX::load("env")->get("web_url");?>?class=source_service&method=check&q='+q,
	    			method : 'GET',
	    			timeout : 120000,
	    			success : function(res)
	    			{
	    				if(res=='success'){
	    					//更新成功
	    					$('body >div:last').append($('<span style="color:#099;">success</span>'));
	    					check_miss();
	    				}else{
	    					$('body >div:last').append($('<span style="color:#f00;">error</span>'));
	    					return;
	    				}
	    			},
	    			fail : function()
	    			{
	    			}
	    		})
	    	}

	    	function check_miss()
	    	{
	    		$.ajax({
	    			url : '<?php echo $url;?>',
	    			method : 'GET',
	    			timeout : 3000,
	    			success : function(res)
	    			{
	    				if(!res.match(/(\{"error":"codemiss".+?\})$/i)){
	    					//更新成功
	    					location.reload();
	    				}else{
	    					res = res.replace(/^[\s\S]+?\{"error":"codemiss"/i,'{"error":"codemiss"');
	    					var json = eval('('+res+')');
	    					if(typeof json['cant_match_model_at']!='undefined')
	    					{
	    						$('<div style="color:red;">'+json['cant_match_model_at']+'无法正确匹配出模型类，请您检查是否使用了换行</div>').appendTo($('body'));
	    						return;
	    					}

	    					if(typeof json['model']!='undefined')
	    					{
		    					var div = $('<div style="color:#777;">Model <span style="font-weight:bold;color:#f60;">'+json['model']+'</span> not found at local. System is pulling from PHPX service..</div>');
		    					div.appendTo($('body')).show();
		    					check_online(json['model']);
		    					return;
	    					}
	    					var div = $('body >div:eq(0)').clone();
	    					div.appendTo($('body')).show();
	    					div.find('.taglib').html(json['taglib']+':'+json['tag']);
	    					check_online(json['taglib']+':'+json['tag']);
	    				}
	    			}
	    		})
	    	}

	    	check_miss();
	    });
	    </script>
	</head>
	<body style="font-size:14px;font-family:'Arial';">
		<div style="color:#777;display:none;">
			Tag <span style="font-weight:bold;color:#f00;">&lt;</span><span style="font-weight:bold;color:#399;" class="taglib"></span> <span style="font-weight:bold;color:#f00;">/&gt;</span> not found at local. System is pulling from PHPX service..
		</div>
	</body>
</html>