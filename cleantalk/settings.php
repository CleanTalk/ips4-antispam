//<?php


$form->add( new \IPS\Helpers\Form\YesNo( 'plugin_enabled', \IPS\Settings::i()->plugin_enabled, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'plugin_enabled' ) ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'moderate_new', \IPS\Settings::i()->moderate_new, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'moderate_new' ) ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'show_link', \IPS\Settings::i()->show_link, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'show_link' ) ) );
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
	return TRUE;
}

return $form;