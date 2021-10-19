<?php
namespace App\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Users Model
 *
 * @property \App\Model\Table\UserRolesTable&\Cake\ORM\Association\BelongsTo $UserRoles
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\InstitutionsTable&\Cake\ORM\Association\BelongsTo $Institutions
 * @property \App\Model\Table\CoursesTable&\Cake\ORM\Association\HasMany $Courses
 *
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) : void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('UserRoles', [
            'foreignKey' => 'user_role_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
        ]);
        $this->belongsTo('Institutions', [
            'foreignKey' => 'institution_id',
        ]);
        $this->hasMany('Courses', [
            'foreignKey' => 'user_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) : Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('university')
            ->maxLength('university', 255)
            ->allowEmptyString('university');

        $validator
            ->scalar('shib_eppn')
            ->maxLength('shib_eppn', 255, 'Your Identifier is too long. Please turn to the admin team for support or use the classic login.')
            ->allowEmptyString('shib_eppn')
            ->add('shib_eppn', 'unique', [
                'rule' => 'validateUnique', 'provider' => 'table',
                'message' => 'Your identity is already used by an other account. Please turn to our admin team '
            ]);

        $validator
            ->scalar('password')
            ->maxLength('password', 255, 'Your password is too long.')
            ->minLength('password', 6, 'Your password is to short, it should be at least 6 characters.')
            ->allowEmptyString('password', 'Please provide a password.', function($context) {
                return $context['providers']['table']->invitationMode;
            });

        $validator
            ->setStopOnFailure(true)
            ->email('email', false, 'Email address looks strange.')
            ->add('email', 'unique', [
                'rule' => 'validateUnique', 'provider' => 'table',
                'message' => 'Email address is already in use.'])
            ->email('email', true, 'Email MX check failed.', function($context) {
                return $context['providers']['table']->invitationMode;
            });

        $validator
            ->email('new_email', false, 'Please provide a valid email address.')
            ->maxLength('new_email', 255)
            ->allowEmptyString('new_email')
            ->requirePresence('new_email', 'create')
            ->notEmptyString('new_email', 'Please provide your email address.')
            ->add('new_email', 'unique', [
                'rule' => function($value, $context) {
                    return !(bool) $context['providers']['table']->find()->where(['email' => $value])->count();
                },
                'message' => 'Your email address is not unique to our database.'
            ]);

        $validator
            ->requirePresence('institution_id', true)
            ->add('institution_id', 'allowEmptyIf', [
                'rule' => function ($value, $context) {
                    if(empty($value) AND empty($context['data']['university']))
                        return 'When you do not find your affiliation in the list,
                        you must provide the country, city and name of your institution in the field below.';
                    if(!empty($value) AND !empty($context['data']['university']))
                        return 'Leave this field empty, when you want us to add a new organisation
                        as indicated in the field below';
                    return true;
                }
            ]);

        $validator
            ->scalar('last_name')
            ->maxLength('last_name', 255, 'Your last name is too long.')
            ->notEmptyString('last_name', 'Please provide your last name.');

        $validator
            ->scalar('first_name')
            ->maxLength('first_name', 255, 'Your first name is too long.')
            ->notEmptyString('first_name', 'Please provide your first name.');

        $validator
            ->scalar('academic_title')
            ->maxLength('academic_title', 255, 'Your academic title is too long (> 255 characters). We beg your pardon, that our database cannot take on all your wisdom.')
            ->allowEmptyString('academic_title');

        $validator
            ->scalar('about')
            ->notEmptyString('about', 'For verification of your eligibility, please provide reproducible information of your academical teaching involvement.', 'create');

        $validator
            ->requirePresence('consent', 'create')
            ->allowEmptyString(false, 'You must agree to the terms.')
            ->equals('consent', 1, 'You must agree to the terms.');

        return $validator;
    }


    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) : RulesChecker
    {
        $rules->add($rules->existsIn(['user_role_id'], 'UserRoles'));
        $rules->add($rules->existsIn(['country_id'], 'Countries'));
        $rules->add($rules->existsIn(['institution_id'], 'Institutions'));

        return $rules;
    }


    // 60*60*24 = 86400
    public $tokenExpirationTime = 86400;

    public $invitationMode = false;


    public function generateToken($fieldname = null, $length = 16) : string
    {
        $time = substr((string)time(), -6, 6);
        $possible = '0123456789abcdefghijklmnopqrstuvwxyz';
        // create an unique token
        do {
            $token = '';
            for($i = 0; $i < $length - 6; $i++) {
                $token .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            }
            $token = $time . $token;
            if(empty($fieldname)) break;

            $c = $this->find()->where([$fieldname => $token])->count();
        } while($c > 0);
        return $token;
    }


    public function getModerators($country_id = null, $user_admin = true) : array
    {
        $admins = [];
        // try fetching the moderator in charge of the user's country,
        if(!empty($country_id)) {
            $admins = $this->find()
                ->distinct()->where([
                    'Users.country_id' => $country_id,
                    'Users.user_role_id' => 2,	// moderators
                    'Users.active' => 1
                ])->toArray();
        }
        // then user_admin
        if(empty($admins) AND $user_admin) {
            $admins = $this->find()
                ->distinct()->where([
                    'Users.user_admin' => 1,
                    'Users.active' => 1
                ])->toArray();
        }
        // then admin
        if(empty($admins)) {
            $admins = $this->find()
                ->distinct()->where([
                    'Users.is_admin' => 1,
                    'Users.active' => 1
                ])->toArray();
        }
        return $admins;
    }


    public function register($data = [])
    {
        $expiry = date('Y-m-d H:i:s', time() + $this->tokenExpirationTime);
        $data['new_email'] = $data['email'];
        $data['email_token'] = $this->generateToken('email_token');
        $data['email_token_expires'] = $expiry;
        $data['approval_token'] = $this->generateToken('approval_token');
        $data['approval_token_expires'] = $expiry;

        $user = $this->newEntity($data);
        if($user->hasErrors()) {
            return $user;
        }
        if(!$this->save($user)) {
            return false;
        }
        return $user;
    }
}
