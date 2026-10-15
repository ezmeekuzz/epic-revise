<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\SettingsService;
use App\Libraries\SmtpService;
use App\Models\ContactMessageModel;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

final class ContactController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function sendMessage(): RedirectResponse
    {
        $key = 'contact_message_' . hash('sha256', $this->request->getIPAddress());
        if (! service('throttler')->check($key, 5, MINUTE * 10)) {
            return redirect()->route('contact')->with('error', 'Too many messages sent. Please wait a few minutes and try again.');
        }

        $data = [
            'first_name' => trim((string) $this->request->getPost('firstname')),
            'last_name'  => trim((string) $this->request->getPost('lastname')),
            'phone'      => trim((string) $this->request->getPost('phone')),
            'email'      => mb_strtolower(trim((string) $this->request->getPost('email'))),
            'message'    => trim((string) $this->request->getPost('message')),
        ];
        $rules = [
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'phone'      => 'required|max_length[30]',
            'email'      => 'required|valid_email|max_length[254]',
            'message'    => 'required|max_length[5000]',
        ];

        if (! $this->validateData($data, $rules)) {
            return redirect()->route('contact')->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            (new ContactMessageModel())->insert($data);
        } catch (Throwable $exception) {
            log_message('error', 'Contact message could not be saved: {type}', ['type' => $exception::class]);

            return redirect()->route('contact')->withInput()->with('error', 'Your message could not be sent. Please try again or call us directly.');
        }

        $smtp = new SmtpService();
        $name = trim($data['first_name'] . ' ' . $data['last_name']);
        try {
            $smtp->sendContactConfirmation($data['email'], $name, $data['message']);
        } catch (Throwable $exception) {
            log_message('error', 'Contact confirmation email failed: {type}', ['type' => $exception::class]);
        }

        $supportEmail = (new SettingsService())->get('support_email');
        if ($supportEmail !== '') {
            try {
                $smtp->sendContactNotification($supportEmail, $name, $data['email'], $data['phone'], $data['message']);
            } catch (Throwable $exception) {
                log_message('error', 'Contact notification email failed: {type}', ['type' => $exception::class]);
            }
        }

        return redirect()->route('contact')->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }
}
