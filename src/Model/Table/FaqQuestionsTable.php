<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FaqQuestionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('faq_questions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('FaqCategories', [
            'foreignKey' => 'faq_category_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('sort_order')
            ->requirePresence('sort_order', 'create')
            ->notEmptyString('sort_order');

        $validator
            ->scalar('question')
            ->maxLength('question', 255)
            ->requirePresence('question', 'create')
            ->notEmptyString('question');

        $validator
            ->scalar('answer')
            ->requirePresence('answer', 'create')
            ->notEmptyString('answer');

        $validator
            ->scalar('link_title')
            ->maxLength('link_title', 100)
            ->allowEmptyString('link_title');

        $validator
            ->scalar('link_url')
            ->maxLength('link_url', 255)
            ->allowEmptyString('link_url');
            
        $validator
            ->boolean('published')
            ->requirePresence('published', 'create')
            ->notEmptyString('published');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['faq_category_id'], 'FaqCategories'), ['errorField' => 'faq_category_id']);
        $rules->add($rules->isUnique(['question']), ['errorField' => 'question']);
        return $rules;
    }
}
