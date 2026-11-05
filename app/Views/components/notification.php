<?php

/*
 * Flash notification bridge.
 *
 * Channels:
 *   toast - Toastr. Non-blocking feedback for routine results: saved, updated,
 *           archived, sent, signed in/out, and recoverable form errors.
 *           Flash keys: success, error, warning, info, errors, profileErrors, passwordErrors.
 *   modal - SweetAlert2. Blocking dialogs the user must read: lockouts, expired
 *           links, failed payments or emails, and key account milestones.
 *           Flash key: alert => ['type' => ..., 'title' => ..., 'message' => ...].
 */

$allowedTypes  = ['success', 'error', 'warning', 'info'];
$notifications = [];

$alert = session()->getFlashdata('alert');
if (is_array($alert) && isset($alert['message']) && (string) $alert['message'] !== '') {
    $notifications[] = [
        'channel' => 'modal',
        'type'    => in_array($alert['type'] ?? '', $allowedTypes, true) ? $alert['type'] : 'info',
        'title'   => (string) ($alert['title'] ?? ''),
        'message' => (string) $alert['message'],
        'items'   => [],
    ];
}

$validationItems = [];
foreach (['errors', 'profileErrors', 'passwordErrors'] as $errorKey) {
    $validationErrors = session()->getFlashdata($errorKey);

    if (is_array($validationErrors)) {
        foreach ($validationErrors as $validationError) {
            if (is_scalar($validationError) && (string) $validationError !== '') {
                $validationItems[] = (string) $validationError;
            }
        }
    }
}
$validationItems = array_values(array_unique($validationItems));

foreach ($allowedTypes as $type) {
    $message = session()->getFlashdata($type);

    if (! is_string($message) || $message === '') {
        continue;
    }

    $items = [];
    if ($type === 'error' && $validationItems !== []) {
        // Attach field errors to the matching error toast instead of stacking a second one.
        $items           = $validationItems;
        $validationItems = [];
    }

    $notifications[] = ['channel' => 'toast', 'type' => $type, 'title' => '', 'message' => $message, 'items' => $items];
}

if ($validationItems !== []) {
    $notifications[] = [
        'channel' => 'toast',
        'type'    => 'warning',
        'title'   => 'Please check the highlighted fields',
        'message' => '',
        'items'   => $validationItems,
    ];
}
?>
<?php if ($notifications !== []): ?>
    <div id="epicNotification" hidden>
        <?php foreach ($notifications as $notification): ?>
            <div data-epic-notification data-channel="<?= esc($notification['channel'], 'attr') ?>" data-type="<?= esc($notification['type'], 'attr') ?>" data-title="<?= esc($notification['title'], 'attr') ?>" data-message="<?= esc($notification['message'], 'attr') ?>" data-items="<?= esc(json_encode($notification['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>"></div>
        <?php endforeach ?>
    </div>
<?php endif ?>
