//<?php

class hook13 extends _HOOK_CLASS_
{
  	public function getCheckJSArray()
	{
        $result=Array();
        for($i=-5;$i<=1;$i++)
        {
            $result[]=md5(\IPS\Settings::i()->access_key . '+' . \IPS\Settings::i()->email_in . date("Ymd",time()+86400*$i));
        }
        return $result;
	}

	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return	void
	 * @note	We have to be careful when upgrading in case we are coming from an older version
	 */
	public function save()
	{
      	$new		= $this->_new;
      	$enabled=\IPS\Settings::i()->plugin_enabled;
      	$access_key=\IPS\Settings::i()->access_key;
      	if($enabled==1&&$new)
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
	
		    $sender_email = filter_var($_POST['email_address'], FILTER_SANITIZE_EMAIL);
		    $sender_ip = $ct->ct_session_ip($_SERVER['REMOTE_ADDR']);
	
		    $ct_request = new \CleantalkRequest();
		    $ct_request->auth_key = $config_key;
			$ct_request->sender_nickname = $_POST['username'];
		    $ct_request->sender_ip = $sender_ip;
		    $ct_request->sender_email = $sender_email;
		    $ct_request->sender_info = $post_info;
		    $ct_request->agent = 'ipboard4-18';
		    //$ct_request->js_on = $_COOKIE['ct_checkjs'] == md5(\IPS\Settings::i()->access_key . '+' . \IPS\Settings::i()->email_in) ? 1 : 0;
		    $ct_request->js_on = in_array($_COOKIE['ct_checkjs'], self::getCheckJSArray()) ? 1 : 0;
		    $ct_request->submit_time = $submit_time;
	
		    $ct_result = $ct->isAllowUser($ct_request);
		    if(isset($ct_result->errno) && $ct_result->errno>0)
		    {
		    	//sendErrorMessage("CleanTalk has some problems, errno is ".$ct_result->errno.", errstr is '".$ct_result->errstr."'")
		    }
		    
		    if($ct_result->allow == 1)
		    {
				// Not spammer.
				call_user_func_array( 'parent::save', func_get_args() );
		    }
		    else
		    {
				// Spammer - display message and exit.
				
				if ( \IPS\Request::i()->isAjax() )
				{
					$err_str = '<span style="color:#ab1f39;">' . $ct_result->comment . '</span><script>setTimeout("history.back()", 5000);</script>';
					print $err_str;
				}
				else
				{
					\IPS\Output::i()->sidebar['enabled'] = FALSE;
					\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( "Forbidden", \IPS\Theme::i()->getTemplate( 'global', 'core' )->error( "Forbidden", $ct_result->comment, 1, "" ), array( 'app' => \IPS\Dispatcher::i()->application ? \IPS\Dispatcher::i()->application->directory : NULL, 'module' => \IPS\Dispatcher::i()->module ? \IPS\Dispatcher::i()->module->key : NULL, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', Array(), FALSE, FALSE );
				}
				die();
		    }
      	}
		return call_user_func_array( 'parent::save', func_get_args() );
	}

}