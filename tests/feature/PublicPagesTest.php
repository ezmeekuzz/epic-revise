<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class PublicPagesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pageProvider(): iterable
    {
        yield 'home' => ['/', 'Dorm to Dorm'];
        yield 'about' => ['/about', 'Locally owned. Locally trusted.'];
        yield 'services' => ['/services', 'Our EPIC Services'];
        yield 'scheduling' => ['/scheduling', 'Four steps, start to redelivery'];
        yield 'faqs' => ['/faqs', 'Still Have'];
        yield 'testimonials' => ['/testimonials', 'EPIC'];
        yield 'contact' => ['/contact', 'Contact Us'];
        yield 'login' => ['/login', 'Welcome back'];
        yield 'shipping' => ['/shipping', 'Shipping'];
        yield 'terms' => ['/terms', 'Terms and Conditions'];
    }

    #[DataProvider('pageProvider')]
    public function testPublicPageRendersOnceThroughSharedLayout(string $route, string $expectedText): void
    {
        $result = $this->get($route);

        $result->assertStatus(200);
        $result->assertSee($expectedText);

        $body = (string) $result->response()->getBody();

        $this->assertSame(1, substr_count($body, '<header'));
        $this->assertSame(1, substr_count($body, '<main'));
        $this->assertSame(1, substr_count($body, '<footer'));
        $this->assertStringNotContainsString('$this->include', $body);
    }

    public function testLegacyHtmlRouteStillRendersTheConvertedPage(): void
    {
        $result = $this->get('/about.html');

        $result->assertStatus(200);
        $result->assertSee('Locally owned. Locally trusted.');
    }

    public function testPublicLayoutLoadsLocalSweetAlertNotifications(): void
    {
        $result = $this->withSession([
            'success'   => 'Account action completed.',
            '__ci_vars' => ['success' => 'new'],
        ])->get('/login');

        $result->assertStatus(200);
        $result->assertSee('assets/vendor/sweetalert2/sweetalert2.min.css');
        $result->assertSee('assets/vendor/sweetalert2/sweetalert2.all.min.js');
        $result->assertSee('assets/js/notifications.js');
        $body = (string) $result->response()->getBody();
        $this->assertStringContainsString('id="epicNotification"', $body);
        $this->assertStringContainsString('data-type="success"', $body);
        $this->assertStringContainsString('data-channel="toast"', $body);
        $result->assertSee('assets/vendor/toastr/toastr.min.css');
        $result->assertSee('assets/vendor/toastr/toastr.min.js');
        $result->assertSee('Account action completed.');
    }

    public function testAlertFlashRendersAsModalAndValidationErrorsJoinTheErrorToast(): void
    {
        $result = $this->withSession([
            'alert'     => ['type' => 'warning', 'title' => 'Login link unavailable', 'message' => 'This login link is not valid.'],
            'error'     => 'Your details could not be saved.',
            'errors'    => ['email' => 'The email field is required.'],
            '__ci_vars' => ['alert' => 'new', 'error' => 'new', 'errors' => 'new'],
        ])->get('/login');

        $result->assertStatus(200);
        $body = (string) $result->response()->getBody();
        $this->assertStringContainsString('data-channel="modal" data-type="warning" data-title="' . esc('Login link unavailable', 'attr') . '" data-message="' . esc('This login link is not valid.', 'attr') . '"', $body);
        $this->assertStringContainsString('data-channel="toast" data-type="error"', $body);
        $this->assertStringContainsString(esc(json_encode(['The email field is required.']), 'attr'), $body);
        $this->assertSame(2, substr_count($body, 'data-epic-notification'));
    }

    public function testAdminLoginUsesItsOwnAdministrationLayout(): void
    {
        $result = $this->get('/admin/login');

        $result->assertStatus(200);
        $result->assertSee('Admin sign in');
        $result->assertSee('Authorized personnel only');
        $this->assertStringContainsString('action="' . site_url('admin/login') . '"', (string) $result->response()->getBody());
        $result->assertSee('admin-login-body');

        $body = (string) $result->response()->getBody();
        $this->assertStringNotContainsString('<header class="nav2', $body);
        $this->assertStringNotContainsString('<footer class="footer2', $body);
    }

    public function testServicesUseDirectLinksWithoutSimulatedAvailability(): void
    {
        $response = $this->get('/services');
        $response->assertStatus(200);
        $body = (string) $response->response()->getBody();
        $this->assertStringContainsString('book?booking_type=summer_storage', $body);
        $this->assertStringNotContainsString('account/bookings/new?booking_type', $body, 'Book buttons open the public form, not a login wall.');
        $this->assertStringNotContainsString('Checking live availability', $body);
    }
}
