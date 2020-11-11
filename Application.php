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
                \IPS\core\AdminNotification::send( 'antispambycleantalk', 'antispambycleantalk', 'keyIsEmpty', true );
            }
        }

    }
}