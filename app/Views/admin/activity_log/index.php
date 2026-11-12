<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="page-heading"><div><p class="eyebrow">Security</p><h1>Activity log</h1><p>Who signed in and what changes administrators made. Showing the latest 500 entries. Entries cannot be edited or deleted here.</p></div></div>
<section class="admin-card">
    <form class="filter-bar" action="<?= site_url('admin/activity-log') ?>" method="get"><input class="form-control" name="q" type="search" value="<?= esc($search, 'attr') ?>" placeholder="Action, admin, target or IP address" aria-label="Search activity"><button class="btn btn-primary" type="submit">Search</button></form>
    <?php if ($entries === []): ?>
        <div class="empty-state"><strong>No activity recorded yet</strong><p>Sign-ins and administrator changes will appear here.</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table admin-table align-middle mb-0"><thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Detail</th><th>IP address</th></tr></thead><tbody>
        <?php foreach ($entries as $entry): ?>
            <tr><td><?= esc(date('M j, Y g:i:s A', strtotime((string) $entry['created_at']))) ?></td><td><?= esc($entry['admin_name'] ?: '—') ?></td><td><code><?= esc($entry['action']) ?></code></td><td><?= esc($entry['target'] ?: '—') ?></td><td><?= esc($entry['detail'] ?: '') ?></td><td><?= esc($entry['ip_address'] ?: '') ?></td></tr>
        <?php endforeach ?>
        </tbody></table></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
