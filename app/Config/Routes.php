<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'PagesController::home', ['as' => 'home']);
$routes->get('about', 'PagesController::about', ['as' => 'about']);
$routes->get('services', 'PagesController::services', ['as' => 'services']);
$routes->get('services/summerStorageSession', 'PagesController::startBooking/summer_storage', ['as' => 'services.summer-storage']);
$routes->get('services/summerAdvantageSession', 'PagesController::startBooking/summer_advantage', ['as' => 'services.summer-advantage']);
$routes->get('scheduling', 'PagesController::scheduling', ['as' => 'scheduling']);
$routes->get('faqs', 'PagesController::faqs', ['as' => 'faqs']);
$routes->get('testimonials', 'PagesController::testimonials', ['as' => 'testimonials']);
$routes->get('contact', 'PagesController::contact', ['as' => 'contact']);
$routes->post('contact/sendMessage', 'ContactController::sendMessage', ['as' => 'contact.send']);
$routes->get('shipping', 'PagesController::shipping', ['as' => 'shipping']);
$routes->get('terms', 'PagesController::terms', ['as' => 'terms']);

$routes->group('', ['filter' => 'guest'], static function ($routes): void {
    $routes->get('login', 'AuthController::login', ['as' => 'login']);
    $routes->post('login', 'AuthController::authenticate', ['as' => 'login.authenticate']);
    $routes->post('loginfunc', 'AuthController::authenticate');
    $routes->get('register', 'AuthController::register', ['as' => 'register']);
    $routes->post('register', 'AuthController::store', ['as' => 'register.store']);
    $routes->get('forgot-password', 'AuthController::forgotPassword', ['as' => 'forgot.password']);
    $routes->post('forgot-password', 'AuthController::sendPasswordReset', ['as' => 'forgot.password.send']);
    $routes->get('reset-password', 'AuthController::resetPassword', ['as' => 'reset.password']);
    $routes->post('reset-password', 'AuthController::updatePassword', ['as' => 'reset.password.update']);
});

$routes->group('admin', ['filter' => 'guest'], static function ($routes): void {
    $routes->get('login', 'Admin\AuthController::login', ['as' => 'admin.login']);
    $routes->post('login', 'Admin\AuthController::authenticate', ['as' => 'admin.login.authenticate']);
});

$routes->post('logout', 'AuthController::logout', ['as' => 'logout', 'filter' => 'customer']);
$routes->get('verify-email', 'AuthController::verifyEmail', ['as' => 'verify.email']);
$routes->post('verify-email/resend', 'AuthController::resendVerification', ['as' => 'verify.email.resend']);
$routes->get('magic-login', 'AuthController::magicLogin', ['as' => 'magic.login']);

$routes->group('account', ['filter' => 'customer'], static function ($routes): void {
    $routes->get('', 'AccountController::index', ['as' => 'account']);
    $routes->get('profile', 'AccountController::editProfile', ['as' => 'account.profile']);
    $routes->post('profile', 'AccountController::updateProfile', ['as' => 'account.profile.update']);
    $routes->get('bookings', 'AccountController::myBookings', ['as' => 'account.bookings']);
    $routes->get('payments', 'AccountController::payments', ['as' => 'account.payments']);
    $routes->get('pickup-dates', 'AccountController::pickupDates', ['as' => 'account.pickup-dates']);
    $routes->get('dropoff-dates', 'AccountController::dropoffDates', ['as' => 'account.dropoff-dates']);
    $routes->get('payment-history', 'AccountController::paymentHistory', ['as' => 'account.payment-history']);
    $routes->get('balance', 'AccountController::balance', ['as' => 'account.balance']);
    $routes->get('bookings/new', 'AccountController::newBooking', ['as' => 'account.bookings.new']);
    $routes->post('bookings', 'AccountController::createBooking', ['as' => 'account.bookings.create']);
    $routes->get('bookings/(:num)', 'AccountController::showBooking/$1', ['as' => 'account.bookings.show']);
    $routes->get('bookings/(:num)/items', 'AccountController::additionalItems/$1', ['as' => 'account.bookings.items']);
    $routes->post('bookings/(:num)/items', 'AccountController::saveAdditionalItems/$1', ['as' => 'account.bookings.items.save']);
    $routes->get('bookings/(:num)/payment', 'CustomerPaymentController::show/$1', ['as' => 'account.bookings.payment']);
    $routes->post('bookings/(:num)/payment', 'CustomerPaymentController::process/$1', ['as' => 'account.bookings.payment.process']);
    $routes->get('bookings/(:num)/pickup', 'AccountController::editPickup/$1', ['as' => 'account.bookings.pickup']);
    $routes->post('bookings/(:num)/pickup', 'AccountController::updatePickup/$1', ['as' => 'account.bookings.pickup.update']);
});

