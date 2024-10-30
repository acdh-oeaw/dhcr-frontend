<?php

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenTime;

/*  This file should be run on build to create the JSON file for the seach bar
    As well it should be run automatically on regular basis (hourly) */

class GenSearchListCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->loadModel('DhcrCore.Courses');
        $this->loadModel('Logentries');

        $io->out('~~~ Started: Generate Search List ~~~');

        $courses = $this->Courses->find('all',  [
            'contain' => ['Institutions', 'CourseTypes'],
            'order' => ['Courses.name' => 'asc']
        ])->where(
            [
                'active' => 1,
                'deleted' => 0,
                'Courses.updated >' => new FrozenTime('-489 Days'),
                'approved' => 1
            ]
        );
        $searchList = [];
        foreach ($courses as $course) {
            $searchList[] = trim($course->name) . '  -  ' . trim($course->institution->name)
                . '  -  ' . trim($course->course_type->name);
        }

        $file = fopen('cronjob-output/search_list.json', 'w');
        fwrite($file, json_encode($searchList));
        fclose($file);

        $logType = 10;  // notification
        $logUser = 586; // dhcr application user id
        $logSource = basename(__FILE__, '.php');    // name of this script
        $logSource = str_replace('Command', '', $logSource);    // store less characters in db
        $logAction = 'Generated list';
        $logDetails = 'Total courses: ' . sizeof($searchList);

        $this->Logentries->createLogEntry(
            $logType,
            $logUser,
            $logSource,
            $logAction,
            $logDetails
        );

        $io->out($logDetails);
        $io->out('~~~ Finished: Generate Search List ~~~');
    }
}
