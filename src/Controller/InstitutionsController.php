<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

class InstitutionsController extends AppController
{
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('contributors');
    }

    public function index()
    {
        $user = $this->Authentication->getIdentity();
        if (!($user->user_role_id == 2 || $user->is_admin)) {
            $this->Flash->error(__('Not authorized to institutions index'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Institutions';
        $breadcrumControllers[1] = 'Institutions';
        $breadcrumActions[1] = 'index';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $query = $this->Institutions->find('all', ['order' => ['Institutions.id' => 'ASC']])->contain(['Countries', 'Cities']);
        $this->set('institutions', $this->paginate($query));
        $this->set(compact('user')); // required for contributors menu
    }

    public function view($id = null)
    {
        $institution = $this->Institutions->get($id, ['contain' => ['Cities', 'Countries']]);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($institution);
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Institutions';
        $breadcrumControllers[1] = 'Institutions';
        $breadcrumActions[1] = 'index';
        $breadcrumTitles[2] = 'Institution Details';
        $breadcrumControllers[2] = 'Institutions';
        $breadcrumActions[2] = 'view';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $mapInit = ['lon' => $institution->lon, 'lat' => $institution->lat];
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('institution', 'mapInit'));
    }

    public function add()
    {
        $institution = $this->Institutions->newEmptyEntity();
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($institution);
        if ($this->request->is('post')) {
            $institution = $this->Institutions->patchEntity($institution, $this->request->getData());
            if ($this->Institutions->save($institution)) {
                $this->Flash->success(__('The institution has been added.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The institution could not be added. Please, try again.'));
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Institutions';
        $breadcrumControllers[1] = 'Institutions';
        $breadcrumActions[1] = 'index';
        $breadcrumTitles[2] = 'Add Institution';
        $breadcrumControllers[2] = 'Institutions';
        $breadcrumActions[2] = 'add';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $userInstitution = $this->Institutions->find()->where(['id' => $user->institution_id])->first();
        $mapInit = ['lon' => $userInstitution->lon, 'lat' => $userInstitution->lat];
        $cities = $this->Institutions->Cities->find('list', ['order' => 'Cities.name asc']);
        $countries = $this->Institutions->Countries->find('list', ['order' => 'Countries.name asc']);
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('mapInit', 'institution', 'cities', 'countries'));
    }

    public function edit($id = null)
    {
        $institution = $this->Institutions->get($id);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($institution);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $institution = $this->Institutions->patchEntity($institution, $this->request->getData());
            if ($this->Institutions->save($institution)) {
                $this->Flash->success(__('The institution has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The institution could not be updated. Please, try again.'));
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Institutions';
        $breadcrumControllers[1] = 'Institutions';
        $breadcrumActions[1] = 'index';
        $breadcrumTitles[2] = 'Edit Institution';
        $breadcrumControllers[2] = 'Institutions';
        $breadcrumActions[2] = 'edit';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $mapInit = ['lon' => $institution->lon, 'lat' => $institution->lat];
        $cities = $this->Institutions->Cities->find('list', ['order' => 'Cities.name asc']);
        $countries = $this->Institutions->Countries->find('list', ['order' => 'Countries.name asc']);
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('mapInit', 'institution', 'cities', 'countries'));
    }
}
