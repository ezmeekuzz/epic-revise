<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\SettingsService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Exceptions\PageNotFoundException;

final class PagesController extends BaseController
{
    /** @var list<string> */
    protected $helpers = ['form', 'url'];

    /**
     * Page presentation data is kept here so every action renders through the
     * same layout without repeating metadata or exposing arbitrary view names.
     *
     * @var array<string, array{title: string, description: string, path: string, image?: string}>
     */
    private const PAGES = [
        'home' => [
            'title'       => 'HPU Dorm Storage & Moving | EPIC Storage Solutions',
            'description' => 'Dorm-to-dorm summer storage for High Point University students, including boxes, pickup, secure storage, and redelivery in High Point, NC.',
            'path'        => '',
            'image'       => 'assets/images/Two_women_high_five_2K_202609071746.png',
        ],
        'about' => [
            'title'       => 'About EPIC Storage Solutions | HPU Dorm Storage',
            'description' => 'Meet EPIC Storage Solutions, the locally owned High Point, NC team serving High Point University students and families since 2009.',
            'path'        => 'about',
            'image'       => 'assets/images/hpu-campus-supplied.webp',
        ],
        'services' => [
            'title'       => 'Services | EPIC Storage Solutions',
            'description' => 'Our EPIC Services: Summer Storage and the Summer Advantage Program for High Point University students, plus additional boxes, items, and shipping add-ons.',
            'path'        => 'services',
        ],
        'scheduling' => [
            'title'       => 'Scheduling | EPIC Storage Solutions',
            'description' => 'Our booking policy: schedule your EPIC Storage pick-up, get confirmation and reminder emails, and change your time up to 48 hours in advance.',
            'path'        => 'scheduling',
        ],
        'faqs' => [
            'title'       => 'FAQs | EPIC Storage Solutions',
            'description' => 'Frequently asked questions about EPIC Storage Solutions: packages, sign-up timing, pickup and delivery, packing suggestions, and shipping.',
            'path'        => 'faqs',
        ],
        'testimonials' => [
            'title'       => 'Testimonials | EPIC Storage Solutions',
            'description' => 'Real reviews from HPU parents and students who used EPIC Storage Solutions for dorm-to-dorm summer storage.',
            'path'        => 'testimonials',
        ],
        'contact' => [
            'title'       => 'Contact Us | EPIC Storage Solutions',
            'description' => 'Questions or comments for EPIC Storage Solutions? Call 336.549.5216 or send us a message.',
            'path'        => 'contact',
        ],
        'shipping' => [
            'title'       => 'Shipping | EPIC Storage Solutions',
            'description' => 'EPIC Storage Solutions shipping services, offered in partnership with UPS, for items that need to be mailed instead of stored.',
            'path'        => 'shipping',
        ],
        'terms' => [
            'title'       => 'Terms and Conditions | EPIC Storage Solutions',
            'description' => 'Terms and conditions for EPIC Storage Solutions, LLC storage, shipping, packing, and pickup/delivery services.',
            'path'        => 'terms',
        ],
    ];

    public function home(): string
    {
        return $this->renderPage('home', [
            'pricingSettings' => (new SettingsService())->displayGroup('pricing'),
            'generalSettings' => (new SettingsService())->displayGroup('general'),
        ]);
    }

    public function about(): string
    {
        return $this->renderPage('about');
    }

    public function services(): string
    {
        return $this->renderPage('services', [
            'bookingSettings' => (new SettingsService())->displayGroup('booking'),
            'pricingSettings' => (new SettingsService())->displayGroup('pricing'),
        ]);
    }

    public function scheduling(): string
    {
        return $this->renderPage('scheduling', [
            'bookingSettings' => (new SettingsService())->displayGroup('booking'),
        ]);
    }

    /**
     * Compatibility entry point for the original service-session links.
     * Both service cards now enter the same protected customer booking flow.
     */
    public function startBooking(string $bookingType): RedirectResponse
    {
        if (! in_array($bookingType, ['summer_storage', 'summer_advantage'], true)) {
            throw PageNotFoundException::forPageNotFound('Unknown booking type.');
        }

        return redirect()->to(site_url('account/bookings/new?booking_type=' . rawurlencode($bookingType)));
    }

    public function faqs(): string
    {
        return $this->renderPage('faqs');
    }

    public function testimonials(): string
    {
        return $this->renderPage('testimonials');
    }

    public function contact(): string
    {
        return $this->renderPage('contact');
    }

    public function shipping(): string
    {
        return $this->renderPage('shipping');
    }

    public function terms(): string
    {
        return $this->renderPage('terms', ['currentTerms' => (new SettingsService())->get('current_terms_text', '')]);
    }

    /** @param array<string, mixed> $additionalData */
    private function renderPage(string $page, array $additionalData = []): string
    {
        $metadata = self::PAGES[$page] ?? null;

        if ($metadata === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('pages/' . $page, [
            ...$metadata,
            'activePage'   => $page,
            'canonicalUrl' => site_url($metadata['path']),
            'socialImage'  => isset($metadata['image']) ? base_url($metadata['image']) : null,
            ...$additionalData,
        ]);
    }
}
