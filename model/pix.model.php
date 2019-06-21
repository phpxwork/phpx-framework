<?php
class PixModel extends Model
{
	function upload($tmp_file,$filename){
		if(!is_dir(__ROOT__.'/uploads/')) mkdir(__ROOT__.'/uploads/');
		if(!is_dir(__ROOT__.'/uploads/'.date('Ymd'))) mkdir(__ROOT__.'/uploads/'.date('Ymd'));
		move_uploaded_file($tmp_file, __ROOT__.'/uploads/'.date('Ymd').'/'.$filename);
		return 'uploads/'.date('Ymd').'/'.$filename;
	}
}