//<?php

function CleantalkGetIP()
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


$form->add( new \IPS\Helpers\Form\YesNo( 'plugin_enabled', \IPS\Settings::i()->plugin_enabled, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'plugin_enabled' ) ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'moderate_new', \IPS\Settings::i()->moderate_new, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'moderate_new' ) ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'show_link', \IPS\Settings::i()->show_link, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'show_link' ) ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'cleantalk_sfw', \IPS\Settings::i()->cleantalk_sfw, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'cleantalk_sfw' ) ) );
$form->add( new \IPS\Helpers\Form\Text( 'access_key', \IPS\Settings::i()->access_key, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'access_key' ) ) );

if ( $values = $form->values() )
{
	require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/../uploads/cleantalk.class.php");
	require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/../uploads/JSON.php");
	$values = $form->values();
	
	$ct = new \Cleantalk();
	$ct->work_url = 'http://moderate.cleantalk.ru';
    $ct->server_url = 'http://moderate.cleantalk.ru';
    $ct->server_ttl = 43200;
	
	$ct_request = new \CleantalkRequest();
    $ct_request->auth_key = $values['access_key'];
	$ct_request->sender_nickname = 'CleanTalk';
    $ct_request->sender_ip = $_SERVER['REMOTE_ADDR'];
    $ct_request->sender_email = 'good@cleantalk.org';
    $ct_request->agent = 'ipboard4-17';
    $ct_request->js_on = 1;
    $ct_request->message = 'This message is a test to check the connection to the CleanTalk servers.';

    $ct_result = $ct->isAllowMessage($ct_request);
	
	$form->saveAsSettings();
	if(\IPS\Settings::i()->cleantalk_sfw == 1)
	{
    	$sql="DROP TABLE IF EXISTS `cleantalk_sfw`";
		$result = IPS\Db::i()->query($sql);
		$sql="CREATE TABLE IF NOT EXISTS `cleantalk_sfw` (
`network` int(11) unsigned NOT NULL,
`mask` int(11) unsigned NOT NULL,
INDEX (  `network` ,  `mask` )
) ENGINE = MYISAM ";
		$result = IPS\Db::i()->query($sql);
		$data = Array(	'auth_key' => $values['access_key'],
				'method_name' => '2s_blacklists_db'
			);
			
		$result=sendRawRequest('https://api.cleantalk.org/2.1',$data,false);
		$result=json_decode($result, true);
		if(isset($result['data']))
		{
			$result=$result['data'];
			$query="INSERT INTO `cleantalk_sfw` VALUES ";
			for($i=0;$i<sizeof($result);$i++)
			{
				if($i==sizeof($result)-1)
				{
					$query.="(".$result[$i][0].",".$result[$i][1].")";
				}
				else
				{
					$query.="(".$result[$i][0].",".$result[$i][1]."), ";
				}
			}
			$result = IPS\Db::i()->query($query);
		}
	}
	return TRUE;
}

return $form;