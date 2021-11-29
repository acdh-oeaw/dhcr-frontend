<?php

/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $cities
 */
?>
<div class="cities index content">
    <?= $this->Html->link(__('Add City'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <p>&nbsp;</p>
    <h3><?= __('Cities') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('id') ?></th>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('name') ?></th>
                    <th align="left" style="padding: 5px">Country</th>
                    <th class="actions" align="left" style="padding: 5px"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cities as $city) : ?>
                    <tr>
                        <td style="padding: 5px"><?= h($city->id) ?></td>
                        <td style="padding: 5px"><?= h($city->name) ?></td>
                        <td style="padding: 5px"><?= h($city->country->name)  ?></td>
                        <td class="actions" style="padding: 5px">
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $city->id]) ?>
                            &nbsp;&nbsp;&nbsp;
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $city->id], ['confirm' => __('Are you sure you want to delete # {0}?', $city->id)]) ?>
                        </td>
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