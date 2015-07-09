//<?php

abstract class hook10 extends _HOOK_CLASS_
{
	public function _reply()
    {

	if(ipsRegistry::$settings['cleantalk_enabled'] && 
		ipsRegistry::$settings['cleantalk_moderate_new_guest'] && 
                $this->request['app'] == 'forums' &&
                $this->request['module'] == 'ajax' &&
                $this->request['section'] == 'topics' &&
                $this->request['do'] == 'reply'
         ){
       	    $ver4sym = ipsRegistry::$version;
	    $ver4sym = substr($ver4sym, 0, 4);	// '3.4.'

            if( $this->memberData['member_id'] > 0 ){
                $posts_res = $this->DB->buildAndFetch( array( 
                                                    'select' => 'ct_value',
                                                    'from'   => 'cleantalk_settings',
                                                    'where'  => 'ct_key=\'posts\''
                                                  )
                );
                if(empty( $posts_res )){
                    $posts_conf = 3;
                    $this->DB->insert( 'cleantalk_settings', array( 'ct_key' => 'posts', 'ct_value' => $posts_conf ));
                }else{
                    $posts_conf = intval($posts_res['ct_value']);
                }

                $posts_count = 0;
                # get count of moderated posts
                if(strcmp($ver4sym, '3.2.') >= 0){
                    if ( ! $this->registry->isClassLoaded('topics') ) {
                        $classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
                        $this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
                    }
                
                    $posts_count = intval($this->registry->getClass('topics')->getPosts( 
                                                                    array(
									'authorId'	=> $this->memberData['member_id'],
									'onlyViewable'  => true,
									'onlyVisible'   => true,
                                                                        'getCountOnly'  => true
                                                                        )
                    ));
                }else if($ver4sym == '3.1.'){
                        $this->DB->build(array(
						'select' => 'count(*) as count',
						'from'   => 'posts',
						'where'  => 'author_id=' . $this->memberData['member_id'] . ' and new_topic=0 and queued=0'
					)
                        );
                        $db_res = $this->DB->execute();
                        if($r = $this->DB->fetch($db_res)){
                            $posts_count = intval($r['count']);
                        }
                }else{
                    return parent::_reply();
                }
                
                if( $posts_count >= $posts_conf ){
                    return parent::_reply();
                }
            }

            require_once(IPS_HOOKS_PATH . 'cleantalk.class.php');
	    session_name('cleantalksession');
	    if (!isset($_SESSION)) {
		session_start();
	    }
	    if (array_key_exists('formtime', $_SESSION)) {
		$submit_time = time() - (int) $_SESSION['formtime'];
	    } else {
		$submit_time = NULL;
	    }
	    $_SESSION['formtime'] = time();

	    $post_info = '';
	    if(function_exists('json_encode')){
		$arr = array(
		    'cms_lang' => substr($this->lang->local, 0, 2),
		    'REFFERRER' => $_SERVER['HTTP_REFERER'],
		    'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
		);
		$post_info = json_encode($arr);
	    }
	    if($post_info === FALSE) $post_info = '';

	    $config_url = 'http://moderate.cleantalk.ru';
	    $server = $this->DB->buildAndFetch( array( 
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
            
            $forumId = intval( $this->request['f'] );
            $topicId = intval( $this->request['t'] );
            $postId = intval( $this->request['p'] );

	    if(strcmp($ver4sym, '3.2.') >= 0){
		if ( ! $this->registry->isClassLoaded('topics') ) {
		    $classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
		    $this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
                #get 1-st comment - article itself
		$posts = $this->registry->getClass('topics')->getPosts( 
                                                                    array(
                                                                        'topicId'	=> $topicId,
									'onlyViewable'  => true,
									'onlyVisible'   => true,
									'parse'		=> false,
									'forumId'	=> $forumId,
									'limit'		=> 1,
									'sortField'	=> 'date',
									'sortOrder'	=> 'asc'
                                                                        )
		);
		foreach ($posts as $post) {
			$example .= $post['post'] . "\n\n";
		}
                
                # get last 10 comments
		$posts = $this->registry->getClass('topics')->getPosts( 
                                                                    array(
                                                                        'topicId'	=> $topicId,
									'onlyViewable'  => true,
									'onlyVisible'   => true,
									'parse'		=> false,
									'forumId'	=> $forumId,
									'limit'		=> 10,
									'sortField'	=> 'date',
									'sortOrder'	=> 'desc'
                                                                        )
		);
		foreach ($posts as $post) {
			$example .= $post['post'] . "\n\n";
		}
	    }else if($ver4sym == '3.1.'){
                #get 1-st comment - article itself
		$r = $this->DB->buildAndFetch(array(
						'select' => 'post',
						'from'   => 'posts',
						'where'  => 'topic_id=' . $topicId . ' and new_topic=1 and queued=0',
                                                'order'  => 'post_date ASC',
                                                'limit'	 => array(1),
					)
		);
       		if(!empty($r)){
                    $example .= $r['post'] . "\n\n";
                }

		# get last 10 comments
                $this->DB->build(array(
						'select' => 'post',
						'from'   => 'posts',
						'where'  => 'topic_id=' . $topicId . ' and new_topic=0 and queued=0',
                                                'order'  => 'post_date DESC',
                                                'limit'	 => array(10),
					)
		);
                $db_res = $this->DB->execute();
		while($r = $this->DB->fetch($db_res)){
			$example .= $r['post'] . "\n\n";
		}
	    }else{
		return parent::_reply();
	    }

	    $ct = new Cleantalk();
	    $ct->work_url = $config_work_url;
	    $ct->server_url = $ct_url;
	    $ct->server_ttl = $config_ttl;
	    $ct->server_changed = $config_changed;

	    $sender_ip = $ct->ct_session_ip($this->member->ip_address);

	    $ct_request = new CleantalkRequest();
	    $ct_request->auth_key = $config_key;
	    if($this->memberData['member_id'] == 0){
		$sender_email = filter_var($this->request['EmailAddress'], FILTER_SANITIZE_EMAIL);
		if(array_key_exists('members_display_name', $this->request)){
		    $ct_request->sender_nickname = $this->request['members_display_name'];
		}else if(array_key_exists('UserName', $this->request)){
		    $ct_request->sender_nickname = $this->request['UserName'];
		}else{
		    $ct_request->sender_nickname = NULL;
		}
	    }else{
		$sender_email = $this->memberData['email'];
		$ct_request->sender_nickname = $this->memberData['name'];
	    }
	    $ct_request->sender_ip = $sender_ip;
	    $ct_request->sender_email = $sender_email;
	    $ct_request->post_info = $post_info;
	    $ct_request->agent = 'ipboard-15';
	    $ct_request->response_lang = $config_lang;
	    $ct_request->js_on = 1;
	    $ct_request->submit_time = $submit_time;
	    $ct_request->sender_info = '';
	    $ct_request->message = $this->request['Post'];
	    $ct_request->example = $example;
	    $ct_request->stoplist_check = '';
	    $ct_request->allow_links = 0;


	    $ct_result = $ct->isAllowMessage($ct_request);
	
	    if($ct->server_change){
                if(empty( $server )){
                    $this->DB->insert( 'cleantalk_server', array( 'work_url' => $ct->work_url, 'server_ttl' => $ct->server_ttl, 'server_changed' => time() ));
                }else{
                    $this->DB->update( 'cleantalk_server', array( 'work_url' => $ct->work_url, 'server_ttl' => $ct->server_ttl, 'server_changed' => time() ));
                }
	    }

	    // First check errstr flag.
	    if(!empty($ct_result->errstr) || (!empty($ct_result->inactive) && $ct_result->inactive == 1)){
		    // Cleantalk error so we go default way (no action at all).
		    // Just inform admin.
		    $err_title = ($config_lang == 'ru') ? 'Ошибка хука CleanTalk' : 'CleanTalk hook error';
		    if(!empty($ct_result->inactive) && $ct_result->inactive == 1){
			$err_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/iu', '', $ct_result->comment);
		    }else{
			$err_str = preg_replace('/^[^\*]*?\*\*\*|\*\*\*[^\*]*?$/iu', '', $ct_result->errstr);
		    }
		    $time = $this->DB->buildAndFetch( array( 'select' => 'ct_value',
							  'from'   => 'cleantalk_timelabels',
							  'where'  => 'ct_key=\'mail_error_commentAjaxTopic\'' ) 
		    );

		    if ( empty( $time ) || empty( $time['ct_value'] ) || ( time() - 900 > $time['ct_value'] ) ) {		// 15 minutes
			$this->DB->replace( 'cleantalk_timelabels', array( 'ct_key' => 'mail_error_commentAjaxTopic', 'ct_value' => time() ), array( 'ct_key' ) );

			$mail_from_addr = 'support@cleantalk.ru';
			$mail_from_user = 'CleanTalk';
			$mail_subj = ipsRegistry::$settings['board_name'] . ' - ' . $err_title . '!';
			$mail_body = '<b>' . ipsRegistry::$settings['board_name'] . ' - ' . $err_title . ':</b><br />' . $err_str;

			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classEmail.php', 'classEmail' );
			$emailer = new $classToLoad(
						    array('debug'		=> 0,
							  'debug_path'		=> DOC_IPS_ROOT_PATH . '_mail',
							  'smtp_host'		=> ipsRegistry::$settings['smtp_host'] ? ipsRegistry::$settings['smtp_host'] : 'localhost',
							  'smtp_port'		=> intval(ipsRegistry::$settings['smtp_port']) ? intval(ipsRegistry::$settings['smtp_port']) : 25,
							  'smtp_user'		=> ipsRegistry::$settings['smtp_user'],
							  'smtp_pass'		=> ipsRegistry::$settings['smtp_pass'],
							  'smtp_helo'		=> ipsRegistry::$settings['smtp_helo'],
							  'method'		=> ipsRegistry::$settings['mail_method'],
							  'wrap_brackets'	=> ipsRegistry::$settings['mail_wrap_brackets'],
							  'extra_opts'		=> ipsRegistry::$settings['php_mail_extra'],
							  'charset'		=> 'utf-8',
							  'html'		=> 1
							)
			);
			$emailer->setFrom( $mail_from_addr, $mail_from_user );
			$emailer->setTo( ipsRegistry::$settings['email_in'] );
			$emailer->setSubject( $mail_subj );
			$emailer->setBody( $mail_body );
			$emailer->sendMail();
		    }
		    return parent::_reply();
	    }

	    if($ct_result->allow == 1){
		return parent::_reply();
	    }else{
		if((!empty($ct_result->stop_queue) && $ct_result->stop_queue == 1)){
		    $err_str = '<span style="color:#ab1f39;">' . $ct_result->comment . '</span><script>setTimeout("history.back()", 5000);</script>';
		    if(ipsRegistry::$settings['spam_service_enabled']){
			$ct_resume2log = trim(str_replace('*', '', $ct_result->comment));
			ipsRegistry::DB()->insert( 'spam_service_log', array(
									'log_date'	=> time(),
									'log_code'	=> 4,	// Known spammer
									'log_msg'	=> $ct_resume2log,
									'email_address'	=> $sender_email,
									'ip_address'	=> $sender_ip
									)
			);
		    }
		    $this->returnJsonError($err_str);
		    return;
		}else{
		    $GLOBALS['cleantalk_not_allow'] = 1;
		    return parent::_reply();
		}
	    }
	}else{
	    return parent::_reply();
	}
    }

}