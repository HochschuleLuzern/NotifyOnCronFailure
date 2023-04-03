<?php
/**
 * Copyright (c) 2017 Hochschule Luzern
 *
 * This file is part of the NotifyOnCronFailure-Plugin for ILIAS.

 * NotifyOnCronFailure-Plugin for ILIAS is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * NotifyOnCronFailure-Plugin for ILIAS is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with NotifyOnCronFailure-Plugin for ILIAS.  If not,
 * see <http://www.gnu.org/licenses/>.
 */

require_once './Services/Cron/classes/class.ilCronJob.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/NotifyOnCronFailure/classes/class.ilNotifyOnCronFailurePlugin.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/NotifyOnCronFailure/classes/class.ilNotifyOnCronFailureResult.php';
require_once './Services/Administration/classes/class.ilSetting.php';

/**
 * Class ilNotifyOnCronFailureNotify
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilNotifyOnCronFailureNotify extends ilCronJob
{
    const ID = "crnot_ny";
    
    private $cp;

    public function __construct()
    {
        $this->cp = new ilNotifyOnCronFailurePlugin();
    }
    
    public function getId(): string
    {
        return self::ID;
    }
    
    /**
     * @return bool
     */
    public function hasAutoActivation(): bool
    {
        return false;
    }
    
    /**
     * @return bool
     */
    public function hasFlexibleSchedule(): bool
    {
        return true;
    }
    
    /**
     * @return int
     */
    public function getDefaultScheduleType(): int
    {
        return self::SCHEDULE_TYPE_DAILY;
    }
    
    /**
     * @return array|inttitle
     */
    public function getDefaultScheduleValue(): int
    {
        return 1;
    }
    
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->cp->txt("title");
    }
    
    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->cp->txt("description");
    }
    
    /**
     * Defines whether or not a cron job can be started manually
     * @return bool
     */
    public function isManuallyExecutable(): bool
    {
        return true;
    }
    
    public function hasCustomSettings(): bool
    {
        return true;
    }
    
    public function run(): ilCronJobResult
    {
        include_once "Services/Cron/classes/class.ilCronJobResult.php";
        
        try {
            $data = ilCronManager::getCronJobData();
            $data_plugins = ilCronManager::getPluginJobs();
            
            foreach ($data_plugins as $data_plugin) {
                $data[] = $data_plugin[1];
            }

            $failed_jobs = [];
            
            foreach ($data as $job) {
                if ($job["job_result_status"] == ilCronJobResult::STATUS_CRASHED || $job["job_result_status"] == ilCronJobResult::STATUS_FAIL) {
                    $failed_jobs[] = array("job_id" => $job["job_id"], "job_result_message" => $job["job_result_message"]);
                }
            }

            if (!empty($failed_jobs)) {
                $this->sendEmail($failed_jobs);
            }
            return new ilNotifyOnCronFailureResult(ilNotifyOnCronFailureResult::STATUS_OK, 'Cron job terminated successfully.');
        } catch (Exception $e) {
            return new ilNotifyOnCronFailureResult(ilNotifyOnCronFailureResult::STATUS_OK, 'Cron job crashed: ' . $e->getMessage());
        }
    }
    
    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form): void
    {
        include_once 'Services/Form/classes/class.ilTextInputGUI.php';
        $users = new ilTextInputGUI(
            $this->cp->txt('users_to_notify'),
            'users_to_notify'
        );
        $users->setInfo($this->cp->txt('users_to_notify_desc'));
        
        $setting = new ilSetting("crnot");

        $setting = $setting->get('cron_notify_users_to_notify', "");
        $users->setValue($setting);
        $a_form->addItem($users);
    }
    
    public function saveCustomSettings(ilPropertyFormGUI $a_form): bool
    {
        $setting = new ilSetting("crnot");
        if ($_POST['users_to_notify'] != null) {
            $setting->set('cron_notify_users_to_notify', $_POST['users_to_notify']);
        }
    
        return true;
    }
    
    private function sendEmail($failures)
    {
        $setting = new ilSetting("crnot");
        $users = explode(",", $setting->get('cron_notify_users_to_notify', ""));
        $user_ids = [];
    
        include_once "./Services/User/classes/class.ilObjUser.php";
        foreach ($users as $user) {
            $user_ids[] = ilObjUser::getUserIdByLogin(trim($user));
        }
            
        if ($setting) {
            include_once "./Services/Notification/classes/class.ilSystemNotification.php";
            $ntf = new ilSystemNotification();
            $ntf->setLangModules(array($this->cp->getPrefix()));
            $ntf->setSubjectLangId($this->cp->getPrefix() . "_" . "notification_subject");
            $ntf->setIntroductionLangId($this->cp->getPrefix() . "_" . "notification_body");
            $text = '';
            foreach ($failures as $failure) {
                $text .= $failure['job_id'] . ' (' . $this->cp->txt('cron_status') . ': ' . $failure['job_result_message'] . ")\n";
            }
            
            $ntf->addAdditionalInfo($this->cp->getPrefix() . "_" . "failed_crs", $text, true);
            
            $ntf->sendMail($user_ids);
        }
    }
}
