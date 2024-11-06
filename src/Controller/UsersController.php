<?php

namespace App\Controller;

use App\Authenticator\AppResult;
use Cake\Core\Exception\Exception;
use Cake\Event\EventInterface;
use Cake\Mailer\MailerAwareTrait;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Mailer\Mailer;
use Cake\I18n\FrozenTime;

class UsersController extends AppController
{
    use MailerAwareTrait;

    public $Institutions = null;
    public $InviteTranslations = null;

    public const ALLOW_UNAUTHENTICATED = [
        'signIn',
        'logout',   // avoid redirecting
        'register',
        'verifyMail',
        'resetPassword',
        'unknownIdentity',
        'connectIdentity',
        'registerIdentity',
        'whichTerms'
    ];

    public const SKIP_AUTHORIZATION = [
        'signIn',
        'logout',
        'register',
        'verifyMail',
        'resetPassword',
        'registrationSuccess',
        'unknownIdentity',
        'connectIdentity',
        'registerIdentity',
        'whichTerms',
        'verifyMail'
    ];

    public const DEFAULT_LAYOUT = [
        'signIn',
        'register',
        'registrationSuccess',
        'resetPassword',
        'unknownIdentity',
        'connectIdentity',
        'registerIdentity',
        'whichTerms',
        'verifyMail'
    ];

    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(self::ALLOW_UNAUTHENTICATED);
        if (in_array($this->request->getParam('action'), self::SKIP_AUTHORIZATION)) {
            $this->Authorization->skipAuthorization();
        }
    }

    public function beforeFilter(EventInterface $event)
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            // set the contributor layout for logged in users and certain actions only
            if (!in_array($this->request->getParam('action'), self::DEFAULT_LAYOUT))
                $this->viewBuilder()->setLayout('contributors');
        }
        // we must RETURN the Response object here for parent class redirects to take effect
        return parent::beforeFilter($event);
    }

    /**
     * @param string|null $mode
     * @return \Cake\Http\Response|void|null
     *
     * Set parameter $mode = 'identity' to bypass redirection loop and connect a present
     * but unknown external identity to an already existing account
     */
    public function signIn()
    {
        $redirect = $this->getRequest()->getQuery('redirect');
        // PA 27-05-22 temporary change
        $identity = false;
        // if ($identity = $this->_checkExternalIdentity() and $redirect != '/users/connect_identity') {
        //     return $this->redirect('/users/unknown_identity');
        // }
        // the user is logged in by session, idp or form
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $user = $this->Authentication->getIdentity()->getOriginalData();
            $authentication = $this->Authentication->getAuthenticationService();
            if ($authentication->identifiers()->get('Password')->needsPasswordRehash())
                $user->password = $this->request->getData('password');
            $user->last_login = date('Y-m-d H:i:s');
            $this->Users->save($user);  // Rehash happens on save
            $target = $this->Authentication->getLoginRedirect() ?? '/dashboard/index';
            return $this->redirect($target);
        }
        if ($this->request->is('post') and !$result->isValid()) {
            // evaluate the result here, AppResult might indicate banned user
            $this->Flash->error('Invalid username or password.');
        }
        if ($identity and $redirect === '/users/connect_identity') {
            if ($result->isValid())
                return $this->redirect($redirect);
            $this->viewBuilder()->setTemplate('connect_identity');
            $this->set('identity', $identity);
        } else {
            // render the login form, providing federated authentication
            // $this->_setIdentityProviderTarget(); // PA 30-04-22 temporary disabled federated login
            $this->set('idpTarget', false);
        }
    }

    public function logout()
    {
        $this->Authentication->logout();
        return $this->redirect(['controller' => 'Users', 'action' => 'signIn']);
    }

    protected function _setIdentityProviderTarget()
    {
        // get the shibboleth return parameter
        $here = 'https://dev-dhcr.clarin-dariah.eu/users/sign-in';
        $get = 'https://dhcr.clarin-dariah.eu/Shibboleth.sso/Login?target=' . urlencode($here);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $get);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:54.0) Gecko/20100101 Firefox/54.0');
        curl_exec($ch);
        $url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        $idpTarget = false;
        if ($url) {
            $query = explode('&', explode('?', $url)[1]);
            foreach ($query as $para) {
                $p = explode('=', $para);
                if ($p[0] == 'return') {
                    $returnParameter = urldecode($p[1]);
                    // The return parameter contains the idpSelector form action,
                    // which is hardcoded in class IdpSelector.js
                    // Only the target parameter within the return parameter is required
                    if (strpos($returnParameter, '?') !== false) {
                        $q = explode('&', explode('?', $returnParameter)[1]);
                        foreach ($q as $a) {
                            $b = explode('=', $a);
                            if ($b[0] == 'target')
                                $idpTarget = urldecode($b[1]);
                        }
                    }
                    break;
                }
            }
        }
        $this->set(compact('idpTarget'));
    }

    protected function _checkExternalIdentity(): array
    {
        $result = $this->Authentication->getResult();
        if ($result->getStatus() === AppResult::NEW_EXTERNAL_IDENTITY) {
            // return the external identity
            return $result->getData();
        } else {
            $service = $this->Authentication->getAuthenticationService();
            $authenticator = $service->envAuthenticator;
            if ($data = $authenticator->getData($this->getRequest())) {
                return $data;
            }
            return [];
        }
    }

    public function unknownIdentity()
    {
        $identity = $this->_checkExternalIdentity();
        if (empty($identity))
            return $this->redirect('/users/sign-in');
        $session = $this->request->getSession();
        if ($session->check('ignoreIdentity'))
            return $this->redirect('/dashboard/index');
        $this->set(compact('identity'));
    }

    public function connectIdentity()
    {
        $identity = $this->_checkExternalIdentity();
        if (empty($identity))
            return $this->redirect('/users/sign-in');
        // connect account with identity
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            // save the identity shib_eppn to the user
            $user = $this->Authentication->getIdentity();
            $user->shib_eppn = $identity['shib_eppn'];
            $this->Users->save($user);
            $this->_refreshAuthSession();
            $this->Flash->set('Identity connected. Now you can login using your institutional identity provider.');
            return $this->redirect('/dashboard/index');
        }
        // point the form to the regular login action
        // the additional parameter will render the connect_identity view in case of auth errors
        $this->set(compact('identity'));
    }

    public function registerIdentity()
    {
        $identity = $this->_checkExternalIdentity();
        if (empty($identity))
            return $this->redirect('/users/sign-in');
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $this->Flash->set('Please log out before registering a new identity.');
            return $this->redirect('/dashboard/index');
        }
        $data['first_name'] = $identity['first_name'] ?? null;
        $data['last_name'] = $identity['last_name'] ?? null;
        $data['email'] = $identity['email'] ?? null;
        if (empty($data['email']) and preg_match("/^[^@]+@[^@]+\.[a-z]{2,6}$/i", $identity['shib_eppn']))
            $data['email'] = $identity['shib_eppn'];
        // validation is on, show errors!
        $user = $this->Users->newEntity($data, ['validate' => 'create']);
        $user->approved = true;
        $user->email_verified = true;
        $user->shib_eppn = $identity['shib_eppn'];
        if ($this->request->is('post')) {
            // patching the entity, validation and other stuff
            $this->Users->patchEntity($user, $this->request->getData());
            if (!$user->hasErrors(false)) {
                if (empty($user->institution_id)) {
                    $user->approved = false;
                    $this->Users->notifyAdmins();
                } else {
                    try {
                        $this->getMailer('User')->send('welcome', [$user]);
                    } catch (Exception $exception) {
                    }
                }
                $this->Users->save($user);
                $session = $this->request->getSession();
                $session->write('Auth', $user);
                return $this->redirect([
                    'controller' => 'users',
                    'action' => 'registration_success'
                ]);
            } else {
                $this->Flash->set('There are errors! Please check the form and amend the indicated fields.');
            }
        }
        $this->_setOptions();
        $this->set(compact('identity', 'user'));
    }

    public function ignoreIdentity()
    {
        $session = $this->request->getSession();
        $session->write('ignoreIdentity', true);
        $this->redirect('/dashboard/index');
    }

    public function register()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            if (!$this->_checkCaptcha()) {
                $this->Flash->set('The CAPTCHA test failed, please try again.');
                $this->redirect(['controller' => 'users', 'action' => 'register']);
            }
            // patching the entity, validation and other stuff
            $user = $this->Users->newEntity($this->request->getData(), ['validate' => 'create']);
            $user->email_token = $this->Users->generateToken('email_token');
            $user->new_email = $user->email;
            $user->approval_token = $this->Users->generateToken('approval_token');
            $user->approval_token_expires = $this->Users->getLongTokenExpiry();
            // set country_id
            if ($user->institution_id) {
                $country_id = $this->Users->Institutions->find()->where(['id' => $user->institution_id])->first()->country_id;
                $user->set('country_id', $country_id);
            }
            if (!$user->hasErrors(false)) {
                $this->Users->save($user);
                try {
                    $this->getMailer('User')->send('confirmationMail', [$user]);
                } catch (Exception $exception) {    // todo handle empty catch statement
                }
                $session = $this->request->getSession();
                $session->write('Auth', $user);
                return $this->redirect(['controller' => 'users', 'action' => 'registration_success']);
            } else {
                $this->Flash->set('There are errors! Please check the form and amend the indicated fields.');
            }
        }
        // render form
        $this->_setOptions();
        $this->set('user', $user);
    }

    public function registrationSuccess()
    {
        $user = $this->Authentication->getIdentity();
        if ($user->can('accessDashboard', $user))
            return $this->redirect(
                ['controller' => 'Dashboard', 'action' => 'index']
            );  // todo clean up
        $user = $this->Authentication->getIdentity();
        $this->set('user', $user);
    }

    public function verifyMail($token = null)
    {
        $user = $this->Authentication->getIdentity();
        if ($user and !$token) {
            $success = false;
            if ($this->request->is('post')) {
                $data = [
                    'new_email' => $this->request->getData('new_email'),
                    'email_token' => $this->Users->generateToken('email_token')
                ];
                $user->setAccess('*', true);
                $user = $this->Users->patchEntity($user, $data);
                if (!$user->getErrors()) {
                    $this->Users->save($user);
                    $success = true;
                } else {
                    $this->set('user', $user);
                }
            }
            if (!empty($user->new_email)) {
                // this code also runs when hitting the send-again button
                try {
                    $this->getMailer('User')->send('confirmationMail', [$user]);
                } catch (Exception $exception) {
                }
                $success = true;
            }
            if ($success) {
                $this->Flash->set('Confirmation mail has been sent, check your inbox to complete verification.');
                return $this->redirect('/dashboard/index');
            }
        }
        if ($token) {
            // the emailtemplate, used below needs the institution name to be available
            $user = $this->Users->find()->where(['email_token' => $token])->contain(['Institutions'])->first();
            if ($user) {
                // handle new users
                if (!$user->email_verified) {
                    $this->Users->notifyAdmins($user);
                }
                $user->email = $user->new_email;
                $user->new_email = null;
                $user->email_token = null;
                $user->email_verified = true;
                $user = $this->Users->save($user);
                // log the user in
                $this->Authentication->setIdentity($user);
                $this->Flash->set('Your email address has been verified');
                return $this->redirect('/dashboard/index');
            }
        }
        $this->redirect('/');
    }

    public function resetPassword($token = null)
    {
        if (!empty($token)) {
            $user = $this->Users->find()->where([
                'password_token' => $token,
                'password_token_expires >=' => date('Y-m-d H:i:s')
            ])->contain([])->first();
            if ($user) {
                if (!$user->active) {
                    $this->Flash->set('This account has been disabled.');
                    return $this->redirect('/users/sign-in');
                }
                if ($this->request->is('post')) {
                    $user = $this->Users->patchEntity($user, $this->request->getData(), ['fields' => ['password']]);
                    if (!$user->hasErrors()) {
                        $user->password_token = null;
                        $user->password_token_expires = null;
                        $this->Users->save($user);
                        $this->Flash->set('Password has been set successfully, now log in using your new password.');
                        return $this->redirect('/users/sign-in');
                    }
                }
                // render form (password)
                $this->set('token', $token);
            } else {
                $this->Flash->set('The passed token is not valid any more.');
                return $this->redirect('/users/sign-in');
            }
        } elseif (empty($token)) {
            if ($this->request->is('post')) {
                $user = $this->Users->find()->where([
                    'email' => $this->request->getData('email'),
                ])->contain([])->first();
                if (!empty($user)) {
                    if (!$user->active) {
                        $this->Flash->set('This account has been disabled.');
                        return $this->redirect('/users/sign-in');
                    }
                    $user->setAccess('*', true);
                    $user = $this->Users->patchEntity($user, [
                        'password_token_expires' => $this->Users->getShortTokenExpiry(),
                        'password_token' => $this->Users->generateToken('password_token')
                    ]);   // converting the expiry to frozen time type
                    $this->Users->save($user);
                    try {
                        $this->getMailer('User')->send('resetPassword', [$user]);
                    } catch (Exception $exception) {
                    }
                    $this->set('mailSent', true);
                } else {
                    $this->Flash->set('No user with that emailaddress found.');
                    return $this->redirect('/users/sign-in');
                }
            }
            // render form (email)  // todo: merge with changePassword()
        }
    }

    public function approve($key = null)
    {
        $user = $this->Authentication->getIdentity();
        if (is_numeric($key)) {    // access from web interface
            $approvingUser = $this->Users->get($key);
            if (!$approvingUser) {
                $this->Flash->set('An account with id ' . $key . ' could not be found.');
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
            }
        } elseif ($key != null) {    // check if access from email with token
            $approvingUser = $this->Users->find()->where([
                'Users.approval_token' => $key,
                'approved' => 0
            ])
                ->first();
            if (!$approvingUser) {
                $this->Flash->set('Token invalid. Maybe the user is already approved?');
                return $this->redirect(['controller' => 'Users', 'action' => 'approve']);
            }
        }
        if (isset($approvingUser)) {    // the specified user exists
            $this->Authorization->authorize($approvingUser);
            // check if an institution is selected
            if (!$approvingUser->institution_id) {
                $this->Flash->error(__('No institution provided. Edit user data!'));
                return $this->redirect(['action' => 'approve']);
            }
            $approvingUser->set('approved', 1);
            if ($this->Users->save($approvingUser)) {
                $this->getMailer('User')->send('welcome', [$approvingUser]);
                $this->Flash->set('The account has been approved successfully.');
                // check if user has subscribed for newsletter AND in production
                if ($approvingUser->mail_list == 1 && (env('DHCR_BASE_URL') == 'https://dhcr.clarin-dariah.eu/')) {
                    // sync setting with mailman
                    $requestUrl = env('LIST_SUBSCRIBE_URL');
                    $responseText = 'Successfully subscribed';      // response contains "Successfully subscribed:"
                    $requestUrl .= $approvingUser->email . '&adminpw=' . env('LIST_ADMIN_PWD');
                    $html = file_get_contents($requestUrl);
                    if (stripos($html, $responseText) < 0) {
                        //  subscribing with mailman didn't work. unsubscribe in DB, then the user will see a reminder to subscribe in the main dashboard.
                        $approvingUser->mail_list = 0;
                        $this->Users->save($approvingUser);
                    }
                }
            } else {
                $this->Flash->set('Approval failed.');
            }
            return $this->redirect(['controller' => 'Users', 'action' => 'approve']);
        }
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Needs Attention';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'needsAttention';
        $breadcrumTitles[1] = 'Account Approval';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'approve';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        if ($user->is_admin) {
            $query = $this->Users->find('all', ['order' => ['Users.created' => 'DESC']])
                ->contain('Institutions')
                ->where([
                    'approved' => 0,
                    'active' => 1
                ]);
            $this->set('users', $this->paginate($query));
            $usersCount = $this->Users->find()->where([
                'approved' => 0,
                'active' => 1
            ])
                ->count();
        } elseif ($user->user_role_id == 2) {
            $query = $this->Users->find('all', ['order' => ['Users.created' => 'DESC']])
                ->contain('Institutions')
                ->where([
                    'approved' => 0,
                    'active' => 1,
                    'Users.country_id' => $user->country_id
                ]);
            $this->set('users', $this->paginate($query));
            $usersCount = $this->Users->find()->where([
                'approved' => 0,
                'active' => 1,
                'Users.country_id' => $user->country_id
            ])
                ->count();
        } else {
            $this->Flash->error(__('Not authorized to user approval'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        $this->set(compact('usersCount'));
        $this->set(compact('user')); // required for contributors menu
        // "customize" view
        $this->set('users_icon', 'user');
        $this->set('users_view_type', 'Account Approval');
        $this->render('users-list');
    }

    public function view($id = null)
    {
        $viewedUser = $this->Users->get($id, ['contain' => ['Countries', 'Institutions']]);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($viewedUser);
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'User Details';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'view';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('viewedUser'));
    }

    public function edit($id = null, $photo_action = null)
    {
        $editUser = $this->Users->get($id, ['contain' => ['Countries']]);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($editUser);
        if ($user->is_admin) {
            $editUser->setAccess('user_role_id', true);
            $editUser->setAccess('is_admin', true);
            $editUser->setAccess('user_admin', true);
            $editUser->setAccess('active', true);
        }
        $editUser = $this->Users->patchEntity($editUser, $this->request->getData());

        if ($photo_action == 'delete_photo' && $user->is_admin) {
            $errorMessage = false;
            if (!unlink('uploads/user_photos/' . $editUser->photo_url)) {
                $errorMessage = 'Unable to delete photo';
            }
            $editUser->photo_url = NULL;
            if (!$this->Users->save($editUser)) {
                $errorMessage = 'Unable to remove photo file';
            }
            if ($errorMessage) {
                $this->Flash->error($errorMessage);
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'contributorNetwork']);
            } else {
                $this->Flash->success(__('Photo removed.'));
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'contributorNetwork']);
            }
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $upload = $this->request->getUploadedFile('photo');
            if ($upload !== null && $upload->getError() !== \UPLOAD_ERR_NO_FILE) {
                $photoObject = $this->request->getData('photo');
                $fileType = $photoObject->getClientMediaType();
                $errorMessage = false;
                if ($fileType == "image/png" || $fileType == "image/jpeg" || $fileType == "image/jpg") {
                    $size = getimagesize($photoObject->getStream()->getMetadata('uri'));
                    $width = $size[0];
                    $height = $size[1];
                    if ($width == 132 && $height == 170) {
                        if (!file_exists('uploads/user_photos/')) {
                            mkdir('uploads/user_photos/', 0775, true);
                        }
                        $timestamp = new FrozenTime();
                        $timestamp = $timestamp->i18nFormat('yyyy-MM-dd_HH-mm-ss');
                        $photoUrl = $timestamp . '-' . $photoObject->getClientFilename();
                        // truncate long filenames
                        if (strlen($photoUrl)  > 100) {
                            $dotPos = strrpos($photoUrl, '.');
                            if ($dotPos == FALSE) {
                                $this->Flash->error('File extention missing');
                                return $this->redirect(['controller' => 'Dashboard', 'action' => 'contributorNetwork']);
                            }
                            $ext = substr($photoUrl, $dotPos, 5);
                            $photoUrl = substr($photoUrl, 0, $dotPos);  // remove extention
                            $photoUrl = substr($photoUrl, 0, 95);  // truncate
                            $photoUrl = $photoUrl . $ext;
                        }
                        $photoObject->moveTo('uploads/user_photos/' . $photoUrl);
                        $editUser->photo_url = $photoUrl;
                    } else {
                        $errorMessage = "Wrong file dimensions";
                    }
                } else {
                    $errorMessage = 'Wrong filetype';
                }
                if ($errorMessage) {
                    $this->Flash->error($errorMessage);
                    return $this->redirect(['controller' => 'Dashboard', 'action' => 'contributorNetwork']);
                }
            }

            // set country_id
            $country_id = $this->Users->Institutions->find()->where(['id' => $editUser->institution_id])->first()->country_id;
            $editUser->set('country_id', $country_id);
            if ($this->Users->save($editUser)) {
                $this->Flash->success(__('The user has been updated.'));
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'contributorNetwork']);
            }
            $this->Flash->error(__('The user could not be updated. Please, try again.'));
        }
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'Edit User';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'edit';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $institutions = $this->Users->Institutions->find('list', ['order' => 'Institutions.name asc']);
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('editUser', 'institutions'));
    }

    protected function _setOptions()
    {
        $institutions = $this->Users->Institutions->find('list', [
            'fields' => ['Institutions.id', 'Institutions.name', 'Countries.name'], // restrict selected fields
            'keyField' => 'id',
            'valueField' => 'name',
            'groupField' => 'country.name'  // the resulting data array path, not very intuitive compared to other field naming conventions in this context
        ])->contain(['Countries'])->toArray();
        // restore alphabetical country order, sort option on finder does not have effect
        ksort($institutions);
        foreach ($institutions as $country => &$country_list)
            asort($country_list);
        $countries = $this->Users->Countries->find('list', [
            'order' => 'Countries.name ASC'
        ])->toArray();
        $userRoles = $this->Users->UserRoles->find('list')->toArray();
        $this->set(compact('institutions', 'countries', 'userRoles'));
    }

    public function whichTerms()
    {
        if (!str_ends_with($_SERVER['REQUEST_URI'], '?'))
            $this->redirect('/users/which-terms?');
    }

    public function profile()
    {
        $user = $this->Users->get($this->Authentication->getIdentity()->id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Profile updated.'));
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'profileSettings']);
            } else {
                $this->Flash->error(__('Profile could not be updated. Please, try again.'));
            }
        }
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Profile Settings';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'profileSettings';
        $breadcrumTitles[1] = 'Edit Profile';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'profile';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $institutions = $this->Users->Institutions->find('list', ['order' => 'name asc']);
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('institutions'));
    }

    public function changeEmail()
    {
        $user = $this->Users->get($this->Authentication->getIdentity()->id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $hashedPassword = $user->password;
            $submittedPassword = $this->request->getData()['password'];
            $hasher = new DefaultPasswordHasher();
            if ($hasher->check($submittedPassword, $hashedPassword)) {
                // user submitted the correct password
                $data = [
                    'new_email' => $this->request->getData('new_email'),
                    'email_token' => $this->Users->generateToken('email_token')
                ];
                $user->setAccess('*', true);
                $user = $this->Users->patchEntity($user, $data);
                if (!$user->getErrors()) {
                    $this->Users->save($user);
                    try {
                        $this->getMailer('User')->send('confirmationMail', [$user]);
                        $this->Flash->success(__('Confirmation mail has been sent, check your inbox to complete verification.'));
                        return $this->redirect(['controller' => 'Dashboard', 'action' => 'profileSettings']);
                    } catch (Exception $exception) {
                    }
                    $this->Flash->error(__('Error. Mail not sent.'));
                } else {
                    $this->Flash->error(__('Error saving token.'));
                }
            } else {
                $this->Flash->error(__('Wrong password.'));
            }
        }
        $this->viewBuilder()->setLayout('contributors');
        // set breadcrums
        $breadcrumTitles[0] = 'Profile Settings';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'profileSettings';
        $breadcrumTitles[1] = 'Change Email Address';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'changeEmail';
        $this->set(compact('user')); // required for contributors menu
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
    }

    public function changePassword()
    {
        $user = $this->Users->get($this->Authentication->getIdentity()->id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($user != null) {
                // set token and send mail
                $user->setAccess('*', true);
                $user = $this->Users->patchEntity($user, [
                    'password_token_expires' => $this->Users->getShortTokenExpiry(),
                    'password_token' => $this->Users->generateToken('password_token')
                ]);
                $this->Users->save($user);
                try {
                    $this->getMailer('User')->send('resetPassword', [$user]);
                    $this->Flash->success(__('Password reset mail has been sent.'));
                    return $this->redirect(['controller' => 'Dashboard', 'action' => 'profileSettings']);
                } catch (Exception $exception) {
                }
                $this->Flash->error(__('Error. Mail not sent.'));
            } else {
                $this->Flash->error(__('Error. User not found.'));
            }
        }
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Profile Settings';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'profileSettings';
        $breadcrumTitles[1] = 'Change Password';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'changePassword';
        $this->set(compact('user')); // required for contributors menu
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
    }

    public function newsletter()
    {
        $user = $this->Users->get($this->Authentication->getIdentity()->id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $subscriptionStatusNew = $this->request->getData()['mail_list'];
            if ($subscriptionStatusNew != $user->mail_list) {   // user changed subscription status
                if (env('DHCR_BASE_URL') != 'https://dhcr.clarin-dariah.eu/') { // guard against changes to mailman from not production
                    $this->Flash->error('Error: Changes to subscription can only be made in production.');
                    return $this->redirect(['controller' => 'Dashboard', 'action' => 'profileSettings']);
                }
                if ($subscriptionStatusNew == 1) {
                    $requestUrl = env('LIST_SUBSCRIBE_URL');
                    $responseText = 'Successfully subscribed';      // response contains "Successfully subscribed:"
                } else {
                    $requestUrl = env('LIST_UNSUBSCRIBE_URL');
                    $responseText = 'Successfully unsubscribed';    // response contains "Successfully Unsubscribed:"
                }
                $requestUrl .= $user->email . '&adminpw=' . env('LIST_ADMIN_PWD');
                $html = file_get_contents($requestUrl);
                if (stripos($html, $responseText) > 0) {    // mailman processed action
                    $user = $this->Users->patchEntity($user, $this->request->getData());
                    if ($this->Users->save($user)) {
                        $this->Flash->success($responseText);
                        return $this->redirect(['controller' => 'Dashboard', 'action' => 'profileSettings']);
                    } else {
                        $this->Flash->error('Error: Subscription status not saved in DB.');
                    }
                } else {    // invalid response from mailman
                    $this->Flash->error('Error: Subscription change not processed external.');
                }
            }
        }
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Profile Settings';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'profileSettings';
        $breadcrumTitles[1] = 'Sign up to the Mailing List';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'newsletter';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $this->set(compact('user')); // required for contributors menu
    }

    public function invite()
    {
        $this->loadModel('Institutions');
        $this->loadModel('InviteTranslations');
        $invitedUser = $this->Users->newEmptyEntity();
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($invitedUser);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $invitedUser = $this->Users->patchEntity($invitedUser, $this->request->getData());
            // check if an institution is selected
            if (!$invitedUser->institution_id) {
                $this->Flash->error(__('No institution selected!'));
                return $this->redirect(['action' => 'invite']);
            }
            // set country_id
            $country_id = $this->Institutions->find()->where(['id' => $invitedUser->institution_id])->first()->country_id;
            $invitedUser->set('country_id', $country_id);
            // set approved and email verified, since the mod checked this
            $invitedUser->set('approved', 1);
            $invitedUser->set('email_verified', 1);
            // set invite_message
            $inviteTranslationId = $this->request->getData('inviteTranslation');
            $inviteMessage = $this->InviteTranslations->find()->where(['id' => $inviteTranslationId])->first();
            // set password token
            $invitedUser->setAccess('*', true);
            $invitedUser = $this->Users->patchEntity($invitedUser, [
                'password_token_expires' => new FrozenTime('+ 1 days'),
                'password_token' => $this->Users->generateToken('password_token')
            ]);
            // set password link
            $passwordLink = env('DHCR_BASE_URL') . 'users/reset_password/' . $invitedUser->password_token;
            //  personalize message
            $messageBody = $inviteMessage->messageBody;
            if ($user->academic_title != null) {
                $fullName = h(ucfirst($user->academic_title)) . ' ';
            } else {
                $fullName = '';
            }
            $fullName = $fullName . h(ucfirst($user->first_name)) . ' ' . h(ucfirst($user->last_name));
            $messageBody = str_replace('-fullname-', $fullName, $messageBody);
            $messageBody = str_replace('-passwordlink-', $passwordLink, $messageBody);
            $messageSubject = '[DH Course Registry] ' . $inviteMessage->subject;
            if ($this->Users->save($invitedUser)) {
                $mailer = new Mailer('default');
                $mailer->setFrom(env('APP_MAIL_DEFAULT_FROM'))
                    ->setReplyTo([env('APP_MAIL_DEFAULT_REPLY_TO') => 'DH Course Registry'])
                    ->setTo($invitedUser->email)
                    ->setCc(env('APP_MAIL_DEFAULT_CC'))
                    ->setBcc($user->email)
                    ->setReplyTo($user->email)
                    ->setSubject($messageSubject)
                    ->deliver($messageBody);
                $this->Flash->success(__('The invitation has been sent.'));
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'contributorNetwork']);
            }
            $this->Flash->error(__('The invitation could not be sent. Please, try again.'));
        }
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'Invite User';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'invite';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $institutions = $this->Institutions->find('list', ['order' => 'Institutions.name asc']);
        $inviteTranslations = $this->InviteTranslations->find('all', ['order' => 'sortOrder asc', 'contain' => 'Languages'])
            ->where(['active ' => true]);
        $languageList = [];
        foreach ($inviteTranslations as $inviteTranslation) {
            $languageList += [$inviteTranslation->id => $inviteTranslation->language->name];
        }
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('invitedUser', 'institutions', 'inviteTranslations', 'languageList'));
    }

    public function reinvite($id = null)
    {
        $this->loadModel('InviteTranslations');
        $invitedUser = $this->Users->get($id, ['contain' => ['Institutions']]);
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($invitedUser);
        if ($this->request->is(['patch', 'post', 'put'])) {
            // set invite_message
            $inviteTranslationId = $this->request->getData('inviteTranslation');
            $inviteMessage = $this->InviteTranslations->find()->where(['id' => $inviteTranslationId])->first();
            // set password token
            $invitedUser->setAccess('*', true);
            $invitedUser = $this->Users->patchEntity($invitedUser, [
                'password_token_expires' => new FrozenTime('+ 1 days'),
                'password_token' => $this->Users->generateToken('password_token')
            ]);
            // set password link
            $passwordLink = env('DHCR_BASE_URL') . 'users/reset_password/' . $invitedUser->password_token;
            //  personalize message
            $messageBody = $inviteMessage->messageBody;
            if ($user->academic_title != null) {
                $fullName = h(ucfirst($user->academic_title)) . ' ';
            } else {
                $fullName = '';
            }
            $fullName = $fullName . h(ucfirst($user->first_name)) . ' ' . h(ucfirst($user->last_name));
            $messageBody = str_replace('-fullname-', $fullName, $messageBody);
            $messageBody = str_replace('-passwordlink-', $passwordLink, $messageBody);
            $messageSubject = '[DH Course Registry] ' . $inviteMessage->subject;
            if ($this->Users->save($invitedUser)) {
                $mailer = new Mailer('default');
                $mailer->setFrom(env('APP_MAIL_DEFAULT_FROM'))
                    ->setReplyTo([env('APP_MAIL_DEFAULT_REPLY_TO') => 'DH Course Registry'])
                    ->setTo($invitedUser->email)
                    ->setCc(env('APP_MAIL_DEFAULT_CC'))
                    ->setBcc($user->email)
                    ->setReplyTo($user->email)
                    ->setSubject($messageSubject)
                    ->deliver($messageBody);
                $this->Flash->success(__('The invitation has been sent.'));
                return $this->redirect(['controller' => 'Dashboard', 'action' => 'contributorNetwork']);
            }
            $this->Flash->error(__('The invitation could not be sent. Please, try again.'));
        }
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'ReInvite User';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'ReInvite';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $inviteTranslations = $this->InviteTranslations->find('all', ['order' => 'sortOrder asc', 'contain' => 'Languages'])
            ->where(['active ' => true]);
        $languageList = [];
        foreach ($inviteTranslations as $inviteTranslation) {
            $languageList += [$inviteTranslation->id => $inviteTranslation->language->name];
        }
        $this->set(compact('user')); // required for contributors menu
        $this->set(compact('invitedUser', 'languageList'));
    }

    public function moderated()
    {
        $user = $this->Authentication->getIdentity();
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'Moderated Users';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'moderated';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        if ($user->user_role_id == 2) {
            $query = $this->Users->find('all', ['order' => ['Users.last_name' => 'ASC']])
                ->where([
                    'approved' => 1,
                    'active' => 1,
                    'Users.country_id' => $user->country_id,
                ])
                ->contain(['Institutions']);
            $this->set('users', $this->paginate($query));
        } else {
            $this->Flash->error(__('Not authorized to moderated users'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        $this->set(compact('user')); // required for contributors menu
        // "customize" view
        $this->set('users_icon', 'user');
        $this->set('users_view_type', 'Moderated Users');
        $this->render('users-list');
    }

    public function all()
    {
        $user = $this->Authentication->getIdentity();
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'All Users';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'all';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        if ($user->is_admin) {
            $query = $this->Users->find('all', ['order' => ['Users.last_name' => 'ASC']])
                ->contain(['Institutions']);
            $this->set('users', $this->paginate($query));
        } else {
            $this->Flash->error(__('Not authorized to all users'));
            return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
        }
        $this->set(compact('user')); // required for contributors menu
        // "customize" view
        $this->set('users_icon', 'user');
        $this->set('users_view_type', 'All Users');
        $this->render('users-list');
    }

    public function pendingInvitations()
    {
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'Pending Invitations';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'pendingInvitations';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        if ($user->is_admin) {
            $query = $this->Users->find('all', ['order' => ['Users.password_token_expires' => 'DESC']])
                ->where([
                    'approved' => 1,
                    'active' => 1,
                    'email_verified' => 1,
                    'password IS NULL',
                    'password_token IS NOT NULL',
                ])
                ->contain(['Institutions']);
            $this->set('users', $this->paginate($query));
        } elseif ($user->user_role_id == 2) {
            $query = $this->Users->find('all', ['order' => ['Users.password_token_expires' => 'DESC']])
                ->where([
                    'approved' => 1,
                    'active' => 1,
                    'email_verified' => 1,
                    'password IS NULL',
                    'password_token IS NOT NULL',
                    'Users.country_id' => $user->country_id,
                ])
                ->contain(['Institutions']);
            $this->set('users', $this->paginate($query));
        }
        $this->set(compact('user')); // required for contributors menu
        // "customize" view
        $this->set('users_icon', 'option-horizontal');
        $this->set('users_view_type', 'Pending Invitations');
        $this->render('users-list');
    }

    public function moderators()
    {
        $user = $this->Authentication->getIdentity();
        $this->Authorization->authorize($user);
        $this->viewBuilder()->setLayout('contributors');
        // Set breadcrums
        $breadcrumTitles[0] = 'Contributor Network';
        $breadcrumControllers[0] = 'Dashboard';
        $breadcrumActions[0] = 'contributorNetwork';
        $breadcrumTitles[1] = 'Moderators';
        $breadcrumControllers[1] = 'Users';
        $breadcrumActions[1] = 'moderators';
        $this->set((compact('breadcrumTitles', 'breadcrumControllers', 'breadcrumActions')));
        $query = $this->Users->find('all', ['order' => ['Users.last_name' => 'ASC']])
            ->where([
                'user_role_id' => 2
            ])
            ->contain(['Institutions']);
        $this->set('users', $this->paginate($query));
        $this->set(compact('user')); // required for contributors menu
        // "customize" view
        $this->set('users_icon', 'asterisk');
        $this->set('users_view_type', 'Moderators');
        $this->render('users-list');
    }
}
