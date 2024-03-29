//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    exit;
}
require_once(\IPS\Application::getRootPath().'/applications/antispambycleantalk/sources/autoload.php');

use Cleantalk\Antispam\Cleantalk;
use Cleantalk\Antispam\CleantalkRequest;
use Cleantalk\Antispam\CleantalkResponse;
use Cleantalk\Common\Helper as CleantalkHelper;

abstract class antispambycleantalk_hook_comment extends _HOOK_CLASS_
{

    public static function ctCookiesTest()
    {
        try
        {
            try
            {
                if(isset($_COOKIE['ct_cookies_test'])){

                    $cookie_test = json_decode(stripslashes($_COOKIE['ct_cookies_test']), true);

                    $check_srting = trim(\IPS\Settings::i()->ct_access_key);
                    foreach($cookie_test['cookies_names'] as $cookie_name){
                        $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
                    } unset($cokie_name);

                    if($cookie_test['check_value'] == md5($check_srting)){
                        return 1;
                    }else{
                        return 0;
                    }
                }else{
                    return null;
                }
            }
            catch ( \RuntimeException $e )
            {
                if ( method_exists( get_parent_class(), __FUNCTION__ ) )
                {
                    return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
                }
                else
                {
                    throw $e;
                }
            }
        }
        catch ( \RuntimeException $e )
        {
            if ( method_exists( get_parent_class(), __FUNCTION__ ) )
            {
                return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
            }
            else
            {
                throw $e;
            }
        }
    }

