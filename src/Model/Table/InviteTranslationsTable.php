<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class InviteTranslationsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('invite_translations');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('sortOrder')
            ->requirePresence('sortOrder', 'create')
            ->notEmptyString('sortOrder')
            ->add('sortOrder', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('subject')
            ->maxLength('subject', 150)
            ->requirePresence('subject', 'create')
            ->notEmptyString('subject');

        $validator
            ->scalar('messageBody')
            ->requirePresence('messageBody', 'create')
            ->notEmptyString('messageBody');

        $validator
            ->boolean('active')
            ->requirePresence('active', 'create')
            ->notEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['sortOrder']), ['errorField' => 'sortOrder']);
        $rules->add($rules->isUnique(['language_id']), ['errorField' => 'language_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);

        return $rules;
    }
}
