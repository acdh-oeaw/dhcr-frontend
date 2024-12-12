<?php

declare(strict_types=1);

namespace App\Controller;

class FaqQuestionsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        if ($this->request->getParam('action') == 'faqList' && $this->request->getParam('categoryName')  == 'public') {
            $this->Authentication->allowUnauthenticated(['faqList']);
            $this->Authorization->skipAuthorization();
        } else {
            $this->viewBuilder()->setLayout('contributors');
        }
    }

    public function index($categoryId)
    {
        $user = $this->Authentication->getIdentity();
        if (!$user->is_admin) {
            $this->Flash->error(__('Not authorized to FAQ Questions index'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        $categoryName = $this->FaqQuestions->FaqCategories->find()->where(['id' => $categoryId])->first()->name;
        $this->set((compact('categoryId', 'categoryName')));
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'FAQ Questions';
        $breadcrumControllers[1] = 'Dashboard';
        $breadcrumActions[1] = 'faqQuestions';
        $breadcrumTitles[2] = $categoryName . ' Questions';
        $breadcrumControllers[2] = 'FaqQuestions';
        $breadcrumActions[2] = 'index/' . $categoryId;
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
        $faqQuestions = $this->paginate(
            $this->FaqQuestions->find()->where(['faq_category_id' => $categoryId,]),
            ['order' => ['sort_order' => 'asc']]
        );
        $this->set(compact('faqQuestions'));
    }

    public function view($id = null)
    {
        $faqQuestion = $this->FaqQuestions->get($id, ['contain' => ['FaqCategories']]);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'FAQ Questions';
        $breadcrumControllers[1] = 'Dashboard';
        $breadcrumActions[1] = 'faqQuestions';
        $breadcrumTitles[2] = 'View FAQ Question';
        $breadcrumControllers[2] = 'FaqQuestions';
        $breadcrumActions[2] = 'view';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('faqQuestion'));
    }

    public function add()
    {
        $faqQuestion = $this->FaqQuestions->newEmptyEntity();
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($faqQuestion);
        if ($this->request->is('post')) {
            $faqQuestion = $this->FaqQuestions->patchEntity($faqQuestion, $this->request->getData());
            $query = $this->FaqQuestions->find()->where(['faq_category_id =' => $faqQuestion->faq_category_id]);
            $nextSortOrder = $query->select(['sortOrder' => $query->func()->max('sort_order')])->first()->sortOrder + 1;
            $faqQuestion->sort_order = $nextSortOrder;
            // remove spaces to make sure that unique validation works
            $faqQuestion->question = trim($faqQuestion->question);
            if ($this->FaqQuestions->save($faqQuestion)) {
                $this->Flash->success(__('The faq question has been saved.'));
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'faqQuestions']);
            }
            $this->Flash->error(__('The faq question could not be saved. Please, try again.'));
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'FAQ Questions';
        $breadcrumControllers[1] = 'Dashboard';
        $breadcrumActions[1] = 'faqQuestions';
        $breadcrumTitles[2] = 'Add FAQ Question';
        $breadcrumControllers[2] = 'FaqQuestions';
        $breadcrumActions[2] = 'add';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
        $faqCategories = $this->FaqQuestions->FaqCategories->find('list', ['limit' => 200]);
        $this->set(compact('faqQuestion', 'faqCategories'));
    }

    public function edit($id = null)
    {
        $faqQuestion = $this->FaqQuestions->get($id);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($faqQuestion);
        if ($this->request->is(['patch', 'post', 'put'])) {
            // assign new sortOrder is category has changed
            $oldCategory = $faqQuestion->faq_category_id;
            $newCategory = $this->request->getData('faq_category_id');
            $faqQuestion = $this->FaqQuestions->patchEntity($faqQuestion, $this->request->getData());
            if ($newCategory != $oldCategory) {
                $maxSortOrder = $this->FaqQuestions->find('all', ['order' => ['sort_order' => 'DESC']])
                    ->where(['faq_category_id =' => $this->request->getData('faq_category_id')])
                    ->first()
                    ->sort_order;
                $faqQuestion->sort_order = $maxSortOrder + 1;
            }
            // remove spaces to make sure that unique validation works
            $faqQuestion->question = trim($faqQuestion->question);
            if ($this->FaqQuestions->save($faqQuestion)) {
                $this->Flash->success(__('The faq question has been updated.'));
                return $this->redirect(['controller' => 'FaqQuestions', 'action' => 'index', $faqQuestion->faq_category_id]);
            }
            $this->Flash->error(__('The faq question could not be updated. Please, try again.'));
        }
        // Set breadcrums
        $breadcrumTitles[0] = 'Category Lists';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'categoryLists';
        $breadcrumTitles[1] = 'FAQ Questions';
        $breadcrumControllers[1] = 'Dashboard';
        $breadcrumActions[1] = 'faqQuestions';
        $breadcrumTitles[2] = 'Edit FAQ Question';
        $breadcrumControllers[2] = 'FaqQuestions';
        $breadcrumActions[2] = 'edit';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
        $faqCategories = $this->FaqQuestions->FaqCategories->find('list', ['limit' => 200]);
        $this->set(compact('faqQuestion', 'faqCategories'));
    }

    public function moveUp($id)
    {
        $faqQuestion = $this->FaqQuestions->get($id);
        $this->Authentication->getIdentity();
        $this->Authorization->authorize($faqQuestion);
        $firstId = $this->FaqQuestions->find('all', ['order' => ['sort_order' => 'ASC']])
            ->where(['faq_category_id =' => $faqQuestion->faq_category_id])
            ->first()
            ->id;
        if ($faqQuestion->id == $firstId) {
            $this->Flash->error('Can not move up. Already on top.');
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'faqQuestions']);
        }
        $prevFaqQuestion = $this->FaqQuestions->find()->where([
            'faq_category_id =' => $faqQuestion->faq_category_id,
            'sort_order' => $faqQuestion->sort_order - 1,
        ])->first();
        $tempSortOrder = $prevFaqQuestion->sort_order;
        $prevFaqQuestion->sort_order = $faqQuestion->sort_order;
        $faqQuestion->sort_order = $tempSortOrder;
        if ($this->FaqQuestions->save($prevFaqQuestion) && $this->FaqQuestions->save($faqQuestion)) {
            $this->Flash->success(__('Moved up.'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'faqQuestions']);
        }
        $this->Flash->error(__('Error moving up.'));
    }

    public function moveDown($id)
    {
        $faqQuestion = $this->FaqQuestions->get($id);
        $this->Authentication->getIdentity();
        $this->Authorization->authorize($faqQuestion);
        $lastId = $this->FaqQuestions->find('all', ['order' => ['sort_order' => 'DESC']])
            ->where(['faq_category_id =' => $faqQuestion->faq_category_id])
            ->first()
            ->id;
        if ($faqQuestion->id == $lastId) {
            $this->Flash->error('Can not move down. Already on bottom.');
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'faqQuestions']);
        }
        $nextFaqQuestion = $this->FaqQuestions->find()->where([
            'faq_category_id =' => $faqQuestion->faq_category_id,
            'sort_order' => $faqQuestion->sort_order + 1,
        ])->first();
        $tempSortOrder = $nextFaqQuestion->sort_order;
        $nextFaqQuestion->sort_order = $faqQuestion->sort_order;
        $faqQuestion->sort_order = $tempSortOrder;
        if ($this->FaqQuestions->save($nextFaqQuestion) && $this->FaqQuestions->save($faqQuestion)) {
            $this->Flash->success(__('Moved down.'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'faqQuestions']);
        }
        $this->Flash->error(__('Error moving down.'));
    }

    public function faqList($categoryName)
    {
        $categoryName = ucfirst($categoryName);
        $categoryId = $this->FaqQuestions->FaqCategories->find()->where(['name' => $categoryName])->first()->id;
        $faqQuestions = $this->paginate(
            $this->FaqQuestions->find()->where(['faq_category_id' => $categoryId,]),
            ['order' => ['sort_order' => 'asc']]
        );
        if ($categoryName != 'Public') {
            $user = $this->Authentication->getIdentity();
            $this->Authorization->authorize($faqQuestions->first());
        } else {
            $user = '';
        }
        $this->set((compact('categoryId', 'categoryName')));
        $this->set(compact('faqQuestions'));
        // Set breadcrums
        $breadcrumTitles[0] = 'Help';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'help';
        $breadcrumTitles[1] = ucfirst($categoryName) . ' FAQ';
        $breadcrumControllers[1] = 'FaqQuestions';
        $breadcrumActions[1] = 'faq/' . $categoryName;
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
    }

    public function usersAccessWorkflows()
    {
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        // Set breadcrums
        $breadcrumTitles[0] = 'Help';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'help';
        $breadcrumTitles[1] = 'Users, Access and Workflows';
        $breadcrumControllers[1] = 'Help';
        $breadcrumActions[1] = 'processesExplanation';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
    }
}
