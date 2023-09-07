<?php

namespace Cleantalk\ApbctIPS;

class Cron extends \Cleantalk\Common\Cron {

    public function saveTasks($tasks)
    {
        $cron_option_name = $this->cron_option_name;
        $cron = array('last_start' => time(), 'tasks' => $tasks);
        $cron = Helper::JsonEncode($cron);
        \IPS\Settings::i()->changeValues([$cron_option_name => $cron]);
    }

    /**
     * Getting all tasks
     *
     * @return array
     */
    public function getTasks()
    {
        $cron_option_name = $this->cron_option_name;
        $cron = Helper::JsonDecode(\IPS\Settings::i()->$cron_option_name);
        return isset($cron['tasks']) ? $cron['tasks'] : null;
    }

    /**
     * Save option with tasks
     *
     * @return int timestamp
     */
    public function getCronLastStart()
    {
        $cron_option_name = $this->cron_option_name;
        $cron = Helper::JsonDecode(\IPS\Settings::i()->$cron_option_name);
        return isset($cron['last_start']) ? $cron['last_start'] : 0;
    }

    /**
     * Save timestamp of running Cron.
     *
     * @return bool
     */
    public function setCronLastStart()
    {
        $cron_option_name = $this->cron_option_name;
        $cron = array('last_start' => time(), 'tasks' => $this->getTasks());
        $cron = Helper::JsonEncode($cron);
        \IPS\Settings::i()->changeValues([$cron_option_name => $cron]);
        return true;
    }
}