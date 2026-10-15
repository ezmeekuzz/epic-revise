<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\AuthService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthServiceSessionTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        service('session')->destroy();

        parent::tearDown();
    }

    public function testCustomerAndAdminSessionsCoexistAndLogoutIndependently(): void
    {
        $session = service('session');
        $auth = new AuthService(null, $session);

        $auth->startSession([
            'id' => 101,
            'name' => 'Portal Customer',
            'email' => 'customer@example.com',
            'role' => 'customer',
        ]);
        $auth->startSession([
            'id' => 202,
            'name' => 'Operations Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->assertSame(101, $session->get('customer_user_id'));
        $this->assertSame(202, $session->get('admin_user_id'));
        $this->assertTrue($session->get('customer_is_logged_in'));
        $this->assertTrue($session->get('admin_is_logged_in'));

        $auth->logout('admin');

        $this->assertNull($session->get('admin_user_id'));
        $this->assertNull($session->get('admin_is_logged_in'));
        $this->assertSame(101, $session->get('customer_user_id'));
        $this->assertTrue($session->get('customer_is_logged_in'));

        $auth->startSession([
            'id' => 202,
            'name' => 'Operations Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        $auth->logout('customer');

        $this->assertNull($session->get('customer_user_id'));
        $this->assertNull($session->get('customer_is_logged_in'));
        $this->assertSame(202, $session->get('admin_user_id'));
        $this->assertTrue($session->get('admin_is_logged_in'));
    }
}
