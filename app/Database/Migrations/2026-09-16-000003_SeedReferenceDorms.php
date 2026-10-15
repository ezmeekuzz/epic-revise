<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class SeedReferenceDorms extends Migration
{
    /** @var array<string, string> */
    private const DORMS = [
        'BELK' => 'BELK HALL',
        'BLESSING' => 'BLESSING',
        'CAFFEY' => 'CAFFEY HALL',
        'CENTENNIAL-1' => 'CENTENNIAL SQUARE 1',
        'CENTENNIAL-2' => 'CENTENNIAL SQUARE 2',
        'FINCH' => 'FINCH HALL',
        'GREEK-VILLAGE' => 'GREEK VILLAGE',
        'MCCAIN-PLACE' => 'MCCAIN PLACE TOWNHOMES',
        'MCEWEN' => 'MCEWEN',
        'MILLIS' => 'MILLIS HALL',
        'NORTH-COLLEGE-COURT' => 'NORTH COLLEGE COURT',
        'NORTH-COLLEGE-TERRACE' => 'NORTH COLLEGE TERRACE',
        'NORTH-COLLEGE-TOWNHOMES' => 'NORTH COLLEGE TOWNHOMES',
        'NORTH-HALL' => 'NORTH HALL',
        'PANTHER-COMMONS' => 'PANTHER COMMONS',
        'POINT-PLACE' => 'POINT PLACE',
        'UNIVERSITY-CENTER-2' => 'UNIVERSITY CENTER 2',
        'UNIVERSITY-LANDING' => 'UNIVERSITY LANDING TINY HOMES',
        'UNIVERSITY-HOUSES' => 'UNIVERSITY OWNED HOUSES',
        'UNIVERSITY-VILLAGE' => 'UNIVERSITY VILLAGE',
        'ALDRIDGE-1' => 'ALDRIDGE VILLAGE 1',
        'ALDRIDGE-2' => 'ALDRIDGE VILLAGE 2',
        'WANEK' => 'WANEK CENTER',
        'WESLEY' => 'WESLEY HALL',
        'WILSON' => 'WILSON',
        'YADKIN' => 'YADKIN HALL',
        'YORK' => 'YORK HALL',
        'OTHER' => 'OTHER',
    ];

    public function up(): void
    {
        $dorms = $this->db->table('dorms');
        $now = date('Y-m-d H:i:s');

        foreach (self::DORMS as $code => $name) {
            $existing = $dorms
                ->groupStart()
                ->where('name', $name)
                ->orWhere('code', $code)
                ->groupEnd()
                ->get()
                ->getRowArray();

            if ($existing !== null) {
                continue;
            }

            $dorms->insert([
                'name' => $name,
                'code' => $code,
                'address' => null,
                'pickup_notes' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }
    }

    public function down(): void
    {
        $this->db->table('dorms')
            ->whereIn('code', array_keys(self::DORMS))
            ->whereIn('name', array_values(self::DORMS))
            ->delete();
    }
}
