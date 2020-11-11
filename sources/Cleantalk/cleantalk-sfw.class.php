<?php

/**
Cleantalk Spam FireWall class
**/

class CleanTalkSFW
{
	public $ip = 0;
	public $ip_str = '';
	public $ip_array = Array();
	public $ip_str_array = Array();
	public $blocked_ip = '';
	public $result = false;
	
	public $debug_data = '';
	
	public function cleantalk_get_real_ip()
	{
		if ( \function_exists( 'apache_request_headers' ) )
		{
			$headers = apache_request_headers();
		}
		else
		{
			$headers = $_SERVER;
		}
		if ( array_key_exists( 'X-Forwarded-For', $headers ) )
		{
			$the_ip=explode(",", trim($headers['X-Forwarded-For']));
			$the_ip = trim($the_ip[0]);
			$this->ip_str_array[]=$the_ip;
			$this->ip_array[]=sprintf("%u", ip2long($the_ip));
		}
		if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ))
		{
			$the_ip=explode(",", trim($headers['HTTP_X_FORWARDED_FOR']));
			$the_ip = trim($the_ip[0]);
			$this->ip_str_array[]=$the_ip;
			$this->ip_array[]=sprintf("%u", ip2long($the_ip));
		}
		$the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
		$this->ip_str_array[]=$the_ip;
		$this->ip_array[]=sprintf("%u", ip2long($the_ip));

		if(isset($_GET['sfw_test_ip']))
		{
			$the_ip=$_GET['sfw_test_ip'];
			$this->ip_str_array[]=$the_ip;
			$this->ip_array[]=sprintf("%u", ip2long($the_ip));
		}
	}
	
	public function check_ip()
	{
		$passed_ip='';
		
		for($i=0;$i<sizeof($this->ip_array);$i++)
		{
			$result = IPS\Db::i()->select('count(network)', 'antispambycleantalk_sfw', "network = ".$this->ip_array[$i]." & mask", "", 1);
			$cnt = $result->first();

			if($cnt>0)
			{
				$this->result=true;
				$this->blocked_ip=$this->ip_str_array[$i];
			}
			else
			{
				$passed_ip = $this->ip_str_array[$i];
			}
		}
		if($passed_ip!='')
		{
			$domain = ( isset( $_SERVER['HTTP_HOST'] )
				? $_SERVER['HTTP_HOST']
				: isset( $_SERVER['SERVER_NAME'] ) )
					? $_SERVER['SERVER_NAME']
					: null;
			$key=\IPS\Settings::i()->ct_access_key;
			@setcookie( 'ct_sfw_pass_key', md5( $passed_ip . $key ), 0, '/', $domain );
		}
	}
	
	public function sfw_die()
	{
		$key=\IPS\Settings::i()->ct_access_key;
		$sfw_die_page=file_get_contents(dirname(__FILE__)."/sfw_die_page.html");
		$sfw_die_page=str_replace("{REMOTE_ADDRESS}",$this->blocked_ip,$sfw_die_page);
		$sfw_die_page=str_replace("{REQUEST_URI}",$_SERVER['REQUEST_URI'],$sfw_die_page);
		$sfw_die_page=str_replace("{SFW_COOKIE}",md5($this->blocked_ip.$key),$sfw_die_page);
		if(strpos( $_SERVER['REQUEST_URI'], 'debug') !== false ){
			$debug = '<h1>IP and Networks</h1>'
			         . var_export($this->ip_str_array, true)
			         .'<h1>Blocked IPs</h1>'
			         . var_export($this->blocked_ip, true)
			         .'<h1>Passed IPs</h1>'
			         . var_export($this->passed_ip, true)
			         . '<h1>Headers</h1>'
			         . var_export(apache_request_headers(), true)
			         . '<h1>REMOTE_ADDR</h1>'
			         . var_export(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NO', true)
			         . '<h1>SERVER_ADDR</h1>'
			         . var_export(isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'NO', true)
			         . '<h1>IP_ARRAY</h1>'
			         . var_export($this->ip_array, true)
			         . '<h1>ADDITIONAL</h1>'
			         . var_export($this->debug_data, true);
		}else
			$debug = '';
		$sfw_die_page = str_replace( "{DEBUG}", $debug, $sfw_die_page );
		
		@header('HTTP/1.0 403 Forbidden');
		print $sfw_die_page;
		die();
	}
}

?>