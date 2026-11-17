<?php

declare(strict_types=1);

namespace App\Libraries;

use Throwable;

/** Append-only record of what administrators did. Logging never breaks the request. */
final class ActivityLog
{
    public static function record(string $action, ?string $target = null, ?string $detail = null, ?int $adminId = null, ?string $adminName = null, string $outcome = 'ok'): void
    {
        try {
            $session = service('session');
            db_connect()->table('admin_activity_log')->insert([
                'admin_user_id' => $adminId ?? ((int) $session->get('admin_user_id') ?: null),
                'admin_name'    => mb_substr($adminName ?? (string) $session->get('admin_user_name'), 0, 120) ?: null,
                'action'        => mb_substr($action, 0, 120),
                'outcome'       => $outcome === 'rejected' ? 'rejected' : 'ok',
                'target'        => $target !== null ? mb_substr($target, 0, 255) : null,
                'detail'        => $detail !== null ? mb_substr($detail, 0, 500) : null,
                'ip_address'    => service('request')->getIPAddress(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            // Housekeeping: keep roughly 13 months of history without a scheduled job.
            if (random_int(1, 50) === 1) {
                db_connect()->table('admin_activity_log')->where('created_at <', date('Y-m-d H:i:s', strtotime('-400 days')))->delete();
            }
        } catch (Throwable) {
            log_message('error', 'Admin activity log write failed for action {action}', ['action' => $action]);
        }
    }
}
