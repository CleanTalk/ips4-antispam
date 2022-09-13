<?php

namespace Cleantalk\ApbctIPS;

class Helper extends \Cleantalk\Common\Helper {

    /**
     * Get fw stats from the storage.
     *
     * @return array
     * @example array( 'firewall_updating' => false, 'firewall_updating_id' => md5(), 'firewall_update_percent' => 0, 'firewall_updating_last_start' => 0 )
     * @important This method must be overloaded in the CMS-based Helper class.
     */
    public static function getFwStats()
    {
        //die( __METHOD__ . ' method must be overloaded in the CMS-based Helper class' );
        return array('firewall_updating_id' => isset(\IPS\Settings::i()->firewall_updating_id) ? \IPS\Settings::i()->firewall_updating_id : null, 'firewall_updating_last_start' => isset(\IPS\Settings::i()->firewall_updating_last_start) ? \IPS\Settings::i()->firewall_updating_last_start : 0, 'firewall_update_percent' => isset(\IPS\Settings::i()->firewall_update_percent) ? \IPS\Settings::i()->firewall_update_percent : 0);
    }

    /**
     * Save fw stats on the storage.
     *
     * @param array $fw_stats
     * @return bool
     * @important This method must be overloaded in the CMS-based Helper class.
     */
    public static function setFwStats( $fw_stats )
    {
        \IPS\Settings::i()->firewall_updating_id = isset($fw_stats['firewall_updating_id']) ? $fw_stats['firewall_updating_id'] : null;
        \IPS\Settings::i()->firewall_updating_last_start = isset($fw_stats['firewall_updating_last_start']) ? $fw_stats['firewall_updating_last_start'] : 0;
        \IPS\Settings::i()->firewall_update_percent = isset($fw_stats['firewall_update_percent']) ? $fw_stats['firewall_update_percent'] : 0;
    }

    /**
     * Implement here any actions after SFW updating finished.
     *
     * @return void
     */
    public static function SfwUpdate_DoFinisnAction()
    {
        \IPS\Settings::i()->sfw_last_check = time();
    }

    public static function saveError($error, $type = null)
    {
        $type = isset($type) ? $type : 'common';
        if ( is_string($error) ) {
            $current_errors = \IPS\Settings::i()->ct_errors;
            $current_errors = self::JsonDecode($current_errors);
            $current_errors[] = array('type' => $type, 'error_text' => $error);
            $current_errors = self::JsonEncode($current_errors);
            return \IPS\Settings::i()->changeValues(array('ct_errors' => $current_errors));
        }
        return false;
    }

    public static function getErrors($type)
    {
        if (is_string($type)){
            if ($type === 'all'){
                return self::JsonDecode(\IPS\Settings::i()->ct_errors);
            }
            $errors = self::JsonDecode(\IPS\Settings::i()->ct_errors);
            if ($errors && is_array($errors)) {
                foreach ( $errors as $error ) {
                    if ( isset($error['type'],$error['error_text']) && $error['type'] === $type ) {
                        $result[] = array('type' => $error['type'], 'error_text' => $error['error_text']);
                    }
                }
                return isset($result) ? $result : false;
            }
        }
        return false;
    }

    public static function clearErrors($type = null)
    {
        if (!isset($type)){
            return \IPS\Settings::i()->ct_errors = '';
        }
        if (is_string($type)){
            $current_errors_encoded = \IPS\Settings::i()->ct_errors;
            $current_errors = self::JsonDecode($current_errors_encoded);
            if ($current_errors && is_array($current_errors)){
                foreach ($current_errors as $error){
                    if (isset($error['type'],$error['error_text'])  && $error['type'] !== $type){
                        $cleared_errors[] = array('type'=>$error['type'],'error_text'=>$error['error_text']);
                    }
                }
                if (!empty($cleared_errors)){
                    if (self::JsonEncode($cleared_errors)){
                        return \IPS\Settings::i()->changeValues(array('ct_errors'=>$cleared_errors));
                    }
                }
                return \IPS\Settings::i()->changeValues(array('ct_errors'=>$current_errors_encoded));
            }
        }
        return false;
    }

    public static function drawOutputErrors($errors)
    {
        if ( empty($errors) ) {
            return;
        }

        if ( is_array($errors) ) {
            foreach ( $errors as $error ) {
                if ( is_array($error) && isset($error['type'],$error['error_text']) ) {
                            \IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate('global', 'core')->message('Anti-Spam '
                                . $error['type'] . ' error: '
                                . htmlspecialchars($error['error_text'])
                                , 'error');
                }
            }
        }
    }

    public static function JsonDecode($json){
        try {
            return json_decode($json,true);
        }
        catch (\Exception $e){
            self::saveError(json_last_error(),'json_decode');
            return false;
        }
    }

    public static function JsonEncode($string){
        try {
            return json_encode($string);
        }
        catch (\Exception $e){
            self::saveError(json_last_error(),'json_encode');
            return false;
        }
    }
}