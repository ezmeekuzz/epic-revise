<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

final class ImportSuppliedTerms extends Migration
{
    public function up(): void
    {
        $terms = $this->source();
        // The supplied 81 KB document exceeds MySQL TEXT's 65,535-byte limit.
        $this->forge->modifyColumn('app_settings', ['setting_value' => ['type' => 'MEDIUMTEXT', 'null' => true]]);
        $this->forge->modifyColumn('bookings', ['terms_snapshot' => ['type' => 'MEDIUMTEXT', 'null' => true]]);
        // Preserve operator edits when deploying to an already-customized installation.
        $this->db->table('app_settings')->where('setting_key', 'current_terms_text')
            ->groupStart()->where('setting_value', '')->orWhere('setting_value', null)->groupEnd()
            ->update(['setting_value' => $terms, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down(): void
    {
        $terms = $this->source();
        $longSnapshots = $this->db->table('bookings')->where('OCTET_LENGTH(terms_snapshot) >', 65535, false)->countAllResults();
        $longSettings = $this->db->table('app_settings')->where('OCTET_LENGTH(setting_value) >', 65535, false)
            ->groupStart()->where('setting_key !=', 'current_terms_text')->orWhere('setting_value !=', $terms)->groupEnd()->countAllResults();
        $this->db->table('app_settings')->where('setting_key', 'current_terms_text')->where('setting_value', $terms)
            ->update(['setting_value' => '', 'updated_at' => date('Y-m-d H:i:s')]);
        // Wider columns are backward compatible. Retain them when shrinking would lose records.
        if ($longSettings === 0) {
            $this->forge->modifyColumn('app_settings', ['setting_value' => ['type' => 'TEXT', 'null' => true]]);
        }
        if ($longSnapshots === 0) {
            $this->forge->modifyColumn('bookings', ['terms_snapshot' => ['type' => 'TEXT', 'null' => true]]);
        }
    }

    private function source(): string
    {
        $terms = file_get_contents(APPPATH . 'Resources/terms/2026-09-10-articles-1-15.txt');
        if ($terms === false || trim($terms) === '') {
            throw new RuntimeException('The supplied terms source is missing or empty.');
        }
        return $terms;
    }
}
