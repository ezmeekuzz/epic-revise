<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddServiceProcessing extends Migration
{
    /** @var array<string, array{name: string, sizes: array<string, string>}> */
    private const CATALOG = [
        'WARDROBE-BOX' => ['name' => 'Wardrobe Box', 'sizes' => ['Small (24 x 20 x 34 in)' => '70.00', 'Large (24 x 24 x 48 in)' => '100.00']],
        'TV' => ['name' => 'TV', 'sizes' => ['24 in or less' => '45.00', '36 in or less' => '60.00', '46 in or less' => '75.00', '56 in or less' => '85.00', '65 in or less' => '100.00']],
        'FRIDGE' => ['name' => 'Fridge', 'sizes' => ['Small (1.7 cu ft)' => '45.00', 'Medium (2.7 cu ft)' => '55.00', 'Large (3.3 cu ft)' => '65.00']],
        'FURNITURE' => ['name' => 'Furniture', 'sizes' => ['Small (nightstand)' => '45.00', 'Medium (TV stand)' => '65.00', 'Large (desk)' => '100.00']],
        'DRESSER' => ['name' => 'Dresser', 'sizes' => ['Medium (4 drawers)' => '65.00', 'Large (6+ drawers)' => '100.00']],
        'CHAIR' => ['name' => 'Chair', 'sizes' => ['Small (desk chair)' => '30.00', 'Medium (room chair)' => '50.00']],
        'PLASTIC-BINS' => ['name' => 'Plastic Bins', 'sizes' => ['Small' => '45.00', 'Large' => '60.00']],
        'LUGGAGE' => ['name' => 'Luggage', 'sizes' => ['Small' => '30.00', 'Large' => '60.00']],
        'SHELVES' => ['name' => 'Shelves', 'sizes' => ['Small (2 shelves)' => '35.00', 'Medium (4 shelves)' => '45.00', 'Large (6 shelves)' => '60.00']],
        'COUCH' => ['name' => 'Couch', 'sizes' => ['Loveseat' => '75.00', 'Full length' => '115.00']],
        'FUTON' => ['name' => 'Futon', 'sizes' => ['Standard' => '75.00']],
        'LAMP' => ['name' => 'Lamp', 'sizes' => ['Desk lamp' => '25.00', 'Floor lamp' => '45.00']],
        'MIRROR' => ['name' => 'Mirror', 'sizes' => ['Lightweight' => '25.00', 'Full length' => '70.00']],
        'CAMP-TRUNK' => ['name' => 'Camp Trunk', 'sizes' => ['Standard' => '50.00']],
        'BAR-CART' => ['name' => 'Bar Cart', 'sizes' => ['Standard' => '65.00']],
        'BEAN-BAG' => ['name' => 'Bean Bag Chair', 'sizes' => ['Small (32 in wide)' => '40.00', 'Medium (40 in wide)' => '50.00', 'Large (52+ in wide)' => '65.00']],
        'DUFFLE-BAG' => ['name' => 'Duffle Bag', 'sizes' => ['Small' => '35.00', 'Large' => '50.00']],
        'MICROWAVE' => ['name' => 'Microwave', 'sizes' => ['Standard' => '30.00']],
        'VACUUM' => ['name' => 'Vacuum', 'sizes' => ['Standard' => '25.00']],
        'CLEANING-TOOLS' => ['name' => 'Swiffer / Mop / Broom', 'sizes' => ['Standard' => '25.00']],
        'WALL-ART' => ['name' => 'Wall Art', 'sizes' => ['Small (20 in wide)' => '25.00', 'Medium (30 in wide)' => '45.00', 'Large (50+ in wide)' => '65.00']],
        'DRYING-RACK' => ['name' => 'Drying Rack', 'sizes' => ['Standard' => '15.00']],
        'MATTRESS-PAD' => ['name' => 'Mattress Pad', 'sizes' => ['Twin bed' => '45.00', 'Full bed' => '70.00']],
        'ROLLING-DRAWERS' => ['name' => 'Plastic Rolling Drawers', 'sizes' => ['Small (14 x 12 x 24 in)' => '45.00', 'Large (15 x 21 x 24 in)' => '60.00']],
        'BIKE' => ['name' => 'Bike', 'sizes' => ['Standard' => '65.00']],
        'GOLF-CLUBS' => ['name' => 'Golf Clubs', 'sizes' => ['Standard' => '60.00']],
        'CAR' => ['name' => 'Car', 'sizes' => ['Four months' => '400.00']],
        'MOTORCYCLE-SCOOTER' => ['name' => 'Motorcycle / Scooter', 'sizes' => ['May to August' => '195.00']],
        'MISCELLANEOUS' => ['name' => 'Miscellaneous', 'sizes' => ['Standard' => '0.00']],
    ];

    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'is_study_abroad' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'study_abroad_additional_storage_price'],
            'is_summer_school' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'is_study_abroad'],
            'summer_school_fee' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00', 'after' => 'is_summer_school'],
        ]);

        $this->seedPricingSettings();
        $this->seedCatalog();
    }

    public function down(): void
    {
        $this->db->table('app_settings')->whereIn('setting_key', [
            'summer_storage_additional_box_price',
            'summer_school_fee',
        ])->delete();

        $this->db->table('items')->whereIn('sku', array_keys(self::CATALOG))->delete();
        $this->forge->dropColumn('bookings', ['is_study_abroad', 'is_summer_school', 'summer_school_fee']);
    }

    private function seedPricingSettings(): void
    {
        foreach ([
            'summer_storage_additional_box_price' => '50.00',
            'summer_school_fee' => '75.00',
        ] as $key => $value) {
            if ($this->db->table('app_settings')->where('setting_key', $key)->countAllResults() > 0) {
                continue;
            }

            $this->db->table('app_settings')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'group_name' => 'pricing',
                'is_encrypted' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedCatalog(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (self::CATALOG as $sku => $definition) {
            $item = $this->db->table('items')->where('sku', $sku)->get()->getRowArray();
            if ($item === null) {
                $this->db->table('items')->insert([
                    'name' => $definition['name'],
                    'sku' => $sku,
                    'category' => 'Additional Storage',
                    'description' => 'Optional Summer Storage item.',
                    'unit_price' => '0.00',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $itemId = (int) $this->db->insertID();
            } else {
                $itemId = (int) $item['id'];
            }

            foreach ($definition['sizes'] as $sizeName => $price) {
                if ($this->db->table('item_sizes')->where(['item_id' => $itemId, 'name' => $sizeName])->countAllResults() > 0) {
                    continue;
                }

                $this->db->table('item_sizes')->insert([
                    'item_id' => $itemId,
                    'name' => $sizeName,
                    'sku' => $sku . '-' . strtoupper(substr(hash('sha256', $sizeName), 0, 8)),
                    'unit_price' => $price,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
