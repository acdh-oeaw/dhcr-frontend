<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

class CitiesController extends AppController
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
            $this->Flash->error(__('Not authorized to cities index'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Cities';
        $breadcrumControllers[1] = 'Cities';
        $breadcrumActions[1] = 'index';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $query = $this->Cities->find('all', ['order' => ['Cities.id' => 'ASC']])->contain('Countries');
        $this->set('cities', $this->paginate($query));
        $this->set(compact('user')); // required for contributors menu
    }

    public function add()
    {
        $city = $this->Cities->newEmptyEntity();
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($city);
        if ($this->request->is('post')) {
            $city = $this->Cities->patchEntity($city, $this->request->getData());
            if ($this->Cities->save($city)) {
                $this->Flash->success(__('The city has been added.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The city could not be added. Please, try again.'));
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Cities';
        $breadcrumControllers[1] = 'Cities';
        $breadcrumActions[1] = 'index';
        $breadcrumTitles[2] = 'Add City';
        $breadcrumControllers[2] = 'Cities';
        $breadcrumActions[2] = 'add';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $countries = $this->Cities->Countries->find('list', ['order' => 'Countries.name asc']);
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('city', 'countries'));
    }

    public function edit($id = null)
    {
        $city = $this->Cities->get($id);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($city);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $city = $this->Cities->patchEntity($city, $this->request->getData());
            if ($this->Cities->save($city)) {
                $this->Flash->success(__('The city has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The city could not be updated. Please, try again.'));
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'Cities';
        $breadcrumControllers[1] = 'Cities';
        $breadcrumActions[1] = 'index';
        $breadcrumTitles[2] = 'Edit City';
        $breadcrumControllers[2] = 'Cities';
        $breadcrumActions[2] = 'edit';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $countries = $this->Cities->Countries->find('list', ['order' => 'Countries.name asc'])->all();
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('city', 'countries'));
    }
}
