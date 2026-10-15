<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Email\Email;
use Config\Email as EmailConfig;
use Config\Services;
use RuntimeException;

final class SmtpService
{
    public function configuredMailer(): Email
    {
        $settings = new SettingsService();
        if ($settings->get('smtp_enabled') !== '1') {
            throw new RuntimeException('SMTP delivery is disabled.');
        }

        $host     = $settings->get('smtp_host');
        $username = $settings->get('smtp_username');
        $password = $settings->get('smtp_password');
        $from     = $settings->get('email_from_address');

        if ($host === '' || $username === '' || $password === '' || $from === '') {
            throw new RuntimeException('SMTP host, username, password, and from address are required.');
        }

        $config              = new EmailConfig();
        $config->protocol    = 'smtp';
        $config->SMTPHost    = $host;
        $config->SMTPPort    = (int) $settings->get('smtp_port', '587');
        $config->SMTPUser    = $username;
        $config->SMTPPass    = $password;
        $config->SMTPCrypto  = $settings->get('smtp_crypto', 'tls');
        $config->SMTPTimeout = 10;
        $config->mailType    = 'html';
        $config->charset     = 'UTF-8';
        $config->CRLF        = "\r\n";
        $config->newline     = "\r\n";

        return Services::email($config, false);
    }

    public function sendTest(string $recipient): bool
    {
        $settings = new SettingsService();
        $email    = $this->configuredMailer();
        $sender = $this->configureSender($email, $settings);
        $messageId = $this->messageId();
        $email->setHeader('X-Epic-Delivery-ID', $messageId);
        $email->setTo($recipient);
        $email->setSubject('Epic Storage Solutions SMTP test [' . $messageId . ']');
        $email->setMessage('<p>This test message was sent from the Epic administrator settings page.</p><p>Time: ' . esc(date('F j, Y g:i A T')) . '</p>');

        $sent = $email->send(false);
        $this->logSubmission('test', $sent, $recipient, $sender, $messageId, $settings);

        return $sent;
    }

    public function sendVerification(string $recipient, string $name, string $verificationUrl, string $serviceLabel): bool
    {
        $settings = new SettingsService();
        $email    = $this->configuredMailer();
        $sender = $this->configureSender($email, $settings);
        $messageId = $this->messageId();
        $email->setHeader('X-Epic-Delivery-ID', $messageId);
        $email->setTo($recipient);
        $email->setSubject('Confirm your Epic Storage Solutions account');
        $logoSource = $this->attachInlineImage($email, 'epic-logo-white.png');
        $email->setMessage(view('emails/verify_account', [
            'name'           => $name,
            'verificationUrl' => $verificationUrl,
            'serviceLabel'   => $serviceLabel,
            'expiresHours'   => 24,
            'logoSource'     => $logoSource,
        ]));

        $sent = $email->send(false);
        $this->logSubmission('verification', $sent, $recipient, $sender, $messageId, $settings);

        return $sent;
    }

    /**
     * @param array<string, mixed>      $booking
     * @param list<array<string, mixed>> $bookingItems
     * @param array<string, mixed>|null $dorm
     */
    public function sendBalanceDueNotice(array $booking, array $bookingItems, ?array $dorm, float $paid, float $balance, string $paymentUrl): bool
    {
        return $this->sendBookingNotice(
            (string) $booking['customer_email'],
            (string) $booking['customer_name'],
            'balance_due',
            'Balance due on your Epic Storage booking ' . $booking['reference_code'],
            'Payment reminder',
            'Balance due: $' . number_format($balance, 2),
            [
                'This is a notification regarding your outstanding balance with Epic Storage Solutions. We kindly request that you settle the remaining balance for your storage order.',
                'If you have already made a payment, please disregard this notice. Payments may take 2-3 business days to reflect on your account.',
                'Need assistance? Contact Service Support at support@epicstoragehpu.com or call our office at 336.549.5216.',
            ],
            'Pay balance now',
            $paymentUrl,
            [
                [
                    'title' => 'Payment summary',
                    'rows' => [
                        ['Order number', (string) $booking['reference_code']],
                        ['Total amount', '$' . number_format((float) $booking['total_amount'], 2)],
                        ['Amount received', '$' . number_format($paid, 2)],
                        ['Balance owed', '$' . number_format($balance, 2)],
                    ],
                ],
                $this->bookingInfoTable($booking, $dorm),
                $this->bookingPricingTable($booking, $bookingItems),
            ],
        );
    }

