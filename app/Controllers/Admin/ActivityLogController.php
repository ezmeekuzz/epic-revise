<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\ResponseInterface;

final class ActivityLogController extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));

        return view('admin/activity_log/index', [
            'title'           => 'Activity Log',
            'activeAdminPage' => 'activity-log',
            'entries'         => $this->query($search)->limit(500)->get()->getResultArray(),
            'search'          => $search,
        ]);
    }

    /** Full export of the entries matching the search, for audits. */
    public function exportCsv(): ResponseInterface
    {
        $buffer = fopen('php://temp', 'r+');
        fputcsv($buffer, ['When', 'Admin', 'Action', 'Outcome', 'Target', 'Detail', 'IP address']);
        foreach ($this->query(trim((string) $this->request->getGet('q')))->limit(50000)->get()->getResultArray() as $row) {
            // A leading = + - or @ would be run as a formula by spreadsheet software.
            $cells = [$row['created_at'], $row['admin_name'], $row['action'], $row['outcome'], $row['target'], $row['detail'], $row['ip_address']];
            fputcsv($buffer, array_map(static fn ($cell): string => preg_match('/^[=+\-@]/', (string) $cell) === 1 ? chr(39) . $cell : (string) $cell, $cells));
        }
        rewind($buffer);
        $csv = stream_get_contents($buffer);
        fclose($buffer);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="admin-activity-' . date('Y-m-d-His') . '.csv"')
            ->setBody((string) $csv);
    }

    private function query(string $search): BaseBuilder
    {
        $builder = db_connect()->table('admin_activity_log')->orderBy('id', 'DESC');
        if ($search !== '') {
            $builder->groupStart()->like('action', $search)->orLike('admin_name', $search)->orLike('target', $search)->orLike('ip_address', $search)->orLike('outcome', $search)->groupEnd();
        }

        return $builder;
    }
}
