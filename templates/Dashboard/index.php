<p></p>
<div id="welcome_user">
            <?php
            echo '<p></p>';
            // Welcome user
            echo 'Hello ' . ucfirst(trim($user->academic_title)) . ' ' . ucfirst(trim($user->first_name)) . ' ' . ucfirst(trim($user->last_name))
                . ', thanks for contributing to the DHCR';
            switch ($user->user_role_id) {
                case 1:
                    echo ' as <strong><font color="black"> administrator</font></strong>';
                    break;
                case 2:
                    echo ' as <strong><font color="black"> moderator</font></strong> of  <strong><font color="black">' . $user->country->name .'</font></strong>';
                    break;
            }
            echo '.';
            ?>
        </div>
<p></p>
<h2><span class="glyphicon glyphicon-star"></span>&nbsp;&nbsp;&nbsp;Dashboard</h2>
<p></p>
<div id="dashboard">
    <?= $this->Html->link(
        '<p></p><span class="glyphicon glyphicon-flag"></span><br>Needs Attention<p></p>',
        [
            'controller' => 'dashboard',
            'action' => 'needsAttention'
        ],
        [
            'class' => 'blue button',
            'title' => 'Needs Attention',
            'escape' => false
        ]
    ) ?>
    <?= $this->Html->link(
        '<p></p><span class="glyphicon glyphicon-list"></span><br>Category Lists<p></p>',
        [
            'controller' => 'dashboard',
            'action' => 'categoryLists'
        ],
        [
            'class' => 'blue button',
            'title' => 'Category Lists',
            'escape' => false
        ]
    ) ?>
    <?= $this->Html->link(
        '<p></p><span class="glyphicon glyphicon-education"></span><br>Administrate Courses<p></p>',
        [
            'controller' => 'Dashboard',
            'action' => 'adminCourses'
        ],
        [
            'class' => 'blue button',
            'title' => 'Administrate Courses',
            'escape' => false
        ]
    ) ?>
    <?= $this->Html->link(
        '<p></p><span class="glyphicon glyphicon-cog"></span><br>Profile Settings<p></p>',
        [
            'controller' => 'Dashboard',
            'action' => 'profileSettings'
        ],
        [
            'class' => 'blue button',
            'title' => 'Profile Settings',
            'escape' => false
        ]
    ) ?>
    <?= $this->Html->link(
        '<p></p><span class="glyphicon glyphicon-user"></span><br>Contributor Network<p></p>',
        [
            'controller' => 'Dashboard',
            'action' => 'contributorNetwork'
        ],
        [
            'class' => 'blue button',
            'title' => 'Contributor Network',
            'escape' => false
        ]
    ) ?>

</div>