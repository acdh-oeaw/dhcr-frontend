<div class="logentries index content">
    <p></p>
    <h2><span class="glyphicon glyphicon-folder-open"></span>&nbsp;&nbsp;&nbsp;Log Entries</h2>
    <p></p>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
    </div>
    <p></p>
    <div class="table-responsive">
        <p>
            <ul>
                <li><?= $this->Html->link('Show all log entries', ['controller' => 'logentries', 'action' => 'index']) ?></li>
                <li><?= $this->Html->link('Show only errors (Type >= 50)', ['controller' => 'logentries', 'action' => 'errors']) ?></li>
            </ul>
            Available log types:<br>
            10 - Notification<br>
            20 - Sent email (not implemented yet)<br>
            30 - Automated problem fixing (course reminders, etc.)<br>
            50 - Non-fatal error<br>
            90 - Fatal error<br>
        </p>
        <table>
            <thead>
                <tr>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('id') ?></th>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('logentry_code_id', 'Type') ?></th>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('source_name', 'Application Source') ?></th>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('subject', 'Action') ?></th>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('description', 'Details') ?></th>
                    <th align="left" style="padding: 5px"><?= $this->Paginator->sort('created', ['label' => 'Date & Time']) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logentries as $logentry) : ?>
                    <tr>
                        <td align="left" style="padding: 5px"><?= $this->Number->format($logentry->id) ?></td>
                        <td align="left" style="padding: 5px"><?= $logentry->logentry_code->id . ' - ' . $logentry->logentry_code->name ?></td>
                        <td align="left" style="padding: 5px"><?= h($logentry->source_name) ?></td>
                        <td align="left" style="padding: 5px"><?= h($logentry->subject) ?></td>
                        <td align="left" style="padding: 5px"><?= $this->Text->autoParagraph($logentry->description) ?></td>
                        <td align="left" style="padding: 5px"><?= h($logentry->created->i18nFormat('yyyy-MM-dd HH:mm')) ?> UTC</td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <hr>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p></p>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>