    /**
     * @param array<string, mixed>      $booking
     * @param list<array<string, mixed>> $bookingItems
     * @param array<string, mixed>|null $dorm
     */
    public function sendBookingConfirmation(array $booking, array $bookingItems, ?array $dorm, ?string $scheduleUrl): bool
    {
        $paragraphs = ['We processed your ' . $booking['service_name'] . ' order at High Point University.'];
        if ($scheduleUrl !== null) {
            $paragraphs[] = 'You will receive a follow-up email for box delivery and a reminder to schedule your pickup. If you know your pickup time now, choose it using the link below to secure your spot.';
        }
        $paragraphs[] = 'If you have additional items to add to your order or any questions, contact Service Support at support@epicstoragehpu.com or call our office at 336.549.5216.';

        return $this->sendBookingNotice(
            (string) $booking['customer_email'],
            (string) $booking['customer_name'],
            'booking_confirmation',
            'Your Epic Storage booking ' . $booking['reference_code'] . ' is confirmed',
            'Booking confirmed',
            'Booking ' . $booking['reference_code'] . ' is confirmed',
            $paragraphs,
            $scheduleUrl !== null ? 'Choose your pickup schedule' : null,
            $scheduleUrl,
            [
                $this->bookingInfoTable($booking, $dorm),
                $this->bookingPricingTable($booking, $bookingItems),
            ],
        );
    }

    public function sendPickupReminder(string $recipient, string $name, string $referenceCode, string $pickupDate, ?string $pickupTime, ?string $pickupLocation): bool
    {
        $when = date('F j, Y', strtotime($pickupDate)) . ($pickupTime ? ' at ' . date('g:i A', strtotime($pickupTime)) : '');
        $paragraphs = ['Epic has you scheduled for pickup on ' . $when . '. If you need to change this time, you can do so up to 48 hours prior to your scheduled pickup.'];
        if ($pickupLocation) {
            $paragraphs[] = 'Location: ' . $pickupLocation;
        }
        $paragraphs[] = 'If you have additional items to add to your order or any questions, contact Service Support at support@epicstoragehpu.com or call our office at 336.549.5216.';

        return $this->sendBookingNotice(
            $recipient,
            $name,
            'pickup_reminder',
            'Pickup reminder for booking ' . $referenceCode,
            'Pickup reminder',
            'Pickup scheduled: ' . $when,
            $paragraphs,
        );
    }

    public function sendPickupNeededNotice(string $recipient, string $name, string $referenceCode, string $scheduleUrl): bool
    {
        return $this->sendBookingNotice(
            $recipient,
            $name,
            'pickup_needed',
            'Schedule your pickup for booking ' . $referenceCode,
            'Action needed',
            'Schedule your pickup',
            [
                'We don\'t have a pickup date on file yet for your Epic Storage booking ' . $referenceCode . '.',
                'Please schedule a pickup time so we can collect your items.',
            ],
            'Schedule pickup',
            $scheduleUrl,
        );
    }

    public function sendDropoffReminder(string $recipient, string $name, string $referenceCode, string $dropoffDate, ?string $dropoffTime, ?string $dropoffLocation): bool
    {
        $when = date('F j, Y', strtotime($dropoffDate)) . ($dropoffTime ? ' at ' . date('g:i A', strtotime($dropoffTime)) : '');
        $paragraphs = ['Epic will return your items on ' . $when . '. If you need to change this time, contact us as soon as possible.'];
        if ($dropoffLocation) {
            $paragraphs[] = 'Location: ' . $dropoffLocation;
        }
        $paragraphs[] = 'If you have any questions, contact Service Support at support@epicstoragehpu.com or call our office at 336.549.5216.';

        return $this->sendBookingNotice(
            $recipient,
            $name,
            'dropoff_reminder',
            'Drop-off reminder for booking ' . $referenceCode,
            'Drop-off reminder',
            'Drop-off scheduled: ' . $when,
            $paragraphs,
        );
    }

