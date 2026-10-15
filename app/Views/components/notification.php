<?php

$notificationType    = null;
$notificationMessage = null;
$successMessage      = session()->getFlashdata('success');
$errorMessage        = session()->getFlashdata('error');

if (is_string($successMessage) && $successMessage !== '') {
    $notificationType    = 'success';
    $notificationMessage = $successMessage;
} elseif (is_string($errorMessage) && $errorMessage !== '') {
    $notificationType    = 'error';
    $notificationMessage = $errorMessage;
} else {
    foreach (['errors', 'profileErrors', 'passwordErrors'] as $errorKey) {
        $validationErrors = session()->getFlashdata($errorKey);

        if (is_array($validationErrors) && $validationErrors !== []) {
            $messages            = array_values(array_filter(array_map('strval', $validationErrors)));
            $notificationType    = 'warning';
            $notificationMessage = implode(' ', $messages);
            break;
        }
    }
}
?>
<?php if ($notificationType !== null && $notificationMessage !== null): ?>
    <div id="epicNotification" data-type="<?= esc($notificationType, 'attr') ?>" data-message="<?= esc($notificationMessage, 'attr') ?>" hidden></div>
<?php endif ?>
