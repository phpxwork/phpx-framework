<?php
class CurlModel{
	function post($url, $data_string) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-AjaxPro-Method:ShowList',
			'Content-Type: application/json; charset=utf-8',
			'Content-Length: ' . strlen($data_string))
		);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

}