$routes->group('admin', ['filter' => 'admin'], static function ($routes): void {
    $routes->post('logout', 'Admin\AuthController::logout', ['as' => 'admin.logout']);
    $routes->get('', 'Admin\\DashboardController::index', ['as' => 'admin.dashboard']);
    $routes->get('users', 'Admin\\UsersController::index', ['as' => 'admin.users']);
    $routes->get('users/new', 'Admin\\UsersController::new', ['as' => 'admin.users.new']);
    $routes->post('users', 'Admin\\UsersController::create', ['as' => 'admin.users.create']);
    $routes->get('users/(:num)/edit', 'Admin\\UsersController::edit/$1', ['as' => 'admin.users.edit']);
    $routes->post('users/(:num)', 'Admin\\UsersController::update/$1', ['as' => 'admin.users.update']);
    $routes->post('users/(:num)/delete', 'Admin\\UsersController::delete/$1', ['as' => 'admin.users.delete']);
    $routes->get('bookings', 'Admin\\BookingsController::index', ['as' => 'admin.bookings']);
    $routes->get('bookings/summer-storage', 'Admin\\BookingsController::summerStorage', ['as' => 'admin.bookings.summer-storage']);
    $routes->get('bookings/summer-advantage', 'Admin\\BookingsController::summerAdvantage', ['as' => 'admin.bookings.summer-advantage']);
    $routes->get('bookings/export.csv', 'Admin\\BookingsController::exportCsv', ['as' => 'admin.bookings.export.csv']);
    $routes->get('bookings/export.xlsx', 'Admin\\BookingsController::exportExcel', ['as' => 'admin.bookings.export.xlsx']);
    $routes->post('bookings/notify/bulk', 'Admin\\BookingsController::notifyBulk', ['as' => 'admin.bookings.notify.bulk']);
    $routes->get('bookings/new', 'Admin\\BookingsController::new', ['as' => 'admin.bookings.new']);
    $routes->post('bookings', 'Admin\\BookingsController::create', ['as' => 'admin.bookings.create']);
    $routes->get('bookings/(:num)', 'Admin\\BookingsController::show/$1', ['as' => 'admin.bookings.show']);
    $routes->get('bookings/(:num)/edit', 'Admin\\BookingsController::edit/$1', ['as' => 'admin.bookings.edit']);
    $routes->get('bookings/(:num)/export.pdf', 'Admin\\BookingsController::exportPdf/$1', ['as' => 'admin.bookings.export.pdf']);
    $routes->post('bookings/(:num)', 'Admin\\BookingsController::update/$1', ['as' => 'admin.bookings.update']);
    $routes->post('bookings/(:num)/items', 'Admin\\BookingsController::addItem/$1', ['as' => 'admin.bookings.items.add']);
    $routes->post('bookings/(:num)/items/(:num)/delete', 'Admin\\BookingsController::removeItem/$1/$2', ['as' => 'admin.bookings.items.delete']);
    $routes->post('bookings/(:num)/delete', 'Admin\\BookingsController::delete/$1', ['as' => 'admin.bookings.delete']);
    $routes->post('bookings/(:num)/warehouse', 'Admin\\BookingsController::updateWarehouse/$1', ['as' => 'admin.bookings.warehouse.update']);
    $routes->post('bookings/(:num)/notify/confirmation', 'Admin\\BookingsController::notifyConfirmation/$1', ['as' => 'admin.bookings.notify.confirmation']);
    $routes->post('bookings/(:num)/notify/balance-due', 'Admin\\BookingsController::notifyBalanceDue/$1', ['as' => 'admin.bookings.notify.balance-due']);
    $routes->post('bookings/(:num)/notify/pickup-reminder', 'Admin\\BookingsController::notifyPickupReminder/$1', ['as' => 'admin.bookings.notify.pickup-reminder']);
    $routes->post('bookings/(:num)/notify/dropoff-reminder', 'Admin\\BookingsController::notifyDropoffReminder/$1', ['as' => 'admin.bookings.notify.dropoff-reminder']);
    $routes->get('schedule', 'Admin\\ScheduleController::index', ['as' => 'admin.schedule']);
    $routes->get('logistics', 'Admin\\LogisticsController::index', ['as' => 'admin.logistics']);
    $routes->post('logistics/(:num)/(:segment)', 'Admin\\LogisticsController::update/$1/$2', ['as' => 'admin.logistics.update']);
    $routes->get('dorms', 'Admin\\DormsController::index', ['as' => 'admin.dorms']);
    $routes->get('dorms/new', 'Admin\\DormsController::new', ['as' => 'admin.dorms.new']);
    $routes->post('dorms', 'Admin\\DormsController::create', ['as' => 'admin.dorms.create']);
    $routes->get('dorms/(:num)/edit', 'Admin\\DormsController::edit/$1', ['as' => 'admin.dorms.edit']);
    $routes->post('dorms/(:num)', 'Admin\\DormsController::update/$1', ['as' => 'admin.dorms.update']);
    $routes->post('dorms/(:num)/delete', 'Admin\\DormsController::delete/$1', ['as' => 'admin.dorms.delete']);
    $routes->get('items', 'Admin\\ItemsController::index', ['as' => 'admin.items']);
    $routes->get('items/new', 'Admin\\ItemsController::new', ['as' => 'admin.items.new']);
    $routes->post('items', 'Admin\\ItemsController::create', ['as' => 'admin.items.create']);
    $routes->get('items/(:num)/edit', 'Admin\\ItemsController::edit/$1', ['as' => 'admin.items.edit']);
    $routes->post('items/(:num)', 'Admin\\ItemsController::update/$1', ['as' => 'admin.items.update']);
    $routes->post('items/(:num)/delete', 'Admin\\ItemsController::delete/$1', ['as' => 'admin.items.delete']);
    $routes->get('items/(:num)/sizes', 'Admin\\ItemsController::sizes/$1', ['as' => 'admin.items.sizes']);
    $routes->post('items/(:num)/sizes', 'Admin\\ItemsController::createSize/$1', ['as' => 'admin.items.sizes.create']);
    $routes->post('items/(:num)/sizes/(:num)', 'Admin\\ItemsController::updateSize/$1/$2', ['as' => 'admin.items.sizes.update']);
    $routes->post('items/(:num)/sizes/(:num)/delete', 'Admin\\ItemsController::deleteSize/$1/$2', ['as' => 'admin.items.sizes.delete']);
    $routes->get('payments', 'Admin\\PaymentsController::index', ['as' => 'admin.payments']);
    $routes->get('payments/new', 'Admin\\PaymentsController::new', ['as' => 'admin.payments.new']);
    $routes->post('payments', 'Admin\\PaymentsController::create', ['as' => 'admin.payments.create']);
    $routes->post('payments/(:num)/complete', 'Admin\\PaymentsController::complete/$1', ['as' => 'admin.payments.complete']);
    $routes->post('payments/(:num)/refund', 'Admin\\PaymentsController::refund/$1', ['as' => 'admin.payments.refund']);
    $routes->get('transactions', 'Admin\\TransactionsController::index', ['as' => 'admin.transactions']);
    $routes->get('settings', 'Admin\\SettingsController::index', ['as' => 'admin.settings']);
    $routes->post('settings/(:alpha)', 'Admin\\SettingsController::update/$1', ['as' => 'admin.settings.update']);
    $routes->post('settings/test-email', 'Admin\\SettingsController::testEmail', ['as' => 'admin.settings.test-email']);
    $routes->get('account', 'Admin\\AccountController::edit', ['as' => 'admin.account']);
    $routes->get('account/verify', 'Admin\\AccountController::verify', ['as' => 'admin.account.verify']);
    $routes->post('account/verify', 'Admin\\AccountController::verifyPassword', ['as' => 'admin.account.verify.submit']);
    $routes->post('account/profile', 'Admin\\AccountController::updateProfile', ['as' => 'admin.account.profile']);
    $routes->post('account/password', 'Admin\\AccountController::updatePassword', ['as' => 'admin.account.password']);
});

// Backward-compatible routes for links to the previous standalone HTML files.
$routes->get('index.html', 'PagesController::home');
$routes->get('about.html', 'PagesController::about');
$routes->get('services.html', 'PagesController::services');
$routes->get('scheduling.html', 'PagesController::scheduling');
$routes->get('faqs.html', 'PagesController::faqs');
$routes->get('testimonials.html', 'PagesController::testimonials');
$routes->get('contact.html', 'PagesController::contact');
$routes->get('login.html', 'AuthController::login', ['filter' => 'guest']);
$routes->get('shipping.html', 'PagesController::shipping');
$routes->get('terms.html', 'PagesController::terms');
