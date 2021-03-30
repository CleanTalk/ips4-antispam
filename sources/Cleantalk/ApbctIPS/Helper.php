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
}