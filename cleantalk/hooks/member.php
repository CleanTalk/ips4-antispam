//<?php

class hook9 extends _HOOK_CLASS_
{
	public function save()
	{
		$new		= $this->_new;
		
		$enabled=\IPS\Settings::i()->plugin_enabled;
		if($enabled==1&&$new)
		{
			require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/uploads/cleantalk/cleantalk.class.php");
			require_once(dirname($_SERVER['SCRIPT_FILENAME'])."/uploads/cleantalk/JSON.php");
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
		    if(function_exists('json_encode'))
		    {
				$arr = array(
				    'cms_lang' => substr($this->lang->local, 0, 2),
				    'REFFERRER' => $_SERVER['HTTP_REFERER'],
				    'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
				);
				$post_info = json_encode($arr);
		    }
		    if($post_info === FALSE) $post_info = '';
	
		    $ct_url = 'http://moderate.cleantalk.ru';
		    $server = \IPS\Db::i()->select( array( 
	                                                    'select' => 'work_url, server_ttl, server_changed',
	                                                    'from'   => 'cleantalk_server'
	                                                  )
		    );
		    $config_work_url = !empty( $server ) ? strval($server['work_url']) : $ct_url;
		    $config_ttl = !empty( $server ) ? intval($server['server_ttl']) : 43200;
		    $config_changed = !empty( $server ) ? intval($server['server_changed']) : 1349162987;
	
		    $config_key = empty(ipsRegistry::$settings['cleantalk_auth_key']) ? 'enter key' : \IPS\Settings::i()->access_key;

		    $ct = new Cleantalk();
		    $ct->work_url = $config_work_url;
		    $ct->server_url = $ct_url;
		    $ct->server_ttl = $config_ttl;
		    $ct->server_changed = $config_changed;
	
		    $sender_email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
		    $sender_ip = $ct->ct_session_ip($this->ip_address);
	
		    $ct_request = new CleantalkRequest();
		    $ct_request->auth_key = $config_key;
			$ct_request->sender_nickname = $this->name;
		    $ct_request->sender_ip = $sender_ip;
		    $ct_request->sender_email = $sender_email;
		    $ct_request->post_info = $post_info;
		    $ct_request->agent = 'ipboard4-15';
		    $ct_request->js_on = $this->request['ct_checkjs'] == md5($config_key . '+' . \IPS\Settings::i()->admin_email ? 1 : 0;
		    $ct_request->submit_time = $submit_time;
	
		    $ct_result = $ct->isAllowUser($ct_request);
		
		    if($ct->server_change)
		    {
	                if(empty( $server ))
	                {
	                    \IPS\Db::i()->insert( 'cleantalk_server', array( 'work_url' => $ct->work_url, 'server_ttl' => $ct->server_ttl, 'server_changed' => time() ));
	                }
	                else
	                {
	                    \IPS\Db::i()->update( 'cleantalk_server', array( 'work_url' => $ct->work_url, 'server_ttl' => $ct->server_ttl, 'server_changed' => time() ));
	                }
		    }
	
		    // First check errstr flag.
		    if(!empty($ct_result->errstr) || (!empty($ct_result->inactive) && $ct_result->inactive == 1))
		    {
			    // Cleantalk error so we go default way (no action at all).
			    // Just inform admin.
			    $err_title = ($config_lang == 'ru') ? 'Ошибка хука CleanTalk' : 'CleanTalk hook error';
			    if(!empty($ct_result->inactive) && $ct_result->inactive == 1)
			    {
					$err_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/iu', '', $ct_result->comment);
			    }
			    else
			    {
					$err_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/iu', '', $ct_result->errstr);
			    }
			    $time = \IPS\Db::i()->select( array( 'select' => 'ct_value',
								  'from'   => 'cleantalk_timelabels',
								  'where'  => 'ct_key=\'mail_error_registerProcessForm\'' ) 
			    );
	
			    if ( empty( $time ) || empty( $time['ct_value'] ) || ( time() - 900 > $time['ct_value'] ) )
			    {
					\IPS\Settings::i()->update( 'cleantalk_timelabels', array( 'ct_key' => 'mail_error_registerProcessForm', 'ct_value' => time() ), array( 'ct_key' ) );
			    }
			    return;
		    }
	
		    if($ct_result->allow == 1)
		    {
				// Not spammer.
				return true;
		    }
		    else
		    {
				// Spammer - display message and exit.
				$err_str = '<span style="color:#ab1f39;">' . $ct_result->comment . '</span><script>setTimeout("history.back()", 5000);</script>';
				\IPS\Output->error($err_str,500);
				return false;
		    }
		}
		else
		{
		    return;
		}
		}
	}
}