    public function sendContactConfirmation(string $recipient, string $name, string $message): bool
    {
        return $this->sendBookingNotice(
            $recipient,
            $name,
            'contact_confirmation',
            'Thank you for contacting Epic Storage Solutions',
            'Message received',
            'Thanks for reaching out',
            [
                'We received your message and will get back to you as soon as possible.',
                'For reference, here is what you sent us: "' . $message . '"',
            ],
        );
    }

    public function sendContactNotification(string $recipient, string $name, string $email, string $phone, string $message): bool
    {
        return $this->sendBookingNotice(
            $recipient,
            'Epic Team',
            'contact_notification',
            'New contact message from ' . $name,
            'New contact message',
            $name . ' sent a message',
            [
                'Email: ' . $email,
                'Phone: ' . $phone,
                'Message: ' . $message,
            ],
        );
    }

    public function sendPasswordReset(string $recipient, string $name, string $resetUrl): bool
    {
        return $this->sendBookingNotice(
            $recipient,
            $name,
            'password_reset',
            'Reset your Epic Storage Solutions password',
            'Password reset',
            'Reset your password',
            [
                'We received a request to reset your Epic Storage Solutions password. Use the button below to choose a new one.',
                'This link expires in 1 hour and can be used once. If you did not request this, you can safely ignore this email.',
            ],
            'Reset password',
            $resetUrl,
        );
    }

    /**
     * @param list<string> $paragraphs
     * @param list<array{title: string, rows: list<array{0: string, 1: string}>}> $tables
     */
    private function sendBookingNotice(string $recipient, string $name, string $purpose, string $subject, string $eyebrow, string $heading, array $paragraphs, ?string $ctaLabel = null, ?string $ctaUrl = null, array $tables = []): bool
    {
        $settings  = new SettingsService();
        $email     = $this->configuredMailer();
        $sender    = $this->configureSender($email, $settings);
        $messageId = $this->messageId();
        $email->setHeader('X-Epic-Delivery-ID', $messageId);
        $email->setTo($recipient);
        $email->setSubject($subject);
        $logoSource = $this->attachInlineImage($email, 'epic-logo-white.png');
        $email->setMessage(view('emails/booking_notice', [
            'name'       => $name,
            'eyebrow'    => $eyebrow,
            'heading'    => $heading,
            'paragraphs' => $paragraphs,
            'ctaLabel'   => $ctaLabel,
            'ctaUrl'     => $ctaUrl,
            'logoSource' => $logoSource,
            'tables'     => $tables,
        ]));

        $sent = $email->send(false);
        $this->logSubmission($purpose, $sent, $recipient, $sender, $messageId, $settings);

        return $sent;
    }

