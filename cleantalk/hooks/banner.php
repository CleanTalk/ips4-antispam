//<?php

class hook16 extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
	$last_check=intval(\IPS\Settings::i()->cleantalk_last_check);
	$last_status=intval(\IPS\Settings::i()->cleantalk_last_status);
	
	$api_key = \IPS\Settings::i()->access_key;
	$result = '';
	$html = '';
	if(time()-$last_check>60) // && $api_key!=0 && $api_key!=''
	{
		$data = array();
		$data['auth_key'] = $api_key;
		$data['method_name'] = 'notice_validate_key';
		if(!function_exists('sendRawRequest'))
		{
			require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/../uploads/cleantalk.class.php");
			require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/../uploads/JSON.php");
		}
		$result = sendRawRequest('https://api.cleantalk.org',$data);
		$result = json_decode(trim($result));
		if(isset($result->valid))
		{
			$new_status = intval($result->valid);
			\IPS\Settings::i()->cleantalk_last_status = $new_status;
		}
		else
		{
			$new_status = 0;
		}
		if($new_status == 1 && $last_status == 0)
		{
			\IPS\Settings::i()->cleantalk_show_banner = 1;
		}
		\IPS\Settings::i()->cleantalk_last_check = time();
	}
	if(isset($_COOKIE['cleantalk_close_banner']))
	{
		\IPS\Settings::i()->cleantalk_show_banner = 0;
	}
	$show_banner=intval(\IPS\Settings::i()->cleantalk_show_banner);
	if($show_banner == 1)
	{
		$html = "<div style='width:99%;background: #90EE90; padding:10px;border: 2px dashed green;margin:3px;font-size:16px;text-align:center;' id='cleantalk_banner'>Like antispam by CleanTalk? <a href='https://community.invisionpower.com/files/file/7706-anti-spam-ips4/' target='_blank'>Leave a review!</a><div style='float:right;margin-top:-5px;margin-right:-5px;'><a href=# style='text-decoration:none;font-size:14px;font-weight:600;' onclick='jQuery(\"#cleantalk_banner\").hide(\"slow\");document.cookie=\"cleantalk_close_banner = 1;\"'>X</a></div></div>";
	}
 return array_merge_recursive( array (
  'globalTemplate' => 
  array (
    0 => 
    array (
      'selector' => '#acpPageHeader',
      'type' => 'add_after',
      'content' => $html,
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */




}