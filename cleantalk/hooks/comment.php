//<?php

abstract class hook15 extends _HOOK_CLASS_
{
	public static function create( $item, $comment, $first=FALSE, $guestName=NULL, $incrementPostCount=NULL, $member=NULL, \IPS\DateTime $time=NULL )
	{
		$topic=$item;
		if ( $member === NULL )
		{
			$member = \IPS\Member::loggedIn();
		}
		if(isset($_POST['topic_title']))
		{
			$comment=$_POST['topic_title']."\n".$comment;
		}
		$access_key=\IPS\Settings::i()->access_key;
		if(!$member->isAdmin() && $member->member_posts<=10 && \IPS\Settings::i()->moderate_new==1)
		{
			require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/uploads/cleantalk.class.php");
			require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/uploads/JSON.php");
			session_name('cleantalksession');
		    if (!isset($_SESSION))
		    {
				session_start();
		    }
		    if (array_key_exists('formtime', $_SESSION))
		    {
				$submit_time = time() - (int) $_SESSION['formtime'];
		    }
		    else
		    {
				$submit_time = NULL;
		    }
		    $_SESSION['formtime'] = time();
	
		    $post_info = '';
		    $lang=\IPS\Lang::getEnabledLanguages();
		    $locale=$lang[\IPS\Lang::defaultLanguage()]->short;
		    if(function_exists('json_encode'))
		    {
		    	
				$arr = array(
				    'cms_lang' => $locale,
				    'REFFERRER' => $_SERVER['HTTP_REFERER'],
				    'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
				);
				$post_info = json_encode($arr);
		    }
		    if($post_info === FALSE) $post_info = '';
		    
		    $ct_url = 'http://moderate.cleantalk.ru';
		    
		    $config_work_url =  $ct_url;
		    $config_ttl = 43200;
		    $config_changed = 1349162987;
		    
		    $config_key = $access_key;
		    
		    $ct = new \Cleantalk();
		    $ct->work_url = $config_work_url;
		    $ct->server_url = $ct_url;
		    $ct->server_ttl = $config_ttl;
		    $ct->server_changed = $config_changed;
		    
		    $sender_email = filter_var($member->email, FILTER_SANITIZE_EMAIL);
		    $sender_ip = $ct->ct_session_ip($_SERVER['REMOTE_ADDR']);
	
		    $ct_request = new \CleantalkRequest();
		    $ct_request->auth_key = $config_key;
			$ct_request->sender_nickname = $member->name;
		    $ct_request->sender_ip = $sender_ip;
		    $ct_request->sender_email = $sender_email;
		    $ct_request->post_info = $post_info;
		    $ct_request->agent = 'ipboard4-16';
		    
		    $js_keys=Array();
	        for($i=-5;$i<=1;$i++)
	        {
	            $js_keys[]=md5(\IPS\Settings::i()->access_key . '+' . \IPS\Settings::i()->email_in . date("Ymd",time()+86400*$i));
	        }
		    
		    $ct_request->js_on = in_array($_COOKIE['ct_checkjs'], $js_keys) ? 1 : 0;
		    $ct_request->submit_time = $submit_time;
		    $ct_request->message = $comment;
	
		    $ct_result = $ct->isAllowMessage($ct_request);
		    
		    if($ct_result->allow == 1)
		    {
				// Not spammer.
				//call_user_func_array( 'parent::save', func_get_args() );
				return call_user_func_array( 'parent::create', func_get_args() );
		    }
		    else
		    {
		    	if(isset($_POST['topic_title']))
		    	{
		    		$topic->delete();
		    	}
				if ( \IPS\Request::i()->isAjax() )
				{
					$result=Array("type"=>"error","message"=>$ct_result->comment);
					$result=json_encode($result);
					\IPS\Output::i()->sendOutput( $result, 200, "application/json" );
				}
				else
				{
					\IPS\Output::i()->sidebar['enabled'] = FALSE;
					\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( "Forbidden", \IPS\Theme::i()->getTemplate( 'global', 'core' )->error( "Forbidden", $ct_result->comment, 1, "" ), array( 'app' => \IPS\Dispatcher::i()->application ? \IPS\Dispatcher::i()->application->directory : NULL, 'module' => \IPS\Dispatcher::i()->module ? \IPS\Dispatcher::i()->module->key : NULL, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', Array(), FALSE, FALSE );
				}
				die();
				return call_user_func_array( 'parent::create', func_get_args() );
		    }
		}
		return call_user_func_array( 'parent::create', func_get_args() );
	}
	
}