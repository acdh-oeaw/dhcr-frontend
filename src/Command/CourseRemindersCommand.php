<?php

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Mailer;
use Exception;

class CourseRemindersCommand extends Command
{
    public $Courses = null;
    public $Users = null;
    public $Logentries = null;

    private function getModeratorEmails()
    {
        $moderators = $this->Users->find()->where([
            'user_role_id' => 2,
            'active' => 1,
        ]);
        $countriesModeratorEmails = [];
        foreach ($moderators as $moderator) {
            $countriesModeratorEmails[$moderator->country_id][] = $moderator->email;
        }
        return $countriesModeratorEmails;
    }

    private function getUseradminEmails()
    {
        $useradmins = $this->Users->find()->where([
            'user_admin' => 1,
            'active' => 1,
        ]);
        $useradminsEmails = [];
        foreach ($useradmins as $useradmin) {
            $useradminsEmails[] = $useradmin->email;
        }
        return $useradminsEmails;
    }

    private function getOutdatedCourses()
    {
        $outdatedCourses = $this->Courses->find('all',  [
            'contain' => ['Users'],
            'order' => ['Courses.updated' => 'desc']
        ])->where(
            [
                'Courses.updated <' => Configure::read('courseWarnDate'),
                'Courses.updated >' => Configure::read('courseArchiveDate'),
                'Courses.active' => 1,
                'Courses.deleted' => 0,
            ]
        );
        return $outdatedCourses;
    }

