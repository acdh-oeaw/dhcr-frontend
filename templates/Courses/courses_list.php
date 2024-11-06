<?php

use Cake\Core\Configure;

$coursesCount = $courses->count();
?>

<div class="courses index content">
    <p></p>
    <h2><span class="glyphicon glyphicon-<?= $course_icon ?>"></span>&nbsp;&nbsp;&nbsp;<?= $course_view_type ?></h2>
    <div class="table-responsive">
        <?php
        if ($course_view_type == 'My Courses') {
            echo $this->Html->image('dhcr-feature-badge-300.png', [
                'url' => '/img/dhcr-feature-badge-300.png',
                'target' => '_blank',
                'width' => 150,
                'height' => 67
            ]);
        ?>
            <p>Please help promote the DH course registry by sharing your courses on social media or placing the DHCR-featured
                badge on institutional websites.</p>
            <font color="red"><strong>
                    <p>
                        If the course does not take place anymore, please unpublish it (Click Edit -> Uncheck publish).
                    </p>
                    <p>If the course is still running but you wish to transfer the course ownership to a fellow lecturer, please
                        <a href="/info#contact">contact us</a>.
                    </p>
                </strong></font>
        <?php
        }
        if ($course_view_type != 'Course Approval' && $coursesCount > 0) {
        ?>
            <p>
                <strong><u>Course Status</u></strong><br>
                <strong>
                    <font color="green">Green:</font>
                </strong> Actively maintained<br>
                <strong>
                    <font color="orange">Orange:</font>
                </strong> Reminder sent more than 1 month ago, needs to be updated<br>
                <strong>
                    <font color="red">Red:</font>
                </strong> Outdated, not shown in the public registry
            </p>
        <?php
        }
        if ($coursesCount == 0) {
            echo '<p>No courses in this list.</p>';
            if ($course_view_type == 'My Courses') {
                echo '<p>Click ' . $this->Html->link(__('here'), ['action' => 'add']) . ' to add one.</p>';
            }
            if ($course_view_type == 'Course Approval') {
                echo '<p>You are up to date!</p>';
            }
        }
        ?>
        </p>
        <table>
            <?php if ($coursesCount > 0) { ?>
                <thead>
                    <tr>
                        <th align="center" style="padding: 5px">Actions</th>
                        <th align="left" style="padding: 5px"><?= $this->Paginator->sort('updated') ?></th>
                        <th align="left" style="padding: 5px"><?= $this->Paginator->sort('active', ['label' => 'Published']) ?></th>
                        <th align="left" style="padding: 5px"><?= $this->Paginator->sort('name', ['label' => 'Course Name']) ?></th>
                        <th align="left" style="padding: 5px">Education Type</th>
                        <th align="left" style="padding: 5px">Institution</th>
                        <?php if ($course_view_type != 'My Courses') {
                            echo '<th align="left" style="padding: 5px">Course owner</th>';
                        } ?>
                        <th align="left" style="padding: 5px">Course URL</th>
                    </tr>
                </thead>
            <?php } ?>
            <tbody>
                <?php foreach ($courses as $course) : ?>
                    <tr>
                        <td style="padding: 5px" align="center">
                            <?php
                            if ($course_view_type == 'Course Approval') {
                                echo $this->Html->link(__('Approve'), ['action' => 'approve', $course->id]) . '<br>';
                            }
                            echo $this->Html->link(__('Update/Edit'), ['action' => 'edit', $course->id]) . '<br>';
                            echo $this->Html->link(__('Share'), ['action' => 'view', $course->id]) . '<br>';
                            if ($user->user_role_id == 2 || $user->is_admin) {
                                echo $this->Html->link(__('Transfer'), ['action' => 'transfer', $course->id]) . '<br>';
                            }
                            ?>
                        </td>
                        <td style="padding: 5px" bgcolor="
                            <?php
                            if ($course->updated > Configure::read('courseYellowDate')) {
                                echo '#81d41a';
                                $textColor = 'black';
                            } elseif (($course->updated < Configure::read('courseYellowDate')) && ($course->updated > Configure::read('courseRedDate'))) {
                                echo '#ffbf00';
                                $textColor = 'black';
                            } else {
                                echo '#fc2530';
                                $textColor = 'white';
                            }
                            ?>
                        ">
                            <strong>
                                <font color="<?= $textColor ?>"><?= $course->updated->timeAgoInWords(['format' => 'yyyy-MM-dd', 'end' => '+1 year']) ?></font>
                            </strong>
                        </td>
                        <td style="padding: 5px">
                            <strong>
                                <font color="<?= ($course->active) ? 'green">Yes' : 'red">No' ?></font></strong>
                        </td>
                        <td style=" padding: 5px"><?= $this->Html->link(__($course->name), ['action' => 'view', $course->id]) ?>
                        <td style="padding: 5px"><?= $course->course_type->name ?></td>
                        <td style="padding: 5px"><?= $course->institution->name ?></td>
                        <?php if ($course_view_type != 'My Courses') {
                            echo '<td style="padding: 5px">' . ucfirst($course->user->academic_title) . ' ' . ucfirst($course->user->first_name)
                                . ' ' . ucfirst($course->user->last_name)  . '</td>';
                        } ?>
                        <td style="padding: 5px"><?= $this->Html->link('Link', $course->info_url) ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <?= $this->Paginator->first('<< ' . __('first')) ?>
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
        <?= $this->Paginator->last(__('last') . ' >>') ?>
        <p>&nbsp;</p>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>