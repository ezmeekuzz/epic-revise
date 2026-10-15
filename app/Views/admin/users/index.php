<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div><p class="eyebrow">Customer management</p><h1>Users</h1><p>Search, create, update, deactivate, or remove customer accounts. Administrator credentials are managed separately. The old Create a customer or administrator account flow is intentionally split.</p></div>
    <a class="btn btn-primary" href="<?= site_url('admin/users/new') ?>">Add user</a>
</div>

<section class="admin-card">
    <form class="filter-bar" action="<?= site_url('admin/users') ?>" method="get">
        <div class="filter-search"><label class="visually-hidden" for="q">Search users</label><input class="form-control" type="search" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Search name or email"></div>
        <div><label class="visually-hidden" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Active</option><option value="pending"<?= $filters['status'] === 'pending' ? ' selected' : '' ?>>Pending email confirmation</option><option value="inactive"<?= $filters['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option></select></div>
        <button class="btn btn-dark" type="submit">Filter</button>
        <?php if (array_filter($filters) !== []): ?><a class="btn btn-link" href="<?= site_url('admin/users') ?>">Clear</a><?php endif ?>
    </form>

    <?php if ($users === []): ?>
        <div class="empty-state"><strong>No matching users</strong><p>Adjust the filters or create a new account.</p><a class="btn btn-primary" href="<?= site_url('admin/users/new') ?>">Add user</a></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table js-datatable admin-table align-middle mb-0">
                <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Last login</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= esc($user['name']) ?><?= (int) $user['id'] === $currentUserId ? ' (you)' : '' ?></strong><small><?= esc($user['email']) ?></small></td>
                        <td><span class="role-pill"><?= esc(ucfirst($user['role'])) ?></span></td>
                        <td><span class="status-pill status-<?= esc($user['status'], 'attr') ?>"><?= esc(ucfirst($user['status'])) ?></span></td>
                        <td><?= $user['last_login_at'] ? esc(date('M j, Y g:i a', strtotime((string) $user['last_login_at']))) : '<span class="text-muted">Never</span>' ?></td>
                        <td><?= esc(date('M j, Y', strtotime((string) $user['created_at']))) ?></td>
                        <td class="text-end actions-cell">
                            <?php if ((int) $user['id'] === $currentUserId): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/account') ?>">My account</a>
                            <?php else: ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/users/' . $user['id'] . '/edit') ?>">Edit</a>
                                <form action="<?= site_url('admin/users/' . $user['id'] . '/delete') ?>" method="post" data-confirm="Remove <?= esc($user['name'], 'attr') ?>? This account will no longer be able to sign in." data-confirm-title="Remove this user?" data-confirm-button="Yes, remove user" data-confirm-danger="true" data-loading-title="Removing user">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