    private function createReminderItems($outdatedCourses)
    {
        foreach ($outdatedCourses as $outdatedCourse) {
            $email = $outdatedCourse->user->email;
            $reminderItems[$email]['name'] = $outdatedCourse->user->first_name . ' ' . $outdatedCourse->user->last_name;
            $reminderItems[$email][] = $outdatedCourse;
        }
        return $reminderItems;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->loadModel('DhcrCore.Courses');
        $this->loadModel('Users');
        $this->loadModel('Logentries');
        $waitPeriod = new FrozenTime('-10 Days');    // no mail will be sent within $waitPeriod days after previous mail

        // needed for logging
        $scriptName = str_replace('Command', '', basename(__FILE__, '.php'));

        // clean up old log entries after 3 months
        $oldEntries = $this->Logentries->find()->where(['created <' => new FrozenTime('-3 Months')]);
        $io->out('Cleaned up old log entries: ' . $oldEntries->count());
        $this->Logentries->deleteManyOrFail($oldEntries);

        // guard against sending mails from dev or test
        if (env('DHCR_BASE_URL') != 'https://dhcr.clarin-dariah.eu/') {
            die("ERROR: Reminder mails should only be sent in production. Program aborted.\n");
        }

        $io->out('~~~ Started Course Reminders ~~~');

        $countriesModeratorEmails = $this->getModeratorEmails();
        $io->out('Countries with moderators: ' . sizeof(($countriesModeratorEmails)));

        $useradminsEmails = $this->getUseradminEmails();
        $io->out('User Admins: ' . sizeof($useradminsEmails));

        $outdatedCourses = $this->getOutdatedCourses();
        $io->out('Outdated Courses: ' . $outdatedCourses->count());

        $reminderItems = $this->createReminderItems($outdatedCourses, $io);
        $io->out('Reminder Items: ' . sizeof($reminderItems));

        $totalMails = 0;
        foreach ($reminderItems as $email => $reminderItem) {
            // guard against sending too frequent reminder mails
            if ($reminderItem[0]['last_reminder'] > $waitPeriod) {
                $errorMessage = 'Previous reminder sent too recently. First course ID: ' . $reminderItem[0]['id'];
                $this->Logentries->createLogEntry(
                    '50',
                    '586',
                    $scriptName,
                    'Spam guard',
                    $errorMessage
                );
                continue;
            }

            $to = [$email];
            // determine if a CC should be sent
            $sendCc = false;
            foreach ($reminderItem as $key => $course) {
                if (!is_object($course)) {
                    continue;
                }
                if ($course->updated < Configure::read('courseModWarnDate')) {
                    $sendCc = true;
                }
            }

            // check if a moderator is available, otherwise send to useradmins
            if ($sendCc) {
                if (array_key_exists($reminderItem[0]['country_id'], $countriesModeratorEmails)) {
                    $cc = $countriesModeratorEmails[$reminderItem[0]['country_id']];
                } else {
                    $cc = $useradminsEmails;
                }
            }

            $logDescription = 'To: ' . $to[0] . "\n";

            // guard against sending two mails to the same user
            if ($sendCc && ($to == $cc)) {
                $sendCc = false;
                $logDescription .= "Cc: No, owner is also mod\n";
            }

            // startup period: don't send CCs until fixed date 19-09-2022
            if ($sendCc && (new FrozenTime('Now') < new FrozenTime('2022-09-19 00:00:00'))) {
                $sendCc = false;
                $logDescription .= "Cc: disabled until 19-09-22\n";
            }

            if ($sendCc) {
                $logDescription .= 'Cc: ';
                foreach ($cc as $c) {
                    $logDescription .= $c . ' ';
                }
                $logDescription .= "\n";
            }

            // set last_reminder timestamp and add course id's to log description
            $logDescription .= 'Expired courses: ' . (sizeof($reminderItem) - 1) . "\nIDs: ";
            foreach ($reminderItem as $key => $course) {
                if (!is_object($course)) {
                    continue;
                }
                $logDescription .= $course['id'] . ' ';
                $course->set('last_reminder', new FrozenTime('Now'));
                if (!$this->Courses->save($course)) {
                    $errorMessage = 'Could not update last_reminder.';
                    $this->Logentries->createLogEntry(
                        '90',
                        '586',
                        $scriptName,
                        'DB error',
                        $errorMessage
                    );
                    die('ERROR: ' . $errorMessage . " Program aborted.\n");
                }
            }
            // log reminderItem
            $this->Logentries->createLogEntry(
                '30',
                '586',
                $scriptName,
                'Sent reminder mail',
                $logDescription
            );

            // send mail
            $subject = '[DH Course Registry] Expired course';
            if (sizeof($reminderItem) > 2) {
                $subject .= 's';
            }
            try {
                $mailer = new Mailer();
                if ($sendCc) {
                    $mailer->setCc($cc);
                }
                $mailer->setFrom(env('APP_MAIL_DEFAULT_FROM'))
                    ->setSender(env('APP_MAIL_DEFAULT_FROM'))
                    ->setReturnPath(env('APP_MAIL_DEFAULT_FROM'))
                    ->setTo($to)
                    ->setReplyTo([env('APP_MAIL_DEFAULT_REPLY_TO') => 'DH Course Registry'])
                    ->setSubject($subject)
                    ->setViewVars('reminderItem', $reminderItem)
                    ->viewBuilder()->setTemplate('course_reminders/reminder');
                $mailer->deliver();
                $totalMails++;
            } catch (Exception $ex) {
                $logSubject = 'Error sending mail';
                $logDescription = 'First course ID: ' . $reminderItem[0]['id'];
                $this->Logentries->createLogEntry(
                    '90',
                    '586',
                    $scriptName,
                    $logSubject,
                    $logDescription
                );
                die('ERROR: ' . $logSubject . ' ' . $logDescription);
            }

            // progress indicator
            echo '.';
        }
        echo "\n";

        // log summary stats
        $logDescription =
            'Outdated Courses: ' . $outdatedCourses->count() . "\n" .
            'Reminder Items: ' . sizeof($reminderItems) . "\n" .
            'Mails sent: ' . $totalMails;
        $this->Logentries->createLogEntry(
            '10',
            '586',
            $scriptName,
            'Finished. Stats:',
            $logDescription
        );

        $io->out('Mails sent: ' . $totalMails);
        $io->out('~~~ Finished Course Reminders ~~~');
    }
}
