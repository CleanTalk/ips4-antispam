<?php

namespace Cleantalk\ApbctIPS;

class Cron extends \Cleantalk\Common\Cron {

    public function saveTasks($tasks)
    {
        $cron_option_name = $this->cron_option_name;
        \IPS\Settings::i()->cron_option_name = array('last_start' => time(), 'tasks' => $tasks);
    }

    /**
     * Getting all tasks
     *
     * @return array
     */
    public function getTasks()
    {
        // TODO: Implement getTasks() method.
        $cron_option_name = $this->cron_option_name;
        return isset(\IPS\Settings::i()->cron_option_name->tasks) ? \IPS\Settings::i()->cron_option_name->tasks : null;
    }

    /**
     * Save option with tasks
     *
     * @return int timestamp
     */
    public function getCronLastStart()
    {
        // TODO: Implement getCronLastStart() method.
        $cron_option_name = $this->cron_option_name;
        return isset(\IPS\Settings::i()->cron_option_name->last_start) ? \IPS\Settings::i()->cron_option_name->last_start : 0;
    }

    /**
     * Save timestamp of running Cron.
     *
     * @return bool
     */
    public function setCronLastStart()
    {
        $cron_option_name = $this->cron_option_name;
        \IPS\Settings::i()->cron_option_name = array('last_start' => time(), 'tasks' => $this->getTasks());
        return true;
    }
}