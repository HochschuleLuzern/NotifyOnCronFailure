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

class ilNotifyOnCronFailureNotify extends ilCronJob {
	
	const ID = "crnot_ny";
	
	private $cp;

	public function __construct() {
		$this->cp = new ilNotifyOnCronFailurePlugin();
	}
	
	public function getId() {
		return self::ID;
	}
	
	/**
	 * @return bool
	 */
	public function hasAutoActivation() {
		return true;
	}
	
	/**
	 * @return bool
	 */
	public function hasFlexibleSchedule() {
		return true;
	}
	
	/**
	 * @return int
	 */
	public function getDefaultScheduleType() {
		return self::SCHEDULE_TYPE_DAILY;
	}
	
	/**
	 * @return array|inttitle
	 */
	public function getDefaultScheduleValue() {
		return 1;
	}
	
	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->cp->txt("title");
	}
	
	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->cp->txt("description");
	}
	
	/**
	 * Defines whether or not a cron job can be started manually
	 * @return bool
	 */
	public function isManuallyExecutable()
	{
		return true;
	}
	
	public function hasCustomSettings()
	{
		return true;
	}
	
	public function run() {
		include_once "Services/Cron/classes/class.ilCronJobResult.php";
		
		try {
			$data = ilCronManager::getCronJobData();

			$failed_jobs;
			
			foreach ($data as $job) {
				if ($job["job_status"] == ilCronJobResult::STATUS_CRASHED || $job["job_status"] == ilCronJobResult::STATUS_FAIL) {
					$failed_jobs[] = array("job_id" => $job["job_id"], "job_status" => $job["job_status"]);
				}
			}

			if (isset($failed_jobs)) {
				$this->sendEmail($failed_jobs);
			}
			return new ilNotifyOnCronFailureResult(ilNotifyOnCronFailureResult::STATUS_OK, 'Cron job terminated successfully.');
		} catch (Exception $e) {
			return new ilNotifyOnCronFailureResult(ilNotifyOnCronFailureResult::STATUS_OK, 'Cron job crashed: ' . $e->getMessage());
		}
		
	}
	
	public function executeCommand()
	{
		global $ilCtrl, $tpl, $ilUser;
		$cmd = $ilCtrl->getCmd();
		echo "123";
		die;
	}
	
	public function addCustomSettingsToForm(ilPropertyFormGUI $a_form)
	{
		global $ilSetting;
	
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
	
	public function saveCustomSettings(ilPropertyFormGUI $a_form)
	{
		$setting = new ilSetting("crnot");
		if ($_POST['users_to_notify'] != null) {
			$setting->set('cron_notify_users_to_notify', $_POST['users_to_notify']);
		}
	
		return true;
	}
	
	private function sendEmail($failures) {
		global $ilSetting;
	
		$setting = new ilSetting("crnot");
		$users = explode(",", $setting->get('cron_notify_users_to_notify', ""));
		$user_ids;
	
		include_once "./Services/User/classes/class.ilObjUser.php";
		foreach ($users as $user) {
			$user_ids[] = ilObjUser::getUserIdByLogin(trim($user));
		}
			
		if ($setting) {
			include_once "./Services/Notification/classes/class.ilSystemNotification.php";
			$ntf = new ilSystemNotification();
			$ntf->setLangModules(array($this->cp->getPrefix()));
			$ntf->setSubjectLangId($this->cp->getPrefix()."_"."notification_subject");
			$ntf->setIntroductionLangId($this->cp->getPrefix()."_"."notification_body");
			foreach($failures as $failure) {
				$ntf->addAdditionalInfo($this->cp->getPrefix()."_"."cron_id", $failure['job_id']);
				$ntf->addAdditionalInfo($this->cp->getPrefix()."_"."status_cron", $failure['job_status']);
			}
	
			$ntf->sendMail($user_ids);
		}
	}
}