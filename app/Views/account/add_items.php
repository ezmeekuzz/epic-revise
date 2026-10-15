<?= $this->extend('layouts/account') ?>
<?= $this->section('content') ?>
<main class="account-main py-5"><div class="container">
    <div class="portal-card">
        <h1 class="h2">Add additional items</h1>
        <p>Order <?= esc($booking['reference_code']) ?> stays together with its existing payments. Additional boxes use your order's saved box price of $<?= number_format((float) $booking['additional_box_amount'], 2) ?>. Item prices are shown below. Existing study abroad charges apply to additions.</p>
        <form action="<?= site_url('account/bookings/' . $booking['id'] . '/items') ?>" method="post" data-loading-title="Adding items to your order">
            <?= csrf_field() ?>
            <input type="hidden" name="request_token" value="<?= esc($requestToken, 'attr') ?>">
            <div class="mb-4"><label class="form-label" for="additional_boxes">Additional boxes</label><input class="form-control" id="additional_boxes" name="additional_boxes" type="number" min="0" max="<?= max(0, 100 - (int) $booking['additional_box_quantity']) ?>" step="1" value="<?= esc(old('additional_boxes', '0'), 'attr') ?>" required></div>
            <?php if ($booking['booking_type'] === 'summer_storage'): ?>
                <h2 class="h4">Additional storage items</h2>
                <p>Enter a quantity for each size you need; leave the others at zero.</p>
                <?php if ($itemCatalog === []): ?><p>No additional item sizes are currently available. You can still add boxes.</p><?php endif ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($itemCatalog as $item): foreach ($item['sizes'] as $size): ?>
                        <div class="col-md-6"><label class="form-label" for="size_<?= (int) $size['id'] ?>"><?= esc($item['name'] . ' — ' . $size['name']) ?> ($<?= esc($size['unit_price']) ?> each)</label><input class="form-control" id="size_<?= (int) $size['id'] ?>" name="quantities[<?= (int) $size['id'] ?>]" type="number" min="0" max="100" step="1" value="<?= esc(old('quantities.' . $size['id'], '0'), 'attr') ?>"></div>
                    <?php endforeach; endforeach ?>
                </div>
            <?php endif ?>
            <div class="d-flex flex-wrap gap-3"><button class="btn btn-brand-primary" type="submit">Add items and review balance</button><a class="btn btn-brand-outline" href="<?= site_url('account/bookings/' . $booking['id']) ?>">Back to order</a></div>
        </form>
    </div>
</div></main>
<?= $this->endSection() ?>
