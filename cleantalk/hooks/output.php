//<?php

class hook14 extends _HOOK_CLASS_
{
	public function getTitle( $title )
	{
		if(\IPS\Settings::i()->cleantalk_sfw == 1)
		{
			$is_sfw_check=true;
		   	$ip=$this->CleantalkGetIP();
		   	$ip=array_unique($ip);
		   	$key=\IPS\Settings::i()->access_key;
		   	for($i=0;$i<sizeof($ip);$i++)
			{
		    	if(isset($_COOKIE['ct_sfw_pass_key']) && $_COOKIE['ct_sfw_pass_key']==md5($ip[$i].$key))
		    	{
		    		$is_sfw_check=false;
		    		if(isset($_COOKIE['ct_sfw_passed']))
		    		{
		    			@setcookie ('ct_sfw_passed', '0', 1, "/");
		    		}
		    	}
		    }
			if($is_sfw_check)
			{
				include_once(dirname(__FILE__)."/uploads/cleantalk-sfw.class.php");
				$sfw = new \CleanTalkSFW();
				$sfw->cleantalk_get_real_ip();
				$sfw->check_ip();
				if($sfw->result)
				{
					$sfw->sfw_die();
				}
			}
		}
		if(session_id()=='')session_start();
		$show_link=\IPS\Settings::i()->show_link;
		$html = '
<script type="text/javascript">
function ctSetCookie(c_name, value, def_value) {
    document.cookie = c_name + "=" + escape(value.replace(/^def_value$/, value)) + "; path=/";
}
ctSetCookie("%s", "%s", "%s");
</script>
';
		$ct_checkjs_key=md5(\IPS\Settings::i()->access_key . '+' . \IPS\Settings::i()->email_in . date("Ymd",time()));
		$html = sprintf($html, "ct_checkjs", $ct_checkjs_key, 0);
		if($show_link==1)
		{
			$html.="<div id='cleantalk_footer_link' style='width:100%;text-align:center;'><a href='https://cleantalk.org/ips-cs-4-anti-spam-plugin'>IPS spam</a> blocked by CleanTalk.</div>";
		}
		$this->endBodyCode.=$html;
		return $title;
	}
	
	public function CleantalkGetIP()
	{
		$result=Array();
		if ( function_exists( 'apache_request_headers' ) )
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
			$result[] = trim($the_ip[0]);
		}
		if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ))
		{
			$the_ip=explode(",", trim($headers['HTTP_X_FORWARDED_FOR']));
			$result[] = trim($the_ip[0]);
		}
		$result[] = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	
		if(isset($_GET['sfw_test_ip']))
		{
			$result[]=$_GET['sfw_test_ip'];
		}
		return $result;
	}
}