    /**
     * @param array<string, mixed> $booking
     * @param array<string, mixed>|null $dorm
     * @return array{title: string, rows: list<array{0: string, 1: string}>}
     */
    private function bookingInfoTable(array $booking, ?array $dorm): array
    {
        $dormLine = trim((string) ($dorm['name'] ?? $booking['pickup_location'] ?? '') . ($booking['room_number'] ? ' - Room ' . $booking['room_number'] : ''));

        return [
            'title' => 'Booking details',
            'rows' => [
                ['Student name', (string) $booking['customer_name']],
                ['Student ID', $booking['student_id'] ? (string) $booking['student_id'] : 'Not provided'],
                ['Student email', (string) $booking['customer_email']],
                ['Student phone', $booking['customer_phone'] ? (string) $booking['customer_phone'] : 'Not provided'],
                ['Dorm and room', $dormLine !== '' ? $dormLine : 'Not provided'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $booking
     * @param list<array<string, mixed>> $bookingItems
     * @return array{title: string, rows: list<array{0: string, 1: string}>}
     */
    private function bookingPricingTable(array $booking, array $bookingItems): array
    {
        $isStorage = $booking['booking_type'] === 'summer_storage';
        $rows = [];

        if ($isStorage) {
            $rows[] = ['Base amount', '$' . number_format((float) $booking['base_price'], 2)];
            foreach ($bookingItems as $item) {
                $rows[] = [$item['item_name'] . ' (x' . $item['quantity'] . ')', '$' . number_format((float) $item['line_total'], 2)];
            }
        }
        if ((float) $booking['addtl_box_total_amount'] > 0) {
            $rows[] = [$isStorage ? 'Additional boxes' : 'Boxes', '$' . number_format((float) $booking['addtl_box_total_amount'], 2)];
        }
        if ($isStorage && (int) ($booking['is_summer_school'] ?? 0) === 1) {
            $rows[] = ['Summer school fee', '$' . number_format((float) $booking['summer_school_fee'], 2)];
        }
        if ($isStorage && (int) ($booking['is_study_abroad'] ?? 0) === 1) {
            $rows[] = ['Study abroad adjustment', '$' . number_format((float) $booking['study_abroad_additional_storage_price'], 2)];
        }
        $rows[] = ['Total', '$' . number_format((float) $booking['total_amount'], 2)];

        return ['title' => 'Pricing breakdown', 'rows' => $rows];
    }

    private function configureSender(Email $email, SettingsService $settings): string
    {
        $configuredFrom = $settings->get('email_from_address');
        $smtpUsername   = $settings->get('smtp_username');
        $sender         = $configuredFrom;

        // A From domain that differs from the authenticated mailbox is commonly
        // accepted by SMTP, then rejected or quarantined by the receiving server.
        // Use the authenticated mailbox for the envelope/header sender and retain
        // the configured address for replies when it is a different valid address.
        if (filter_var($smtpUsername, FILTER_VALIDATE_EMAIL) !== false
            && $this->emailDomain($configuredFrom) !== ''
            && $this->emailDomain($smtpUsername) !== ''
            && ! hash_equals($this->emailDomain($configuredFrom), $this->emailDomain($smtpUsername))) {
            $sender = $smtpUsername;
            if (filter_var($configuredFrom, FILTER_VALIDATE_EMAIL) !== false) {
                $email->setReplyTo($configuredFrom);
            }
            log_message('warning', 'SMTP sender domain differs from authenticated mailbox; using the authenticated mailbox as From.');
        }

        $email->setFrom($sender, $settings->get('email_from_name', 'Epic Storage Solutions'));

        return $sender;
    }

    private function emailDomain(string $address): string
    {
        $parts = explode('@', trim($address));

        return count($parts) === 2 ? strtolower($parts[1]) : '';
    }

    private function messageId(): string
    {
        return strtoupper(bin2hex(random_bytes(5)));
    }

    private function logSubmission(string $purpose, bool $sent, string $recipient, string $sender, string $messageId, SettingsService $settings): void
    {
        log_message($sent ? 'info' : 'error', 'SMTP {purpose} submission {status}: id={id}, host={host}, port={port}, crypto={crypto}, sender_domain={senderDomain}, recipient_domain={recipientDomain}', [
            'purpose' => $purpose,
            'status' => $sent ? 'accepted' : 'rejected',
            'id' => $messageId,
            'host' => $settings->get('smtp_host'),
            'port' => $settings->get('smtp_port', '587'),
            'crypto' => $settings->get('smtp_crypto', 'tls') ?: 'none',
            'senderDomain' => $this->emailDomain($sender),
            'recipientDomain' => $this->emailDomain($recipient),
        ]);
    }

    private function attachInlineImage(Email $email, string $filename): string
    {
        $path = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path) && $email->attach($path, 'inline', $filename) !== false) {
            $cid = $email->setAttachmentCID($path);
            if (is_string($cid) && $cid !== '') {
                return 'cid:' . $cid;
            }
        }

        // Keep a remote fallback for environments where the asset is missing,
        // while production messages use the embedded CID image above.
        return base_url('assets/images/' . rawurlencode($filename));
    }
}
