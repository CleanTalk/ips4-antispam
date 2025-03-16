<?php
/**
 * @brief        Member Listener
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * {subpackage}
 * @since        12 Mar 2025
 */

namespace IPS\antispambycleantalk\listeners;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Cleantalk\Antispam\Cleantalk;
use Cleantalk\Antispam\CleantalkRequest;
use Cleantalk\Common\Helper as CleantalkHelper;
use IPS\Events\ListenerType\MemberListenerType;
use IPS\Member as MemberClass;

use function defined;

if ( !defined('\IPS\SUITE_UNIQUE_KEY') ) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Member Listener
 */
class Register extends MemberListenerType
{
    private static function getCheckJSArray()
    {
        $result = array();

        for ( $i = -5; $i <= 1; $i++ ) {
            $result[] = md5(
                \IPS\Settings::i()->ct_access_key . '+' . \IPS\Settings::i()->email_in . date(
                    "Ymd",
                    time() + 86400 * $i
                )
            );
        }

        return $result;
    }

    private static function ctCookiesTest()
    {
        if ( isset($_COOKIE['ct_cookies_test']) ) {
            $cookie_test = json_decode(stripslashes($_COOKIE['ct_cookies_test']), true);

            $check_srting = trim(\IPS\Settings::i()->ct_access_key);
            foreach ( $cookie_test['cookies_names'] as $cookie_name ) {
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            }
            unset($cokie_name);

            if ( $cookie_test['check_value'] == md5($check_srting) ) {
                return 1;
            }

            return 0;
        }

        return null;
    }

    public function onCreateAccount(MemberClass $member)
    {
        $ct_access_key = \IPS\Settings::i()->ct_access_key;

        $sender_info = '';
        $post_info = '';
        $lang = \IPS\Lang::getEnabledLanguages();
        $locale = $lang[\IPS\Lang::defaultLanguage()]->short;

        // Pointer data
        $pointer_data = (isset($_COOKIE['ct_pointer_data']) ? json_decode($_COOKIE['ct_pointer_data']) : 0);
        // Timezone from JS
        $js_timezone = (isset($_COOKIE['ct_timezone']) ? $_COOKIE['ct_timezone'] : 0);
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
            'REFFERRER_PREVIOUS' => isset($_COOKIE['ct_prev_referer']) ? $_COOKIE['ct_prev_referer'] : null,
            'cookies_enabled' => self::ctCookiesTest(),
            'site_url' => $_SERVER['HTTP_HOST'],
        );
        $sender_info = json_encode($arr);

        $arr = array(
            'comment_type' => 'register',
        );

        $post_info = json_encode($arr);

        if ( $sender_info === false ) {
            $sender_info = '';
        }
        if ( $post_info === false ) {
            $post_info = '';
        }

        $config_key = $ct_access_key;
        $ct = new Cleantalk();
        $ct->server_url = \IPS\Settings::i()->ct_server_url;
        $ct->work_url = \IPS\Settings::i()->ct_work_url;
        $ct->server_ttl = \IPS\Settings::i()->ct_server_ttl;
        $ct->server_changed = \IPS\Settings::i()->ct_server_changed;

        $sender_email = filter_var($_POST['email_address'], FILTER_SANITIZE_EMAIL);

        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = $config_key;
        $ct_request->sender_nickname = $_POST['username'];

        $ct_request->sender_ip = CleantalkHelper::ip__get(array('real'), false);
        $ct_request->x_forwarded_for = CleantalkHelper::ip__get(array('x_forwarded_for'), false);
        $ct_request->x_real_ip = CleantalkHelper::ip__get(array('x_real_ip'), false);

        $ct_request->sender_email = $sender_email;
        $ct_request->sender_info = $sender_info;
        $ct_request->post_info = $post_info;
        $ct_request->agent = 'ipboard4-230';

        $ct_request->js_on = \in_array($_COOKIE['ct_checkjs'], self::getCheckJSArray()) ? 1 : 0;
        $ct_request->submit_time = isset($_COOKIE['ct_ps_timestamp']) ? time() - (int)$_COOKIE['ct_ps_timestamp'] : 0;
        $ct_result = $ct->isAllowUser($ct_request);

        if ( $ct->server_change ) {
            \IPS\Settings::i()->ct_work_url = $ct->work_url;
            \IPS\Settings::i()->ct_server_ttl = $ct->server_ttl;
            \IPS\Settings::i()->ct_server_changed = time();
        }
        if ( $ct_result && isset($ct_result->errno) && $ct_result->errno == 0 ) {
            if ( isset($ct_result->errno) && $ct_result->errno > 0 ) {
                //sendErrorMessage("CleanTalk has some problems, errno is ".$ct_result->errno.", errstr is '".$ct_result->errstr."'")
            }

            if ( $ct_result->allow == 0 ) {
                // Spammer - set user status as "spam"
                $member->flagAsSpammer();
            }
        }
    }
}
