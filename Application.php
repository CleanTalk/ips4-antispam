<?php
/**
 * @brief		Antispam by Cleantalk Application Class
 * @author		<a href='https://cleantalk.org'>CleanTalk team</a>
 * @copyright	(c) 2020 CleanTalk team
 * @package		Invision Community
 * @subpackage	Antispam by Cleantalk
 * @since		26 Oct 2020
 * @version		
 */
 
namespace IPS\antispambycleantalk;

require_once(\IPS\Application::getRootPath().'/applications/antispambycleantalk/sources/autoload.php');

use Cleantalk\ApbctIPS\Helper as CleantalkHelper;
use Cleantalk\ApbctIPS\DB;
use Cleantalk\Common\Firewall\Firewall;

define('APBCT_TBL_FIREWALL_DATA', \IPS\DB::i()->prefix . 'cleantalk_sfw');      // Table with firewall data.
define('APBCT_TBL_FIREWALL_LOG',  \IPS\DB::i()->prefix . 'cleantalk_sfw_logs'); // Table with firewall logs.
define('APBCT_TBL_AC_LOG',        \IPS\DB::i()->prefix . 'cleantalk_ac_log');   // Table with firewall logs.
define('APBCT_TBL_AC_UA_BL',      \IPS\DB::i()->prefix . 'cleantalk_ua_bl');    // Table with User-Agents blacklist.
define('APBCT_TBL_SESSIONS',      \IPS\DB::i()->prefix . 'cleantalk_sessions'); // Table with session data.
define('APBCT_SPAMSCAN_LOGS',     \IPS\DB::i()->prefix . 'cleantalk_spamscan_logs'); // Table with session data.
define('APBCT_SELECT_LIMIT',      5000); // Select limit for logs.
define('APBCT_WRITE_LIMIT',       5000); // Write limit for firewall data.

/**
 * Antispam by Cleantalk Application Class
 */
class _Application extends \IPS\Application
{
    public function installOther() {

        // Show admin notification about empty key
        $coreApp = \IPS\Application::load('core');
        if( \version_compare( $coreApp->version, '4.4.0') >= 0 ) {
            if( ! \IPS\Settings::i()->ct_access_key ) {
                \IPS\core\AdminNotification::send( 'antispambycleantalk', 'Notification', 'keyIsEmpty', true );
            }
        }

    }
	static public function apbct_sfw_update($access_key = '') {
	    if( empty( $access_key ) ){
        	$access_key = \IPS\Settings::i()->ct_access_key;
        	if (empty($access_key)) {
        		return false;
        	}
	    }     
        $firewall = new Firewall(
            $access_key,
            DB::getInstance(),
            APBCT_TBL_FIREWALL_LOG
        );
        $firewall->setSpecificHelper( new CleantalkHelper() );
        $fw_updater = $firewall->getUpdater( APBCT_TBL_FIREWALL_DATA );
        $fw_updater->update();
	    
	}
	static public function apbct_sfw_send_logs($access_key = '') {
	    if( empty( $access_key ) ){
        	$access_key = \IPS\Settings::i()->ct_access_key;
        	if (empty($access_key)) {
        		return false;
        	}
	    } 

        $firewall = new Firewall( $access_key, DB::getInstance(), APBCT_TBL_FIREWALL_LOG );
		$firewall->setSpecificHelper( new CleantalkHelper() );
        $result = $firewall->sendLogs();

        return true;
	}
}