<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Security;

/**
 * @internal
 */
final class SecurityConfigTest extends CIUnitTestCase
{
    public function testCsrfCookieRemainsStableAcrossCustomerAndAdminTabs(): void
    {
        $security = new Security();

        $this->assertSame('cookie', $security->csrfProtection);
        $this->assertFalse($security->regenerate);
        $this->assertTrue($security->redirect);
        $this->assertGreaterThan(0, $security->expires);
    }
}
