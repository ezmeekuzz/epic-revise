<dl class="account-details">
    <?php foreach (\App\Libraries\CustomerDetails::FIELDS as $field => [$label]): ?>
        <div><dt><?= esc($label) ?></dt><dd><?= esc($user[$field] ?? '') ?: 'Not provided' ?></dd></div>
    <?php endforeach ?>
</dl>
