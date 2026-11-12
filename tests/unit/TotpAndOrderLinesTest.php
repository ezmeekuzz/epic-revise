<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\OrderLines;
use App\Libraries\Totp;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TotpAndOrderLinesTest extends CIUnitTestCase
{
    public function testTotpMatchesRfc6238Vector(): void
    {
        // RFC 6238 SHA-1 test secret "12345678901234567890" at T=59 gives 94287082 (8 digits) -> 287082 (6 digits).
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $totp   = new Totp();

        $this->assertSame('287082', $totp->code($secret, intdiv(59, 30)));
        $this->assertTrue($totp->verify($secret, '287082', 0, 59));
        $this->assertFalse($totp->verify($secret, '000000', 0, 59));
        $this->assertFalse($totp->verify($secret, 'abcdef', 1, 59));
    }

    public function testStorageOrderLinesDescribeEveryCharge(): void
    {
        $booking = [
            'booking_type' => 'summer_storage', 'base_price' => '450.00', 'additional_box_quantity' => 2, 'additional_box_amount' => '50.00',
            'addtl_box_total_amount' => '100.00', 'is_summer_school' => 0, 'is_study_abroad' => 1, 'study_abroad_additional_storage_price' => '550.00',
            'discount_amount' => '0.00', 'service_fee_percent' => '3.00', 'service_fee_amount' => '33.00',
        ];
        $lines  = OrderLines::build($booking, []);
        $labels = array_column($lines, 'label');

        $this->assertSame('Standard Package (5 lg boxes / Base Package)', $labels[0]);
        $this->assertStringContainsString('Additional boxes × 2 @ $50.00', $labels[1]);
        $this->assertStringContainsString('Study abroad', $labels[2]);
        $this->assertSame('Credit card service fee (3%)', $labels[3]);
    }

    public function testSummerAdvantageIsPerBoxOnly(): void
    {
        $lines = OrderLines::build([
            'booking_type' => 'summer_advantage', 'base_price' => '0.00', 'additional_box_quantity' => 4, 'additional_box_amount' => '50.00',
            'addtl_box_total_amount' => '200.00', 'discount_amount' => '0.00', 'service_fee_amount' => '0.00',
        ], []);

        $this->assertCount(1, $lines);
        $this->assertSame('Summer Advantage boxes × 4 @ $50.00 per box', $lines[0]['label']);
    }
}
