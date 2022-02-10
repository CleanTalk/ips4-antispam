<?php

namespace Cleantalk\ApbctIPS;

class RemoteCalls extends \Cleantalk\Common\RemoteCalls {
    /**
     * SFW update
     *
     * @return string
     */
    public function action__sfw_update()
    {
        return \IPS\antispambycleantalk\_Application::apbct_sfw_update( $this->api_key );
    }

    /**
     * SFW send logs
     *
     * @return string
     */
    public function action__sfw_send_logs()
    {
        return \IPS\antispambycleantalk\_Application::apbct_sfw_send_logs( $this->api_key );
    }

    public function action__sfw_update__write_base()
    {
        return \IPS\antispambycleantalk\_Application::apbct_sfw_update( $this->api_key );
    }
    /**
     * Get available remote calls from the storage.
     *
     * @return array
     */
    protected function getAvailableRcActions()
    {
        $remote_calls = \IPS\Settings::i()->ct_remote_calls;
        return ($remote_calls && !empty($remote_calls)) ? $remote_calls : array('close_renew_banner' => array('last_call' => 0, 'cooldown' => self::COOLDOWN), 'sfw_update' => array('last_call' => 0, 'cooldown' => self::COOLDOWN), 'sfw_send_logs' => array('last_call' => 0, 'cooldown' => self::COOLDOWN), 'sfw_update__write_base' => array('last_call' => 0, 'cooldown' => 0));
    }

    /**
     * Set last call timestamp and save it to the storage.
     *
     * @param array $action
     * @return void
     */
    protected function setLastCall( $action )
    {
        $remote_calls = $this->getAvailableRcActions();
        $action_name = array_keys($action)[0];
        $remote_calls[$action_name]['last_call'] = time();
        \IPS\Settings::i()->ct_remote_calls = $remote_calls;
    }
}