//<?php

abstract class hook10 extends _HOOK_CLASS_
{




	/**
	 * Create comment
	 *
	 * @param	\IPS\Content\Item		$item				The content item just created
	 * @param	string					$comment			The comment
	 * @param	bool					$first				Is the first comment?
	 * @param	string					$guestName			If author is a guest, the name to use
	 * @param	bool|NULL				$incrementPostCount	Increment post count? If NULL, will use static::incrementPostCount()
	 * @param	\IPS\Member|NULL		$member				The author of this comment. If NULL, uses currently logged in member.
	 * @param	\IPS\DateTime|NULL		$time				The time
	 * @return	static
	 */
	static public function create( $item, $comment, $first=false, $guestName=NULL, $incrementPostCount=NULL, $member=NULL, \IPS\DateTime $time=NULL )
	{
		if(\IPS\Settings::i()->plugin_enabled && 
		\IPS\Settings::i()->cleantalk_moderate_new_guest)
		{

            if( $member->member_id > 0 )
            {
                $posts_res = \IPS\Db::i()->select( array( 
                                                    'select' => 'ct_value',
                                                    'from'   => 'cleantalk_settings',
                                                    'where'  => 'ct_key=\'posts\''
                                                  )
                );
                if(empty( $posts_res ))
                {
                    $posts_conf = 3;
                    \IPS\Db::i()->insert( 'cleantalk_settings', array( 'ct_key' => 'posts', 'ct_value' => $posts_conf ));
                }
                else
                {
                    $posts_conf = intval($posts_res['ct_value']);
                }

                $posts_count = 0;
                # get count of moderated posts
                        \IPS\Db::i()->build(array(
						'select' => 'count(*) as count',
						'from'   => 'posts',
						'where'  => 'author_id=' . $this->memberData['member_id'] . ' and new_topic=0 and queued=0'
					)
                        );
                        $db_res = \IPS\Db::i()->execute();
                        if($r = \IPS\Db::i()->fetch($db_res)){
                            $posts_count = intval($r['count']);
                            }
                
                if( $posts_count >= $posts_conf )
                {
                    return true;
                }
            }

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
				    'cms_lang' => substr(\IPS\Settings::i()->lang->local, 0, 2),
				    'REFFERRER' => $_SERVER['HTTP_REFERER'],
				    'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
				);
				$post_info = json_encode($arr);
	    	}
			if($post_info === FALSE) $post_info = '';

		    $config_url = 'http://moderate.cleantalk.ru';
		    $server = \IPS\Db::i()->select( array( 
	                                                    'select' => 'work_url, server_ttl, server_changed',
	                                                    'from'   => 'cleantalk_server'
	                                                  )
		    );
      	    $config_work_url = !empty( $server ) ? strval($server['work_url']) : $ct_url;
		    $config_ttl = !empty( $server ) ? intval($server['server_ttl']) : 43200;
		    $config_changed = !empty( $server ) ? intval($server['server_changed']) : 1349162987;
	
		    $config_key = empty(ipsRegistry::$settings['cleantalk_auth_key']) ? 'enter key' : ipsRegistry::$settings['cleantalk_auth_key'];
		    $config_lang = empty(ipsRegistry::$settings['cleantalk_response_lang']) ? 'en' : ipsRegistry::$settings['cleantalk_response_lang'];

            $example = '';

			# get last 10 comments
	                \IPS\Db::i()->build(array(
							'select' => 'post',
							'from'   => 'posts',
							'where'  => 'topic_id=' . $topicId . ' and new_topic=0 and queued=0',
	                                                'order'  => 'post_date DESC',
	                                                'limit'	 => array(10),
						)
			);
                $db_res = \IPS\Db::i()->execute();
			while($r = \IPS\Db::i()->fetch($db_res))
			{
				$example .= $r['post'] . "\n\n";
			}

		    $ct = new Cleantalk();
		    $ct->work_url = $config_work_url;
		    $ct->server_url = $ct_url;
		    $ct->server_ttl = $config_ttl;
		    $ct->server_changed = $config_changed;
	
		    $sender_ip = $ct->ct_session_ip($member->ip_address);
	
		    $ct_request = new CleantalkRequest();
		    $ct_request->auth_key = $config_key;
			$sender_email = filter_var($member->email, FILTER_SANITIZE_EMAIL);
			$ct_request->sender_nickname = $member->name;
		    $ct_request->sender_ip = $sender_ip;
		    $ct_request->sender_email = $sender_email;
		    $ct_request->post_info = $post_info;
		    $ct_request->agent = 'ipboard4-15';
		    $ct_request->response_lang = $config_lang;
		    $ct_request->js_on = 1;
		    $ct_request->submit_time = $submit_time;
		    $ct_request->sender_info = '';
		    $ct_request->message = $this->request['Post'];
		    $ct_request->example = $example;
		    $ct_request->stoplist_check = '';
		    $ct_request->allow_links = 0;


		    $ct_result = $ct->isAllowMessage($ct_request);
		
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
		    return true;
		}
	}

}