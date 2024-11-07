<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;

class StatisticsController extends AppController
{
    public $Courses = null;
    public $Users = null;
    public $FaqQuestions = null;
    public $InviteTranslations = null;
    
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('contributors');
    }

    private function getCoursesKeyData()
    {
        $coursesTotal = $this->Courses->find()->count();
        $coursesBackend = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => Configure::read('courseArchiveDate'),
            'approved' => 1,
        ])
            ->count();
        $coursesPublic = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => new FrozenTime('-489 Days'),
            'approved' => 1,
        ])
            ->count();  // ca. 16 Months
        return [$coursesTotal, $coursesBackend, $coursesPublic];
    }

    private function getUpdatedCourseCounts($periods)
    {
        // @PARAM $periods array, containing the number of months
        $updatedCourseCounts[] = ['Months ago', 'Updated courses'];
        foreach ($periods as $period) {
            $count = $this->Courses->find()->where([
                'active' => 1,
                'deleted' => 0,
                'updated >=' => new FrozenTime('-' . $period . ' Months'),
                'approved' => 1,
            ])
                ->count();
            $updatedCourseCounts[] = [$period, $count];
        }
        return $updatedCourseCounts;
    }

    private function getArchivedSoonCourseCounts($periods)
    {
        // @PARAM $periods array, containing the number of months
        $archivedSoonCourseCounts[] = ['Months from now', 'Archived in this month'];
        foreach ($periods as $period) {
            $count = $this->Courses->find()->where([
                'active' => 1,
                'deleted' => 0,
                'updated >=' => new FrozenTime('-' . (24 - ($period - 1)) . ' Months'),
                'updated <' => new FrozenTime('-' . (24 - $period) . ' Months'),
                'approved' => 1,
            ])
                ->count();
            $archivedSoonCourseCounts[] = [$period, $count];
        }
        return $archivedSoonCourseCounts;
    }

    private function getCourseCountsPerCountry($outdated=false)
    {
        if($outdated == true) { 
            $updatedMin = new FrozenTime('-24 Months');
            $updatedMax = new FrozenTime('-16 Months');
        } else {
            $updatedMin = new FrozenTime('-16 Months');
            $updatedMax = new FrozenTime();
        }
        $items = $this->Courses->find()
            ->where([
                'active' => 1,
                'deleted' => 0,
                'updated >' => $updatedMin,
                'updated <=' => $updatedMax,
                'approved' => 1
            ])
            ->group('country_id')
            ->contain(['Countries'])
            ->order(['Countries.name' => 'asc']);
        $courseCountsPerCountry = [];
        $courseCountsPerCountry[] = ['Country', 'Number of courses'];
        foreach ($items as $item) {
            $result = $this->Courses->find()
                ->where([
                    'country_id' => $item->country_id,
                    'active' => 1,
                    'deleted' => 0,
                    'updated >' => $updatedMin,
                    'updated <=' => $updatedMax,
                    'approved' => 1
                ])
                ->count();
            $courseCountsPerCountry[] = [$item->country->name, $result];
        }
        return $courseCountsPerCountry;
    }

    private function getCourseCountsPerEducType()
    {
        $items = $this->Courses->find()
            ->where([
                'active' => 1,
                'deleted' => 0,
                'updated >' => new FrozenTime('-16 Months'),
                'approved' => 1
            ])
            ->group('course_type_id')
            ->contain(['CourseTypes'])
            ->order(['CourseTypes.id' => 'asc']);
        $courseCountsPerEducType = [];
        $courseCountsPerEducType[] = ['Education type', 'Number of courses'];
        foreach ($items as $item) {
            $result = $this->Courses->find()
                ->where([
                    'course_type_id' => $item->course_type_id,
                    'active' => 1,
                    'deleted' => 0,
                    'updated >' => new FrozenTime('-16 Months'),
                    'approved' => 1
                ])
                ->count();
            $courseCountsPerEducType[] = [$item->course_type->name, $result];
        }
        return $courseCountsPerEducType;
    }

    private function getNewCourseCounts($periods)
    {
        // @PARAM $periods array, containing the number of months
        $newCourseCounts[] = ['Months ago', 'New courses'];
        foreach ($periods as $period) {
            $count = $this->Courses->find()->where([
                'deleted' => 0,
                'created <=' => new FrozenTime('-' . $period - 1 . ' Months'),
                'created >' => new FrozenTime('-' . $period . ' Months'),
            ])
                ->count();
            $newCourseCounts[] = [$period, $count];
        }
        return $newCourseCounts;
    }

    private function getNewAddedCourses($amount)
    {
        // @PARAM $amount integer, amount of course objects that are returned
        $newAddedCourses = $this->Courses->find()
            ->where(['deleted' => 0])
            ->contain(['Institutions', 'Countries', 'Users'])
            ->order(['Courses.created' => 'desc'])
            ->limit($amount);   // this shows also unpublished and not (yet) approved courses!
        return $newAddedCourses;
    }

    private function getUsersKeyData()
    {
        $usersTotal = $this->Users->find()->count();
        $usersSubscribed = $this->Users->find()->where([
            'mail_list' => 1,
        ])->count();
        $usersAvailable = $this->Users->find()->where([
            'email_verified' => 1,
            'password IS NOT NULL',
            'approved' => 1,
            'active' => 1,
        ])->count();
        $usersAvailableSubscribed = $this->Users->find()->where([
            'email_verified' => 1,
            'password IS NOT NULL',
            'approved' => 1,
            'active' => 1,
            'mail_list' => 1,
        ])->count();
        $moderators = $this->Users->find()->where([
            'user_role_id' => 2,
        ])->count();
        $moderatorsSubscribed = $this->Users->find()->where([
            'user_role_id' => 2,
            'mail_list' => 1,
        ])->count();
        $administrators = $this->Users->find()->where([
            'is_admin' => 1,
        ])->count();
        $userAdmins = $this->Users->find()->where([
            'user_admin' => 1,
        ])->count();

        return [
            $usersTotal, $usersSubscribed,
            $usersAvailable, $usersAvailableSubscribed,
            $moderators, $moderatorsSubscribed,
            $administrators, $userAdmins
        ];
    }

    private function getLoggedinUserCounts($periods)
    {
        $loggedinUserCounts[] = ['Months ago', 'Logged in users'];
        foreach ($periods as $period) {
            $count = $this->Users->find()->where([
                'email_verified' => 1,
                'password IS NOT NULL',
                'approved' => 1,
                'active' => 1,
                'last_login >=' => new FrozenTime('-' . $period . ' Months'),
            ])->count();
            $loggedinUserCounts[] = [$period, $count];
        }
        return $loggedinUserCounts;
    }

    private function getLoggedinModeratorCounts($periods)
    {
        $loggedinModeratorCounts[] = ['Months ago', 'Logged in moderators'];
        foreach ($periods as $period) {
            $count = $this->Users->find()->where([
                'email_verified' => 1,
                'password IS NOT NULL',
                'approved' => 1,
                'active' => 1,
                'last_login >=' => new FrozenTime('-' . $period . ' Months'),
                'user_role_id' => 2,
            ])->count();
            $loggedinModeratorCounts[] = [$period, $count];
        }
        return $loggedinModeratorCounts;
    }

    public function courseStatistics()
    {
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        $this->loadModel('DhcrCore.Courses');
        // set breadcrums
        $breadcrumTitles[0] = 'Statistics';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'Statistics';
        $breadcrumTitles[1] = 'Course Statistics';
        $breadcrumControllers[1] = 'Statistics';
        $breadcrumActions[1] = 'courseStatistics';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        [$coursesTotal, $coursesBackend, $coursesPublic] = $this->getCoursesKeyData();
        $updatedCourseCounts = $this->getUpdatedCourseCounts(range(1, 24));
        $archivedSoonCourseCounts = $this->getArchivedSoonCourseCounts(range(1, 12));
        $outdatedCoursesPerCountries = $this->getCourseCountsPerCountry($outdated=true);
        $courseCountsPerCountry = $this->getCourseCountsPerCountry($outdated=false);
        $this->set('courseCountsPerEducType', $this->getCourseCountsPerEducType());
        $newCourseCounts = $this->getNewCourseCounts(range(1, 18));
        $newAddedCourses = $this->getNewAddedCourses(25);
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('coursesTotal', 'coursesBackend', 'coursesPublic'));
        $this->set(compact(
            'updatedCourseCounts',
            'archivedSoonCourseCounts',
            'outdatedCoursesPerCountries',
            'courseCountsPerCountry',
            'newCourseCounts',
            'newAddedCourses'
        ));
    }

    public function userStatistics()
    {
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        $this->loadModel('Users');
        // set breadcrums
        $breadcrumTitles[0] = 'Statistics';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'Statistics';
        $breadcrumTitles[1] = 'User Statistics';
        $breadcrumControllers[1] = 'Statistics';
        $breadcrumActions[1] = 'userStatistics';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        [
            $usersTotal, $usersSubscribed,
            $usersAvailable, $usersAvailableSubscribed,
            $moderators, $moderatorsSubscribed,
            $administrators, $userAdmins
        ] = $this->getUsersKeyData();
        $loggedinPeriods = range(1, 24);    // periods in months
        $loggedinUserCounts = $this->getLoggedinUserCounts($loggedinPeriods);
        $loggedinModeratorCounts = $this->getLoggedinModeratorCounts($loggedinPeriods);
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact(
            'usersTotal',
            'usersAvailable',
            'usersSubscribed',
            'usersAvailableSubscribed',
            'moderators',
            'moderatorsSubscribed',
            'administrators',
            'userAdmins'
        ));
        $this->set(compact('loggedinUserCounts', 'loggedinModeratorCounts'));
    }

    public function summaryStatistics()
    {
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        $this->loadModel('DhcrCore.Courses');
        $this->loadModel('Users');
        $this->loadModel('FaqQuestions');
        $this->loadModel('InviteTranslations');
        // set breadcrums
        $breadcrumTitles[0] = 'Statistics';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'Statistics';
        $breadcrumTitles[1] = 'Summary Statistics';
        $breadcrumControllers[1] = 'Statistics';
        $breadcrumActions[1] = 'summaryStatistics';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        // courses
        [$coursesTotal, $coursesBackend, $coursesPublic] = $this->getCoursesKeyData();
        $this->set(compact('coursesTotal', 'coursesBackend', 'coursesPublic'));
        // users
        [
            $usersTotal, $usersSubscribed,
            $usersAvailable, $usersAvailableSubscribed,
            $moderators, $moderatorsSubscribed
        ] = $this->getUsersKeyData();
        $this->set(compact(
            'usersTotal',
            'usersAvailable',
            'usersSubscribed',
            'usersAvailableSubscribed',
            'moderators',
            'moderatorsSubscribed'
        ));
        // institutions
        $institutionsTotal = $this->Courses->Institutions->find('all')->count();
        $institutionsCourses = $this->Courses->find()->group('institution_id')->count();
        $institutionsBackend = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => Configure::read('courseArchiveDate'),
        ])
            ->group('institution_id')
            ->count();
        $institutionsPublic = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => new FrozenTime('-489 Days'),
        ])
            ->group('institution_id')
            ->count();
        $this->set(compact('institutionsTotal', 'institutionsCourses', 'institutionsBackend', 'institutionsPublic'));
        // countries
        $countriesUsersAvailable = $this->Users->find()->where([
            'email_verified' => 1,
            'password IS NOT NULL',
            'approved' => 1,
            'active' => 1,
        ])
            ->group('country_id')
            ->count();
        $countriesCourses = $this->Courses->find()->group('country_id')->count();
        $countriesCoursesBackend = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => Configure::read('courseArchiveDate'),
        ])
            ->group('country_id')
            ->count();
        $countriesCoursesPublic = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => new FrozenTime('-489 Days'),
        ])
            ->group('country_id')
            ->count();

        $this->set(compact('countriesUsersAvailable', 'countriesCourses', 'countriesCoursesBackend', 'countriesCoursesPublic'));
        // cities
        $citiesTotal = $this->Courses->Cities->find()->count();
        $citiesCourses = $this->Courses->find()->group('city_id')->count();
        $citiesCoursesBackend = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => Configure::read('courseArchiveDate'),
        ])
            ->group('city_id')
            ->count();
        $citiesCoursesPublic = $this->Courses->find()->where([
            'active' => 1,
            'deleted' => 0,
            'updated >' => new FrozenTime('-489 Days'),
        ])
            ->group('city_id')
            ->count();
        $this->set(compact('citiesTotal', 'citiesCourses', 'citiesCoursesBackend', 'citiesCoursesPublic'));
        // faq questions
        $faqQuestionsTotal = $this->FaqQuestions->find()->count();
        $faqQuestionsPublished = $this->FaqQuestions->find()->where(['published' => 1])->count();
        $faqQuestionsPublishedPublic = $this->FaqQuestions->find()->where([
            'published' => 1,
            'faq_category_id' => 1,
        ])->count();
        $faqQuestionsPublishedContributor = $this->FaqQuestions->find()->where([
            'published' => 1,
            'faq_category_id' => 2,
        ])->count();
        $faqQuestionsPublishedModerator = $this->FaqQuestions->find()->where([
            'published' => 1,
            'faq_category_id' => 3,
        ])->count();
        $this->set(compact(
            'faqQuestionsTotal',
            'faqQuestionsPublished',
            'faqQuestionsPublishedPublic',
            'faqQuestionsPublishedContributor',
            'faqQuestionsPublishedModerator'
        ));

        // translations
        $inviteTranslationsTotal = $this->InviteTranslations->find('all')->count();
        $inviteTranslationsPublished = $this->InviteTranslations->find('all')->where(['active ' => true])->count();
        $this->set(compact('inviteTranslationsTotal', 'inviteTranslationsPublished'));


        $this->set(compact('user')); // required for contributors menu
    }

    public function appInfo()
    {
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        // set breadcrums
        $breadcrumTitles[0] = 'Statistics';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'Statistics';
        $breadcrumTitles[1] = 'App Info';
        $breadcrumControllers[1] = 'Statistics';
        $breadcrumActions[1] = 'appInfo';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
    }
}
