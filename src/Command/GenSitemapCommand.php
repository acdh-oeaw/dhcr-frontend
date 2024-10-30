<?php

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenTime;

/*  This file should be run daily*/

class GenSitemapCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->loadModel('DhcrCore.Courses');
        $this->loadModel('Logentries');

        $courses = $this->Courses->find('all',  [
            'contain' => ['Institutions'],
            'order' => ['Courses.updated' => 'DESC']
        ])->where(
            [
                'active' => 1,
                'deleted' => 0,
                'Courses.updated >' => new FrozenTime('-489 Days'),
                'approved' => 1
            ]
        );

        // add header
        $file = fopen('cronjob-output/sitemap.xml', 'w');
        fwrite($file, "<?xml version='1.0' encoding='UTF-8'?>\n\n");
        fwrite($file, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");

        // add static links
        $staticLinks = [
            'https://dhcr.clarin-dariah.eu',
            'https://dhcr.clarin-dariah.eu/info',
            'https://dhcr.clarin-dariah.eu/info#purpose',
            'https://dhcr.clarin-dariah.eu/info#publications',
            'https://dhcr.clarin-dariah.eu/info#data',
            'https://dhcr.clarin-dariah.eu/info#clarin-dariah',
            'https://dhcr.clarin-dariah.eu/faq/public'
        ];
        foreach ($staticLinks as $staticLink) {
            fwrite($file, "    <url>\n");
            fwrite($file, "        <loc>$staticLink</loc>\n");
            fwrite($file, "    </url>\n");
        }

        // add all public shown courses
        $courseDetailBaseUrl = env('DHCR_BASE_URL') . 'courses/view/';
        foreach ($courses as $course) {
            $url = $courseDetailBaseUrl . $course->id;
            $updated = $course->updated->i18nFormat('yyyy-MM-dd');
            fwrite($file, "    <url>\n");
            fwrite($file, "        <loc>$url</loc>\n");
            fwrite($file, "        <lastmod>$updated</lastmod>\n");
            fwrite($file, "    </url>\n");
        }

        // add footer
        fwrite($file, "</urlset>\n");
        fclose($file);

        // log and output result
        $action = 'Generated sitemap';
        $description = 'Courses: ' . $courses->count() . ' Static links: ' . sizeof($staticLinks);
        $scriptName = basename(__FILE__, '.php');
        $scriptName = str_replace('Command', '', $scriptName);
        $this->Logentries->createLogEntry(
            '10',
            '586',
            $scriptName,
            $action,
            $description
        );
        $io->out($action . ' - ' . $description);
    }
}
