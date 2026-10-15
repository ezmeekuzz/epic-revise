<?php

declare(strict_types=1);

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthenticationFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    public function testCustomerCanRegisterPendingVerificationAndPasswordIsHashed(): void
    {
        $result = $this->post('/register', [
            ...\Tests\Support\CustomerFixture::details(),
            csrf_token()       => csrf_hash(),
            'name'             => 'Jamie Student',
            'email'            => 'Jamie@example.com',
            'password'         => 'correct horse battery staple',
            'password_confirm' => 'correct horse battery staple',
            'terms'            => '1',
        ]);

        $result->assertRedirectTo('/verify-email');
        $result->assertSessionHas('verification_email', 'jamie@example.com');
        $result->assertSessionMissing('customer_is_logged_in');

        $user = (new UserModel())->where('email', 'jamie@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user['role']);
        $this->assertSame('pending', $user['status']);
        $this->assertTrue(password_verify('correct horse battery staple', $user['password_hash']));
        $this->assertNotSame('correct horse battery staple', $user['password_hash']);
    }

    public function testRegistrationRejectsDuplicateEmail(): void
    {
        $this->createUser('Existing User', 'existing@example.com', 'customer');

        $result = $this->post('/register', [
            ...\Tests\Support\CustomerFixture::details(),
            csrf_token()       => csrf_hash(),
            'name'             => 'Another User',
            'email'            => 'EXISTING@example.com',
            'password'         => 'another safe password',
            'password_confirm' => 'another safe password',
            'terms'            => '1',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('errors');
        $this->assertSame(1, (new UserModel())->where('email', 'existing@example.com')->countAllResults());
    }

    public function testCustomerAndAdminLoginReachTheirOwnAreas(): void
    {
        $this->createUser('Customer User', 'customer@example.com', 'customer');
        $this->createUser('Admin User', 'admin@example.com', 'admin');

        $customer = $this->post('/login', [
            csrf_token() => csrf_hash(),
            'email'      => 'customer@example.com',
            'password'   => 'valid password 123',
        ]);
        $customer->assertRedirectTo('/account');
        $customer->assertSessionHas('customer_is_logged_in', true);
        $customer->assertSessionHas('customer_user_name', 'Customer User');

        $admin = $this->withSession()->post('/admin/login', [
            csrf_token() => csrf_hash(),
            'email'      => 'admin@example.com',
            'password'   => 'valid password 123',
        ]);
        $admin->assertRedirectTo('/admin');
        $admin->assertSessionHas('admin_is_logged_in', true);
        $admin->assertSessionHas('admin_user_name', 'Admin User');
        $admin->assertSessionHas('customer_is_logged_in', true);

        $this->withSession()->get('/account')->assertStatus(200);
        $this->withSession()->get('/admin')->assertStatus(200);
    }

    public function testEachLogoutPreservesTheOtherRoleSession(): void
    {
        $customerId = $this->createUser('Customer User', 'customer@example.com', 'customer');
        $adminId = $this->createUser('Admin User', 'admin@example.com', 'admin');
        $dualSession = [
            'customer_user_id' => $customerId,
            'customer_user_name' => 'Customer User',
            'customer_user_email' => 'customer@example.com',
            'customer_is_logged_in' => true,
            'admin_user_id' => $adminId,
            'admin_user_name' => 'Admin User',
            'admin_user_email' => 'admin@example.com',
            'admin_is_logged_in' => true,
        ];

        $adminLogout = $this->withSession($dualSession)->post('/admin/logout', [csrf_token() => csrf_hash()]);
        $adminLogout->assertRedirectTo('/admin/login');
        $adminLogout->assertSessionMissing('admin_is_logged_in');
        $adminLogout->assertSessionHas('customer_is_logged_in', true);

        $customerLogout = $this->withSession($dualSession)->post('/logout', [csrf_token() => csrf_hash()]);
        $customerLogout->assertRedirectTo('/login');
        $customerLogout->assertSessionMissing('customer_is_logged_in');
        $customerLogout->assertSessionHas('admin_is_logged_in', true);
    }

    public function testCustomerAndAdminCredentialsCannotUseTheOtherLogin(): void
    {
        $this->createUser('Customer User', 'customer@example.com', 'customer');
        $this->createUser('Admin User', 'admin@example.com', 'admin');

        $adminAtCustomerLogin = $this->post('/login', [
            csrf_token() => csrf_hash(),
            'email'      => 'admin@example.com',
            'password'   => 'valid password 123',
        ]);
        $adminAtCustomerLogin->assertRedirect();
        $adminAtCustomerLogin->assertSessionHas('error');
        $adminAtCustomerLogin->assertSessionMissing('customer_is_logged_in');

        $customerAtAdminLogin = $this->post('/admin/login', [
            csrf_token() => csrf_hash(),
            'email'      => 'customer@example.com',
            'password'   => 'valid password 123',
        ]);
        $customerAtAdminLogin->assertRedirect();
        $customerAtAdminLogin->assertSessionHas('error');
        $customerAtAdminLogin->assertSessionMissing('admin_is_logged_in');
    }

    public function testInactiveUserCannotLogin(): void
    {
        $this->createUser('Inactive User', 'inactive@example.com', 'customer', 'inactive');

        $result = $this->post('/login', [
            csrf_token() => csrf_hash(),
            'email'      => 'inactive@example.com',
            'password'   => 'valid password 123',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
        $result->assertSessionMissing('customer_is_logged_in');
    }

    public function testAdminRouteRejectsCustomerRole(): void
    {
        $id = $this->createUser('Customer User', 'customer@example.com', 'customer');

        $result = $this->withSession([
            'admin_user_id'      => $id,
            'admin_user_name'    => 'Customer User',
            'admin_user_email'   => 'customer@example.com',
            'admin_is_logged_in' => true,
        ])->get('/admin');

        $result->assertRedirectTo('/admin/login');
    }

    public function testUnauthenticatedAccountRouteRedirectsToLogin(): void
    {
        $result = $this->get('/account');

        $result->assertRedirectTo('/login');
    }

    public function testSelectedServiceWaitsForLoginAndPreservesTheBookingDestination(): void
    {
        $result = $this->get('/account/bookings/new?booking_type=summer_storage');

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('error', 'Log in with your Epic customer account to continue with your selected service.');
        $result->assertSessionHas('customer_intended_url', '/account/bookings/new?booking_type=summer_storage');

        $this->createUser('Returning Customer', 'returning@example.com', 'customer');
        $login = $this->withSession([
            'customer_intended_url' => '/account/bookings/new?booking_type=summer_storage',
        ])->post('/login', [
            csrf_token() => csrf_hash(),
            'email' => 'returning@example.com',
            'password' => 'valid password 123',
        ]);

        $login->assertRedirectTo('/account/bookings/new?booking_type=summer_storage');
        $login->assertSessionHas('customer_is_logged_in', true);
    }

    public function testUnauthenticatedAdminRouteRedirectsToAdminLogin(): void
    {
        $result = $this->get('/admin');

        $result->assertRedirectTo('/admin/login');
    }

    public function testAdministratorCanRenderDashboardAndCreateCustomer(): void
    {
        $adminId = $this->createUser('Admin User', 'admin@example.com', 'admin');
        $session = [
            'admin_user_id'      => $adminId,
            'admin_user_name'    => 'Admin User',
            'admin_user_email'   => 'admin@example.com',
            'admin_is_logged_in' => true,
        ];

        $dashboard = $this->withSession($session)->get('/admin');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('Current activity across bookings');
        $dashboard->assertSee('assets/vendor/sweetalert2/sweetalert2.all.min.js');
        $dashboard->assertSee('assets/js/notifications.js');

        $result = $this->withSession($session)->post('/admin/users', [
            ...\Tests\Support\CustomerFixture::details(),
            csrf_token()       => csrf_hash(),
            'name'             => 'Managed Customer',
            'email'            => 'managed@example.com',
            'password'         => 'managed password 123',
            'password_confirm' => 'managed password 123',
            'role'             => 'customer',
            'status'           => 'active',
        ]);

        $result->assertRedirectTo('/admin/users');
        $this->seeInDatabase('users', [
            'email'  => 'managed@example.com',
            'role'   => 'customer',
            'status' => 'active',
        ]);
    }

    public function testAdministratorCanUpdateAndRemoveAnotherUser(): void
    {
        $adminId = $this->createUser('Admin User', 'admin@example.com', 'admin');
        $userId  = $this->createUser('Customer User', 'customer@example.com', 'customer');
        $session = [
            'admin_user_id'      => $adminId,
            'admin_user_name'    => 'Admin User',
            'admin_user_email'   => 'admin@example.com',
            'admin_is_logged_in' => true,
        ];

        $updated = $this->withSession($session)->post('/admin/users/' . $userId, [
            ...\Tests\Support\CustomerFixture::details(),
            csrf_token()       => csrf_hash(),
            'name'             => 'Updated Customer',
            'email'            => 'updated@example.com',
            'password'         => '',
            'password_confirm' => '',
            'role'             => 'customer',
            'status'           => 'inactive',
        ]);
        $updated->assertRedirectTo('/admin/users');
        $this->seeInDatabase('users', ['id' => $userId, 'email' => 'updated@example.com', 'status' => 'inactive']);

        $deleted = $this->withSession($session)->post('/admin/users/' . $userId . '/delete', [
            ...\Tests\Support\CustomerFixture::details(),csrf_token() => csrf_hash()]);
        $deleted->assertRedirectTo('/admin/users');
        $this->assertNull((new UserModel())->find($userId));
        $this->assertNotNull((new UserModel())->withDeleted()->find($userId));
    }

    public function testAdministratorCannotDeleteOwnAccount(): void
    {
        $adminId = $this->createUser('Admin User', 'admin@example.com', 'admin');

        $result = $this->withSession([
            'admin_user_id'      => $adminId,
            'admin_user_name'    => 'Admin User',
            'admin_user_email'   => 'admin@example.com',
            'admin_is_logged_in' => true,
        ])->post('/admin/users/' . $adminId . '/delete', [
            ...\Tests\Support\CustomerFixture::details(),csrf_token() => csrf_hash()]);

        $result->assertRedirectTo('/admin/users');
        $result->assertSessionHas('error');
        $this->assertNotNull((new UserModel())->find($adminId));
    }

    public function testAdministratorCanChangePasswordWithCurrentPassword(): void
    {
        $adminId = $this->createUser('Admin User', 'admin@example.com', 'admin');

        $result = $this->withSession([
            'admin_user_id'      => $adminId,
            'admin_user_name'    => 'Admin User',
            'admin_user_email'   => 'admin@example.com',
            'admin_is_logged_in' => true,
        ])->post('/admin/account/password', [
            csrf_token()       => csrf_hash(),
            'current_password' => 'valid password 123',
            'password'         => 'a completely new password',
            'password_confirm' => 'a completely new password',
        ]);

        $result->assertRedirectTo('/admin/account');
        $user = (new UserModel())->find($adminId);
        $this->assertTrue(password_verify('a completely new password', $user['password_hash']));
    }

    private function createUser(string $name, string $email, string $role, string $status = 'active'): int
    {
        return (int) (new UserModel())->insert([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role'          => $role,
            'status'        => $status,
        ], true);
    }
}
