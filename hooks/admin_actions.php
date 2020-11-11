//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class antispambycleantalk_hook_admin_actions extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {

    $html = '';
    if(isset($_COOKIE['cleantalk_close_banner']))
        \IPS\Settings::i()->ct_cleantalk_show_banner = 0;

    $show_banner = \intval(\IPS\Settings::i()->ct_cleantalk_show_banner);
    if($show_banner == 1)
        $html = "<div style='width:99%;background: #90EE90; padding:10px;border: 2px dashed green;margin:3px;font-size:16px;text-align:center;' id='cleantalk_banner'>Like antispam by CleanTalk? <a href='https://community.invisionpower.com/files/file/7706-anti-spam-ips4/' target='_blank'>Leave a review!</a><div style='float:right;margin-top:-5px;margin-right:-5px;'><a href=# style='text-decoration:none;font-size:14px;font-weight:600;' onclick='jQuery(\"#cleantalk_banner\").hide(\"slow\");document.cookie=\"cleantalk_close_banner = 1; path=/; expires= Fri, 31 Dec 9999 23:59:59 GMT\";'>X</a></div></div>";

    return array_merge_recursive(array (
        'globalTemplate' =>
            array (
                0 => array (
                    'selector' => '#acpPageHeader',
                    'type' => 'add_after',
                    'content' => $html,
                ),
            ),
    ), parent::hookData() );

}
/* End Hook Data */


}
