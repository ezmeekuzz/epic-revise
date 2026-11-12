<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

final class ActivityLogController extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $builder = db_connect()->table('admin_activity_log')->orderBy('id', 'DESC');
        if ($search !== '') {
            $builder->groupStart()->like('action', $search)->orLike('admin_name', $search)->orLike('target', $search)->orLike('ip_address', $search)->groupEnd();
        }

        return view('admin/activity_log/index', [
            'title'           => 'Activity Log',
            'activeAdminPage' => 'activity-log',
            'entries'         => $builder->limit(500)->get()->getResultArray(),
            'search'          => $search,
        ]);
    }
}
