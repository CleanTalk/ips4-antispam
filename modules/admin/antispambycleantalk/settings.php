<?php


namespace IPS\antispambycleantalk\modules\admin\antispambycleantalk;

require_once(\IPS\Application::getRootPath().'/applications/antispambycleantalk/sources/autoload.php');


use Cleantalk\Antispam\Cleantalk as Cleantalk;
use Cleantalk\Antispam\CleantalkRequest as CleantalkRequest;
use Cleantalk\Antispam\CleantalkResponse as CleantalkResponse;
use Cleantalk\ApbctIPS\Helper as CleantalkHelper;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * apbct_settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
    /**
     * Execute
     *
     * @return  void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
        parent::execute();
    }

    /**
     * ...
     *
     * @return  void
     */
    protected function manage()
    {
        // This is the default method if no 'do' parameter is specified
        //Errors handler
        CleantalkHelper::drawOutputErrors(CleantalkHelper::getErrors('users_spam_check'));
        # Build Form
        $form = new \IPS\Helpers\Form;
        $form->add( new \IPS\Helpers\Form\YesNo( 'ct_moderate_new', \IPS\Settings::i()->ct_moderate_new, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ct_moderate_new' ) ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'ct_contact_form_check', \IPS\Settings::i()->ct_contact_form_check, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ct_contact_form_check' ) ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'ct_show_link', \IPS\Settings::i()->ct_show_link, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ct_show_link' ) ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'ct_cleantalk_sfw', \IPS\Settings::i()->ct_cleantalk_sfw, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ct_cleantalk_sfw' ) ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'ct_spam_check',\IPS\Settings::i()->ct_spam_check, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ct_spam_check') ) );
        $form->add( new \IPS\Helpers\Form\Number( 'ct_posts_to_check', (empty(\IPS\Settings::i()->ct_posts_to_check) ? 10 : \IPS\Settings::i()->ct_posts_to_check), FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ct_posts_to_check', 'min' => 1, 'max' => 10000) ) );
        $form->add( new \IPS\Helpers\Form\Text( 'ct_access_key', (empty(\IPS\Settings::i()->ct_access_key) ? NULL : \IPS\Settings::i()->ct_access_key), FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ct_access_key', 'placeholder' => 'Enter the key') ) );

        /* Save */
        if ( $values = $form->values( TRUE ) )
        {

            $values = $form->values();

            $ct = new Cleantalk();
            $ct->server_url = \IPS\Settings::i()->ct_server_url;
            $ct->work_url = \IPS\Settings::i()->ct_work_url;
            $ct->server_ttl = \IPS\Settings::i()->ct_server_ttl;
            $ct->server_changed = \IPS\Settings::i()->ct_server_changed;


            $ct_request = new CleantalkRequest();
            $ct_request->auth_key = $values['ct_access_key'];
            $ct_request->feedback = '0:ipboard4-221';
            $ct->sendFeedback($ct_request);
            if ($ct->server_change)
            {
                \IPS\Settings::i()->ct_work_url = $ct->work_url;
                \IPS\Settings::i()->ct_server_ttl = $ct->server_ttl;
                \IPS\Settings::i()->ct_server_changed = time();
            }

            if( $values['ct_cleantalk_sfw'] == 1 ){
                \IPS\antispambycleantalk\_Application::apbct_sfw_update( $values['ct_access_key']);
                \IPS\antispambycleantalk\_Application::apbct_sfw_send_logs( $values['ct_access_key']);
            }

            $form->saveAsSettings( $values );
            CleantalkHelper::clearErrors();
        }

        // Show admin notification about empty key
        $coreApp = \IPS\Application::load('core');
        if( \version_compare( $coreApp->version, '4.4.0') >= 0 ) {
            if( ! \IPS\Settings::i()->ct_access_key ) {
                \IPS\core\AdminNotification::send( 'antispambycleantalk', 'Notification', 'keyIsEmpty', true );
            } else {
                \IPS\core\AdminNotification::remove( 'antispambycleantalk', 'Notification', 'keyIsEmpty' );
            }
        }


        /* Output */
        \IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( "app=antispambycleantalk&module=antispambycleantalk&controller=settings" ), 'antispambycleantalk_settings' );
        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('antispambycleantalk_settings');
        \IPS\Output::i()->output .= $form;

        //add a block with custom table of spammers found
        if ( \IPS\Settings::i()->ct_spam_check ) {
            //get spammer users (has a set bitoption)
            $select = \IPS\_Db::i()->select('member_id,name,email,ip_address', 'core_members', array('members_bitoptions=?', '65537'))->setKeyField('member_id');
            foreach ( $select as $key => $value ) {
                $spammers[$value['member_id']] = array(
                    'email' => $value['email'],
                    'name' => $value['name'],
                    'ip_address' => $value['ip_address'],
                );
            }
            //get history of last check
            $select = \IPS\_Db::i()->select('log_member,log_date', 'core_member_history', array('log_type=? and log_data=?', 'ct_check', '{"spammer":"1"}'))->setKeyField('log_member');
            foreach ( $select as $key => $value ) {
                $history[$key] = array(
                    'log_date' => $value['log_date']
                );
            }
            //get last 10 records
            $history = !empty($history) ? \array_slice($history, -10, 10, true) : null;
            //draw block HTML
            $block = '<table class="ipsTable"><tbody>';
            $block .= '<tr><td colspan="5" style="text-align: center"><h3>List of last 10 spammers found</h3></td></tr>';
            $block .= '<tr><th>Member ID</th><th>Username</th><th>Email</th><th>IP address</th><th>Last check</th></tr>';

            if ( !empty($spammers) && \is_array($spammers) ) {
                foreach ( $spammers as $key => $value ) {
                    if ( !empty($history) && \array_key_exists($key, $history) ) {
                        $last_check = !empty($history[$key]['log_date']) ? date('Y-m-d H:i:s', (int)$history[$key]['log_date']) : 'N\A';
                        $name = !empty($value['name']) ? htmlspecialchars($value['name']) : 'N\A';
                        $email = !empty($value['email']) ? htmlspecialchars($value['email']) : 'N\A';
                        $ip_address = !empty($value['ip_address']) ? htmlspecialchars($value['ip_address']) : 'N\A';
                        $block .= '<tr><td>' . $key . '</td>';
                        $block .= '<td>' . $name . '</td>';
                        $block .= '<td>' . $email . '</td>';
                        $block .= '<td>' . $ip_address . '</td>';
                        $block .= '<td>' . $last_check . '</td></tr>';
                    }
                }
            } else {
                $block .= '<tr><td colspan="5" style="text-align: center">No spammers found.</td></tr>';
            }

            $button = '<a target="_blank" title="Run spam check and proceed to the full list of users" class="ipsUrl" href="?app=core&module=members&controller=members&sortby=joined&filter=members_filter_spam&ct_spam_check_run=1" >Click to check users for spam</a>';
            $block .= '<tr><td colspan="5" style="text-align: center">' . $button . '</td></tr>';
            $block .= '</tbody></table>';

            //render block
            \IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate('global', 'core')->block(NULL, $block, TRUE, 'ipsPad');
        }
    }

    // Create new methods with the same name as the 'do' parameter which should execute it
}