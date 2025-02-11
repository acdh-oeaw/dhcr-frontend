<div id="sitemap">
    <ul>
        <li>
            <p>
                <?= $this->Html->link(
                    '<span class="glyphicon glyphicon-home"></span>Course Registry',
                    ['controller' => 'Courses', 'action' => 'index'],
                    ['escape' => false]
                ) ?>
            </p>
        </li>
        <li>
            <p>
                <?= $this->Html->link(
                    '<span class="glyphicon glyphicon-modal-window"></span>Dashboard',
                    ['controller' => 'Dashboard', 'action' => 'index'],
                    ['escape' => false]
                ) ?>
            </p>
        </li>
        <li>
            <p>
                <?= $this->Html->link(
                    '<span class="glyphicon glyphicon-flag"></span>Needs Attention',
                    ['controller' => 'Dashboard', 'action' => 'needsAttention'],
                    ['escape' => false]
                ) ?>
            </p>
            <ul class="attention">
                <?php
                if ($user->user_role_id == 2 || $user->is_admin) {
                    echo '<li>';
                    echo $this->Html->link('Account Approval', ['controller' => 'Users', 'action' => 'approve']);
                    echo '</li>';
                    echo '<li>';
                    echo $this->Html->link('Course Approval', ['controller' => 'Courses', 'action' => 'approve']);
                    echo '</li>';
                }
                ?>
                <li>
                    <?= $this->Html->link(
                        'Course Expiry',
                        ['controller' => 'Courses', 'action' => 'expired']
                    ) ?>
                </li>
            </ul>
        </li>
        <li>
            <p>
                <?= $this->Html->link(
                    '<span class="glyphicon glyphicon-education"></span>Administrate Courses',
                    ['controller' => 'Dashboard', 'action' => 'adminCourses'],
                    ['escape' => false]
                ) ?>
            </p>
            <ul class="dashboard">
                <li>
                    <?= $this->Html->link(
                        'Add Course',
                        ['controller' => 'Courses', 'action' => 'add']
                    ) ?>
                </li>
                <li>
                    <?= $this->Html->link(
                        'My Courses',
                        ['controller' => 'Courses', 'action' => 'myCourses']
                    ) ?>
                </li>
                <?php
                if ($user->user_role_id == 2) {
                    echo '<li>';
                    echo $this->Html->link('Moderated Courses', ['controller' => 'Courses', 'action' => 'moderated']);
                    echo '</li>';
                }
                if ($user->is_admin) {
                    echo '<li>';
                    echo $this->Html->link('All Courses', ['controller' => 'Courses', 'action' => 'all']);
                    echo '</li>';
                }
                ?>
            </ul>
        </li>
        <?php
        if ($user->user_role_id == 2 || $user->is_admin) {
        ?>
            <li>
                <p>
                    <?= $this->Html->link(
                        '<span class="glyphicon glyphicon-user"></span>Contributor Network',
                        ['controller' => 'Dashboard', 'action' => 'contributorNetwork'],
                        ['escape' => false]
                    ) ?>
                </p>
                <ul class="users">
                    <li>
                        <?= $this->Html->link(
                            'Invite User',
                            ['controller' => 'Users', 'action' => 'invite']
                        ) ?>
                    </li>
                    <li>
                        <?php
                        if ($user->user_role_id == 2) {
                            echo $this->Html->link(
                                'Moderated Users',
                                ['controller' => 'Users', 'action' => 'moderated']
                            );
                        }
                        ?>
                    </li>
                    <li>
                        <?php
                        if ($user->is_admin) {
                            echo $this->Html->link(
                                'All Users',
                                ['controller' => 'Users', 'action' => 'all']
                            );
                        }
                        ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            'Pending Invitations',
                            ['controller' => 'Users', 'action' => 'pendingInvitations']
                        ) ?>
                    </li>
                    <li>
                        <?php
                        if ($user->is_admin) {
                            echo $this->Html->link(
                                'Moderators',
                                ['controller' => 'Users', 'action' => 'moderators']
                            );
                        }
                        ?>
                    </li>
                </ul>
            </li>
        <?php
        }
        if ($user->user_role_id == 2 || $user->is_admin) {
        ?>
            <li>
                <p>
                    <?= $this->Html->link(
                        '<span class="glyphicon glyphicon-list"></span>Category Lists',
                        ['controller' => 'Dashboard', 'action' => 'categoryLists'],
                        ['escape' => false]
                    ) ?>
                </p>
                <ul class="tables">
                    <li>
                        <?= $this->Html->link(
                            'Cities',
                            ['controller' => 'Cities', 'action' => 'index']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            'Institutions',
                            ['controller' => 'Institutions', 'action' => 'index']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            'Countries',
                            ['controller' => 'Countries', 'action' => 'index']
                        ) ?>
                    </li>
                    <li>
                        <?php
                        if ($user->is_admin) {
                            echo $this->Html->link(
                                'Languages',
                                ['controller' => 'Languages', 'action' => 'index']
                            );
                        }
                        ?>
                    </li>
                    <li>
                        <?php
                        if ($user->is_admin) {
                            echo $this->Html->link(
                                'Translations',
                                ['controller' => 'InviteTranslations', 'action' => 'index']
                            );
                        }
                        ?>
                    </li>
                    <li>
                        <?php
                        if ($user->is_admin) {
                            echo $this->Html->link(
                                'Log Entries',
                                ['controller' => 'Logentries', 'action' => 'index']
                            );
                        }
                        ?>
                    </li>
                    <li>
                        <?php
                        if ($user->is_admin) {
                            echo $this->Html->link(
                                'FAQ Questions',
                                ['controller' => 'Dashboard', 'action' => 'faqQuestions']
                            );
                        }
                        ?>
                    </li>
                </ul>
            </li>
        <?php
        }
        ?>
        <li>
            <p>
                <?= $this->Html->link(
                    '<span class="glyphicon glyphicon-cog"></span>Profile Settings',
                    ['controller' => 'Dashboard', 'action' => 'profileSettings'],
                    ['escape' => false]
                ) ?>
            </p>
            <ul class="profile">
                <li>
                    <?= $this->Html->link(
                        'Change Email Address',
                        ['controller' => 'Users', 'action' => 'changeEmail']
                    ) ?>
                </li>
                <li>
                    <?= $this->Html->link(
                        'Change Password',
                        ['controller' => 'Users', 'action' => 'changePassword']
                    ) ?>
                </li>
                <li>
                    <?= $this->Html->link(
                        'Sign up to the Mailing List',
                        ['controller' => 'Users', 'action' => 'newsletter']
                    ) ?>
                </li>
                <li>
                    <?= $this->Html->link(
                        'Edit Profile',
                        ['controller' => 'Users', 'action' => 'profile']
                    ) ?>
                </li>
            </ul>
        </li>
        <li>
            <p>
                <?= $this->Html->link(
                    '<span class="glyphicon glyphicon-question-sign"></span>Help',
                    ['controller' => 'Dashboard', 'action' => 'help'],
                    ['escape' => false]
                ) ?>
            </p>
            <ul class="profile">
                <li>
                    <?= $this->Html->link(
                        'Contributor FAQ',
                        ['controller' => 'faqQuestions', 'action' => 'faqList', 'contributor']
                    ) ?>
                </li>
                <?php
                if ($user->user_role_id == 2 || $user->is_admin) {
                    echo '<li>';
                    echo $this->Html->link(
                        'Moderator FAQ',
                        ['controller' => 'faqQuestions', 'action' => 'faqList', 'moderator']
                    );
                    echo '</li>';
                }
                if ($user->user_role_id == 2 || $user->is_admin) {
                    echo '<li>';
                    echo $this->Html->link('Users, Access and Workflows', ['controller' => 'FaqQuestions', 'action' => 'usersAccessWorkflows']);
                    echo '</li>';
                }
                ?>
            </ul>
        </li>
        <?php
        if ($user->is_admin) {
        ?>
            <li>
                <p>
                    <?= $this->Html->link(
                        '<span class="glyphicon glyphicon-stats"></span>Statistics',
                        ['controller' => 'Dashboard', 'action' => 'statistics'],
                        ['escape' => false]
                    ) ?>
                </p>
                <ul class="profile">
                    <li>
                        <?= $this->Html->link('Summary Statistics', ['controller' => 'Statistics', 'action' => 'summaryStatistics']) ?>
                    </li>
                    <li>
                        <?= $this->Html->link('Course Statistics', ['controller' => 'Statistics', 'action' => 'courseStatistics']) ?>
                    </li>
                    <li>
                        <?= $this->Html->link('User Statistics', ['controller' => 'Statistics', 'action' => 'userStatistics']) ?>
                    </li>
                    <li>
                        <?= $this->Html->link('App Info', ['controller' => 'Statistics', 'action' => 'appInfo']) ?>
                    </li>
                </ul>
            </li>
        <?php } ?>
        <li>
            <p>
                <?= $this->Html->link(
                    '<span class="glyphicon glyphicon-log-out"></span>Logout',
                    ['controller' => 'Users', 'action' => 'logout'],
                    ['escape' => false]
                ) ?>
            </p>
        </li>

    </ul>
</div>