<?php

/* Unofficial Scribd PHP Class library */

class Scribd {

	public $api_key;
	public $secret;
	private $url;
	public $session_key;
  public $my_user_id;
	private $error;

	public function __construct($api_key, $secret) {
		$this->api_key = $api_key;
		$this->secret = $secret;
		$this->url = "http://api.scribd.com/api?api_key=" . $api_key;
	 }


  /**
   * Get a list of the current users files
   * @return array containing doc_id, title, description, access_key, and conversion_status for all documents
   */
	public function getList(){
		$method = "docs.getList";

		$result = $this->postRequest($method, $params);
		return $result['resultset'];
	}

	/* Post Request */
	
	private function postRequest($method, $params){
		$params['method'] = $method;
		$params['session_key'] = $this->session_key;
    $params['my_user_id'] = $this->my_user_id;


		$post_params = array();
		foreach ($params as $key => &$val) {
			if(!empty($val)){
				
				if (is_array($val)) $val = implode(',', $val);
				if($key != 'file' && substr($val, 0, 1) == "@"){
					$val = chr(32).$val;
				}
					
				$post_params[$key] = $val;
			}
		}    
		$secret = $this->secret;
		$post_params['api_sig'] = $this->generate_sig($params, $secret);
		$request_url = $this->url;
       
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $request_url );       
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1 );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params );
		$xml = curl_exec( $ch );
		$result = simplexml_load_string($xml); 
		curl_close($ch);

			if($result['stat'] == 'fail'){
		
				//This is ineffecient.
				$error_array = (array)$result;
				$error_array = (array)$error_array;
				$error_array = (array)$error_array['error'];
				$error_array = $error_array['@attributes'];
				$this->error = $error_array['code'];

				throw new Exception($error_array['message'], $error_array['code']);

				return 0;
			
			}
			if($result['stat'] == "ok"){
				
				//This is shifty. Works currently though.
				$result = $this->convert_simplexml_to_array($result);
				if(urlencode((string)$result) == "%0A%0A" && $this->error == 0){
					$result = "1";
					return $result;
				}else{
					return $result;
				}
			}
	}

	public static function generate_sig($params_array, $secret) {
		$str = '';

		ksort($params_array);
		// Note: make sure that the signature parameter is not already included in
		//       $params_array.
		foreach ($params_array as $k=>$v) {
		  $str .= $k . $v;
		}
		$str = $secret . $str;

		return md5($str);
	}

	public static function convert_simplexml_to_array($sxml) {
		$arr = array();
		if ($sxml) {
		  foreach ($sxml as $k => $v) {
				if($arr[$k]){
					$arr[$k." ".(count($arr) + 1)] = self::convert_simplexml_to_array($v);
				}else{
					$arr[$k] = self::convert_simplexml_to_array($v);
				}
			}
		}
		if (sizeof($arr) > 0) {
		  return $arr;
		} else {
		  return (string)$sxml;
		}
	}
}
?>