<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

class LogentriesController extends AppController
{
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('contributors');
    }

    public function index()
    {
        $user = $this->Authentication->getIdentity();
        if (!$user->is_admin) {
            $this->Flash->error(__('Not authorized to logentries index'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Log Entries';
        $breadcrumControllers[1] = 'Logentries';
        $breadcrumActions[1] = 'index';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $query = $this->Logentries->find('all')->contain(['LogentryCodes', 'Users']);
        $this->set('logentries', $this->paginate($query, ['order' => ['Logentries.id' => 'DESC']]));
        $this->set(compact('user')); // required for contributors menu
        $this->render('show-entries');
    }

    public function errors()
    {
        $user = $this->Authentication->getIdentity();
        if (!$user->is_admin) {
            $this->Flash->error(__('Not authorized to logentries errors'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Log Entries';
        $breadcrumControllers[1] = 'Logentries';
        $breadcrumActions[1] = 'index';
        $breadcrumTitles[2] = 'Errors';
        $breadcrumControllers[2] = 'Logentries';
        $breadcrumActions[2] = 'errors';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $query = $this->Logentries->find('all')
            ->where(['logentry_code_id >=' => '50'])
            ->contain(['LogentryCodes', 'Users']);
        $this->set('logentries', $this->paginate($query, ['order' => ['Logentries.id' => 'DESC']]));
        $this->set(compact('user')); // required for contributors menu
        $this->render('show-entries');
    }
}