    public static function create( $item, $comment, $first=FALSE, $guestName=NULL, $incrementPostCount=NULL, $member=NULL, \IPS\DateTime $time=NULL, $ipAddress=NULL, $hiddenStatus=NULL, $anonymous=NULL ){
        try
        {
            try
            {

                try{

                    $topic=$item;

                    if ( $member === NULL )
                        $member = \IPS\Member::loggedIn();
                    $permissionCheckFunction = \in_array( 'IPS\Content\Review', class_parents( \get_called_class() ) ) ? 'canReview' : 'canComment';
                    if ( !$member->member_id and !$item->$permissionCheckFunction( $member, FALSE ) )
                        return \call_user_func_array( 'parent::create', \func_get_args() );
                    $comment_to_check = isset($_POST['topic_title']) ? $_POST['topic_title']."\n".trim(strip_tags($comment)) : trim(strip_tags($comment));

                    $ct_access_key=\IPS\Settings::i()->ct_access_key;
                    if(isset($member) && !$member->isAdmin() && $member->member_posts <= \IPS\Settings::i()->ct_posts_to_check && \IPS\Settings::i()->ct_moderate_new==1){

                        $sender_info = ''; $post_info = '';
                        $lang=\IPS\Lang::getEnabledLanguages();
                        $locale=$lang[\IPS\Lang::defaultLanguage()]->short;

                        // Pointer data
                        $pointer_data = (isset($_COOKIE['ct_pointer_data']) ? json_decode ($_COOKIE['ct_pointer_data']) : 0);
                        // Timezone from JS
                        $js_timezone =  (isset($_COOKIE['ct_timezone']) ? $_COOKIE['ct_timezone'] : 0);
                        //First key down timestamp
                        $first_key_press_timestamp = isset($_COOKIE['ct_fkp_timestamp']) ? $_COOKIE['ct_fkp_timestamp'] : 0;
                        // Page opened timestamp
                        $page_set_timestamp = (isset($_COOKIE['ct_ps_timestamp']) ? $_COOKIE['ct_ps_timestamp'] : 0);

                        $arr = array(
                            'cms_lang' => $locale,
                            'REFFERRER' => $_SERVER['HTTP_REFERER'],
                            'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
                            'mouse_cursor_positions' => $pointer_data,
                            'js_timezone' => $js_timezone,
                            'key_press_timestamp' => $first_key_press_timestamp,
                            'page_set_timestamp' => $page_set_timestamp,
                            'REFFERRER_PREVIOUS' => isset($_COOKIE['ct_prev_referer'])?$_COOKIE['ct_prev_referer']:null,
                            'cookies_enabled' => self::ctCookiesTest(),
                        );

                        $sender_info = json_encode($arr);

                        $arr = array(
                            'comment_type' => 'comment',
                        );

                        $post_info = json_encode($arr);

                        if($sender_info === FALSE)
                            $sender_info = '';
                        if($post_info === FALSE)
                            $post_info = '';

                        $config_key = $ct_access_key;

                        $ct = new Cleantalk();
                        $ct->server_url = \IPS\Settings::i()->ct_server_url;
                        $ct->work_url = \IPS\Settings::i()->ct_work_url;
                        $ct->server_ttl = \IPS\Settings::i()->ct_server_ttl;
                        $ct->server_changed = \IPS\Settings::i()->ct_server_changed;

                        $sender_email = filter_var($member->email, FILTER_SANITIZE_EMAIL);

                        // Trying to get email from POST
                        if (! $sender_email && isset($_POST['guest_email'])) {
							$sender_email = filter_var($_POST['guest_email'], FILTER_SANITIZE_EMAIL);
                        }

                        $ct_request = new CleantalkRequest();
                        $ct_request->auth_key = $config_key;

                        if(isset($_POST['guest_name']))
                            $ct_request->sender_nickname = $_POST['guest_name'];
                        else
                            $ct_request->sender_nickname = $member->name;

                        $ct_request->sender_ip          = CleantalkHelper::ip__get(array('real'), false);
                        $ct_request->x_forwarded_for    = CleantalkHelper::ip__get(array('x_forwarded_for'), false);
                        $ct_request->x_real_ip          = CleantalkHelper::ip__get(array('x_real_ip'), false);
                        $ct_request->sender_email = $sender_email;
                        $ct_request->sender_info = $sender_info;
                        $ct_request->post_info = $post_info;
                        $ct_request->agent = 'ipboard4-230';

                        $js_keys=Array();
                        for($i=-5;$i<=1;$i++){
                            $js_keys[]=md5(\IPS\Settings::i()->ct_access_key . '+' . \IPS\Settings::i()->email_in . date("Ymd",time()+86400*$i));
                        }

                        $ct_request->js_on = \in_array($_COOKIE['ct_checkjs'], $js_keys) ? 1 : 0;
                        $ct_request->submit_time = isset($_COOKIE['ct_ps_timestamp']) ? time() - \intval($_COOKIE['ct_ps_timestamp']) : 0;
                        $ct_request->message = trim(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/","\n", $comment_to_check));

                        $ct_result = $ct->isAllowMessage($ct_request);
                        if ($ct->server_change)
                        {
                            \IPS\Settings::i()->ct_work_url = $ct->work_url;
                            \IPS\Settings::i()->ct_server_ttl = $ct->server_ttl;
                            \IPS\Settings::i()->ct_server_changed = time();
                        }
                        if ($ct_result && isset($ct_result->errno) && $ct_result->errno == 0)
                        {
                            if(isset($ct_result->errno) && $ct_result->errno>0){
                                //sendErrorMessage("CleanTalk has some problems, errno is ".$ct_result->errno.", errstr is '".$ct_result->errstr."'")
                            }
                            if($ct_result->allow == 1){
                                // Not spammer.
                                //call_user_func_array( 'parent::save', \func_get_args() );
                                return \call_user_func_array( 'parent::create', \func_get_args() );
                            }else{
                                if(isset($_POST['topic_title']))
                                    $topic->delete();

                                if ( \IPS\Request::i()->isAjax() ){
                                    $result=Array("type"=>"error","message"=>$ct_result->comment);
                                    \IPS\Output::i()->json($result);
                                }else{
                                    \IPS\Output::i()->sidebar['enabled'] = FALSE;
                                    /*
                                    \IPS\Output::i()->sendOutput(
                                        \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate(
                                            "Forbidden",
                                            \IPS\Theme::i()->getTemplate( 'global', 'core' )->error( "Forbidden", $ct_result->comment, 403, "" ),
                                            array(
                                                'app' => \IPS\Dispatcher::i()->application ? \IPS\Dispatcher::i()->application->directory : NULL,
                                                'module' => \IPS\Dispatcher::i()->module ? \IPS\Dispatcher::i()->module->key : NULL,
                                                'controller' => \IPS\Dispatcher::i()->controller
                                            )
                                        ),
                                        200,
                                        'text/html',
                                        Array(),
                                        FALSE,
                                        FALSE
                                    );
                                    //*/
                                    $ct_die_html = '<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Blacklisted</title>
    <style type="text/css">
        html {
            background: #f1f1f1;
        }
        body {
            background: #fff;
            color: #444;
            font-family: "Open Sans", sans-serif;
            margin: 2em auto;
            padding: 1em 2em;
            max-width: 700px;
            -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
        }
        h1 {
            border-bottom: 1px solid #dadada;
            clear: both;
            color: #666;
            font: 24px "Open Sans", sans-serif;
            margin: 30px 0 0 0;
            padding: 0;
            padding-bottom: 7px;
        }
        #error-page {
            margin-top: 50px;
        }
        #error-page p {
            font-size: 14px;
            line-height: 1.5;
            margin: 25px 0 20px;
        }
        a {
            color: #21759B;
            text-decoration: none;
        }
        a:hover {
            color: #D54E21;
        }

            </style>
</head>
<body id="error-page">
    <p><center><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk.</b> Spam protection</center><br><br>
%ERROR_TEXT%
<script>setTimeout("history.back()", 5000);</script></p>
<p><a href=\'javascript:history.back()\'>&laquo; Back</a></p></body>';
                                    \IPS\Output::i()->sendOutput(str_replace('%ERROR_TEXT%', $ct_result->comment, $ct_die_html),
                                        200,
                                        'text/html',
                                        Array(),
                                        FALSE,
                                        FALSE
                                    );
                                    //}
                                }
                                die();
                                return \call_user_func_array( 'parent::create', \func_get_args() );
                            }
                        }
                    }
                    return \call_user_func_array( 'parent::create', \func_get_args() );
                }
                catch ( \RuntimeException $e ){

                    if ( method_exists( get_parent_class(), __FUNCTION__ ) )
                        return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
                    else
                        throw $e;
                }
            }
            catch ( \RuntimeException $e )
            {
                if ( method_exists( get_parent_class(), __FUNCTION__ ) )
                {
                    return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
                }
                else
                {
                    throw $e;
                }
            }
        }
        catch ( \RuntimeException $e )
        {
            if ( method_exists( get_parent_class(), __FUNCTION__ ) )
            {
                return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
            }
            else
            {
                throw $e;
            }
        }
    }

}
