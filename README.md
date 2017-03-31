# NotifyOnCronFailure

NotifyOnCronFailure is a Cron-Plugin that checks for failed or crashed jobs and notifies a selected set of people

**Minimum ILIAS Version:**
5.1.0

**Maximum ILIAS Version:**
5.1.999

**Responsible Developer:**
Stephan Winiker - stephan.winiker@hslu.ch

**Supported Languages:**
German, English

### Quick Installation Guide
1. Copy the content of this folder in <ILIAS_directory>/Customizing/global/plugins/Services/Cron

2. Access ILIAS, go to the administration menu and select "Plugins" in the menu on the right.

3. Look for the NotifyOnCronFailure plugin in the table, press the "Action" button and select "Update".

4. Press the "Action" button and select "Refresh Languages" to update the language-files.

5. Got to the administration menu, select "General Settings" and then "Cron Jobs".

6. Look for "Notify on Cron Crash or Failure " in the table and click "Edit".

7. Choose your schedule and enter the usernames of all users who should be notified.

8. Save and activate the Cron-Job.

***Notice***
To avoid for this plugin to be deactivated itself, it always returns a successful run, if there is a problem, it will be shown in the "Re
sult Info" column.